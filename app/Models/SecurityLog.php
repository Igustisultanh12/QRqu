<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'ip_address',
        'user_agent',
        'event_type',
        'severity',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function logEvent(
        string $eventType,
        string $severity = 'medium',
        ?array $details = null,
        ?int $customerId = null,
        ?int $userId = null
    ): self {
        return self::create([
            'customer_id' => $customerId,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent(),
            'event_type' => $eventType,
            'severity' => $severity,
            'details' => $details,
        ]);
    }
}
