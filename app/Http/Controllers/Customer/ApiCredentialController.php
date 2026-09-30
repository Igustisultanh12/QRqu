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
        $user = $request->user();
        if ($user->isAdmin() && !$user->customer) {
            $user->ensureCustomerProfile();
        }
        $customer = $user->fresh()->customer;
        if ($customer) {
            $customer->ensureStores();
        }

        $credentials = $customer ? ApiCredential::where('customer_id', $customer->id)
            ->with('store')
            ->latest()
            ->get() : collect();

        $stores = $customer ? $customer->stores()->orderBy('is_default', 'desc')->orderBy('name')->get() : collect();

        return Inertia::render('Customer/ApiCredentials/Index', [
            'credentials' => $credentials,
            'stores' => $stores,
            'flash_secret' => session('new_secret'),
            'flash_key' => session('new_key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:50',
            'environment' => 'required|in:production,sandbox',
            'store_id' => ['nullable', \Illuminate\Validation\Rule::exists('stores', 'id')->where('customer_id', $customer->id)],
            'ip_whitelist' => 'nullable|string',
        ]);

        $storeId = $request->input('store_id') ?: $customer->defaultStore?->id;

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
            'store_id' => $storeId,
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
            'store_id' => $storeId,
        ]);

        return redirect()->route('customer.credentials.index')
            ->with('new_key', $apiKey)
            ->with('new_secret', $plainSecret)
            ->with('success', 'API Credential berhasil dibuat! Harap simpan API Secret Anda sekarang, karena tidak akan ditampilkan lagi.');
    }

    public function update(Request $request, ApiCredential $credential): RedirectResponse
    {
        $customer = $request->user()->customer;
        if ($credential->customer_id !== $customer->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:50',
            'store_id' => ['nullable', \Illuminate\Validation\Rule::exists('stores', 'id')->where('customer_id', $customer->id)],
        ]);

        $storeId = $request->input('store_id') ?: $customer->defaultStore?->id;

        $before = $credential->toArray();
        $credential->update([
            'name' => $request->input('name'),
            'store_id' => $storeId,
        ]);

        AuditLog::record('UPDATE_API_CREDENTIAL', $credential, $before, [
            'name' => $credential->name,
            'store_id' => $storeId,
        ]);

        return redirect()->route('customer.credentials.index')->with('success', "Konfigurasi toko untuk API Key \"{$credential->name}\" berhasil diperbarui!");
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
