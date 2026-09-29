<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone' => ['nullable', 'string', 'max:25'],
            'whatsapp' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'role' => 'customer',
                'status' => 'active',
                'password' => Hash::make($request->password),
            ]);

            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'company_name' => $request->company_name ?? $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? $request->whatsapp,
                'whatsapp' => $request->whatsapp ?? $request->phone,
                'address' => $request->address,
                'country' => 'ID',
                'status' => 'active',
            ]);

            AuditLog::record('USER_REGISTRATION', $user, null, ['email' => $user->email], $user->id);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}