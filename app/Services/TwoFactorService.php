<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class TwoFactorService
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(private SettingsService $settings)
    {
    }

    public function required(User $user): bool
    {
        return $this->settings->security()['require_2fa'] || $user->two_factor_enabled;
    }

    public function send(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        Cache::put($this->key($user), Hash::make($code), now()->addMinutes(10));
        Cache::forget($this->attemptsKey($user));

        $appName = config('app.name');

        Mail::raw("Your {$appName} sign-in code is {$code}. It expires in 10 minutes.", function ($message) use ($user, $appName) {
            $message->to($user->email)->subject("Your {$appName} sign-in code");
        });
    }

    public function verify(User $user, string $code): bool
    {
        $hash = Cache::get($this->key($user));

        if (! $hash) {
            return false;
        }

        if (! Hash::check($code, $hash)) {
            // Burn the code after too many wrong guesses so a 6-digit code
            // can't be brute-forced within its 10-minute window.
            $attempts = (int) Cache::get($this->attemptsKey($user), 0) + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::forget($this->key($user));
                Cache::forget($this->attemptsKey($user));
            } else {
                Cache::put($this->attemptsKey($user), $attempts, now()->addMinutes(10));
            }

            return false;
        }

        Cache::forget($this->key($user));
        Cache::forget($this->attemptsKey($user));

        return true;
    }

    private function key(User $user): string
    {
        return "2fa_code_{$user->id}";
    }

    private function attemptsKey(User $user): string
    {
        return "2fa_attempts_{$user->id}";
    }
}
