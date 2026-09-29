<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'status',
        'whatsapp_number',
        'phone',
        'avatar',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'email_verified_at',
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return null;
    }

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function securityLogs()
    {
        return $this->hasMany(SecurityLog::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
            ->wherePivot('model_type', static::class)
            ->withPivot('model_type');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permissionName) {
            $query->where('name', $permissionName);
        })->exists();
    }

    public function ensureCustomerProfile(): Customer
    {
        if ($this->customer) {
            return $this->customer;
        }

        $customer = Customer::firstOrCreate(
            ['user_id' => $this->id],
            [
                'name' => $this->name,
                'company_name' => $this->isAdmin() ? 'QRqu Administrator HQ' : ($this->name . ' Store'),
                'email' => $this->email,
                'phone' => $this->phone ?? $this->whatsapp_number ?? '081234567890',
                'whatsapp' => $this->whatsapp_number ?? $this->phone ?? '081234567890',
                'status' => 'active',
            ]
        );

        if ($this->isAdmin() && !$customer->hasActiveSubscription()) {
            $plan = Plan::where('slug', 'semiannual-180d')->first() ?? Plan::first();
            if ($plan) {
                Subscription::create([
                    'customer_id' => $customer->id,
                    'plan_id' => $plan->id,
                    'starts_at' => now(),
                    'expires_at' => now()->addYears(5),
                    'status' => 'active',
                    'auto_renew' => true,
                ]);
            }
        }

        return $customer;
    }
}