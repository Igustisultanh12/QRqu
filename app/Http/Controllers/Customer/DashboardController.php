<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isAdmin() && !$user->customer) {
            $user->ensureCustomerProfile();
        }
        $customer = $user->fresh()->customer;

        if (!$customer) {
            return Inertia::render('Customer/SetupProfile', ['user' => $user]);
        }

        $customer->load('activeSubscription.plan');

        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();

        $apiUsageToday = (int) $customer->apiUsages()->where('date', $today)->sum('request_count');
        $apiUsageMonth = (int) $customer->apiUsages()->where('date', '>=', $thisMonth)->sum('request_count');

        $transactionsQuery = Transaction::where('customer_id', $customer->id);
        $totalTransactions = $transactionsQuery->count();
        $successfulTransactions = (clone $transactionsQuery)->where('status', 'PAID')->count();
        $pendingTransactions = (clone $transactionsQuery)->where('status', 'PENDING')->count();
        $failedTransactions = (clone $transactionsQuery)->whereIn('status', ['FAILED', 'EXPIRED'])->count();
        $totalVolume = (float) (clone $transactionsQuery)->where('status', 'PAID')->sum('amount');

        // Recent transactions
        $recentTransactions = Transaction::where('customer_id', $customer->id)
            ->with('invoice')
            ->latest()
            ->limit(5)
            ->get();

        // Daily chart data for the last 7 days
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $label = now()->subDays($i)->format('d M');
            $count = Transaction::where('customer_id', $customer->id)
                ->whereDate('created_at', $date)
                ->count();
            $volume = (float) Transaction::where('customer_id', $customer->id)
                ->where('status', 'PAID')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $chartData[] = [
                'date' => $label,
                'count' => $count,
                'volume' => $volume,
            ];
        }

        return Inertia::render('Customer/Dashboard', [
            'customer' => $customer,
            'subscription' => $customer->activeSubscription ? [
                'plan_name' => $customer->activeSubscription->plan->name,
                'status' => $customer->activeSubscription->status,
                'remaining_days' => $customer->activeSubscription->remainingDays(),
                'expires_at' => $customer->activeSubscription->expires_at->format('d M Y'),
                'rate_limit_rpm' => $customer->activeSubscription->plan->rate_limit_rpm,
                'transaction_limit' => $customer->activeSubscription->plan->transaction_limit,
            ] : null,
            'kpi' => [
                'api_today' => $apiUsageToday,
                'api_month' => $apiUsageMonth,
                'total_transactions' => $totalTransactions,
                'successful_transactions' => $successfulTransactions,
                'pending_transactions' => $pendingTransactions,
                'failed_transactions' => $failedTransactions,
                'total_volume' => $totalVolume,
                'total_volume_formatted' => 'Rp ' . number_format($totalVolume, 0, ',', '.'),
            ],
            'recent_transactions' => $recentTransactions,
            'chart_data' => $chartData,
        ]);
    }
}
