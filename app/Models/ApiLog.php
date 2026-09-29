<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'customer_id',
        'api_credential_id',
        'method',
        'path',
        'ip_address',
        'user_agent',
        'status_code',
        'duration_ms',
        'request_payload',
        'response_body',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_body' => 'array',
            'status_code' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function credential()
    {
        return $this->belongsTo(ApiCredential::class, 'api_credential_id');
    }
}
