<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SettlementController extends Controller
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

        $customer->ensureStores();
        $stores = $customer->stores()->orderBy('is_default', 'desc')->orderBy('name')->get();

        $dokuFeeEnabled = SystemSetting::get('doku_settlement_fee_enabled', 'true') === 'true';
        $dokuFeePercent = (float) SystemSetting::get('doku_settlement_fee_percent', 0.7);

        // Hitung statistik saldo per toko
        $storeStats = [];
        foreach ($stores as $st) {
            $stGross = (float) $customer->transactions()->where('store_id', $st->id)->where('status', 'PAID')->sum('amount');
            $stFee = $dokuFeeEnabled ? round($stGross * ($dokuFeePercent / 100), 2) : 0;
            $stNet = max(0, $stGross - $stFee);
            $stWithdrawn = (float) $customer->settlements()->where('store_id', $st->id)->where('status', 'selesai')->sum('amount');
            $stPending = (float) $customer->settlements()->where('store_id', $st->id)->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
            $stBalance = max(0, $stNet - ($stWithdrawn + $stPending));

            $storeStats[] = [
                'id' => $st->id,
                'name' => $st->name,
                'code' => $st->code,
                'is_default' => $st->is_default,
                'gross_income' => $stGross,
                'doku_fee' => $stFee,
                'net_income' => $stNet,
                'withdrawn' => $stWithdrawn,
                'pending' => $stPending,
                'balance' => $stBalance,
            ];
        }

        // Akumulasi keseluruhan toko pelanggan
        $totalGross = (float) $customer->transactions()->where('status', 'PAID')->sum('amount');
        $totalFee = $dokuFeeEnabled ? round($totalGross * ($dokuFeePercent / 100), 2) : 0;
        $totalNet = max(0, $totalGross - $totalFee);
        $totalWithdrawn = (float) $customer->settlements()->where('status', 'selesai')->sum('amount');
        $pendingWithdrawn = (float) $customer->settlements()->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
        $totalBalance = max(0, $totalNet - ($totalWithdrawn + $pendingWithdrawn));

        // Filter toko terpilih
        $selectedStoreId = $request->input('store_id', 'all');
        $selectedStore = null;

        if ($selectedStoreId !== 'all' && !empty($selectedStoreId)) {
            $selectedStore = $stores->firstWhere('id', (int) $selectedStoreId);
        }

        if ($selectedStore) {
            $stStat = collect($storeStats)->firstWhere('id', $selectedStore->id);
            $activeBalance = $stStat ? $stStat['balance'] : 0;
            $activeIncome = $stStat ? $stStat['gross_income'] : 0;
            $activeFee = $stStat ? $stStat['doku_fee'] : 0;
            $activeNet = $stStat ? $stStat['net_income'] : 0;
            $activeWithdrawn = $stStat ? $stStat['withdrawn'] : 0;
            $activePending = $stStat ? $stStat['pending'] : 0;

            $settlementsQuery = $customer->settlements()->where('store_id', $selectedStore->id);
        } else {
            $activeBalance = $totalBalance;
            $activeIncome = $totalGross;
            $activeFee = $totalFee;
            $activeNet = $totalNet;
            $activeWithdrawn = $totalWithdrawn;
            $activePending = $pendingWithdrawn;

            $settlementsQuery = $customer->settlements();
        }

        $settlements = $settlementsQuery
            ->with('store')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customer/Settlements/Index', [
            'stores' => $storeStats,
            'selected_store_id' => $selectedStoreId,
            'selected_store' => $selectedStore,
            'doku_fee_enabled' => $dokuFeeEnabled,
            'doku_fee_percent' => $dokuFeePercent,
            'doku_fee_amount' => $activeFee,
            'balance' => $activeBalance,
            'total_income' => $activeIncome,
            'total_net' => $activeNet,
            'total_withdrawn' => $activeWithdrawn,
            'pending_withdrawn' => $activePending,
            'settlements' => $settlements,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdmin() && !$user->customer) {
            $user->ensureCustomerProfile();
        }
        $customer = $user->fresh()->customer;

        if (!$customer) {
            return redirect()->back()->withErrors(['customer' => 'Profil merchant belum siap.']);
        }

        $customer->ensureStores();

        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'store_id' => 'nullable',
        ], [
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.min' => 'Batas minimum penarikan dana adalah Rp 10.000.',
            'bank_name.required' => 'Nama bank atau e-wallet tujuan wajib dipilih / diisi.',
            'account_number.required' => 'Nomor rekening atau nomor e-wallet wajib diisi.',
            'account_name.required' => 'Nama pemilik rekening / e-wallet wajib diisi.',
        ]);

        $dokuFeeEnabled = SystemSetting::get('doku_settlement_fee_enabled', 'true') === 'true';
        $dokuFeePercent = (float) SystemSetting::get('doku_settlement_fee_percent', 0.7);

        $storeId = $request->input('store_id');
        $store = null;
        if (!empty($storeId) && $storeId !== 'all') {
            $store = $customer->stores()->find($storeId);
        }

        // Calculate available balance for this store or total
        if ($store) {
            $income = (float) $customer->transactions()->where('store_id', $store->id)->where('status', 'PAID')->sum('amount');
            $fee = $dokuFeeEnabled ? round($income * ($dokuFeePercent / 100), 2) : 0;
            $net = max(0, $income - $fee);
            $withdrawn = (float) $customer->settlements()->where('store_id', $store->id)->where('status', 'selesai')->sum('amount');
            $pending = (float) $customer->settlements()->where('store_id', $store->id)->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
            $availableBalance = max(0, $net - ($withdrawn + $pending));
        } else {
            $income = (float) $customer->transactions()->where('status', 'PAID')->sum('amount');
            $fee = $dokuFeeEnabled ? round($income * ($dokuFeePercent / 100), 2) : 0;
            $net = max(0, $income - $fee);
            $withdrawn = (float) $customer->settlements()->where('status', 'selesai')->sum('amount');
            $pending = (float) $customer->settlements()->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
            $availableBalance = max(0, $net - ($withdrawn + $pending));
        }

        if ((float) $request->amount > $availableBalance) {
            throw ValidationException::withMessages([
                'amount' => 'Saldo Anda tidak mencukupi untuk penarikan ini. Saldo tersedia saat ini: Rp ' . number_format($availableBalance, 0, ',', '.'),
            ]);
        }

        $settlement = Settlement::create([
            'settlement_number' => Settlement::generateNumber(),
            'customer_id' => $customer->id,
            'store_id' => $store ? $store->id : null,
            'user_id' => $user->id,
            'amount' => $request->amount,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'status' => 'verifikasi',
        ]);

        $storeInfo = $store ? " untuk toko \"{$store->name}\"" : "";
        return redirect()->back()->with('success', "Pengajuan penarikan dana{$storeInfo} sebesar Rp " . number_format($settlement->amount, 0, ',', '.') . " ({$settlement->settlement_number}) berhasil diajukan dengan status: Verifikasi.");
    }

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin() && !$user->customer) {
            $user->ensureCustomerProfile();
        }
        $customer = $user->fresh()->customer;

        if (!$customer) {
            abort(403, 'Profil merchant tidak ditemukan.');
        }

        $customer->ensureStores();
        $stores = $customer->stores()->orderBy('is_default', 'desc')->orderBy('name')->get();

        $dokuFeeEnabled = SystemSetting::get('doku_settlement_fee_enabled', 'true') === 'true';
        $dokuFeePercent = (float) SystemSetting::get('doku_settlement_fee_percent', 0.7);

        // Hitung statistik saldo per toko
        $storeStats = [];
        foreach ($stores as $st) {
            $stGross = (float) $customer->transactions()->where('store_id', $st->id)->where('status', 'PAID')->sum('amount');
            $stFee = $dokuFeeEnabled ? round($stGross * ($dokuFeePercent / 100), 2) : 0;
            $stNet = max(0, $stGross - $stFee);
            $stWithdrawn = (float) $customer->settlements()->where('store_id', $st->id)->where('status', 'selesai')->sum('amount');
            $stPending = (float) $customer->settlements()->where('store_id', $st->id)->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
            $stBalance = max(0, $stNet - ($stWithdrawn + $stPending));

            $storeStats[] = [
                'id' => $st->id,
                'name' => $st->name,
                'code' => $st->code,
                'is_default' => $st->is_default,
                'gross_income' => $stGross,
                'doku_fee' => $stFee,
                'net_income' => $stNet,
                'withdrawn' => $stWithdrawn,
                'pending' => $stPending,
                'balance' => $stBalance,
            ];
        }

        $totalGross = (float) $customer->transactions()->where('status', 'PAID')->sum('amount');
        $totalFee = $dokuFeeEnabled ? round($totalGross * ($dokuFeePercent / 100), 2) : 0;
        $totalNet = max(0, $totalGross - $totalFee);
        $totalWithdrawn = (float) $customer->settlements()->where('status', 'selesai')->sum('amount');
        $pendingWithdrawn = (float) $customer->settlements()->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
        $totalBalance = max(0, $totalNet - ($totalWithdrawn + $pendingWithdrawn));

        $storeId = $request->input('store_id', 'all');
        $selectedStore = null;

        if ($storeId !== 'all' && !empty($storeId)) {
            $selectedStore = $stores->firstWhere('id', (int) $storeId);
        }

        if ($selectedStore) {
            $stStat = collect($storeStats)->firstWhere('id', $selectedStore->id);
            $activeBalance = $stStat ? $stStat['balance'] : 0;
            $activeIncome = $stStat ? $stStat['gross_income'] : 0;
            $activeFee = $stStat ? $stStat['doku_fee'] : 0;
            $activeNet = $stStat ? $stStat['net_income'] : 0;
            $activeWithdrawn = $stStat ? $stStat['withdrawn'] : 0;

            $settlements = $customer->settlements()->where('store_id', $selectedStore->id)->latest()->get();
        } else {
            $activeBalance = $totalBalance;
            $activeIncome = $totalGross;
            $activeFee = $totalFee;
            $activeNet = $totalNet;
            $activeWithdrawn = $totalWithdrawn;

            $settlements = $customer->settlements()->latest()->get();
        }

        $docId = 'STL-' . now()->format('Ymd') . '-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT);
        $printedAt = now()->format('d/m/Y H:i:s') . ' WIB';

        $data = [
            'customer' => $customer,
            'selectedStore' => $selectedStore,
            'stores' => $stores,
            'storeStats' => $storeStats,
            'dokuFeeEnabled' => $dokuFeeEnabled,
            'dokuFeePercent' => $dokuFeePercent,
            'dokuFeeAmount' => $activeFee,
            'totalIncome' => $activeIncome,
            'totalNet' => $activeNet,
            'totalWithdrawn' => $activeWithdrawn,
            'balance' => $activeBalance,
            'settlements' => $settlements,
            'docId' => $docId,
            'printedAt' => $printedAt,
        ];

        $fontDir = storage_path('fonts');
        if (!is_dir($fontDir)) {
            @mkdir($fontDir, 0775, true);
        }

        $cleanCustomerName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $customer->name);
        $storeSlug = $selectedStore ? Str::slug($selectedStore->name) . '-' : '';
        $filename = "laporan-saldo-{$storeSlug}{$cleanCustomerName}-" . now()->format('Ymd') . ".pdf";

        try {
            $pdf = Pdf::loadView('pdf.settlement_statement', $data)
                ->setPaper('a4', 'portrait');

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Settlement PDF Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Gagal membuat dokumen PDF Laporan Saldo: ' . $e->getMessage());
        }
    }
}
