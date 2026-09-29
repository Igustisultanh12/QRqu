<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'duration_days',
        'price',
        'transaction_limit',
        'api_limit',
        'rate_limit_rpm',
        'webhook_limit',
        'features',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'transaction_limit' => 'integer',
            'api_limit' => 'integer',
            'rate_limit_rpm' => 'integer',
            'webhook_limit' => 'integer',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
