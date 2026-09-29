<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $settings = [
            'app_name' => SystemSetting::get('app_name', config('app.name', 'QRqu')),
            'timezone' => SystemSetting::get('timezone', config('app.timezone', 'Asia/Jakarta')),
            'currency' => SystemSetting::get('currency', 'IDR'),
            'maintenance_mode' => SystemSetting::get('maintenance_mode', 'false') === 'true',
            'default_expire_minutes' => (int) SystemSetting::get('default_expire_minutes', 60),
            'api_timestamp_tolerance' => (int) SystemSetting::get('api_timestamp_tolerance', 300),
            'webhook_max_retries' => (int) SystemSetting::get('webhook_max_retries', 4),
            'monthly_price' => (float) SystemSetting::get('monthly_price', 150000),
            'monthly_quota' => (int) SystemSetting::get('monthly_quota', 1000),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'app_name' => 'required|string|max:100',
            'timezone' => 'required|string|max:50',
            'currency' => 'required|string|max:10',
            'maintenance_mode' => 'required|boolean',
            'default_expire_minutes' => 'required|integer|min:5|max:1440',
            'api_timestamp_tolerance' => 'required|integer|min:30|max:1800',
            'webhook_max_retries' => 'required|integer|min:1|max:10',
            'monthly_price' => 'required|numeric|min:0',
            'monthly_quota' => 'required|integer|min:1',
        ]);

        foreach ($request->only([
            'app_name', 'timezone', 'currency', 'default_expire_minutes',
            'api_timestamp_tolerance', 'webhook_max_retries',
            'monthly_price', 'monthly_quota'
        ]) as $key => $val) {
            SystemSetting::set($key, (string) $val);
        }

        SystemSetting::set('maintenance_mode', $request->boolean('maintenance_mode') ? 'true' : 'false');

        // Sinkronisasi ke starter plan bulanan agar paket otomatis mengikuti aturan admin
        $starterPlan = \App\Models\Plan::where('slug', 'monthly-30d')
            ->orWhere('slug', 'starter-30d')
            ->orWhere('duration_days', 30)
            ->first();

        if ($starterPlan) {
            $starterPlan->update([
                'price' => (float) $request->input('monthly_price'),
                'transaction_limit' => (int) $request->input('monthly_quota'),
            ]);
        }

        AuditLog::record('UPDATE_SYSTEM_SETTINGS', null, null, $request->all());

        return redirect()->back()->with('success', 'Pengaturan sistem, harga paket, dan kuota bulanan berhasil disimpan!');
    }
}