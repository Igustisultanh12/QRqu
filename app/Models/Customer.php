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
            ->ofMany([
                'id' => 'max',
            ], function ($query) {
                $query->whereIn('status', ['active', 'suspended'])
                      ->where('expires_at', '>', now());
            });
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function defaultStore()
    {
        return $this->hasOne(Store::class)->where('is_default', true);
    }

    public function ensureStores(): void
    {
        if ($this->stores()->count() === 0) {
            $defaultName = $this->company_name ?: ($this->name . ' Store');
            $defaultStore = $this->stores()->create([
                'name' => $defaultName,
                'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $defaultName), 0, 8)) ?: 'STORE1',
                'is_default' => true,
                'status' => 'active',
            ]);

            // If there are ROMEI transactions/invoices, also create Toko ROMEI
            $hasRomei = $this->invoices()->where(function ($q) {
                $q->where('external_id', 'like', '%ROMEI%')
                  ->orWhere('description', 'like', '%ROMEI%');
            })->exists();

            if ($hasRomei && !str_contains(strtoupper($defaultName), 'ROMEI')) {
                $romeiStore = $this->stores()->create([
                    'name' => 'Toko ROMEI',
                    'code' => 'ROMEI',
                    'is_default' => false,
                    'status' => 'active',
                ]);

                $romeiInvoiceIds = $this->invoices()->where(function ($q) {
                    $q->where('external_id', 'like', '%ROMEI%')
                      ->orWhere('description', 'like', '%ROMEI%');
                })->pluck('id');

                $this->invoices()->whereIn('id', $romeiInvoiceIds)->update(['store_id' => $romeiStore->id]);
                $this->transactions()->whereIn('invoice_id', $romeiInvoiceIds)->update(['store_id' => $romeiStore->id]);
            }

            // Link remaining invoices & transactions to defaultStore
            $this->invoices()->whereNull('store_id')->update(['store_id' => $defaultStore->id]);
            $this->transactions()->whereNull('store_id')->update(['store_id' => $defaultStore->id]);
        }
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

    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function getBalanceAttribute(): float
    {
        $revenue = (float) $this->transactions()->where('status', 'PAID')->sum('amount');
        $withdrawn = (float) $this->settlements()->where('status', 'selesai')->sum('amount');
        $pending = (float) $this->settlements()->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
        return max(0.0, $revenue - ($withdrawn + $pending));
    }

    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->transactions()->where('status', 'PAID')->sum('amount');
    }

    public function getTotalWithdrawnAttribute(): float
    {
        return (float) $this->settlements()->where('status', 'selesai')->sum('amount');
    }

    public function getPendingWithdrawnAttribute(): float
    {
        return (float) $this->settlements()->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
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
