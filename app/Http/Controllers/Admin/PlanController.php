<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::withCount('subscriptions')->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'duration_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'transaction_limit' => 'required|integer|min:1',
            'api_limit' => 'required|integer|min:1',
            'rate_limit_rpm' => 'required|integer|min:1',
            'webhook_limit' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'status' => 'required|in:active,inactive',
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . $data['duration_days'] . 'd';
        $data['webhook_limit'] = !empty($data['webhook_limit']) ? (int) $data['webhook_limit'] : 1;

        $plan = Plan::create($data);
        AuditLog::record('CREATE_PLAN', $plan, null, $data);

        return redirect()->back()->with('success', "Paket {$plan->name} berhasil dibuat!");
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'duration_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'transaction_limit' => 'required|integer|min:1',
            'api_limit' => 'required|integer|min:1',
            'rate_limit_rpm' => 'required|integer|min:1',
            'webhook_limit' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'status' => 'required|in:active,inactive',
        ]);

        $data['webhook_limit'] = !empty($data['webhook_limit']) ? (int) $data['webhook_limit'] : ($plan->webhook_limit ?: 1);

        $before = $plan->toArray();
        $plan->update($data);
        AuditLog::record('UPDATE_PLAN', $plan, $before, $data);

        return redirect()->back()->with('success', "Paket {$plan->name} berhasil diperbarui!");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus paket yang masih memiliki pelanggan aktif.');
        }

        AuditLog::record('DELETE_PLAN', $plan, $plan->toArray(), null);
        $plan->delete();

        return redirect()->back()->with('success', 'Paket berhasil dihapus.');
    }
}
