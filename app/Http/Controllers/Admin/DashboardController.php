<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SubscriptionHistory;
use App\Models\Transaction;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $suspendedCustomers = Customer::where('status', 'suspended')->count();

        // Separate Subscription Revenue vs Gross Transaction Value
        $totalSubscriptionRevenue = (float) SubscriptionHistory::sum('amount_paid');
        $grossTransactionValue = (float) Transaction::where('status', 'PAID')->sum('amount');

        $totalTransactions = Transaction::count();
        $successfulTransactions = Transaction::where('status', 'PAID')->count();
        $pendingTransactions = Transaction::where('status', 'PENDING')->count();
        $failedTransactions = Transaction::whereIn('status', ['FAILED', 'EXPIRED'])->count();

        $todayDate = now()->toDateString();
        $todayTransactions = Transaction::whereDate('created_at', $todayDate)->count();
        $todayVolume = (float) Transaction::where('status', 'PAID')->whereDate('created_at', $todayDate)->sum('amount');

        // Webhook delivery stats
        $totalDeliveries = WebhookDelivery::count();
        $successfulDeliveries = WebhookDelivery::where('status', 'DELIVERED')->count();
        $failedDeliveries = WebhookDelivery::where('status', 'FAILED')->count();
        $webhookSuccessRate = $totalDeliveries > 0 ? round(($successfulDeliveries / $totalDeliveries) * 100, 1) : 100;

        // Daily chart data for the last 14 days
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $label = now()->subDays($i)->format('d M');
            $count = Transaction::whereDate('created_at', $date)->count();
            $volume = (float) Transaction::where('status', 'PAID')->whereDate('created_at', $date)->sum('amount');

            $chartData[] = [
                'date' => $label,
                'count' => $count,
                'volume' => $volume,
            ];
        }

        $recentTransactions = Transaction::with(['customer', 'invoice'])->latest()->limit(8)->get();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'suspended_customers' => $suspendedCustomers,
                'subscription_revenue' => $totalSubscriptionRevenue,
                'subscription_revenue_formatted' => 'Rp ' . number_format($totalSubscriptionRevenue, 0, ',', '.'),
                'gross_transaction_value' => $grossTransactionValue,
                'gross_transaction_value_formatted' => 'Rp ' . number_format($grossTransactionValue, 0, ',', '.'),
                'total_transactions' => $totalTransactions,
                'successful_transactions' => $successfulTransactions,
                'pending_transactions' => $pendingTransactions,
                'failed_transactions' => $failedTransactions,
                'today_transactions' => $todayTransactions,
                'today_volume' => $todayVolume,
                'today_volume_formatted' => 'Rp ' . number_format($todayVolume, 0, ',', '.'),
                'webhook_success_rate' => $webhookSuccessRate,
                'webhook_failed_count' => $failedDeliveries,
            ],
            'chart_data' => $chartData,
            'recent_transactions' => $recentTransactions,
        ]);
    }
}