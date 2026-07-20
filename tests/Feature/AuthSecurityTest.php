<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private function superAdmin(): User
    {
        return User::where('email', 'admin@zaga.local')->firstOrFail();
    }

    /** C4/C5 — repeated wrong passwords for one account lock it out. */
    public function test_repeated_failed_logins_are_locked_out(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin@zaga.local', 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => 'admin@zaga.local', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email'),
        );
    }

    /** C5 — a 2FA code is burned after too many wrong guesses, so it can't be brute-forced. */
    public function test_two_factor_code_is_invalidated_after_repeated_wrong_guesses(): void
    {
        $user = $this->superAdmin();
        $service = app(TwoFactorService::class);

        $service->send($user);
        $this->assertTrue(Cache::has("2fa_code_{$user->id}"), 'A code should be cached after send().');

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($service->verify($user, '000000'));
        }

        $this->assertFalse(
            Cache::has("2fa_code_{$user->id}"),
            'After 5 wrong guesses the code must be discarded, forcing a resend.'
        );
    }

    /** M3 — disabling 2FA requires re-entering the password. */
    public function test_toggling_two_factor_requires_the_current_password(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.account.twoFactor'), ['two_factor_enabled' => '1'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($admin)
            ->patch(route('admin.account.twoFactor'), ['two_factor_enabled' => '1', 'current_password' => 'password'])
            ->assertSessionHasNoErrors();
    }
}
