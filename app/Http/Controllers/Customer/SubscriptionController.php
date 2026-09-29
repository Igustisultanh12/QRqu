<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;
        $activeSubscription = $customer->activeSubscription ? $customer->activeSubscription->load('plan') : null;

        $plans = Plan::where('status', 'active')->orderBy('price')->get();
        $histories = SubscriptionHistory::where('customer_id', $customer->id)
            ->with('plan')
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/Subscription/Index', [
            'activeSubscription' => $activeSubscription,
            'plans' => $plans,
            'histories' => $histories,
        ]);
    }

    public function subscribe(Request $request, Plan $plan): RedirectResponse
    {
        $customer = $request->user()->customer;

        $currentSub = $customer->activeSubscription;
        $startsAt = now();
        $expiresAt = now()->addDays($plan->duration_days);

        // If currently active, extend expiry
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
            'note' => "Berlangganan paket {$plan->name} ({$plan->duration_days} hari)",
            'amount_paid' => $plan->price,
        ]);

        AuditLog::record('SUBSCRIBE_PLAN', $subscription, null, [
            'plan' => $plan->name,
            'amount' => $plan->price,
        ]);

        // Otomatis terbitkan Kredensial API Sandbox jika baru pertama kali berlangganan
        if ($customer->apiCredentials()->count() === 0) {
            \App\Models\ApiCredential::generateCredentials($customer->id, 'sandbox', 'Sandbox Key');
        }

        return redirect()->back()->with('success', "Berhasil mengaktifkan langganan {$plan->name}! Fitur API & Webhook kini telah terbuka.");
    }
}
