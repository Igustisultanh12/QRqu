<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'UP',
            'timestamp' => now()->toIso8601String(),
            'app' => 'QRqu Gateway',
        ]);
    }

    public function ready(): JsonResponse
    {
        $checks = [
            'database' => false,
            'cache' => false,
        ];

        try {
            DB::connection()->getPdo();
            $checks['database'] = true;
        } catch (\Exception $e) {
            $checks['database_error'] = $e->getMessage();
        }

        try {
            Cache::put('health_ping', true, 10);
            $checks['cache'] = Cache::get('health_ping') === true;
        } catch (\Exception $e) {
            $checks['cache_error'] = $e->getMessage();
        }

        $allOk = $checks['database'] && $checks['cache'];

        return response()->json([
            'status' => $allOk ? 'READY' : 'DEGRADED',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allOk ? 200 : 503);
    }
}
