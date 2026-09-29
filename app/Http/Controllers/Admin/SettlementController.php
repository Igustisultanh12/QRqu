<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Settlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettlementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Settlement::with(['customer', 'user'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', strtolower($request->status));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('settlement_number', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%")
                    ->orWhere('bank_name', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $settlements = $query->paginate(15)->withQueryString();

        // Stats summary
        $stats = [
            'total_count' => Settlement::count(),
            'total_amount' => (float) Settlement::where('status', 'selesai')->sum('amount'),
            'verifikasi_count' => Settlement::where('status', 'verifikasi')->count(),
            'verifikasi_amount' => (float) Settlement::where('status', 'verifikasi')->sum('amount'),
            'proses_count' => Settlement::where('status', 'proses')->count(),
            'proses_amount' => (float) Settlement::where('status', 'proses')->sum('amount'),
            'selesai_count' => Settlement::where('status', 'selesai')->count(),
            'selesai_amount' => (float) Settlement::where('status', 'selesai')->sum('amount'),
            'ditolak_count' => Settlement::where('status', 'ditolak')->count(),
        ];

        return Inertia::render('Admin/Settlements/Index', [
            'settlements' => $settlements,
            'stats' => $stats,
            'filters' => [
                'status' => $request->status ?? 'all',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    public function updateStatus(Request $request, Settlement $settlement): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:verifikasi,proses,selesai,ditolak',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $settlement->status;
        $newStatus = strtolower($request->status);

        $updates = [
            'status' => $newStatus,
            'admin_notes' => $request->admin_notes,
        ];

        if ($newStatus === 'proses' && !$settlement->processed_at) {
            $updates['processed_at'] = now();
        }

        if ($newStatus === 'selesai' && !$settlement->completed_at) {
            $updates['completed_at'] = now();
            if (!$settlement->processed_at) {
                $updates['processed_at'] = now();
            }
        }

        $settlement->update($updates);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'settlement.status_updated',
            'model_type' => Settlement::class,
            'model_id' => (string) $settlement->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus, 'admin_notes' => $request->admin_notes],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $statusLabels = [
            'verifikasi' => 'Verifikasi',
            'proses' => 'Proses Transfer',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];

        return redirect()->back()->with('success', "Status penarikan {$settlement->settlement_number} berhasil diubah ke: {$statusLabels[$newStatus]}.");
    }
}
