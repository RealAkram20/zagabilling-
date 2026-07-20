<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Payment;
use App\Models\UnlockCode;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PaymentRepository $payments,
        private UnlockCodeService $unlockCodes,
        private AuditLogger $auditLogger,
        private PesapalClient $pesapal,
    ) {
    }

    public function checkout(Device $device, int $installments = 1): array
    {
        $payment = $this->initiate($device, $installments);

        if (! $this->pesapal->configured()) {
            if (! $this->canSimulate()) {
                $payment->update(['status' => Payment::STATUS_FAILED]);

                return ['payment' => $payment, 'error' => 'Online payments are temporarily unavailable. Please contact support to make a payment.'];
            }

            $this->markPaid($payment, 'SANDBOX-' . $payment->merchant_reference);

            return ['payment' => $payment, 'simulated' => true];
        }

        $order = $this->pesapal->submitOrder($payment);

        if (! empty($order['order_tracking_id']) && ! empty($order['redirect_url'])) {
            $payment->update(['pesapal_tracking_id' => $order['order_tracking_id']]);

            return ['payment' => $payment, 'redirect_url' => $order['redirect_url']];
        }

        $payment->update(['status' => Payment::STATUS_FAILED]);

        return ['payment' => $payment, 'error' => $order['error']['message'] ?? 'Unable to start the payment. Please try again.'];
    }

    public function verifyByTracking(string $orderTrackingId): ?Payment
    {
        $payment = $this->payments->findByTracking($orderTrackingId);

        if (! $payment || $payment->isPaid()) {
            return $payment;
        }

        $status = $this->pesapal->transactionStatus($orderTrackingId);
        $code = (int) ($status['status_code'] ?? 0);

        if ($code === 1) {
            $this->markPaid($payment, $orderTrackingId);
        } elseif (in_array($code, [2, 3], true)) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
        }

        return $payment->fresh();
    }

    public function collectCash(Device $device, int $installments = 1): UnlockCode
    {
        $resolved = $this->resolve($device, $installments);

        $payment = $this->payments->create([
            'device_id' => $device->id,
            'client_id' => $device->client_id,
            'amount' => $resolved['amount'],
            'installments_count' => $resolved['installments'],
            'status' => Payment::STATUS_PENDING,
            'method' => 'cash',
            'method_label' => 'Cash',
            'merchant_reference' => 'CASH-' . strtoupper(Str::random(10)),
        ]);

        return $this->markPaid($payment);
    }

    public function collectMobile(Device $device, int $installments, string $phone): array
    {
        $resolved = $this->resolve($device, $installments);

        $payment = $this->payments->create([
            'device_id' => $device->id,
            'client_id' => $device->client_id,
            'amount' => $resolved['amount'],
            'installments_count' => $resolved['installments'],
            'status' => Payment::STATUS_PENDING,
            'method' => 'pesapal',
            'method_label' => 'Mobile Money',
            'merchant_reference' => 'MM-' . strtoupper(Str::random(10)),
        ]);

        if (! $this->pesapal->configured()) {
            if (! $this->canSimulate()) {
                $payment->update(['status' => Payment::STATUS_FAILED]);

                return ['error' => 'Mobile money is temporarily unavailable. Configure the payment gateway or collect cash instead.'];
            }

            $code = $this->markPaid($payment, 'SIMULATED-' . $payment->merchant_reference);

            return ['simulated' => true, 'code' => $code->code, 'reference' => $payment->merchant_reference];
        }

        $order = $this->pesapal->submitOrder($payment, $phone);

        if (! empty($order['order_tracking_id']) && ! empty($order['redirect_url'])) {
            $payment->update(['pesapal_tracking_id' => $order['order_tracking_id']]);

            return ['redirect_url' => $order['redirect_url'], 'reference' => $payment->merchant_reference];
        }

        $payment->update(['status' => Payment::STATUS_FAILED]);

        return ['error' => $order['error']['message'] ?? 'Unable to start the mobile money payment.'];
    }

    public function issuedCode(Payment $payment): ?string
    {
        return UnlockCode::where('payment_id', $payment->id)->latest()->first()?->code;
    }

    public function pollByReference(string $reference): array
    {
        $payment = $this->payments->findByReference($reference);

        if (! $payment) {
            return ['status' => 'unknown'];
        }

        if (! $payment->isPaid() && $payment->pesapal_tracking_id) {
            $this->verifyByTracking($payment->pesapal_tracking_id);
            $payment->refresh();
        }

        if ($payment->isPaid()) {
            return ['status' => 'paid', 'code' => $this->issuedCode($payment)];
        }

        return ['status' => $payment->status];
    }

    public function initiate(Device $device, int $installments = 1): Payment
    {
        $resolved = $this->resolve($device, $installments);

        return $this->payments->create([
            'device_id' => $device->id,
            'client_id' => $device->client_id,
            'amount' => $resolved['amount'],
            'installments_count' => $resolved['installments'],
            'status' => Payment::STATUS_PENDING,
            'method' => 'pesapal',
            'merchant_reference' => 'ZP-' . strtoupper(Str::random(10)),
        ]);
    }

    public function markPaid(Payment $payment, ?string $trackingId = null): UnlockCode
    {
        return DB::transaction(function () use ($payment, $trackingId) {
            // Lock the row for the duration of the transaction so concurrent
            // callback / IPN / admin-poll requests for the same payment cannot
            // each credit it — that would decrement the balance twice, extend
            // the due date twice, and issue two unlock codes for one payment.
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $existing = UnlockCode::where('payment_id', $locked->id)->latest()->first();

            // Already credited by a racing request — return the code it issued.
            if ($locked->isPaid() && $existing) {
                return $existing;
            }

            $device = $locked->device;

            if (! $locked->isPaid()) {
                $locked->update([
                    'status' => Payment::STATUS_PAID,
                    'pesapal_tracking_id' => $trackingId,
                    'paid_at' => now(),
                ]);

                $device->decrement('balance', (float) $locked->amount);
                if ((float) $device->balance < 0) {
                    $device->update(['balance' => 0]);
                }

                $device->update(['status' => Device::STATUS_ACTIVE]);

                $this->auditLogger->record(
                    'payment.verified',
                    "Payment {$locked->merchant_reference} verified for {$device->account_number}",
                    $locked,
                );
            }

            if ($existing) {
                return $existing;
            }

            $installments = max((int) $locked->installments_count, 1);

            return $this->unlockCodes->issue($device, UnlockCode::TYPE_FULL, $locked, null, $installments);
        });
    }

    /**
     * Resolve the amount to charge and the number of installments that amount
     * actually buys. The installment count drives the unlock duration, so it
     * MUST be derived from the money collected — never taken directly from the
     * client request — otherwise a caller could request many installments,
     * have the charge capped to their balance, and still receive a long unlock.
     *
     * @return array{amount: float, installments: int}
     */
    private function resolve(Device $device, int $requested): array
    {
        $requested = max($requested, 1);
        $installmentAmount = round((float) $device->installmentAmount(), 2);
        $balance = (float) $device->balance;

        $amount = round($installmentAmount * $requested, 2);
        if ($balance > 0) {
            $amount = min($amount, $balance);
        }

        $installments = $installmentAmount > 0
            ? max(1, (int) floor(round($amount / $installmentAmount, 6)))
            : $requested;

        return ['amount' => $amount, 'installments' => $installments];
    }

    /**
     * Simulated (gateway-less) payments are a local/testing convenience only.
     * In any other environment an unconfigured gateway must fail closed so a
     * public caller can never mint a free unlock code.
     */
    private function canSimulate(): bool
    {
        return app()->environment('local', 'testing');
    }
}
