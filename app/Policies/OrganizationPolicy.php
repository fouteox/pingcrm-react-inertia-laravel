<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

final class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->account_id === $organization->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->account_id === $organization->account_id;
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->account_id === $organization->account_id;
    }

    public function restore(User $user, Organization $organization): bool
    {
        return $user->account_id === $organization->account_id;
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return $user->account_id === $organization->account_id;
    }
}
