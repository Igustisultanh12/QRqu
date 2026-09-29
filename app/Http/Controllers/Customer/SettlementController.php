<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $totalIncome = (float) $customer->transactions()->where('status', 'PAID')->sum('amount');
        $totalWithdrawn = (float) $customer->settlements()->where('status', 'selesai')->sum('amount');
        $pendingWithdrawn = (float) $customer->settlements()->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
        $balance = max(0, $totalIncome - ($totalWithdrawn + $pendingWithdrawn));

        $settlements = $customer->settlements()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customer/Settlements/Index', [
            'balance' => $balance,
            'total_income' => $totalIncome,
            'total_withdrawn' => $totalWithdrawn,
            'pending_withdrawn' => $pendingWithdrawn,
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

        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
        ], [
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.min' => 'Batas minimum penarikan dana adalah Rp 10.000.',
            'bank_name.required' => 'Nama bank atau e-wallet tujuan wajib dipilih / diisi.',
            'account_number.required' => 'Nomor rekening atau nomor e-wallet wajib diisi.',
            'account_name.required' => 'Nama pemilik rekening / e-wallet wajib diisi.',
        ]);

        // Calculate available balance
        $totalIncome = (float) $customer->transactions()->where('status', 'PAID')->sum('amount');
        $totalWithdrawn = (float) $customer->settlements()->where('status', 'selesai')->sum('amount');
        $pendingWithdrawn = (float) $customer->settlements()->whereIn('status', ['verifikasi', 'proses'])->sum('amount');
        $availableBalance = max(0, $totalIncome - ($totalWithdrawn + $pendingWithdrawn));

        if ((float) $request->amount > $availableBalance) {
            throw ValidationException::withMessages([
                'amount' => 'Saldo Anda tidak mencukupi untuk penarikan ini. Saldo tersedia saat ini: Rp ' . number_format($availableBalance, 0, ',', '.'),
            ]);
        }

        $settlement = Settlement::create([
            'settlement_number' => Settlement::generateNumber(),
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount' => $request->amount,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'status' => 'verifikasi',
        ]);

        return redirect()->back()->with('success', 'Pengajuan penarikan dana sebesar Rp ' . number_format($settlement->amount, 0, ',', '.') . ' (' . $settlement->settlement_number . ') berhasil diajukan dengan status: Verifikasi.');
    }
}
