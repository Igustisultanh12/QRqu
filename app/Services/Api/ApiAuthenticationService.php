<?php

namespace App\Services\Api;

use App\Models\ApiCredential;
use App\Models\Customer;
use App\Models\SecurityLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiAuthenticationService
{
    /**
     * Authenticate incoming merchant API request
     * Returns array [ 'authenticated' => bool, 'credential' => ApiCredential, 'customer' => Customer, 'error' => ?array ]
     */
    public function authenticate(Request $request): array
    {
        $apiKey = $request->header('X-QRQU-Key');
        $timestamp = $request->header('X-QRQU-Timestamp');
        $nonce = $request->header('X-QRQU-Nonce');
        $signature = $request->header('X-QRQU-Signature');

        if (!$apiKey || !$timestamp || !$nonce || !$signature) {
            SecurityLog::logEvent('missing_auth_headers', 'low', [
                'headers' => [
                    'has_key' => !empty($apiKey),
                    'has_timestamp' => !empty($timestamp),
                    'has_nonce' => !empty($nonce),
                    'has_signature' => !empty($signature),
                ]
            ]);

            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'MISSING_AUTH_HEADERS',
                    'message' => 'Missing required authentication headers (X-QRQU-Key, X-QRQU-Timestamp, X-QRQU-Nonce, X-QRQU-Signature)',
                ],
            ];
        }

        // 1. Find API Credential
        $credential = ApiCredential::with('customer.activeSubscription.plan')->where('api_key', $apiKey)->first();
        if (!$credential || $credential->status !== 'active') {
            SecurityLog::logEvent('invalid_api_key', 'medium', ['api_key' => substr($apiKey, 0, 12) . '...']);

            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'The provided API Key is invalid or inactive',
                ],
            ];
        }

        $customer = $credential->customer;
        if (!$customer || $customer->status !== 'active') {
            SecurityLog::logEvent('inactive_customer_access', 'medium', ['customer_id' => $customer?->id], $customer?->id);

            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'ACCOUNT_SUSPENDED',
                    'message' => 'Customer account is not active or suspended',
                ],
            ];
        }

        // 2. Validate IP Whitelist
        $clientIp = $request->ip();
        if (!$credential->isIpAllowed($clientIp)) {
            SecurityLog::logEvent('unauthorized_ip', 'high', ['ip' => $clientIp, 'allowed' => $credential->ip_whitelist], $customer->id);

            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'IP_NOT_WHITELISTED',
                    'message' => "Client IP {$clientIp} is not authorized for this API Key",
                ],
            ];
        }

        // 3. Validate Timestamp Tolerance
        $tolerance = (int) SystemSetting::get('api_timestamp_tolerance', env('QRQU_API_TIMESTAMP_TOLERANCE', 300));
        $requestTime = is_numeric($timestamp) ? (int) $timestamp : strtotime($timestamp);
        $currentTime = time();

        if (abs($currentTime - $requestTime) > $tolerance) {
            SecurityLog::logEvent('timestamp_expired', 'medium', [
                'request_time' => $requestTime,
                'current_time' => $currentTime,
                'tolerance' => $tolerance,
            ], $customer->id);

            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'TIMESTAMP_EXPIRED',
                    'message' => "Request timestamp is beyond acceptable tolerance ({$tolerance}s)",
                ],
            ];
        }

        // 4. Validate Nonce (Replay Attack Prevention)
        $nonceCacheKey = "api_nonce:{$apiKey}:{$nonce}";
        if (Cache::has($nonceCacheKey)) {
            SecurityLog::logEvent('replay_attack_detected', 'high', ['nonce' => $nonce, 'api_key' => $apiKey], $customer->id);

            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'REPLAY_ATTACK_DETECTED',
                    'message' => 'Request nonce has already been used within the tolerance window',
                ],
            ];
        }
        Cache::put($nonceCacheKey, true, $tolerance * 2);

        // 5. Validate HMAC-SHA256 Signature
        $plainSecret = $credential->getDecryptedSecret();
        if (!$plainSecret) {
            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'CREDENTIAL_DECRYPTION_ERROR',
                    'message' => 'Unable to verify credential integrity',
                ],
            ];
        }

        $rawBody = $request->getContent();
        $expectedSignaturePayload = $apiKey . $timestamp . $nonce . $rawBody;
        $expectedSignature = hash_hmac('sha256', $expectedSignaturePayload, $plainSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            SecurityLog::logEvent('invalid_signature', 'high', [
                'provided_signature' => $signature,
                'api_key' => $apiKey,
            ], $customer->id);

            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'INVALID_SIGNATURE',
                    'message' => 'Request signature does not match computed HMAC-SHA256',
                ],
            ];
        }

        // 6. Validate Active Subscription
        if (!$customer->hasActiveSubscription()) {
            return [
                'authenticated' => false,
                'error' => [
                    'code' => 'SUBSCRIPTION_EXPIRED',
                    'message' => 'An active subscription plan is required to access QRqu API',
                ],
            ];
        }

        // Update last used timestamp asynchronously/quietly
        $credential->updateQuietly(['last_used_at' => now()]);

        return [
            'authenticated' => true,
            'credential' => $credential,
            'customer' => $customer,
            'error' => null,
        ];
    }
}
