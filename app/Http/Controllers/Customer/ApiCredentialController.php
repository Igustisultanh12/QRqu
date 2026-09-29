<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApiCredentialController extends Controller
{
    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;
        $credentials = ApiCredential::where('customer_id', $customer->id)
            ->latest()
            ->get();

        return Inertia::render('Customer/ApiCredentials/Index', [
            'credentials' => $credentials,
            'flash_secret' => session('new_secret'),
            'flash_key' => session('new_key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'environment' => 'required|in:production,sandbox',
            'ip_whitelist' => 'nullable|string',
        ]);

        $customer = $request->user()->customer;

        $ipWhitelist = null;
        if ($request->filled('ip_whitelist')) {
            $ipWhitelist = array_filter(array_map('trim', explode("\n", $request->input('ip_whitelist'))));
        }

        $env = $request->input('environment');
        $prefix = ($env === 'sandbox') ? 'qrqu_sand_' : 'qrqu_live_';
        $apiKey = $prefix . Str::random(32);
        $plainSecret = 'sec_' . Str::random(48);

        $credential = ApiCredential::create([
            'customer_id' => $customer->id,
            'name' => $request->input('name'),
            'environment' => $env,
            'api_key' => $apiKey,
            'api_secret_hash' => Hash::make($plainSecret),
            'api_secret_encrypted' => Crypt::encryptString($plainSecret),
            'ip_whitelist' => $ipWhitelist,
            'status' => 'active',
        ]);

        AuditLog::record('CREATE_API_CREDENTIAL', $credential, null, [
            'environment' => $env,
            'name' => $credential->name,
        ]);

        return redirect()->back()
            ->with('new_key', $apiKey)
            ->with('new_secret', $plainSecret)
            ->with('success', 'API Credential berhasil dibuat! Harap simpan API Secret Anda sekarang, karena tidak akan ditampilkan lagi.');
    }

    public function revoke(Request $request, ApiCredential $credential): RedirectResponse
    {
        $customer = $request->user()->customer;
        if ($credential->customer_id !== $customer->id) {
            abort(403);
        }

        $credential->update(['status' => 'revoked']);

        AuditLog::record('REVOKE_API_CREDENTIAL', $credential, ['status' => 'active'], ['status' => 'revoked']);

        return redirect()->back()->with('success', 'API Credential telah dinonaktifkan (revoked).');
    }

    public function updateIpWhitelist(Request $request, ApiCredential $credential): RedirectResponse
    {
        $customer = $request->user()->customer;
        if ($credential->customer_id !== $customer->id) {
            abort(403);
        }

        $request->validate([
            'ip_whitelist' => 'nullable|string',
        ]);

        $ipWhitelist = null;
        if ($request->filled('ip_whitelist')) {
            $ipWhitelist = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r|,/", $request->input('ip_whitelist')))));
        }

        $credential->update(['ip_whitelist' => $ipWhitelist]);

        return redirect()->back()->with('success', 'IP Whitelist berhasil diperbarui.');
    }
}
