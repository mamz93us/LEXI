<?php

declare(strict_types=1);

namespace App\Livewire\TimeEntries;

use App\Models\Client;
use App\Models\Company;
use App\Models\LegalCase;
use App\Models\TimeEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?TimeEntry $entry = null;

    public string $worked_on = '';

    public int $minutes = 30;

    public ?int $rate_egp_per_hour = null;

    public ?string $description = null;

    public bool $billable = true;

    public string $subject_kind = 'case'; // case|company|client|none

    public ?int $subject_id = null;

    public function mount(?TimeEntry $entry = null): void
    {
        if ($entry && $entry->exists) {
            $this->entry = $entry;
            $this->worked_on = $entry->worked_on->toDateString();
            $this->minutes = $entry->minutes;
            $this->rate_egp_per_hour = $entry->rate_piastres ? intdiv($entry->rate_piastres, 100) : null;
            $this->description = $entry->description;
            $this->billable = (bool) $entry->billable;
            $this->subject_kind = match ($entry->subject_type) {
                LegalCase::class => 'case',
                Company::class => 'company',
                Client::class => 'client',
                default => 'none',
            };
            $this->subject_id = $entry->subject_id;
        } else {
            $this->worked_on = now()->toDateString();
        }
    }

    #[Computed]
    public function subjects(): array
    {
        return match ($this->subject_kind) {
            'case' => LegalCase::query()->orderByDesc('created_at')->limit(200)->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->case_number.' — '.$c->title])->all(),
            'company' => Company::query()->orderBy('name')->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->name_ar ?? $c->name])->all(),
            'client' => Client::query()->orderBy('name')->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->name_ar ?? $c->name])->all(),
            default => [],
        };
    }

    protected function rules(): array
    {
        return [
            'worked_on' => ['required', 'date'],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'rate_egp_per_hour' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billable' => ['boolean'],
            'subject_kind' => ['required', Rule::in(['case', 'company', 'client', 'none'])],
            'subject_id' => ['nullable', 'integer'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $subjectType = match ($data['subject_kind']) {
            'case' => LegalCase::class,
            'company' => Company::class,
            'client' => Client::class,
            default => null,
        };

        $payload = [
            'user_id' => auth()->id(),
            'worked_on' => $data['worked_on'],
            'minutes' => $data['minutes'],
            'rate_piastres' => $data['rate_egp_per_hour'] !== null ? $data['rate_egp_per_hour'] * 100 : null,
            'description' => $data['description'],
            'billable' => $data['billable'],
            'subject_type' => $subjectType,
            'subject_id' => $subjectType ? $data['subject_id'] : null,
        ];

        if ($this->entry) {
            $this->entry->update($payload);
        } else {
            TimeEntry::create($payload);
        }

        return $this->redirectRoute('time-entries.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.time-entries.form');
    }
}
