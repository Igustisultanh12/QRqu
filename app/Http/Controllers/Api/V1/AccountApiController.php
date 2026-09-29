<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountApiController extends Controller
{
    /**
     * Get Merchant Account Details (GET /api/v1/account)
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');
        $customer->load('activeSubscription.plan');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'company_name' => $customer->company_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'country' => $customer->country,
                'status' => $customer->status,
                'subscription' => $customer->activeSubscription ? [
                    'plan' => $customer->activeSubscription->plan->name,
                    'status' => $customer->activeSubscription->status,
                    'starts_at' => $customer->activeSubscription->starts_at->toIso8601String(),
                    'expires_at' => $customer->activeSubscription->expires_at->toIso8601String(),
                    'remaining_days' => $customer->activeSubscription->remainingDays(),
                    'rate_limit_rpm' => $customer->activeSubscription->plan->rate_limit_rpm,
                ] : null,
            ],
            'request_id' => $request->attributes->get('request_id'),
        ]);
    }

    /**
     * Get Account Usage (GET /api/v1/account/usage)
     */
    public function usage(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();

        $todayUsage = $customer->apiUsages()->where('date', $today)->sum('request_count');
        $monthUsage = $customer->apiUsages()->where('date', '>=', $thisMonth)->sum('request_count');

        $totalTransactions = $customer->transactions()->count();
        $successTransactions = $customer->transactions()->where('status', 'PAID')->count();
        $totalAmountPaid = (float) $customer->transactions()->where('status', 'PAID')->sum('amount');

        $plan = $customer->activeSubscription?->plan;

        return response()->json([
            'success' => true,
            'data' => [
                'api_requests' => [
                    'today' => (int) $todayUsage,
                    'this_month' => (int) $monthUsage,
                    'monthly_limit' => $plan ? $plan->api_limit : 10000,
                    'usage_percentage' => $plan && $plan->api_limit > 0 ? round(($monthUsage / $plan->api_limit) * 100, 2) : 0,
                    'rate_limit_rpm' => $customer->getRateLimitRpm(),
                ],
                'transactions' => [
                    'total' => $totalTransactions,
                    'successful' => $successTransactions,
                    'total_volume_idr' => $totalAmountPaid,
                    'monthly_limit' => $plan ? $plan->transaction_limit : 1000,
                ],
            ],
            'request_id' => $request->attributes->get('request_id'),
        ]);
    }

    /**
     * Get Account Balance / Settlement info (GET /api/v1/account/balance)
     */
    public function balance(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $paidVolume = (float) $customer->transactions()->where('status', 'PAID')->sum('amount');
        $pendingVolume = (float) $customer->transactions()->where('status', 'PENDING')->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => 'IDR',
                'gross_settled_amount' => $paidVolume,
                'pending_amount' => $pendingVolume,
            ],
            'request_id' => $request->attributes->get('request_id'),
        ]);
    }
}
