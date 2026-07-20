<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Device;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\DeviceService;
use App\Services\PaymentService;
use App\Services\TokenCodec;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BillingScheduleTest extends TestCase
{
    use DatabaseTransactions;

    private function monthlyPlan(): Plan
    {
        return Plan::where('cadence', 'monthly')->firstOrFail();
    }

    private function freshDevice(Plan $plan): Device
    {
        $device = Device::firstOrFail();
        $device->update([
            'client_id' => null,
            'plan_id' => null,
            'status' => 'unassigned',
            'next_due_at' => null,
            'unlock_counter' => 0,
        ]);

        return $device->fresh();
    }

    public function test_enroll_sets_due_date_to_exactly_what_the_device_will_compute(): void
    {
        $plan = $this->monthlyPlan();
        $device = $this->freshDevice($plan);
        $client = Client::firstOrFail();

        $code = app(DeviceService::class)->enroll($device, [
            'client_id' => $client->id,
            'plan_id' => $plan->id,
        ]);
        $device->refresh();

        $decoded = app(TokenCodec::class)->decode($code->code, $device->hmac_secret);
        $deviceLockDate = Carbon::today()->addDays($decoded['duration_days'])->toDateString();

        $this->assertSame($plan->cadenceDays(), $decoded['duration_days'],
            'The first code should grant exactly one cadence period.');
        $this->assertSame($deviceLockDate, $device->next_due_at->toDateString(),
            'Portal next_due_at must equal the device lock date to the day.');
    }

    public function test_deposit_is_recorded_as_the_first_payment(): void
    {
        $plan = $this->monthlyPlan();
        $device = $this->freshDevice($plan);
        $client = Client::firstOrFail();

        app(DeviceService::class)->enroll($device, [
            'client_id' => $client->id,
            'plan_id' => $plan->id,
        ]);

        $deposit = $device->fresh()->payments()->where('method', 'deposit')->first();
        $this->assertNotNull($deposit, 'Enrolling should record the deposit as a paid payment.');
        $this->assertSame(Payment::STATUS_PAID, $deposit->status);
    }

    public function test_a_payment_advances_the_due_date_by_exactly_one_cadence(): void
    {
        $plan = $this->monthlyPlan();
        $device = $this->freshDevice($plan);
        $client = Client::firstOrFail();

        app(DeviceService::class)->enroll($device, [
            'client_id' => $client->id,
            'plan_id' => $plan->id,
        ]);
        $afterEnroll = $device->fresh()->next_due_at->copy();

        app(PaymentService::class)->collectCash($device->fresh(), 1);
        $afterPayment = $device->fresh()->next_due_at->copy();

        $this->assertSame(
            $afterEnroll->addDays($plan->cadenceDays())->toDateString(),
            $afterPayment->toDateString(),
            'A one-installment payment must advance the due date by exactly one cadence — no double counting.'
        );
    }

    public function test_a_multi_installment_payment_extends_from_the_previous_due_date(): void
    {
        $plan = $this->monthlyPlan();
        $device = $this->freshDevice($plan);
        $client = Client::firstOrFail();

        app(DeviceService::class)->enroll($device, [
            'client_id' => $client->id,
            'plan_id' => $plan->id,
        ]);
        $afterEnroll = $device->fresh()->next_due_at->copy();

        $code = app(PaymentService::class)->collectCash($device->fresh(), 3);
        $afterPayment = $device->fresh()->next_due_at->copy();

        $this->assertSame(
            $afterEnroll->addDays(3 * $plan->cadenceDays())->toDateString(),
            $afterPayment->toDateString(),
            'Three installments must extend the previous due date by exactly three cadences.'
        );
        $this->assertSame(3 * $plan->cadenceDays(), (int) $code->duration_days,
            'The unlock code must carry the same day count the portal added.');
    }

    public function test_cash_amounts_land_on_the_smallest_ugx_denomination(): void
    {
        $plan = $this->monthlyPlan();
        $device = $this->freshDevice($plan);
        $device->update(['price' => 999999, 'plan_id' => $plan->id]);
        $device = $device->fresh();

        $this->assertSame(0.0, fmod($device->depositAmount(), Device::CASH_ROUNDING),
            'The deposit must land on a payable cash amount.');
        $this->assertSame(0.0, fmod($device->installmentAmount(), Device::CASH_ROUNDING),
            'Each installment must land on a payable cash amount.');
        $this->assertGreaterThanOrEqual(
            $device->financedAmount(),
            $device->installmentAmount() * $plan->term_months,
            'Rounded installments must still cover the full financed amount by the end of the term.'
        );
    }
}
