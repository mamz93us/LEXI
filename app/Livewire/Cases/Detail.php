<?php

declare(strict_types=1);

namespace App\Livewire\Cases;

use App\Models\CaseRequest;
use App\Models\Hearing;
use App\Models\Judgment;
use App\Models\JudgmentType;
use App\Models\LegalCase;
use App\Models\RequestType;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Detail extends Component
{
    public LegalCase $case;

    public string $hearing_date = '';

    public string $hearing_purpose = '';

    public ?string $hearing_next_date = null;

    public string $hearing_postponement = '';

    /** @var array<int,array{type_id:?int,party:string,status:string,notes:?string}> */
    public array $hearing_requests = [];

    public ?int $judgment_type_id = null;

    public string $judgment_date = '';

    public string $judgment_presence = 'in_presence';

    public ?string $judgment_summary = null;

    public function mount(LegalCase $case): void
    {
        $this->authorize('view', $case);
        $this->case = $case;
    }

    public function addRequestLine(): void
    {
        $this->hearing_requests[] = [
            'type_id' => null,
            'party' => 'claimant',
            'status' => 'pending',
            'notes' => null,
        ];
    }

    public function removeRequestLine(int $index): void
    {
        unset($this->hearing_requests[$index]);
        $this->hearing_requests = array_values($this->hearing_requests);
    }

    public function saveHearing(): void
    {
        $this->authorize('update', $this->case);

        $this->validate([
            'hearing_date' => ['required', 'date'],
            'hearing_purpose' => ['nullable', 'string', 'max:255'],
            'hearing_next_date' => ['nullable', 'date'],
            'hearing_postponement' => ['nullable', 'string', 'max:255'],
            'hearing_requests.*.type_id' => ['nullable', 'exists:request_types,id'],
            'hearing_requests.*.party' => ['nullable', 'string', 'max:64'],
            'hearing_requests.*.status' => ['nullable', Rule::in(['pending', 'granted', 'rejected', 'deferred'])],
        ]);

        $hearing = Hearing::create([
            'case_id' => $this->case->id,
            'court_id' => $this->case->court_id,
            'session_date' => $this->hearing_date,
            'purpose' => $this->hearing_purpose ?: null,
            'next_date' => $this->hearing_next_date ?: null,
            'postponement_reason' => $this->hearing_postponement ?: null,
        ]);

        foreach ($this->hearing_requests as $line) {
            if (! $line['type_id']) {
                continue;
            }
            CaseRequest::create([
                'hearing_id' => $hearing->id,
                'case_id' => $this->case->id,
                'request_type_id' => $line['type_id'],
                'requesting_party' => $line['party'] ?? null,
                'status' => $line['status'] ?? 'pending',
                'notes' => $line['notes'] ?? null,
            ]);
        }

        $this->reset(['hearing_date', 'hearing_purpose', 'hearing_next_date', 'hearing_postponement', 'hearing_requests']);
        $this->dispatch('hearing-saved');
    }

    public function saveJudgment(): void
    {
        $this->authorize('update', $this->case);

        if (! $this->judgment_type_id) {
            throw ValidationException::withMessages(['judgment_type_id' => 'Choose a judgment type.']);
        }

        $this->validate([
            'judgment_type_id' => ['required', 'exists:judgment_types,id'],
            'judgment_date' => ['required', 'date'],
            'judgment_presence' => ['required', Rule::in(['in_presence', 'in_absentia'])],
            'judgment_summary' => ['nullable', 'string', 'max:5000'],
        ]);

        Judgment::create([
            'case_id' => $this->case->id,
            'judgment_type_id' => $this->judgment_type_id,
            'judgment_date' => $this->judgment_date,
            'presence_type' => $this->judgment_presence,
            'summary' => $this->judgment_summary,
        ]);

        $this->reset(['judgment_type_id', 'judgment_date', 'judgment_presence', 'judgment_summary']);
        $this->dispatch('judgment-saved');
    }

    public function appealThisJudgment(int $judgmentId): void
    {
        $judgment = Judgment::query()->find($judgmentId);
        if (! $judgment || $judgment->case_id !== $this->case->id) {
            return;
        }

        $appeal = LegalCase::create([
            'client_id' => $this->case->client_id,
            'case_number' => $this->case->case_number.'-APP',
            'title' => $this->case->title.' — استئناف',
            'status' => 'open',
            'degree' => 'appeal',
            'case_type_id' => $this->case->case_type_id,
            'parent_case_id' => $this->case->id,
            'appeal_type' => 'استئناف',
        ]);

        $this->redirectRoute('cases.show', ['case' => $appeal->id], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.cases.detail', [
            'requestTypes' => RequestType::orderBy('sort_order')->get(),
            'judgmentTypes' => JudgmentType::orderBy('sort_order')->get(),
            'hearings' => $this->case->hearings()->with('requests.requestType')->orderByDesc('session_date')->get(),
            'judgments' => $this->case->judgments()->with('judgmentType', 'deadlines')->orderByDesc('judgment_date')->get(),
            'chain' => $this->case->chain(),
        ]);
    }
}
