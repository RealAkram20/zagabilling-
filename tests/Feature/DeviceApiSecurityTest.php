<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Device;
use App\Models\Plan;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeviceApiSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private function enrolledDevice(): Device
    {
        $device = Device::firstOrFail();
        $device->update([
            'account_number' => 'ZG-API00001',
            'client_id' => null,
            'plan_id' => null,
            'status' => 'unassigned',
        ]);

        app(DeviceService::class)->enroll($device->fresh(), [
            'client_id' => Client::firstOrFail()->id,
            'plan_id' => Plan::firstOrFail()->id,
        ]);

        return $device->fresh();
    }

    /** H2 — a token without the required ability cannot fetch unlock codes. */
    public function test_token_without_ability_cannot_fetch_unlock_code(): void
    {
        $device = $this->enrolledDevice();
        $plain = $device->createToken('scoped', ['device:heartbeat'])->plainTextToken;

        $this->withToken($plain)->getJson('/api/device/token')
            ->assertForbidden();
    }

    /** H2 — a properly scoped token still works (no regression for real devices). */
    public function test_token_with_ability_can_fetch_unlock_code(): void
    {
        $device = $this->enrolledDevice();
        $plain = $device->createToken('scoped', ['device:heartbeat', 'device:token'])->plainTextToken;

        $this->withToken($plain)->getJson('/api/device/token')
            ->assertOk()
            ->assertJsonStructure(['token', 'type', 'duration_days']);
    }
}
