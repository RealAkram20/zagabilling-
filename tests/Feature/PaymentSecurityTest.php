<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Device;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\UnlockCode;
use App\Services\DeviceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private function enrolledDevice(): Device
    {
        $plan = Plan::where('cadence', 'monthly')->firstOrFail();
        $device = Device::firstOrFail();
        $device->update([
            'account_number' => 'ZG-SEC00001',
            'client_id' => null,
            'plan_id' => null,
            'status' => 'unassigned',
            'next_due_at' => null,
            'unlock_counter' => 0,
        ]);

        app(DeviceService::class)->enroll($device->fresh(), [
            'client_id' => Client::firstOrFail()->id,
            'plan_id' => $plan->id,
        ]);

        return $device->fresh();
    }

    /** C1 — the unlock duration must reflect the money collected, not the requested installment count. */
    public function test_requesting_more_installments_than_the_balance_covers_does_not_buy_a_longer_unlock(): void
    {
        $device = $this->enrolledDevice();
        $plan = $device->plan;

        // Leave only a single installment of balance, then try to buy 120.
        $device->update(['balance' => $device->installmentAmount()]);

        $code = app(PaymentService::class)->collectCash($device->fresh(), 120);

        $this->assertSame(
            $plan->cadenceDays(),
            (int) $code->duration_days,
            'Paying one installment of money must grant exactly one cadence, even when 120 installments are requested.'
        );
    }

    /** C2 — an unconfigured gateway must fail closed in production, never mint a free unlock. */
    public function test_checkout_fails_closed_when_the_gateway_is_unconfigured_outside_local(): void
    {
        $this->app['env'] = 'production';

        $device = $this->enrolledDevice();
        $balanceBefore = (float) $device->balance;
        $codesBefore = UnlockCode::where('device_id', $device->id)->count();

        $result = app(PaymentService::class)->checkout($device->fresh(), 1);

        $this->assertArrayHasKey('error', $result, 'Checkout must return an error when the gateway is not configured in production.');
        $this->assertSame(Payment::STATUS_FAILED, $result['payment']->status);
        $this->assertSame($balanceBefore, (float) $device->fresh()->balance, 'Balance must be untouched — no payment was actually collected.');
        $this->assertSame($codesBefore, UnlockCode::where('device_id', $device->id)->count(), 'No unlock code may be issued for an uncollected payment.');
    }

    /** C3 — the unlock code page must not be reachable by enumeration; only after a completed payment. */
    public function test_code_page_is_denied_without_a_completed_payment(): void
    {
        $device = $this->enrolledDevice();

        $this->get(route('portal.code', $device))
            ->assertRedirect(route('portal.summary', $device))
            ->assertSessionHasErrors('account_number');
    }

    public function test_code_page_is_shown_after_a_payment_completes(): void
    {
        $device = $this->enrolledDevice();

        // In the testing environment an unconfigured gateway simulates success,
        // which grants this session code access.
        $this->post(route('portal.pay', $device), ['installments' => 1])
            ->assertRedirect(route('portal.code', $device));

        $this->get(route('portal.code', $device))
            ->assertOk();
    }

    /** H7 — verifying the same payment twice must credit it once (no double-unlock / double-debit). */
    public function test_verifying_a_payment_twice_credits_it_only_once(): void
    {
        $device = $this->enrolledDevice();
        $service = app(PaymentService::class);

        $payment = $service->initiate($device->fresh(), 1);
        $balanceBefore = (float) $device->fresh()->balance;
        $dueBefore = $device->fresh()->next_due_at?->copy();

        $first = $service->markPaid($payment->fresh());
        $second = $service->markPaid($payment->fresh());

        $this->assertSame($first->id, $second->id, 'A second verification must return the same unlock code, not issue a new one.');
        $this->assertSame(
            1,
            UnlockCode::where('payment_id', $payment->id)->count(),
            'Exactly one unlock code may exist per payment.'
        );
        $this->assertSame(
            round($balanceBefore - (float) $payment->amount, 2),
            round((float) $device->fresh()->balance, 2),
            'The balance must be debited exactly once.'
        );
        $this->assertTrue(
            $dueBefore->addDays($device->plan->cadenceDays())->equalTo($device->fresh()->next_due_at),
            'The due date must advance by exactly one cadence, not two.'
        );
    }
}
