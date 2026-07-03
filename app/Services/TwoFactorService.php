<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class TwoFactorService
{
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

        $appName = config('app.name');

        Mail::raw("Your {$appName} sign-in code is {$code}. It expires in 10 minutes.", function ($message) use ($user, $appName) {
            $message->to($user->email)->subject("Your {$appName} sign-in code");
        });
    }

    public function verify(User $user, string $code): bool
    {
        $hash = Cache::get($this->key($user));

        if (! $hash || ! Hash::check($code, $hash)) {
            return false;
        }

        Cache::forget($this->key($user));

        return true;
    }

    private function key(User $user): string
    {
        return "2fa_code_{$user->id}";
    }
}
