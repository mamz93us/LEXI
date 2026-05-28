<?php

declare(strict_types=1);

namespace App\Livewire\AiDrafter;

use App\Models\Clause;
use App\Models\ClauseVersion;
use App\Models\Client;
use App\Models\Court;
use App\Models\LegalCase;
use App\Models\Proxy;
use App\Models\User;
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

    /**
     * namespace → 'client' | 'lawyer'
     * For توكيلات the agent (الوكيل) is frequently a lawyer in the firm
     * rather than a separate client. This tracks which directory to
     * resolve from when generating.
     *
     * @var array<string, string>
     */
    public array $parties_kind = [];

    /** dotted-key → value  (catalog contract.* / court.* etc.) */
    public array $contract_meta = [];

    /** dotted-key → value  (AI-discovered, non-catalog fields) */
    public array $extra_fields = [];

    /** @var array<int, int> clause ids selected for verbatim inclusion */
    public array $clause_ids = [];

    /** Optional linked case for audit / context. */
    public ?int $case_id = null;

    /**
     * Optional linked proxy whose AI-extracted data feeds the form.
     * Selecting one auto-fills the principal/agent (or whichever parties
     * the proxy carries) plus `proxy.*` tokens like notary_serial, scope.
     */
    public ?int $linked_proxy_id = null;

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

    /**
     * Firm lawyers — anyone in the users table for this tenant. The agent
     * dropdown can flip to this source when the lawyer is acting as
     * authorised representative on a توكيل.
     */
    #[Computed]
    public function lawyersList(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);
    }

    #[Computed]
    public function casesList(): Collection
    {
        return LegalCase::query()->orderByDesc('created_at')->limit(200)->get();
    }

    /**
     * Proxies that have a file uploaded — extracted or not. We only show
     * extracted ones in the picker, since their `toAiVariables()` is
     * meaningful. A pending/extracting proxy is shown as disabled.
     */
    #[Computed]
    public function linkableProxies(): Collection
    {
        return Proxy::query()
            ->with('client')
            ->whereNotNull('file_path')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function linkedProxy(): ?Proxy
    {
        return $this->linked_proxy_id
            ? Proxy::query()->with('client')->find($this->linked_proxy_id)
            : null;
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

        // Build the "already-known" payload from the linked proxy so Claude
        // doesn't ask the lawyer for fields that are already extracted.
        $existingData = [];
        if ($this->linkedProxy) {
            $existingData = $this->linkedProxy->toAiVariables();
        }

        $this->discovering = true;
        try {
            $this->discovery = $discovery->discover($this->doc_type, $this->description, $existingData);
            $this->step = 2;
            $this->seedFormKeys();
            if ($this->linkedProxy) {
                $this->prefillFromLinkedProxy();
            }
        } catch (Throwable $e) {
            $this->error = 'تعذّر استكشاف البيانات المطلوبة: '.$e->getMessage();
        } finally {
            $this->discovering = false;
        }
    }

    /**
     * Seed the wire:model arrays with empty values so Livewire bindings
     * resolve. Called once when entering step 2.
     */
    private function seedFormKeys(): void
    {
        foreach ($this->partiesToFill as $p) {
            $ns = $p['namespace'];
            if (! array_key_exists($ns, $this->parties)) {
                $this->parties[$ns] = null;
            }
            if (! array_key_exists($ns, $this->parties_kind)) {
                $this->parties_kind[$ns] = 'client';
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
    }

    /**
     * When the lawyer has linked an existing proxy, try to match each
     * extracted party (by national_id) to a Client row so the dropdowns
     * land pre-selected. Also copy the proxy's `proxy.*` tokens into
     * `extra_fields` so Claude doesn't re-ask for notary serial, scope,
     * etc. — the prompt told it not to, but defense in depth.
     */
    private function prefillFromLinkedProxy(): void
    {
        $proxy = $this->linkedProxy;
        if (! $proxy) {
            return;
        }
        $extracted = is_array($proxy->extracted_data) ? $proxy->extracted_data : [];

        foreach (($extracted['parties'] ?? []) as $ns => $fields) {
            if (! is_string($ns) || ! is_array($fields)) {
                continue;
            }
            // Only pre-fill if the form actually renders this party.
            $renderedNamespaces = array_column($this->partiesToFill, 'namespace');
            if (! in_array($ns, $renderedNamespaces, true)) {
                continue;
            }

            // Skip if the lawyer has already picked something for this party.
            if (! empty($this->parties[$ns])) {
                continue;
            }

            $nid = $fields['national_id'] ?? null;
            if ($nid) {
                $client = Client::query()->where('national_id', $nid)->first();
                if ($client) {
                    $this->parties[$ns] = $client->id;

                    continue;
                }
            }

            // Fallback: match by Arabic name if no NID hit.
            $name = $fields['name'] ?? null;
            if ($name) {
                $client = Client::query()->where('name_ar', $name)->first();
                if ($client) {
                    $this->parties[$ns] = $client->id;
                }
            }
        }

        // Fold proxy.* tokens into extra_fields so the AI sees them and
        // the lawyer doesn't have to retype anything.
        foreach ($proxy->toAiVariables() as $key => $value) {
            if (str_starts_with($key, 'proxy.') &&
                (! array_key_exists($key, $this->extra_fields) || empty($this->extra_fields[$key]))
            ) {
                $this->extra_fields[$key] = (string) $value;
            }
        }
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    /**
     * Livewire hook: when the lawyer flips the "from clients / from
     * lawyers" radio for a party, clear the previously-selected ID since
     * it refers to a row in the OTHER table.
     */
    public function updatedPartiesKind(mixed $value, string $key): void
    {
        $this->parties[$key] = null;
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

        $extras = $this->extra_fields;

        // If the lawyer linked an existing proxy, fold its AI-extracted
        // tokens (proxy.notary_serial, proxy.scope, plus any party fields
        // that weren't already filled by the form pickers) into the data.
        // Form-side party selections win — `$extras` is only a fallback.
        if ($this->linkedProxy) {
            foreach ($this->linkedProxy->toAiVariables() as $key => $value) {
                if (! array_key_exists($key, $extras) || $extras[$key] === null || $extras[$key] === '') {
                    $extras[$key] = $value;
                }
            }
        }

        $resolved = $resolver->resolve(
            parties: $this->parties,
            contractMeta: $this->contract_meta,
            extra: $extras,
            partiesKind: $this->parties_kind,
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
