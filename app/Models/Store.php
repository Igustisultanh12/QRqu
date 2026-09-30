<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'code',
        'description',
        'address',
        'phone',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }

    public function getBalanceAttribute(): float
    {
        $revenue = (float) $this->transactions()->where('status', 'PAID')->sum('amount');
        $withdrawn = (float) $this->settlements()->where('status', 'selesai')->sum('amount');
        $pending = (float) $this->settlements()->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
        return max(0.0, $revenue - ($withdrawn + $pending));
    }
}
