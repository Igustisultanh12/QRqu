<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdmin() && !$user->customer) {
            $user->ensureCustomerProfile();
        }
        $customer = $user->fresh()->customer;

        if (!$customer) {
            return redirect()->back()->with('error', 'Profil merchant belum tersedia.');
        }

        $customer->ensureStores();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
        ], [
            'name.required' => 'Nama toko wajib diisi.',
            'name.max' => 'Nama toko maksimal 100 karakter.',
        ]);

        $code = !empty($validated['code'])
            ? strtoupper(Str::slug($validated['code']))
            : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 8));

        $store = Store::create([
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'code' => $code ?: 'TOKO-' . rand(100, 999),
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_default' => false,
            'status' => 'active',
        ]);

        return redirect()->back()->with('success', "Toko \"{$store->name}\" berhasil ditambahkan.");
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$user->isAdmin() && (!$customer || $store->customer_id !== $customer->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $store->update($validated);

        return redirect()->back()->with('success', "Data toko \"{$store->name}\" berhasil diperbarui.");
    }
}
