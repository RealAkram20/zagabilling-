<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Repositories\DeviceRepository;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnlockController extends Controller
{
    public function __construct(private DeviceRepository $devices)
    {
    }

    public function lookup(): View
    {
        return view('client.lookup');
    }

    public function find(Request $request): RedirectResponse
    {
        $request->validate(['account_number' => ['required', 'string']]);

        $device = $this->devices->findByAccountNumber($request->input('account_number'));

        if (! $device || ! $device->isEnrolled()) {
            return back()
                ->withInput()
                ->withErrors(['account_number' => 'No active device found for that account number.']);
        }

        return redirect()->route('portal.summary', $device);
    }

    public function summary(Device $device): View|RedirectResponse
    {
        if (! $device->isEnrolled()) {
            return redirect()->route('portal.lookup')->withErrors(['account_number' => 'This device is not active for payments.']);
        }

        $device->load(['client', 'plan']);

        return view('client.summary', ['device' => $device]);
    }

    public function payment(Device $device): View|RedirectResponse
    {
        if (! $device->isEnrolled()) {
            return redirect()->route('portal.lookup')->withErrors(['account_number' => 'This device is not active for payments.']);
        }

        $device->load(['client', 'plan']);

        return view('client.payment', ['device' => $device]);
    }

    public function pay(Request $request, Device $device, PaymentService $payments): RedirectResponse
    {
        $installments = (int) $request->validate([
            'installments' => ['required', 'integer', 'min:1', 'max:120'],
        ])['installments'];

        $result = $payments->checkout($device, $installments);

        if (! empty($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }

        if (! empty($result['error'])) {
            return redirect()->route('portal.summary', $device)->withErrors(['payment' => $result['error']]);
        }

        return redirect()->route('portal.code', $device);
    }

    public function callback(Request $request, PaymentService $payments): RedirectResponse
    {
        $trackingId = $request->query('OrderTrackingId');

        if (! $trackingId) {
            return redirect()->route('portal.lookup');
        }

        $payment = $payments->verifyByTracking($trackingId);

        if ($payment && $payment->isPaid()) {
            return redirect()->route('portal.code', $payment->loadMissing('device')->device);
        }

        if ($payment && $payment->device) {
            return redirect()->route('portal.summary', $payment->device)
                ->withErrors(['payment' => 'Payment not completed yet. If you were charged it will reflect shortly.']);
        }

        return redirect()->route('portal.lookup');
    }

    public function ipn(Request $request, PaymentService $payments): JsonResponse
    {
        $trackingId = $request->input('OrderTrackingId', $request->query('OrderTrackingId'));

        if ($trackingId) {
            $payments->verifyByTracking($trackingId);
        }

        return response()->json([
            'orderNotificationType' => $request->input('OrderNotificationType', 'IPNCHANGE'),
            'orderTrackingId' => $trackingId,
            'orderMerchantReference' => $request->input('OrderMerchantReference', $request->query('OrderMerchantReference')),
            'status' => 200,
        ]);
    }

    public function code(Device $device): View
    {
        $device->load('unlockCodes');
        $unlockCode = $device->unlockCodes()->latest()->first();

        return view('client.code', [
            'device' => $device,
            'unlockCode' => $unlockCode,
        ]);
    }
}
