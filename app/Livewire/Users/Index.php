<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $search = '';

    public string $role_filter = '';

    public bool $show_inactive = false;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    #[Computed]
    public function users(): Collection
    {
        $q = User::query()
            ->whereNotNull('tenant_id')   // exclude any landlord/super-admin
            ->orderBy('is_active', 'desc')
            ->orderBy('name');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'ilike', $term)
                    ->orWhere('name_ar', 'ilike', $term)
                    ->orWhere('email', 'ilike', $term)
                    ->orWhere('national_id', 'like', $term)
                    ->orWhere('bar_association_no', 'like', $term);
            });
        }
        if ($this->role_filter !== '') {
            $q->where('role', $this->role_filter);
        }
        if (! $this->show_inactive) {
            $q->where('is_active', true);
        }

        return $q->get();
    }

    public function deactivate(int $userId): void
    {
        $target = User::query()->find($userId);
        if (! $target) {
            return;
        }
        if (! auth()->user()->can('deactivate', $target)) {
            session()->flash('error', 'لا تملك صلاحية تعطيل هذا المستخدم.');

            return;
        }
        $target->update(['is_active' => false]);
        session()->flash('saved', 'تم تعطيل المستخدم.');
    }

    public function activate(int $userId): void
    {
        $target = User::query()->find($userId);
        if (! $target || ! auth()->user()->can('update', $target)) {
            return;
        }
        $target->update(['is_active' => true]);
        session()->flash('saved', 'تم تفعيل المستخدم.');
    }

    public function render(): View
    {
        return view('livewire.users.index', [
            'roles' => UserRole::cases(),
        ]);
    }
}
