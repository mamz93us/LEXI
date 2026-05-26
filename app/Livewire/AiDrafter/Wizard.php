<?php

declare(strict_types=1);

namespace App\Livewire\AiDrafter;

use App\Models\Clause;
use App\Models\ClauseVersion;
use App\Models\LegalCase;
use App\Models\Template;
use App\Services\Rag\RagGenerator;
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

    /** @var array<string, string> */
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

    #[Computed]
    public function templateVariables(): array
    {
        $vars = $this->template?->currentVersion?->variables ?? [];

        return is_array($vars) ? $vars : [];
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
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function generate(RagGenerator $generator): void
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

        $intent = trim($this->user_intent) !== ''
            ? $this->user_intent
            : "صياغة {$template->type} بناءً على القالب «{$template->title}»";

        try {
            $generation = $generator->draft(
                subject: $subject,
                userIntent: $intent,
                filledData: $this->filled,
                verbatimClauses: $clauseVersions,
            );
            $this->output = $generation->output;
            $this->generation_id = $generation->id;
        } catch (Throwable $e) {
            // RagGenerator already persisted an AiGeneration row marked rejected.
            $this->error = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.ai-drafter.wizard');
    }
}
