<?php

namespace App\Http\Middleware;

use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class CheckApiRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->attributes->get('customer');
        if (!$customer) {
            return $next($request);
        }

        $limitRpm = $customer->getRateLimitRpm();
        $rateLimitKey = 'api_rate_limit:' . $customer->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limitRpm)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            SecurityLog::logEvent('rate_limit_exceeded', 'medium', [
                'limit_rpm' => $limitRpm,
                'retry_after' => $seconds,
            ], $customer->id);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => "Too many API requests. Limit is {$limitRpm} requests per minute. Retry in {$seconds} seconds.",
                ],
                'request_id' => $request->attributes->get('request_id'),
            ], 429)->header('Retry-After', (string) $seconds);
        }

        RateLimiter::hit($rateLimitKey, 60);

        return $next($request);
    }
}
