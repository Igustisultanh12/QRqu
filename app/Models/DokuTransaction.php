<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokuTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'invoice_number',
        'request_id',
        'client_id',
        'amount',
        'doku_url',
        'request_payload',
        'response_payload',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
