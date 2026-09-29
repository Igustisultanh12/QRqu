<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $invoice->customer_id === $user->customer?->id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->customer && $user->customer->hasActiveSubscription();
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $invoice->customer_id === $user->customer?->id && $invoice->canBeCancelled();
    }
}
