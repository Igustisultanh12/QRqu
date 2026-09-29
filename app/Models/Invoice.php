<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'customer_id',
        'external_id',
        'amount',
        'description',
        'customer_name',
        'customer_email',
        'customer_phone',
        'callback_url',
        'webhook_url',
        'status',
        'payment_method',
        'qr_string',
        'qr_url',
        'expired_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function latestTransaction()
    {
        return $this->hasOne(Transaction::class)->latestOfMany();
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['CREATED', 'PENDING']);
    }

    public static function generateId(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(8));
    }
}
