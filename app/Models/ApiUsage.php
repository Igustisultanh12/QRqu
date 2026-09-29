<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'api_credential_id',
        'date',
        'request_count',
        'endpoint_counts',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'string',
            'endpoint_counts' => 'array',
            'request_count' => 'integer',
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

    public static function recordUsage(int $customerId, ?int $credentialId, string $endpoint): void
    {
        $today = now()->toDateString();

        $usage = self::where('customer_id', $customerId)
            ->where('api_credential_id', $credentialId)
            ->where('date', 'like', $today . '%')
            ->first();

        if (! $usage) {
            try {
                $usage = self::create([
                    'customer_id' => $customerId,
                    'api_credential_id' => $credentialId,
                    'date' => $today,
                    'request_count' => 0,
                    'endpoint_counts' => [],
                ]);
            } catch (\Throwable $e) {
                $usage = self::where('customer_id', $customerId)
                    ->where('api_credential_id', $credentialId)
                    ->where('date', 'like', $today . '%')
                    ->first();
            }
        }

        if ($usage) {
            $counts = $usage->endpoint_counts ?? [];
            $counts[$endpoint] = ($counts[$endpoint] ?? 0) + 1;

            $usage->increment('request_count');
            $usage->update(['endpoint_counts' => $counts]);
        }
    }
}
