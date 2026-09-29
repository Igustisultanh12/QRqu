<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
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

    public function show(Transaction $transaction): Response
    {
        $transaction->load(['customer', 'invoice', 'statusHistories', 'dokuTransaction']);

        return Inertia::render('Admin/Transactions/Show', [
            'transaction' => $transaction,
        ]);
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
}
