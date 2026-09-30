<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\Transaction;
use App\Services\Payment\Doku\DokuService;
use App\Services\Webhook\CustomerWebhookService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionTransactionController extends Controller
{
    public function index(Request $request, DokuService $dokuService, CustomerWebhookService $webhookService): Response
    {
        // 1. Auto-sync transaksi subs PENDING terbaru langsung ke DOKU
        $pendingSubs = Transaction::where('external_id', 'like', 'SUB-%')
            ->where('status', 'PENDING')
            ->where('created_at', '>=', now()->subHours(24))
            ->limit(10)
            ->get();

        foreach ($pendingSubs as $trx) {
            $throttleKey = 'doku_subs_sync_' . $trx->id;
            if (!\Illuminate\Support\Facades\Cache::has($throttleKey)) {
                \Illuminate\Support\Facades\Cache::put($throttleKey, true, 2);
                $res = $dokuService->syncTransactionWithDoku($trx, $webhookService);
                if ($res['is_paid'] && $trx->invoice_id) {
                    $sub = Subscription::where('invoice_id', $trx->invoice_id)->where('status', '!=', 'active')->first();
                    if ($sub) {
                        $sub->activateWithExtension();
                    }
                }
            }
        }

        // 2. Base Query Transaksi Langganan (SUB-*)
        $baseQuery = Transaction::where('external_id', 'like', 'SUB-%');

        // Statistik Keseluruhan Transaksi Langganan
        $totalRevenue = (float) (clone $baseQuery)->where('status', 'PAID')->sum('amount');
        $paidCount = (clone $baseQuery)->where('status', 'PAID')->count();
        $pendingCount = (clone $baseQuery)->whereIn('status', ['PENDING', 'CREATED'])->count();
        $thisMonthRevenue = (float) (clone $baseQuery)
            ->where('status', 'PAID')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        // 3. Query dengan Filter
        $query = (clone $baseQuery)->with(['customer', 'invoice', 'dokuTransaction'])->latest();

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('invoice_id', 'like', "%{$search}%")
                    ->orWhere('doku_reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('description', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $transactions = $query->paginate(20)->withQueryString()->through(function ($trx) {
            // Ambil relasi subscription jika ada
            $subscription = Subscription::with('plan')->where('invoice_id', $trx->invoice_id)->first();

            return [
                'id' => $trx->id,
                'invoice_id' => $trx->invoice_id,
                'external_id' => $trx->external_id,
                'amount' => (float) $trx->amount,
                'amount_formatted' => 'Rp ' . number_format($trx->amount, 0, ',', '.'),
                'status' => $trx->status,
                'payment_method' => $trx->payment_method ?? 'QRIS',
                'doku_reference' => $trx->doku_reference,
                'created_at' => $trx->created_at->format('d/m/Y H:i'),
                'paid_at' => $trx->invoice?->paid_at ? $trx->invoice->paid_at->format('d/m/Y H:i') : null,
                'customer' => $trx->customer ? [
                    'id' => $trx->customer->id,
                    'name' => $trx->customer->name,
                    'company_name' => $trx->customer->company_name,
                    'email' => $trx->customer->email,
                ] : null,
                'plan_name' => $subscription?->plan?->name ?: ($trx->invoice?->description ?: 'Paket Langganan'),
                'plan_duration_days' => $subscription?->plan?->duration_days ?: 30,
                'subscription_status' => $subscription?->status,
                'starts_at' => $subscription?->starts_at?->format('d/m/Y'),
                'expires_at' => $subscription?->expires_at?->format('d/m/Y'),
            ];
        });

        $customers = Customer::select('id', 'name', 'company_name')->get();
        $plans = Plan::select('id', 'name', 'price')->get();

        return Inertia::render('Admin/Subscriptions/Transactions', [
            'transactions' => $transactions,
            'customers' => $customers,
            'plans' => $plans,
            'filters' => $request->only(['customer_id', 'status', 'search', 'date_from', 'date_to']),
            'stats' => [
                'total_revenue' => $totalRevenue,
                'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'paid_count' => $paidCount,
                'pending_count' => $pendingCount,
                'this_month_revenue' => $thisMonthRevenue,
                'this_month_revenue_formatted' => 'Rp ' . number_format($thisMonthRevenue, 0, ',', '.'),
            ],
        ]);
    }

    public function show(Transaction $transaction, DokuService $dokuService, CustomerWebhookService $webhookService): Response
    {
        if ($transaction->status === 'PENDING') {
            $dokuService->syncTransactionWithDoku($transaction, $webhookService);
            $transaction->refresh();
        }

        $transaction->load(['customer', 'invoice', 'statusHistories', 'dokuTransaction']);
        $subscription = Subscription::with('plan')->where('invoice_id', $transaction->invoice_id)->first();

        return Inertia::render('Admin/Subscriptions/Show', [
            'transaction' => $transaction,
            'subscription' => $subscription,
        ]);
    }

    public function syncStatus(Request $request, Transaction $transaction, DokuService $dokuService, CustomerWebhookService $webhookService)
    {
        $result = $dokuService->syncTransactionWithDoku($transaction, $webhookService);

        if ($result['is_paid'] && $transaction->invoice_id) {
            $subscription = Subscription::where('invoice_id', $transaction->invoice_id)->first();
            if ($subscription && $subscription->status !== 'active') {
                $subscription->activateWithExtension();
            }
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['is_paid']) {
            return redirect()->back()->with('success', "Pembayaran Langganan BERHASIL diverifikasi DOKU! Ref: {$result['doku_reference']}. Paket langganan merchant aktif.");
        }

        return redirect()->back()->with('info', $result['message']);
    }
}
