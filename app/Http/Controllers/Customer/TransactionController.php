<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;

        $query = Transaction::where('customer_id', $customer->id)
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

    public function show(Request $request, Transaction $transaction): Response
    {
        $customer = $request->user()->customer;
        if ($transaction->customer_id !== $customer->id) {
            abort(403);
        }

        $transaction->load(['invoice', 'statusHistories', 'dokuTransaction']);

        return Inertia::render('Customer/Transactions/Show', [
            'transaction' => $transaction,
        ]);
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
