<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     * Ensure merchant customer has an active subscription to access API & webhook management.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Administrator bypass
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        $customer = $user->customer;

        if (!$customer || !$customer->hasActiveSubscription()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fitur API & Webhook terkunci. Silakan pilih dan aktifkan paket langganan Anda terlebih dahulu.',
                    'subscription_required' => true,
                ], 403);
            }

            return redirect()->route('customer.subscription.index')->with(
                'error',
                'Fitur API & Webhook terkunci. Silakan pilih dan aktifkan paket langganan Anda terlebih dahulu.'
            );
        }

        return $next($request);
    }
}
