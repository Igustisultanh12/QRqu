<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $customer = $user->customer;

        $recentLogins = AuditLog::where('user_id', $user->id)
            ->whereIn('action', ['USER_LOGIN', 'USER_REGISTRATION'])
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Customer/Profile/Edit', [
            'user' => $user,
            'customer' => $customer,
            'recent_logins' => $recentLogins,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:25'],
            'whatsapp' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->fill([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->customer) {
            $user->customer->update([
                'name' => $request->input('name'),
                'company_name' => $request->input('company_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'whatsapp' => $request->input('whatsapp'),
                'address' => $request->input('address'),
            ]);
        }

        AuditLog::record('UPDATE_PROFILE', $user);

        return redirect()->route('profile.edit')->with('status', 'profile-updated')->with('success', 'Profil Anda berhasil diperbarui!');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::record('CHANGE_PASSWORD', $request->user());

        return redirect()->back()->with('success', 'Password Anda berhasil diperbarui!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}