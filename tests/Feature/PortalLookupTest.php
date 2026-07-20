<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Device;
use App\Models\Plan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PortalLookupTest extends TestCase
{
    use DatabaseTransactions;

    private function enrolledDevice(string $accountNumber): Device
    {
        $device = Device::firstOrFail();
        $device->update([
            'account_number' => $accountNumber,
            'client_id' => Client::firstOrFail()->id,
            'plan_id' => Plan::firstOrFail()->id,
        ]);

        return $device->fresh();
    }

    public function test_exact_account_number_reaches_the_summary(): void
    {
        $device = $this->enrolledDevice('ZG-73991');

        $this->post('/find', ['account_number' => 'ZG-73991'])
            ->assertRedirect(route('portal.summary', $device));
    }

    public function test_lookup_forgives_case_spacing_and_missing_prefix(): void
    {
        $device = $this->enrolledDevice('ZG-73991');

        foreach (['zg-73991', 'ZG - 73991', ' zg 73991 ', '73991'] as $typed) {
            $this->post('/find', ['account_number' => $typed])
                ->assertRedirect(route('portal.summary', $device));
        }
    }

    public function test_device_minted_numbers_match_with_or_without_dashes(): void
    {
        $device = $this->enrolledDevice('ZG-TE5TQ-8Q2XN');

        foreach (['ZG-TE5TQ-8Q2XN', 'ZGTE5TQ8Q2XN', 'zg-te5tq-8q2xn', 'TE5TQ-8Q2XN', 'te5tq8q2xn'] as $typed) {
            $this->post('/find', ['account_number' => $typed])
                ->assertRedirect(route('portal.summary', $device));
        }
    }

    public function test_lookup_folds_ambiguous_characters_like_the_device_client(): void
    {
        $device = $this->enrolledDevice('ZG-70191');

        $this->post('/find', ['account_number' => 'ZG-7OI9L'])
            ->assertRedirect(route('portal.summary', $device));
    }

    public function test_unknown_account_number_is_rejected(): void
    {
        $this->from('/')
            ->post('/find', ['account_number' => 'ZG-999999999'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('account_number');
    }

    public function test_device_without_a_plan_gets_the_setup_message(): void
    {
        $device = Device::firstOrFail();
        $device->update([
            'account_number' => 'ZG-73991',
            'client_id' => null,
            'plan_id' => null,
        ]);

        $this->from('/')
            ->post('/find', ['account_number' => 'ZG-73991'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('account_number');

        $this->assertStringContainsString(
            'not set up for payments',
            session('errors')->first('account_number'),
        );
    }
}
