<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;
        $activeSubscription = $customer->activeSubscription ? $customer->activeSubscription->load('plan') : null;

        // Cek jika ada tagihan langganan yang belum dibayar
        $pendingSubscription = Subscription::where('customer_id', $customer->id)
            ->where('status', 'pending_payment')
            ->with(['plan', 'invoice'])
            ->latest()
            ->first();

        // Pastikan invoice belum kedaluwarsa jika ada pending subscription
        if ($pendingSubscription && $pendingSubscription->invoice && $pendingSubscription->invoice->status === 'PAID') {
            $pendingSubscription->update(['status' => 'active']);
            $pendingSubscription = null;
            $activeSubscription = $customer->fresh()->activeSubscription ? $customer->fresh()->activeSubscription->load('plan') : null;
        }

        $plans = Plan::where('status', 'active')->orderBy('price')->get();
        $histories = SubscriptionHistory::where('customer_id', $customer->id)
            ->with('plan')
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/Subscription/Index', [
            'activeSubscription' => $activeSubscription,
            'pendingSubscription' => $pendingSubscription,
            'plans' => $plans,
            'histories' => $histories,
        ]);
    }

    public function subscribe(Request $request, Plan $plan): RedirectResponse
    {
        $customer = $request->user()->customer;

        // 1. Jika paket gratis (0 rupiah), langsung aktifkan tanpa invoice
        if ((float) $plan->price <= 0) {
            $currentSub = $customer->activeSubscription;
            $startsAt = now();
            $expiresAt = now()->addDays($plan->duration_days);

            if ($currentSub && $currentSub->expires_at->isFuture()) {
                $expiresAt = $currentSub->expires_at->copy()->addDays($plan->duration_days);
            }

            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'grace_period_days' => 3,
                'status' => 'active',
                'auto_renew' => true,
            ]);

            SubscriptionHistory::create([
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'event' => $currentSub ? 'upgraded' : 'created',
                'note' => "Berlangganan paket gratis {$plan->name} ({$plan->duration_days} hari)",
                'amount_paid' => 0,
            ]);

            if ($customer->apiCredentials()->count() === 0) {
                \App\Models\ApiCredential::generateCredentials($customer->id, 'sandbox', 'Sandbox Key');
            }

            return redirect()->back()->with('success', "Berhasil mengaktifkan langganan {$plan->name}!");
        }

        // 2. Buat Tagihan Invoice & QRIS untuk pembayaran paket
        $externalId = 'SUB-' . $customer->id . '-' . $plan->id . '-' . time();
        $invoiceResult = $this->invoiceService->createInvoice($customer, [
            'external_id' => $externalId,
            'amount' => (float) $plan->price,
            'description' => "Langganan {$plan->name} ({$plan->duration_days} Hari)",
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            'callback_url' => route('customer.subscription.index'),
        ]);

        if (!$invoiceResult['success']) {
            return redirect()->back()->with('error', 'Gagal membuat tagihan pembayaran QRIS: ' . ($invoiceResult['error']['message'] ?? 'Terjadi kesalahan sistem'));
        }

        /** @var Invoice $invoice */
        $invoice = $invoiceResult['invoice'];

        // Buat record subscription berstatus pending_payment yang terhubung ke invoice
        Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'invoice_id' => $invoice->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays($plan->duration_days),
            'grace_period_days' => 3,
            'status' => 'pending_payment',
            'auto_renew' => true,
        ]);

        AuditLog::record('SUBSCRIBE_INITIATED', $invoice, null, [
            'plan' => $plan->name,
            'amount' => $plan->price,
            'invoice_id' => $invoice->id,
        ]);

        // Arahkan ke halaman checkout QRIS untuk pembayaran
        return redirect()->route('checkout.show', $invoice->id);
    }
}
