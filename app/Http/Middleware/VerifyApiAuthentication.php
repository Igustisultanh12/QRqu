<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use App\Models\ApiUsage;
use App\Models\IdempotencyKey;
use App\Services\Api\ApiAuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiAuthentication
{
    public function __construct(
        protected ApiAuthenticationService $authService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = 'REQ-' . strtoupper(Str::random(16));
        $request->attributes->set('request_id', $requestId);

        // 1. Authenticate Request
        $authResult = $this->authService->authenticate($request);

        if (!$authResult['authenticated']) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            // Log failed attempt
            ApiLog::create([
                'request_id' => $requestId,
                'customer_id' => null,
                'api_credential_id' => null,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent(),
                'status_code' => 401,
                'duration_ms' => $durationMs,
                'request_payload' => $this->maskSensitiveData($request->all()),
                'response_body' => $authResult['error'],
                'error_code' => $authResult['error']['code'],
            ]);

            return response()->json([
                'success' => false,
                'error' => $authResult['error'],
                'request_id' => $requestId,
            ], 401)->header('X-Request-Id', $requestId);
        }

        $customer = $authResult['customer'];
        $credential = $authResult['credential'];
        $request->attributes->set('customer', $customer);
        $request->attributes->set('credential', $credential);

        // 2. Check Idempotency Key (on POST / PUT requests)
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && in_array($request->method(), ['POST', 'PUT'])) {
            $existing = IdempotencyKey::where('customer_id', $customer->id)
                ->where('key', $idempotencyKey)
                ->first();

            if ($existing) {
                return response()->json($existing->response_body, $existing->response_code)
                    ->header('X-Request-Id', $requestId)
                    ->header('X-Idempotent-Replay', 'true');
            }
        }

        // 3. Process Request
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        // 4. Save Idempotency response if key provided and response is successful/client error
        if ($idempotencyKey && in_array($request->method(), ['POST', 'PUT'])) {
            $content = json_decode($response->getContent(), true) ?? [];
            IdempotencyKey::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'key' => $idempotencyKey,
                ],
                [
                    'request_path' => $request->path(),
                    'request_params_hash' => hash('sha256', (string) $request->getContent()),
                    'response_code' => $response->getStatusCode(),
                    'response_body' => $content,
                    'expires_at' => now()->addHours(24),
                ]
            );
        }

        // 5. Asynchronous Logging and Usage Tracking
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $responseBody = json_decode($response->getContent(), true);

        ApiLog::create([
            'request_id' => $requestId,
            'customer_id' => $customer->id,
            'api_credential_id' => $credential->id,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'request_payload' => $this->maskSensitiveData($request->all()),
            'response_body' => $this->maskSensitiveData($responseBody),
            'error_code' => $response->getStatusCode() >= 400 ? ($responseBody['error']['code'] ?? 'HTTP_' . $response->getStatusCode()) : null,
        ]);

        ApiUsage::recordUsage($customer->id, $credential->id, $request->path());

        return $response;
    }

    private function maskSensitiveData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $masked = $data;
        $sensitiveKeys = ['password', 'secret', 'api_secret', 'token', 'authorization', 'signature'];

        foreach ($masked as $key => $val) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $masked[$key] = '********';
            } elseif (is_array($val)) {
                $masked[$key] = $this->maskSensitiveData($val);
            }
        }

        return $masked;
    }
}
