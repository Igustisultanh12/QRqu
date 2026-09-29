<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Payment\Doku\DokuService;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isAdmin() && !$user->customer) {
            $user->ensureCustomerProfile();
        }
        $customer = $user->fresh()->customer;

        $query = Transaction::where('customer_id', $customer?->id)
            ->with(['invoice', 'statusHistories'])
            ->latest();

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

        $transactions = $query->paginate(15)->withQueryString();

        return Inertia::render('Customer/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['status', 'search', 'date_from', 'date_to']),
        ]);
    }

    public function show(Request $request, Transaction $transaction, DokuService $dokuService, CustomerWebhookService $webhookService): Response
    {
        $user = $request->user();
        $customer = $user->customer;
        if (!$user->isAdmin() && (!$customer || $transaction->customer_id !== $customer->id)) {
            abort(403);
        }

        // Jika transaksi masih PENDING, sinkronkan otomatis ke server DOKU live
        if ($transaction->status === 'PENDING') {
            $dokuService->syncTransactionWithDoku($transaction, $webhookService);
            $transaction->refresh();
        }

        $transaction->load(['invoice', 'statusHistories', 'dokuTransaction']);

        return Inertia::render('Customer/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    public function syncStatus(Request $request, Transaction $transaction, DokuService $dokuService, CustomerWebhookService $webhookService)
    {
        $user = $request->user();
        $customer = $user->customer;
        if (!$user->isAdmin() && (!$customer || $transaction->customer_id !== $customer->id)) {
            abort(403);
        }

        $result = $dokuService->syncTransactionWithDoku($transaction, $webhookService);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['is_paid']) {
            return redirect()->back()->with('success', "Pembayaran BERHASIL diverifikasi DOKU! Ref: {$result['doku_reference']}. Status transaksi kini LUNAS.");
        }

        return redirect()->back()->with('info', $result['message']);
    }

    public function simulate(Request $request, Transaction $transaction, \App\Services\Webhook\CustomerWebhookService $webhookService): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customer;
        if (!$user->isAdmin() && (!$customer || $transaction->customer_id !== $customer->id)) {
            abort(403);
        }

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

            $transaction->transitionTo('PAID', 'simulation', 'Simulasi pembayaran lunas oleh merchant di dashboard QRqu');
        });

        if ($invoice) {
            \Illuminate\Support\Facades\Cache::put('payment_status_' . $invoice->id, 'PAID', 300);
        }

        // Tembak webhook otomatis ke endpoint Romei
        $webhookService->dispatchPaymentEvent($transaction->fresh(), 'payment.paid');

        return redirect()->back()->with('success', "Simulasi pembayaran BERHASIL! Transaksi #{$transaction->id} kini berstatus LUNAS dan webhook telah dikirim ke Romei.");
    }

    public function export(Request $request): StreamedResponse
    {
        $customer = $request->user()->customer;

        $query = Transaction::where('customer_id', $customer->id)->with('invoice')->latest();

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $fileName = 'transactions_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Transaction ID', 'Invoice ID', 'External ID', 'Amount (IDR)', 'Status', 'DOKU Ref', 'Created At', 'Paid At']);

            $query->chunk(200, function ($transactions) use ($handle) {
                foreach ($transactions as $trx) {
                    fputcsv($handle, [
                        $trx->id,
                        $trx->invoice_id,
                        $trx->external_id,
                        $trx->amount,
                        $trx->status,
                        $trx->doku_reference ?? '-',
                        $trx->created_at->format('Y-m-d H:i:s'),
                        $trx->invoice?->paid_at ? $trx->invoice->paid_at->format('Y-m-d H:i:s') : '-',
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
