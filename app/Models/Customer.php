<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'company_name',
        'email',
        'phone',
        'whatsapp',
        'address',
        'avatar',
        'country',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'suspended'])
            ->where('expires_at', '>', now())
            ->latestOfMany();
    }

    public function apiCredentials()
    {
        return $this->hasMany(ApiCredential::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function webhookDeliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function apiUsages()
    {
        return $this->hasMany(ApiUsage::class);
    }

    public function hasActiveSubscription(): bool
    {
        // Check if customer status is active
        if ($this->status !== 'active') {
            return false;
        }

        $sub = $this->activeSubscription;
        if (!$sub) {
            return false;
        }

        return $sub->isActive();
    }

    public function getRateLimitRpm(): int
    {
        $sub = $this->activeSubscription;
        if ($sub && $sub->plan) {
            return $sub->plan->rate_limit_rpm ?? 60;
        }

        return 60;
    }
}
