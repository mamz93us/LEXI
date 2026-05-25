<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LegalCase;
use App\Models\User;

class LegalCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, LegalCase $case): bool
    {
        return $this->isStaff($user) && $user->tenant_id === $case->tenant_id;
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, LegalCase $case): bool
    {
        return $this->isStaff($user) && $user->tenant_id === $case->tenant_id;
    }

    public function delete(User $user, LegalCase $case): bool
    {
        return in_array($user->role, [UserRole::Partner, UserRole::Admin], true)
            && $user->tenant_id === $case->tenant_id;
    }

    private function isStaff(User $user): bool
    {
        return in_array($user->role, [
            UserRole::Partner,
            UserRole::Associate,
            UserRole::Paralegal,
            UserRole::Admin,
        ], true);
    }
}
