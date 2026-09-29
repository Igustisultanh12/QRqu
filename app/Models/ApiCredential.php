<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'environment',
        'api_key',
        'api_secret_hash',
        'api_secret_encrypted',
        'ip_whitelist',
        'status',
        'expires_at',
        'last_used_at',
    ];

    protected $hidden = [
        'api_secret_hash',
        'api_secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'ip_whitelist' => 'array',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function usages()
    {
        return $this->hasMany(ApiUsage::class);
    }

    public function verifySecret(string $secret): bool
    {
        return Hash::check($secret, $this->api_secret_hash);
    }

    public function getDecryptedSecret(): ?string
    {
        try {
            return Crypt::decryptString($this->api_secret_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isIpAllowed(?string $ip): bool
    {
        if (empty($this->ip_whitelist) || !is_array($this->ip_whitelist)) {
            return true;
        }

        if (empty($ip)) {
            return false;
        }

        return in_array($ip, $this->ip_whitelist);
    }

    public static function generateCredentials(int $customerId, string $environment = 'production', string $name = 'Default Key'): array
    {
        $prefix = ($environment === 'sandbox') ? 'qrqu_sand_' : 'qrqu_live_';
        $apiKey = $prefix . Str::random(32);
        $plainSecret = 'sec_' . Str::random(48);

        $credential = self::create([
            'customer_id' => $customerId,
            'name' => $name,
            'environment' => $environment,
            'api_key' => $apiKey,
            'api_secret_hash' => Hash::make($plainSecret),
            'api_secret_encrypted' => Crypt::encryptString($plainSecret),
            'status' => 'active',
        ]);

        return [
            'credential' => $credential,
            'plain_secret' => $plainSecret,
            'api_key' => $apiKey,
        ];
    }
}
