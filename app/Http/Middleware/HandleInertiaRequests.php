<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                    'has_verified_email' => $user->hasVerifiedEmail(),
                    'phone' => $user->phone ?? $user->whatsapp_number ?? ($user->customer->phone ?? null),
                    'whatsapp_number' => $user->whatsapp_number ?? ($user->customer->whatsapp ?? null),
                    'avatar' => $user->avatar,
                    'avatar_url' => $user->avatar_url,
                    'created_at' => $user->created_at,
                    'is_admin' => $user->isAdmin(),
                    'is_customer' => $user->isCustomer(),
                    'customer' => $user->customer ? [
                        'id' => $user->customer->id,
                        'name' => $user->customer->name,
                        'company_name' => $user->customer->company_name,
                        'status' => $user->customer->status,
                        'has_active_subscription' => $user->customer->hasActiveSubscription(),
                    ] : null,
                ] : null,
            ],

            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
                'status' => $request->session()->get('status'),
                'new_key' => $request->session()->get('new_key'),
                'new_secret' => $request->session()->get('new_secret'),
                'doku_test_result' => $request->session()->get('doku_test_result'),
            ],

            'app' => [
                'name' => config('app.name', 'QRqu'),
                'env' => config('app.env', 'production'),
            ],
        ];
    }
}