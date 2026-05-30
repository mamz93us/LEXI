<?php

declare(strict_types=1);

namespace App\Livewire\Audit;

use App\Enums\UserRole;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only audit trail viewer. Partner/Admin only — the log records
 * privileged actions (who edited a client's national ID, who deleted a
 * proxy) and must not be browsable by every staff member.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $action_filter = '';

    public string $type_filter = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && in_array($user->role, [UserRole::Partner, UserRole::Admin], true),
            403,
            'سجل التدقيق متاح للشركاء والمديرين فقط.'
        );
    }

    /** Distinct auditable types present, for the filter dropdown. */
    #[Computed]
    public function types(): Collection
    {
        return AuditLog::query()
            ->where('tenant_id', tenant('id'))
            ->distinct()
            ->pluck('auditable_type');
    }

    #[Computed]
    public function logs()
    {
        return AuditLog::query()
            ->where('tenant_id', tenant('id'))
            ->with('user')
            ->when($this->action_filter !== '', fn ($q) => $q->where('action', $this->action_filter))
            ->when($this->type_filter !== '', fn ($q) => $q->where('auditable_type', $this->type_filter))
            ->latest('created_at')
            ->paginate(40);
    }

    public function render(): View
    {
        return view('livewire.audit.index');
    }
}
