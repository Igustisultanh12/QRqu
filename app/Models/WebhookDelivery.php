<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'transaction_id',
        'invoice_id',
        'event_id',
        'event',
        'url',
        'payload',
        'signature',
        'attempt',
        'max_attempts',
        'http_status',
        'response_body',
        'duration_ms',
        'status',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_retry_at' => 'datetime',
            'attempt' => 'integer',
            'max_attempts' => 'integer',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
