<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'invoice_id',
        'type',
        'quantity',
        'price_paid',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_paid' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
