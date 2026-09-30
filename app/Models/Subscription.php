<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'plan_id',
        'invoice_id',
        'starts_at',
        'expires_at',
        'grace_period_days',
        'status',
        'auto_renew',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'grace_period_days' => 'integer',
            'auto_renew' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function histories()
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // Check if expires_at plus grace period is still in the future
        $graceExpiry = $this->expires_at->copy()->addDays($this->grace_period_days ?? 0);
        return $graceExpiry->isFuture();
    }

    public function isExpired(): bool
    {
        return !$this->isActive();
    }

    public function remainingDays(): int
    {
        if ($this->expires_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function isInGracePeriod(): bool
    {
        return $this->expires_at->isPast() && $this->isActive();
    }

    /**
     * Activate subscription with duration extension (stacking) if user already has an active subscription.
     */
    public function activateWithExtension(): bool
    {
        $customer = $this->customer;
        $plan = $this->plan;

        if (!$customer || !$plan) {
            return false;
        }

        $currentActive = Subscription::where('customer_id', $this->customer_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', ['active', 'suspended'])
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
        $startsAt = now();
        $expiresAt = now()->addDays($plan->duration_days);
        $isExtension = false;

        // If customer already has an active subscription that has not expired yet, stack the duration
        if ($currentActive && $currentActive->id !== $this->id && $currentActive->expires_at && $currentActive->expires_at->isFuture()) {
            $expiresAt = $currentActive->expires_at->copy()->addDays($plan->duration_days);
            $currentActive->update(['status' => 'completed']);
            $isExtension = true;
        }

        $this->update([
            'status' => 'active',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'grace_period_days' => 3,
            'auto_renew' => true,
        ]);

        SubscriptionHistory::create([
            'subscription_id' => $this->id,
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'event' => $isExtension ? 'upgraded' : 'created',
            'note' => $isExtension
                ? "Perpanjangan/Upgrade paket {$plan->name} (+{$plan->duration_days} hari). Masa aktif bertambah hingga " . $expiresAt->format('d/m/Y H:i')
                : "Pembayaran QRIS lunas untuk paket {$plan->name} ({$plan->duration_days} hari)",
            'amount_paid' => $this->invoice?->amount ?? ($plan->price ?? 0),
        ]);

        AuditLog::record('SUBSCRIBE_PLAN_PAID', $this, null, [
            'plan' => $plan->name,
            'amount' => $this->invoice?->amount ?? $plan->price,
            'invoice_id' => $this->invoice_id,
            'is_extension' => $isExtension,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        if ($customer->apiCredentials()->count() === 0) {
            ApiCredential::generateCredentials($customer->id, 'sandbox', 'Sandbox Key');
        }

        return true;
    }
}
