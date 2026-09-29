<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Customer::with(['user', 'activeSubscription.plan'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Customer $customer): Response
    {
        $customer->load([
            'user',
            'subscriptions.plan',
            'apiCredentials',
            'invoices' => fn($q) => $q->latest()->limit(10),
            'transactions' => fn($q) => $q->latest()->limit(10),
            'webhooks',
        ]);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function updateStatus(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:active,suspended,blocked,pending',
        ]);

        $before = $customer->status;
        $customer->update(['status' => $request->input('status')]);

        // Also sync user status
        if ($customer->user) {
            $customer->user->update(['status' => $request->input('status')]);
        }

        AuditLog::record('UPDATE_CUSTOMER_STATUS', $customer, ['status' => $before], ['status' => $customer->status]);

        return redirect()->back()->with('success', "Status customer {$customer->name} diubah menjadi {$customer->status}.");
    }

    public function resetPassword(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        if ($customer->user) {
            $customer->user->update([
                'password' => Hash::make($request->input('new_password')),
            ]);

            AuditLog::record('ADMIN_RESET_PASSWORD', $customer->user);
        }

        return redirect()->back()->with('success', "Password user {$customer->name} berhasil direset.");
    }
}
