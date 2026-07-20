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
        $payment = $this->payments->create([
            'device_id' => $device->id,
            'client_id' => $device->client_id,
            'amount' => $this->amountFor($device, $installments),
            'installments_count' => max($installments, 1),
            'status' => Payment::STATUS_PENDING,
            'method' => 'cash',
            'method_label' => 'Cash',
            'merchant_reference' => 'CASH-' . strtoupper(Str::random(10)),
        ]);

        return $this->markPaid($payment);
    }

    public function collectMobile(Device $device, int $installments, string $phone): array
    {
        $payment = $this->payments->create([
            'device_id' => $device->id,
            'client_id' => $device->client_id,
            'amount' => $this->amountFor($device, $installments),
            'installments_count' => max($installments, 1),
            'status' => Payment::STATUS_PENDING,
            'method' => 'pesapal',
            'method_label' => 'Mobile Money',
            'merchant_reference' => 'MM-' . strtoupper(Str::random(10)),
        ]);

        if (! $this->pesapal->configured()) {
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
        return $this->payments->create([
            'device_id' => $device->id,
            'client_id' => $device->client_id,
            'amount' => $this->amountFor($device, $installments),
            'installments_count' => max($installments, 1),
            'status' => Payment::STATUS_PENDING,
            'method' => 'pesapal',
            'merchant_reference' => 'ZP-' . strtoupper(Str::random(10)),
        ]);
    }

    public function markPaid(Payment $payment, ?string $trackingId = null): UnlockCode
    {
        return DB::transaction(function () use ($payment, $trackingId) {
            $payment->update([
                'status' => Payment::STATUS_PAID,
                'pesapal_tracking_id' => $trackingId,
                'paid_at' => now(),
            ]);

            $device = $payment->device;
            $installments = max((int) $payment->installments_count, 1);

            $device->decrement('balance', (float) $payment->amount);
            if ((float) $device->balance < 0) {
                $device->update(['balance' => 0]);
            }

            $device->update(['status' => Device::STATUS_ACTIVE]);

            $this->auditLogger->record(
                'payment.verified',
                "Payment {$payment->merchant_reference} verified for {$device->account_number}",
                $payment,
            );

            return $this->unlockCodes->issue($device, UnlockCode::TYPE_FULL, $payment, null, $installments);
        });
    }

    private function amountFor(Device $device, int $installments): float
    {
        $amount = round($device->installmentAmount() * max($installments, 1), 2);
        $balance = (float) $device->balance;

        return $balance > 0 ? min($amount, $balance) : $amount;
    }
}
