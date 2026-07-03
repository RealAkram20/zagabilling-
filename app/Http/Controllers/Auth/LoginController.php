<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::validate($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $user = User::where('email', $credentials['email'])->first();

        if ($twoFactor->required($user)) {
            $twoFactor->send($user);
            $request->session()->put('login.2fa.user_id', $user->id);
            $request->session()->put('login.2fa.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.show');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showTwoFactor(Request $request)
    {
        if (! $request->session()->has('login.2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verifyTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $userId = $request->session()->get('login.2fa.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate(['code' => ['required', 'digits:6']]);

        $user = User::find($userId);

        if (! $user || ! $twoFactor->verify($user, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code is invalid or has expired.']);
        }

        Auth::login($user, (bool) $request->session()->pull('login.2fa.remember'));
        $request->session()->forget('login.2fa.user_id');
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function resendTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $userId = $request->session()->get('login.2fa.user_id');

        if ($userId && ($user = User::find($userId))) {
            $twoFactor->send($user);
        }

        return back()->with('status', 'A new code has been sent.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
