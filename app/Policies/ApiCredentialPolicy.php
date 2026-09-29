<?php

namespace App\Policies;

use App\Models\ApiCredential;
use App\Models\User;

class ApiCredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ApiCredential $credential): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $credential->customer_id === $user->customer?->id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->customer && $user->customer->hasActiveSubscription();
    }

    public function update(User $user, ApiCredential $credential): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $credential->customer_id === $user->customer?->id;
    }

    public function delete(User $user, ApiCredential $credential): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $credential->customer_id === $user->customer?->id;
    }
}
