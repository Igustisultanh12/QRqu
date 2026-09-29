<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;

class WebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Webhook $webhook): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $webhook->customer_id === $user->customer?->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user->customer;
    }

    public function update(User $user, Webhook $webhook): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $webhook->customer_id === $user->customer?->id;
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $webhook->customer_id === $user->customer?->id;
    }
}
