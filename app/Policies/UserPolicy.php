<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return $user->account_id === $model->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, User $model): bool
    {
        return $user->account_id === $model->account_id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->account_id === $model->account_id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->account_id === $model->account_id;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->account_id === $model->account_id;
    }
}
