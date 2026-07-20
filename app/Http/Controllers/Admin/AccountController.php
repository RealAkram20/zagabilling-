<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(SettingsService $settings): View
    {
        return view('admin.account', [
            'user' => auth()->user(),
            'twoFactorEnforced' => $settings->security()['require_2fa'],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . auth()->id()],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($request->hasFile('avatar')) {
            $directory = public_path('uploads/avatars');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $file = $request->file('avatar');
            $filename = 'av_' . auth()->id() . '_' . now()->timestamp . '.' . $file->extension();
            $file->move($directory, $filename);
            $attributes['avatar_path'] = 'uploads/avatars/' . $filename;
        }

        auth()->user()->update($attributes);

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        auth()->user()->update(['password' => Hash::make($request->input('password'))]);

        // Invalidate this user's other sessions so a previously-stolen session
        // cannot survive a password reset. (Full effect requires the
        // AuthenticateSession middleware on the admin routes.)
        Auth::logoutOtherDevices($request->input('password'));

        return back()->with('status', 'Password updated.');
    }

    public function updateTwoFactor(Request $request): RedirectResponse
    {
        // Changing a security control requires re-entering the password so a
        // hijacked session cannot silently disable two-factor auth.
        $request->validate(['current_password' => ['required', 'current_password']]);

        auth()->user()->update(['two_factor_enabled' => $request->boolean('two_factor_enabled')]);

        return back()->with('status', 'Two-factor preference updated.');
    }
}
