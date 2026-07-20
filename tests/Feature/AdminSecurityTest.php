<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Device;
use App\Models\Plan;
use App\Models\User;
use App\Services\DeviceService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private function superAdmin(): User
    {
        return User::where('email', 'admin@zaga.local')->firstOrFail();
    }

    /** M1 — revealing vault secrets must require a password re-entry server-side when the setting is on. */
    public function test_vault_reveal_is_denied_without_the_password_when_reauth_is_on(): void
    {
        app(SettingsService::class)->setSecurity(['vault_reauth' => '1']);
        $device = Device::firstOrFail();

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.devices.vault', $device))
            ->assertStatus(422)
            ->assertJson(['reauth_required' => true]);
    }

    public function test_vault_reveal_succeeds_with_the_correct_password(): void
    {
        app(SettingsService::class)->setSecurity(['vault_reauth' => '1']);
        $device = Device::firstOrFail();

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.devices.vault', $device), ['password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['bios_password', 'recovery_key']);
    }

    public function test_vault_reveal_rejects_a_wrong_password(): void
    {
        app(SettingsService::class)->setSecurity(['vault_reauth' => '1']);
        $device = Device::firstOrFail();

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.devices.vault', $device), ['password' => 'not-the-password'])
            ->assertStatus(422)
            ->assertJson(['reauth_required' => true]);
    }

    /** M2 — an enrollment code must not enroll a machine whose serial differs from the registered one. */
    public function test_enrollment_is_rejected_when_the_reported_serial_does_not_match(): void
    {
        $device = Device::firstOrFail();
        $device->update([
            'serial' => 'SERIAL-BOUND-1',
            'client_id' => Client::firstOrFail()->id,
            'plan_id' => Plan::firstOrFail()->id,
            'status' => 'active',
        ]);

        $code = app(DeviceService::class)->issueEnrollmentCode($device->fresh());

        $this->postJson('/api/device/enroll', ['code' => $code, 'serial' => 'SOME-OTHER-SERIAL'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'This enrollment code is registered to a different device. Contact support.']);

        // The rejected attempt must not have consumed the code — the right machine can still enroll.
        $this->postJson('/api/device/enroll', ['code' => $code, 'serial' => 'serial-bound-1'])
            ->assertOk()
            ->assertJsonStructure(['token', 'account_number']);
    }

    /** M5 — an SMTP host outside the allowlist is rejected. */
    public function test_smtp_host_outside_the_allowlist_is_rejected(): void
    {
        config(['mail.allowed_hosts' => ['smtp.approved.example']]);

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.settings.mail'), ['host' => 'smtp.attacker.example', 'port' => 587])
            ->assertSessionHasErrors('host');

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.settings.mail'), ['host' => 'smtp.approved.example', 'port' => 587])
            ->assertSessionHasNoErrors();
    }

    /** M-read — the security audit log is not visible to the lowest (support) role. */
    public function test_support_role_cannot_view_the_audit_log(): void
    {
        $support = User::where('role', User::ROLE_SUPPORT)->firstOrFail();
        $operator = User::where('role', User::ROLE_OPERATOR)->firstOrFail();

        $this->actingAs($support)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('admin.audit.index'))->assertOk();
    }
}
