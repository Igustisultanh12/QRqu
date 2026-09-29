<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'provider',
        'is_active',
        'fee_flat',
        'fee_percentage',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'fee_flat' => 'decimal:2',
            'fee_percentage' => 'decimal:2',
        ];
    }
}
