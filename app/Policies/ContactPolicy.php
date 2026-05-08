<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

final class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->account_id === $contact->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->account_id === $contact->account_id;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->account_id === $contact->account_id;
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $user->account_id === $contact->account_id;
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->account_id === $contact->account_id;
    }
}
