<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Payment\Doku\DokuService;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request, DokuService $dokuService, CustomerWebhookService $webhookService): Response
    {
        // Auto-sync transaksi PENDING terbaru langsung ke DOKU (Otomatis seperti admin monitoring)
        $pendingRecent = Transaction::where('status', 'PENDING')
            ->where('created_at', '>=', now()->subHours(24))
            ->limit(10)
            ->get();

        foreach ($pendingRecent as $pendingTrx) {
            $throttleKey = 'doku_index_sync_' . $pendingTrx->id;
            if (!\Illuminate\Support\Facades\Cache::has($throttleKey)) {
                \Illuminate\Support\Facades\Cache::put($throttleKey, true, 2);
                $dokuService->syncTransactionWithDoku($pendingTrx, $webhookService);
            }
        }

        $query = Transaction::with(['customer', 'invoice'])->latest();

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
                    ->orWhere('doku_reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $transactions = $query->paginate(20)->withQueryString();
        $customers = Customer::select('id', 'name', 'company_name')->get();

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'customers' => $customers,
            'filters' => $request->only(['customer_id', 'status', 'search', 'date_from', 'date_to']),
        ]);
    }

    public function show(Transaction $transaction, DokuService $dokuService, CustomerWebhookService $webhookService): Response
    {
        // Jika transaksi masih PENDING, sinkronkan otomatis ke server DOKU live
        if ($transaction->status === 'PENDING') {
            $dokuService->syncTransactionWithDoku($transaction, $webhookService);
            $transaction->refresh();
        }

        $transaction->load(['customer', 'invoice', 'statusHistories', 'dokuTransaction']);

        return Inertia::render('Admin/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    public function syncStatus(Request $request, Transaction $transaction, DokuService $dokuService, CustomerWebhookService $webhookService)
    {
        $result = $dokuService->syncTransactionWithDoku($transaction, $webhookService);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['is_paid']) {
            return redirect()->back()->with('success', "Pembayaran BERHASIL diverifikasi DOKU! Ref: {$result['doku_reference']}. Status transaksi kini LUNAS.");
        }

        return redirect()->back()->with('info', $result['message']);
    }

    public function cancel(Request $request, Transaction $transaction): RedirectResponse
    {
        if (!in_array($transaction->status, ['CREATED', 'PENDING'])) {
            return redirect()->back()->with('error', "Transaksi tidak dapat dibatalkan dalam status {$transaction->status}");
        }

        $transaction->transitionTo('CANCELLED', 'manual_admin', 'Dibatalkan oleh Admin QRqu');

        AuditLog::record('ADMIN_CANCEL_TRANSACTION', $transaction, null, ['status' => 'CANCELLED']);

        return redirect()->back()->with('success', "Transaksi {$transaction->id} berhasil dibatalkan.");
    }

    public function simulate(Request $request, Transaction $transaction, \App\Services\Webhook\CustomerWebhookService $webhookService): RedirectResponse
    {
        if (in_array(strtoupper($transaction->status), ['PAID', 'SUCCESS'])) {
            return redirect()->back()->with('error', 'Transaksi ini sudah lunas sebelumnya.');
        }

        $invoice = $transaction->invoice;

        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $invoice) {
            $transaction->lockForUpdate();
            if ($invoice) {
                $invoice->lockForUpdate();
                $invoice->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                ]);
            }

            $mockRef = 'DOKU-SIM-' . time();
            $transaction->update([
                'doku_reference' => $mockRef,
                'payment_gateway_ref' => $mockRef,
            ]);

            $transaction->transitionTo('PAID', 'admin_simulation', 'Simulasi pelunasan transaksi oleh Master Admin');
        });

        if ($invoice) {
            \Illuminate\Support\Facades\Cache::put('payment_status_' . $invoice->id, 'PAID', 300);
        }

        // Tembak webhook otomatis ke endpoint merchant/Romei
        $webhookService->dispatchPaymentEvent($transaction->fresh(), 'payment.paid');

        AuditLog::record('ADMIN_SIMULATE_PAYMENT', $transaction, ['status' => 'PENDING'], ['status' => 'PAID']);

        return redirect()->back()->with('success', "Transaksi #{$transaction->id} berhasil ditandai LUNAS dan webhook telah dikirim ke merchant.");
    }
}
