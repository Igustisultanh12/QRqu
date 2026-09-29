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
}
