<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Who can manage staff members:
 *   - Partners + Admins: full CRUD on everyone in the tenant
 *   - Everyone else: can only view + edit their own profile
 *
 * Deactivating yourself is blocked so a single-partner firm can't lock
 * itself out by accident. Deleting users is blocked entirely — we set
 * is_active=false instead so audit history stays intact.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->canManageUsers();
    }

    public function view(User $actor, User $target): bool
    {
        if (! $this->sameTenant($actor, $target)) {
            return false;
        }

        return $actor->canManageUsers() || $actor->id === $target->id;
    }

    public function create(User $actor): bool
    {
        return $actor->canManageUsers();
    }

    public function update(User $actor, User $target): bool
    {
        if (! $this->sameTenant($actor, $target)) {
            return false;
        }

        return $actor->canManageUsers() || $actor->id === $target->id;
    }

    /**
     * Deactivate (soft) — only managers, same tenant, never yourself.
     */
    public function deactivate(User $actor, User $target): bool
    {
        if (! $this->sameTenant($actor, $target)) {
            return false;
        }
        if ($actor->id === $target->id) {
            return false;
        }
        // Don't let the last active partner be deactivated.
        if ($target->role === UserRole::Partner) {
            $remainingPartners = User::query()
                ->where('role', UserRole::Partner)
                ->where('is_active', true)
                ->where('id', '!=', $target->id)
                ->count();
            if ($remainingPartners === 0) {
                return false;
            }
        }

        return $actor->canManageUsers();
    }

    /**
     * Both users must belong to the same (non-null) tenant. Blocks a
     * partner in Firm A from acting on a user in Firm B by guessing
     * the integer id — the User model has no global tenant scope, so
     * this is the enforcement point.
     */
    private function sameTenant(User $actor, User $target): bool
    {
        return $actor->tenant_id !== null
            && $actor->tenant_id === $target->tenant_id;
    }
}
