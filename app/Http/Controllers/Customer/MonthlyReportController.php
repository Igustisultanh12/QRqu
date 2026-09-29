<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
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
        $customer = $request->user()->customer;
        if (!$customer) {
            abort(403, 'Profil merchant tidak ditemukan.');
        }

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        // Batasi rentang bulan 1-12
        $month = max(1, min(12, $month));

        // Subscription & Quota Bulanan
        $subscription = $customer->activeSubscription;
        $plan = $subscription?->plan;

        $quotaLimit = $plan?->transaction_limit ?? (int) SystemSetting::get('monthly_quota', 1000);
        $monthlyPrice = $plan?->price ?? (float) SystemSetting::get('monthly_price', 150000);

        // Kuota Terpakai di bulan & tahun terpilih (Hanya Transaksi API Pelanggan, tanpa transaksi langganan SUB-)
        $baseMonthlyQuery = Transaction::where('customer_id', $customer->id)
            ->where('external_id', 'not like', 'SUB-%')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        $quotaUsed = (clone $baseMonthlyQuery)->count();
        $quotaRemaining = max(0, $quotaLimit - $quotaUsed);
        $quotaPercentage = min(100, round(($quotaUsed / max(1, $quotaLimit)) * 100, 1));

        // Ringkasan Transaksi Berhasil vs Gagal
        $successfulQuery = (clone $baseMonthlyQuery)->where('status', 'PAID');
        $successfulCount = $successfulQuery->count();
        $successfulAmount = (float) $successfulQuery->sum('amount');

        $failedQuery = (clone $baseMonthlyQuery)->whereIn('status', ['FAILED', 'CANCELLED']);
        $failedCount = $failedQuery->count();
        $failedAmount = (float) $failedQuery->sum('amount');

        $expiredQuery = (clone $baseMonthlyQuery)->where('status', 'EXPIRED');
        $expiredCount = $expiredQuery->count();
        $expiredAmount = (float) $expiredQuery->sum('amount');

        $pendingQuery = (clone $baseMonthlyQuery)->whereIn('status', ['PENDING', 'CREATED']);
        $pendingCount = $pendingQuery->count();
        $pendingAmount = (float) $pendingQuery->sum('amount');

        $totalCount = $quotaUsed;
        $totalAmount = (float) (clone $baseMonthlyQuery)->sum('amount');
        $successRate = $totalCount > 0 ? round(($successfulCount / $totalCount) * 100, 1) : 0;

        // Query Daftar Transaksi dengan Filter
        $query = (clone $baseMonthlyQuery)->with('invoice')->latest();

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
                    });
            });
        }

        $transactions = $query->paginate(20)->withQueryString()->through(function ($trx) {
            return [
                'id' => $trx->id,
                'invoice_id' => $trx->invoice_id,
                'external_id' => $trx->external_id,
                'nama_transaksi' => $trx->invoice?->description ?: ($trx->external_id ?: 'Transaksi QRIS'),
                'customer_name' => $trx->invoice?->customer_name ?: 'Pelanggan',
                'customer_email' => $trx->invoice?->customer_email,
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

        // Generate Periode String
        $periodDate = Carbon::createFromDate($year, $month, 1);
        $periodLabel = $periodDate->translatedFormat('F Y');

        return Inertia::render('Customer/Reports/Monthly', [
            'period' => [
                'year' => $year,
                'month' => $month,
                'label' => $periodLabel,
            ],
            'quota' => [
                'plan_name' => $plan?->name ?? 'Paket Standard Bulanan',
                'monthly_price' => $monthlyPrice,
                'limit' => $quotaLimit,
                'used' => $quotaUsed,
                'remaining' => $quotaRemaining,
                'percentage' => $quotaPercentage,
                'expires_at' => $subscription?->expires_at?->format('d M Y') ?? 'Berjalan',
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
            'transactions' => $transactions,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            abort(403, 'Profil merchant tidak ditemukan.');
        }

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $month = max(1, min(12, $month));

        $query = Transaction::where('customer_id', $customer->id)
            ->where('external_id', 'not like', 'SUB-%')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with('invoice')
            ->latest();

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
                            ->orWhere('customer_name', 'like', "%{$search}%");
                    });
            });
        }

        $filename = "laporan-bulanan-{$customer->name}-{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".csv";

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Tambahkan BOM untuk UTF-8 Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header Kolom
            fputcsv($handle, [
                'No',
                'Tanggal & Waktu',
                'No Invoice',
                'ID Transaksi',
                'External ID',
                'Nama Transaksi',
                'Nama Pelanggan',
                'Email Pelanggan',
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
                        $trx->invoice_id,
                        $trx->id,
                        $trx->external_id,
                        $trx->invoice?->description ?: ($trx->external_id ?: 'Transaksi QRIS'),
                        $trx->invoice?->customer_name ?: '-',
                        $trx->invoice?->customer_email ?: '-',
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
     * Cetak PDF Buku Tabungan & Mutasi Rekening Transaksi API
     */
    public function exportPdf(Request $request)
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            abort(403, 'Profil merchant tidak ditemukan.');
        }

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $month = max(1, min(12, $month));

        // Hanya transaksi API pelanggan, abaikan transaksi subs
        $baseMonthlyQuery = Transaction::where('customer_id', $customer->id)
            ->where('external_id', 'not like', 'SUB-%')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        $successfulQuery = (clone $baseMonthlyQuery)->where('status', 'PAID');
        $successfulCount = $successfulQuery->count();
        $successfulAmount = (float) $successfulQuery->sum('amount');
        $totalFee = (float) $successfulQuery->sum('fee');
        $netAmount = (float) $successfulQuery->sum('net_amount');

        $totalCount = (clone $baseMonthlyQuery)->count();
        $successRate = $totalCount > 0 ? round(($successfulCount / $totalCount) * 100, 1) : 0;

        // Ambil transaksi secara kronologis (dari awal bulan) sesuai format buku tabungan bank
        $query = (clone $baseMonthlyQuery)->with('invoice')->orderBy('created_at', 'asc')->orderBy('id', 'asc');

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
                            ->orWhere('customer_name', 'like', "%{$search}%");
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

            $transactions[] = [
                'id' => $trx->id,
                'invoice_id' => $trx->invoice_id,
                'external_id' => $trx->external_id,
                'created_at_formatted' => $trx->created_at->format('d/m/Y H:i'),
                'description' => $trx->invoice?->description ?: ($trx->external_id ?: 'Transaksi Pembayaran QRIS'),
                'customer_name' => $trx->invoice?->customer_name ?: '-',
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

        $docId = 'PB-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT);
        $printedAt = now()->format('d/m/Y H:i:s') . ' WIB';

        $data = [
            'customer' => $customer,
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

        $cleanCustomerName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $customer->name);
        $filename = "buku-tabungan-{$cleanCustomerName}-{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".pdf";

        return $pdf->download($filename);
    }
}
