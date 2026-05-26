<?php

declare(strict_types=1);

namespace App\Livewire\AiDrafter;

use App\Models\Clause;
use App\Models\ClauseVersion;
use App\Models\Client;
use App\Models\Court;
use App\Models\LegalCase;
use App\Models\Template;
use App\Services\Rag\RagGenerator;
use App\Services\Templates\VariableCatalog;
use App\Services\Templates\VariableResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class Wizard extends Component
{
    public int $step = 1;

    public ?int $template_id = null;

    public ?int $case_id = null;

    public string $user_intent = '';

    /**
     * Predefined-catalog party pickers: namespace → selected client id.
     * Populated dynamically based on the tokens detected in the template.
     *
     * @var array<string, int|string|null>
     */
    public array $parties = [];

    /**
     * Contract / matter metadata fields: dotted token → value
     * (e.g. 'contract.place' => 'القاهرة').
     *
     * @var array<string, mixed>
     */
    public array $contract_meta = [];

    /**
     * Legacy free-form filled vars for any custom (non-catalog) variables
     * declared in the template's JSON variables list.
     *
     * @var array<string, string>
     */
    public array $filled = [];

    /** @var array<int, int>  selected clause ids */
    public array $clause_ids = [];

    public ?string $output = null;

    public ?int $generation_id = null;

    public ?string $error = null;

    #[Computed]
    public function activeTemplates(): Collection
    {
        return Template::query()->where('is_active', true)->with('currentVersion')->orderBy('title')->get();
    }

    #[Computed]
    public function template(): ?Template
    {
        return $this->template_id
            ? Template::query()->with('currentVersion')->find($this->template_id)
            : null;
    }

    /**
     * Custom (non-catalog) template variables declared via the template's
     * JSON variables array — shown as plain inputs alongside the smart pickers.
     */
    #[Computed]
    public function templateCustomVariables(): array
    {
        $vars = $this->template?->currentVersion?->variables ?? [];
        if (! is_array($vars)) {
            return [];
        }

        return array_values(array_filter($vars, function ($v) {
            $name = is_array($v) ? ($v['name'] ?? '') : '';

            return $name !== '' && ! str_contains($name, '.');
        }));
    }

    /**
     * Party namespaces detected in the template body (e.g. ['seller','buyer']).
     *
     * @return array<int, string>
     */
    #[Computed]
    public function detectedParties(): array
    {
        $body = $this->template?->currentVersion?->body ?? '';
        if ($body === '') {
            return [];
        }

        return VariableCatalog::detectPartiesInTemplate($body);
    }

    /**
     * Contract-meta tokens detected in the template body.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function detectedContractMeta(): array
    {
        $body = $this->template?->currentVersion?->body ?? '';
        if ($body === '') {
            return [];
        }

        return VariableCatalog::detectContractMetaInTemplate($body);
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
    public function partyLabels(): array
    {
        $out = [];
        foreach ($this->detectedParties as $ns) {
            $out[$ns] = VariableCatalog::partyLabel($ns);
        }

        return $out;
    }

    #[Computed]
    public function metaFieldDefs(): array
    {
        $out = [];
        foreach ($this->detectedContractMeta as $token) {
            $out[$token] = VariableCatalog::CONTRACT_META[$token];
        }

        return $out;
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

    public function next(): void
    {
        if ($this->step === 1 && ! $this->template_id) {
            $this->addError('template_id', 'اختر قالباً للمتابعة.');

            return;
        }
        $this->step = min(4, $this->step + 1);

        // When the lawyer moves from step 1 → 2, prepare the party / meta keys
        // so wire:model bindings work even before they type anything.
        if ($this->step === 2) {
            foreach ($this->detectedParties as $ns) {
                if (! array_key_exists($ns, $this->parties)) {
                    $this->parties[$ns] = null;
                }
            }
            foreach ($this->detectedContractMeta as $token) {
                if (! array_key_exists($token, $this->contract_meta)) {
                    $this->contract_meta[$token] = '';
                }
            }
        }
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function generate(RagGenerator $generator, VariableResolver $resolver): void
    {
        $this->error = null;
        $this->output = null;

        $template = $this->template;
        if (! $template || ! $template->currentVersion) {
            $this->error = 'القالب غير صالح.';

            return;
        }

        $subject = $this->case_id
            ? LegalCase::query()->find($this->case_id)
            : $template;

        if (! $subject) {
            $this->error = 'تعذّر تحديد القضية المرتبطة.';

            return;
        }

        $clauseVersions = ClauseVersion::query()
            ->whereIn('clause_id', $this->clause_ids)
            ->get()
            ->groupBy('clause_id')
            ->map(fn ($versions) => $versions->sortByDesc('version_no')->first())
            ->values();

        // Resolve catalog tokens (parties → clients, court FK → name, today, etc.)
        // plus the legacy free-form vars into one flat dotted-key map.
        $resolved = $resolver->resolve(
            parties: $this->parties,
            contractMeta: $this->contract_meta,
            extra: $this->filled,
        );

        $intent = trim($this->user_intent) !== ''
            ? $this->user_intent
            : "صياغة {$template->type} بناءً على القالب «{$template->title}»";

        try {
            $generation = $generator->draft(
                subject: $subject,
                userIntent: $intent,
                filledData: $resolved,
                verbatimClauses: $clauseVersions,
                template: $template->currentVersion,
            );
            $this->redirectRoute('ai-drafter.show', $generation, navigate: true);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.ai-drafter.wizard');
    }
}
