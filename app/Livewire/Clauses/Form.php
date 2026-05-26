<?php

declare(strict_types=1);

namespace App\Livewire\Clauses;

use App\Enums\UserRole;
use App\Models\Clause;
use App\Models\ClauseVersion;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Clause $clause = null;

    public string $topic = '';

    public string $title = '';

    public string $body = '';

    public bool $is_active = true;

    public bool $approve_now = false;

    public function mount(?Clause $clause = null): void
    {
        if ($clause && $clause->exists) {
            $this->clause = $clause->load('currentVersion');
            $this->topic = $clause->topic;
            $this->title = $clause->title;
            $this->is_active = (bool) $clause->is_active;
            $this->body = $clause->currentVersion?->body ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['boolean'],
            'approve_now' => ['boolean'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->clause) {
            $this->clause->update([
                'topic' => $data['topic'],
                'title' => $data['title'],
                'is_active' => $data['is_active'],
            ]);
            $clause = $this->clause;
        } else {
            $clause = Clause::create([
                'topic' => $data['topic'],
                'title' => $data['title'],
                'is_active' => $data['is_active'],
            ]);
        }

        $nextVersion = ($clause->versions()->max('version_no') ?? 0) + 1;

        $canApprove = auth()->user() && in_array(auth()->user()->role, [UserRole::Partner, UserRole::Admin], true);
        $version = ClauseVersion::create([
            'clause_id' => $clause->id,
            'version_no' => $nextVersion,
            'body' => $data['body'],
            'created_by_user_id' => auth()->id(),
            'approved_by_user_id' => ($data['approve_now'] && $canApprove) ? auth()->id() : null,
            'approved_at' => ($data['approve_now'] && $canApprove) ? now() : null,
        ]);
        $clause->update(['current_version_id' => $version->id]);

        return $this->redirectRoute('clauses.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.clauses.form');
    }
}
