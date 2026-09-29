<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokuWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'original_request_id',
        'invoice_number',
        'payload',
        'signature',
        'http_headers',
        'is_processed',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'http_headers' => 'array',
            'is_processed' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
