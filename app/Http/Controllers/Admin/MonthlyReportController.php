<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SystemSetting;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyReportController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $customerId = $request->input('customer_id');
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $month = max(1, min(12, $month));

        // Pengaturan Sistem untuk Harga & Kuota Bulanan
        $globalMonthlyPrice = (float) SystemSetting::get('monthly_price', 150000);
        $globalMonthlyQuota = (int) SystemSetting::get('monthly_quota', 1000);

        // Merchant dropdown options
        $merchants = Customer::select('id', 'name', 'company_name')->orderBy('name')->get();

        // Base Monthly Query (Hanya transaksi API pelanggan merchant, abaikan SUB-)
        $baseQuery = Transaction::where('external_id', 'not like', 'SUB-%')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        $selectedMerchant = null;
        if (!empty($customerId) && $customerId !== 'all') {
            $baseQuery->where('customer_id', $customerId);
            $selectedMerchant = Customer::with('activeSubscription.plan')->find($customerId);
        }

        // Hitung kuota
        if ($selectedMerchant) {
            $plan = $selectedMerchant->activeSubscription?->plan;
            $quotaLimit = $plan?->transaction_limit ?? $globalMonthlyQuota;
            $quotaPrice = $plan?->price ?? $globalMonthlyPrice;
            $quotaUsed = (clone $baseQuery)->count();
            $quotaRemaining = max(0, $quotaLimit - $quotaUsed);
            $quotaPercentage = min(100, round(($quotaUsed / max(1, $quotaLimit)) * 100, 1));
        } else {
            $quotaLimit = $globalMonthlyQuota * max(1, $merchants->count());
            $quotaPrice = $globalMonthlyPrice;
            $quotaUsed = (clone $baseQuery)->count();
            $quotaRemaining = max(0, $quotaLimit - $quotaUsed);
            $quotaPercentage = min(100, round(($quotaUsed / max(1, $quotaLimit)) * 100, 1));
        }

        // Summary Statistics
        $successfulQuery = (clone $baseQuery)->where('status', 'PAID');
        $successfulCount = $successfulQuery->count();
        $successfulAmount = (float) $successfulQuery->sum('amount');

        $failedQuery = (clone $baseQuery)->whereIn('status', ['FAILED', 'CANCELLED']);
        $failedCount = $failedQuery->count();
        $failedAmount = (float) $failedQuery->sum('amount');

        $expiredQuery = (clone $baseQuery)->where('status', 'EXPIRED');
        $expiredCount = $expiredQuery->count();
        $expiredAmount = (float) $expiredQuery->sum('amount');

        $pendingQuery = (clone $baseQuery)->whereIn('status', ['PENDING', 'CREATED']);
        $pendingCount = $pendingQuery->count();
        $pendingAmount = (float) $pendingQuery->sum('amount');

        $totalCount = $quotaUsed;
        $totalAmount = (float) (clone $baseQuery)->sum('amount');
        $successRate = $totalCount > 0 ? round(($successfulCount / $totalCount) * 100, 1) : 0;

        // Query Detail Transaksi
        $query = (clone $baseQuery)->with(['customer', 'invoice'])->latest();

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', strtoupper($status));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('invoice_id', 'like', "%{$search}%")
                    ->orWhere('doku_reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('description', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(20)->withQueryString()->through(function ($trx) {
            return [
                'id' => $trx->id,
                'invoice_id' => $trx->invoice_id,
                'external_id' => $trx->external_id,
                'nama_transaksi' => $trx->invoice?->description ?: ($trx->external_id ?: 'Transaksi QRIS'),
                'merchant_name' => $trx->customer?->name ?: ($trx->customer?->company_name ?: 'Merchant'),
                'customer_name' => $trx->invoice?->customer_name ?: 'Pelanggan',
                'amount' => (float) $trx->amount,
                'fee' => (float) $trx->fee,
                'net_amount' => (float) $trx->net_amount,
                'status' => $trx->status,
                'payment_method' => $trx->payment_method ?? 'QRIS',
                'doku_reference' => $trx->doku_reference ?: '-',
                'created_at' => $trx->created_at->format('d M Y H:i'),
                'paid_at' => $trx->invoice?->paid_at ? $trx->invoice->paid_at->format('d M Y H:i') : null,
            ];
        });

        $periodDate = Carbon::createFromDate($year, $month, 1);
        $periodLabel = $periodDate->translatedFormat('F Y');

        return Inertia::render('Admin/Reports/Monthly', [
            'period' => [
                'year' => $year,
                'month' => $month,
                'label' => $periodLabel,
            ],
            'quota' => [
                'is_single_merchant' => (bool) $selectedMerchant,
                'merchant_name' => $selectedMerchant?->name ?? 'Semua Merchant Platform',
                'monthly_price' => $quotaPrice,
                'limit' => $quotaLimit,
                'used' => $quotaUsed,
                'remaining' => $quotaRemaining,
                'percentage' => $quotaPercentage,
            ],
            'summary' => [
                'total_count' => $totalCount,
                'total_amount' => $totalAmount,
                'successful_count' => $successfulCount,
                'successful_amount' => $successfulAmount,
                'failed_count' => $failedCount,
                'failed_amount' => $failedAmount,
                'expired_count' => $expiredCount,
                'expired_amount' => $expiredAmount,
                'pending_count' => $pendingCount,
                'pending_amount' => $pendingAmount,
                'success_rate' => $successRate,
            ],
            'merchants' => $merchants,
            'transactions' => $transactions,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'customer_id' => $customerId ?: 'all',
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $customerId = $request->input('customer_id');
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $month = max(1, min(12, $month));

        $query = Transaction::where('external_id', 'not like', 'SUB-%')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with(['customer', 'invoice'])
            ->latest();

        if (!empty($customerId) && $customerId !== 'all') {
            $query->where('customer_id', $customerId);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', strtoupper($status));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('invoice_id', 'like', "%{$search}%")
                    ->orWhere('doku_reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $filename = "laporan-bulanan-admin-{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".csv";

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No',
                'Tanggal & Waktu',
                'Nama Merchant',
                'No Invoice',
                'ID Transaksi',
                'External ID',
                'Nama Transaksi',
                'Nama Pelanggan',
                'Nominal (IDR)',
                'Biaya Fee (IDR)',
                'Nominal Bersih (IDR)',
                'Status',
                'Metode Pembayaran',
                'Referensi DOKU',
                'Waktu Lunas',
            ]);

            $no = 1;
            $query->chunk(500, function ($transactions) use ($handle, &$no) {
                foreach ($transactions as $trx) {
                    fputcsv($handle, [
                        $no++,
                        $trx->created_at->format('Y-m-d H:i:s'),
                        $trx->customer?->name ?: '-',
                        $trx->invoice_id,
                        $trx->id,
                        $trx->external_id,
                        $trx->invoice?->description ?: ($trx->external_id ?: 'Transaksi QRIS'),
                        $trx->invoice?->customer_name ?: '-',
                        (float) $trx->amount,
                        (float) $trx->fee,
                        (float) $trx->net_amount,
                        $trx->status,
                        $trx->payment_method ?? 'QRIS',
                        $trx->doku_reference ?: '-',
                        $trx->invoice?->paid_at ? $trx->invoice->paid_at->format('Y-m-d H:i:s') : '-',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Cetak PDF Buku Tabungan & Mutasi Rekening Transaksi API (Admin Portal)
     */
    public function exportPdf(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $customerId = $request->input('customer_id');
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $month = max(1, min(12, $month));

        // Base Query (Hanya transaksi API, abaikan SUB-)
        $baseQuery = Transaction::where('external_id', 'not like', 'SUB-%')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        $selectedMerchant = null;
        if (!empty($customerId) && $customerId !== 'all') {
            $baseQuery->where('customer_id', $customerId);
            $selectedMerchant = Customer::find($customerId);
        }

        $successfulQuery = (clone $baseQuery)->where('status', 'PAID');
        $successfulCount = $successfulQuery->count();
        $successfulAmount = (float) $successfulQuery->sum('amount');
        $totalFee = (float) $successfulQuery->sum('fee');
        $netAmount = (float) $successfulQuery->sum('net_amount');

        $totalCount = (clone $baseQuery)->count();
        $successRate = $totalCount > 0 ? round(($successfulCount / $totalCount) * 100, 1) : 0;

        // Kronologis dari awal bulan
        $query = (clone $baseQuery)->with(['customer', 'invoice'])->orderBy('created_at', 'asc')->orderBy('id', 'asc');

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', strtoupper($status));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('invoice_id', 'like', "%{$search}%")
                    ->orWhere('doku_reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $rawTransactions = $query->get();

        $runningBalance = 0;
        $transactions = [];

        foreach ($rawTransactions as $trx) {
            if ($trx->status === 'PAID') {
                $runningBalance += (float) ($trx->net_amount > 0 ? $trx->net_amount : ($trx->amount - $trx->fee));
            }

            $desc = $trx->invoice?->description ?: ($trx->external_id ?: 'Transaksi Pembayaran QRIS');
            if (!$selectedMerchant && $trx->customer) {
                $desc .= ' [' . $trx->customer->name . ']';
            }

            $transactions[] = [
                'id' => $trx->id,
                'invoice_id' => $trx->invoice_id,
                'external_id' => $trx->external_id,
                'created_at_formatted' => $trx->created_at->format('d/m/Y H:i'),
                'description' => $desc,
                'customer_name' => $trx->invoice?->customer_name ?: ($trx->customer?->name ?: '-'),
                'amount' => (float) $trx->amount,
                'fee' => (float) $trx->fee,
                'net_amount' => (float) $trx->net_amount,
                'running_balance' => $runningBalance,
                'status' => $trx->status,
                'doku_reference' => $trx->doku_reference,
            ];
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $monthName = ($monthNames[$month] ?? 'Bulan ' . $month) . ' ' . $year;

        $docId = 'PB-ADM-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . ($selectedMerchant ? str_pad($selectedMerchant->id, 5, '0', STR_PAD_LEFT) : 'ALL');
        $printedAt = now()->format('d/m/Y H:i:s') . ' WIB';

        $customerData = $selectedMerchant ?: (object) [
            'id' => 0,
            'name' => 'Semua Merchant (Konsolidasi)',
            'company_name' => 'Platform QRqu Gateway',
        ];

        $data = [
            'customer' => $customerData,
            'transactions' => $transactions,
            'year' => $year,
            'month' => $month,
            'monthName' => $monthName,
            'successfulCount' => $successfulCount,
            'successfulAmount' => $successfulAmount,
            'totalFee' => $totalFee,
            'netAmount' => $netAmount,
            'totalCount' => $totalCount,
            'successRate' => $successRate,
            'docId' => $docId,
            'printedAt' => $printedAt,
        ];

        $pdf = Pdf::loadView('pdf.passbook_statement', $data)
            ->setPaper('a4', 'landscape');

        $prefix = $selectedMerchant ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $selectedMerchant->name) : 'semua-merchant';
        $filename = "buku-tabungan-admin-{$prefix}-{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".pdf";

        return $pdf->download($filename);
    }
}
