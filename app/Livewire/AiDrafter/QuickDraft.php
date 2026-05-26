<?php

declare(strict_types=1);

namespace App\Livewire\AiDrafter;

use App\Models\Clause;
use App\Models\ClauseVersion;
use App\Models\Client;
use App\Models\Court;
use App\Models\LegalCase;
use App\Services\Rag\LegalDraftDiscovery;
use App\Services\Rag\RagGenerator;
use App\Services\Templates\DocumentTypeRegistry;
use App\Services\Templates\VariableCatalog;
use App\Services\Templates\VariableResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * Template-less drafter. Lawyer picks a document type + describes the
 * request → Claude returns the data shape it needs → lawyer fills the
 * form → queued job drafts the full document.
 *
 * Differs from the template-based Wizard in that there is no Template /
 * TemplateVersion involved. The subject for the AiGeneration audit row
 * is a synthetic "doc type" reference (we currently pin it to the
 * Tenant model, since the from-scratch flow isn't anchored to a
 * specific Template/Case).
 */
#[Layout('layouts.app')]
class QuickDraft extends Component
{
    public int $step = 1;

    /** Document type key — one of DocumentTypeRegistry::TYPES. */
    public string $doc_type = '';

    /** Free-text description of what the lawyer wants drafted. */
    public string $description = '';

    /** Result of the Claude discovery call. */
    public ?array $discovery = null;

    /** namespace → client id  (catalog parties) */
    public array $parties = [];

    /** dotted-key → value  (catalog contract.* / court.* etc.) */
    public array $contract_meta = [];

    /** dotted-key → value  (AI-discovered, non-catalog fields) */
    public array $extra_fields = [];

    /** @var array<int, int> clause ids selected for verbatim inclusion */
    public array $clause_ids = [];

    /** Optional linked case for audit / context. */
    public ?int $case_id = null;

    public ?string $error = null;

    public ?string $info = null;

    /** Loading flag while the synchronous discovery call is in flight. */
    public bool $discovering = false;

    #[Computed]
    public function docTypes(): array
    {
        return DocumentTypeRegistry::grouped();
    }

    #[Computed]
    public function docTypeMeta(): ?array
    {
        return $this->doc_type ? DocumentTypeRegistry::get($this->doc_type) : null;
    }

    #[Computed]
    public function clientsList(): Collection
    {
        return Client::query()
            ->orderBy('name_ar')
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar', 'national_id', 'type']);
    }

    #[Computed]
    public function courtsList(): Collection
    {
        return Court::query()
            ->orderBy('governorate')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'governorate']);
    }

    #[Computed]
    public function casesList(): Collection
    {
        return LegalCase::query()->orderByDesc('created_at')->limit(200)->get();
    }

    #[Computed]
    public function approvedClauses(): Collection
    {
        return Clause::query()
            ->where('is_active', true)
            ->with(['currentVersion' => fn ($q) => $q->whereNotNull('approved_at')])
            ->get()
            ->filter(fn (Clause $c) => $c->currentVersion !== null)
            ->values();
    }

    /**
     * Final list of parties to render in the form: catalog defaults from
     * the doc type + anything Claude said to add. Deduplicated.
     *
     * @return array<int, array{namespace: string, label_ar: string}>
     */
    #[Computed]
    public function partiesToFill(): array
    {
        $meta = $this->docTypeMeta;
        $defaults = collect($meta['parties'] ?? [])->map(fn ($ns) => [
            'namespace' => $ns,
            'label_ar' => VariableCatalog::partyLabel($ns),
        ]);

        $discovered = collect($this->discovery['parties'] ?? []);

        return $defaults->concat($discovered)
            ->unique('namespace')
            ->values()
            ->all();
    }

    /**
     * Contract-meta tokens to render: catalog defaults from doc type +
     * what's referenced by Claude's discovered fields.
     *
     * @return array<int, array{token: string, label_ar: string, type: string, source?: string}>
     */
    #[Computed]
    public function contractMetaToFill(): array
    {
        $meta = $this->docTypeMeta;
        $defaults = collect($meta['contract_meta'] ?? []);

        return $defaults
            ->map(function (string $token) {
                $def = VariableCatalog::CONTRACT_META[$token] ?? ['label_ar' => $token, 'type' => 'text'];

                return ['token' => $token] + $def;
            })
            ->all();
    }

    public function startDiscovery(LegalDraftDiscovery $discovery): void
    {
        $this->error = null;

        if ($this->doc_type === '' || ! DocumentTypeRegistry::get($this->doc_type)) {
            $this->error = 'اختر نوع الوثيقة للمتابعة.';

            return;
        }
        if (mb_strlen(trim($this->description)) < 10) {
            $this->error = 'صف الوثيقة المطلوبة بشكل أوضح (10 أحرف على الأقل).';

            return;
        }

        $this->discovering = true;
        try {
            $this->discovery = $discovery->discover($this->doc_type, $this->description);
            $this->step = 2;
            // Pre-seed party keys so wire:model bindings work on step 2.
            foreach ($this->partiesToFill as $p) {
                if (! array_key_exists($p['namespace'], $this->parties)) {
                    $this->parties[$p['namespace']] = null;
                }
            }
            foreach ($this->contractMetaToFill as $m) {
                if (! array_key_exists($m['token'], $this->contract_meta)) {
                    $this->contract_meta[$m['token']] = '';
                }
            }
            foreach ($this->discovery['fields'] ?? [] as $f) {
                if (! array_key_exists($f['key'], $this->extra_fields)) {
                    $this->extra_fields[$f['key']] = '';
                }
            }
        } catch (Throwable $e) {
            $this->error = 'تعذّر استكشاف البيانات المطلوبة: '.$e->getMessage();
        } finally {
            $this->discovering = false;
        }
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function generate(RagGenerator $generator, VariableResolver $resolver): void
    {
        $this->error = null;

        $meta = $this->docTypeMeta;
        if (! $meta) {
            $this->error = 'نوع الوثيقة غير صالح.';

            return;
        }

        // The audit row's "subject" is the linked case if any, else null.
        // We don't fall back to Tenant because its primary key is a slug
        // string and `ai_generations.subject_id` is a bigint column —
        // every audit row still carries `tenant_id` via BelongsToTenant,
        // which is what enforces isolation.
        $subject = $this->case_id
            ? LegalCase::query()->find($this->case_id)
            : null;

        $clauseVersions = ClauseVersion::query()
            ->whereIn('clause_id', $this->clause_ids)
            ->get()
            ->groupBy('clause_id')
            ->map(fn ($versions) => $versions->sortByDesc('version_no')->first())
            ->values();

        $resolved = $resolver->resolve(
            parties: $this->parties,
            contractMeta: $this->contract_meta,
            extra: $this->extra_fields,
        );

        $intent = sprintf(
            "نوع الوثيقة: %s\nالوصف من المحامي: %s",
            $meta['label_ar'],
            trim($this->description),
        );

        try {
            $generation = $generator->draft(
                subject: $subject,
                userIntent: $intent,
                filledData: $resolved,
                verbatimClauses: $clauseVersions,
                template: null,
            );
            $this->redirectRoute('ai-drafter.show', $generation, navigate: true);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.ai-drafter.quick-draft');
    }
}
