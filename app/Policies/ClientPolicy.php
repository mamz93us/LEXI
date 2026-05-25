<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Anyone who is not an external "client" role of the firm can view/manage
     * client records. External clients (e.g. portal users) cannot. Tenant
     * isolation is already enforced by the global scope on the Client model —
     * a same-tenant policy check below is defense in depth.
     */
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->isStaff($user) && $user->tenant_id === $client->tenant_id;
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->isStaff($user) && $user->tenant_id === $client->tenant_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return in_array($user->role, [UserRole::Partner, UserRole::Admin], true)
            && $user->tenant_id === $client->tenant_id;
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
