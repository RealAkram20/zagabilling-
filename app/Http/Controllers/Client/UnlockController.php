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
        $request->validate(['account_number' => ['required', 'string', 'max:40']]);

        $device = $this->devices->findByAccountNumber($request->input('account_number'));

        if (! $device) {
            return back()
                ->withInput()
                ->withErrors(['account_number' => 'No device found for that account number. Check the number on your locked screen and try again.']);
        }

        if (! $device->isEnrolled()) {
            return back()
                ->withInput()
                ->withErrors(['account_number' => 'We found your device, but it is not set up for payments yet. Please call support to finish setup.']);
        }

        return redirect()->route('portal.summary', $device);
    }

    public function summary(Request $request, Device $device): View|RedirectResponse
    {
        if (! $device->isEnrolled()) {
            return redirect()->route('portal.lookup')->withErrors(['account_number' => 'This device is not active for payments.']);
        }

        $device->load(['client', 'plan']);

        $codeAccessible = $this->hasCodeAccess($request, $device);

        $lastCode = $codeAccessible
            ? $device->unlockCodes()->where('expires_at', '>', now())->latest()->first()
            : null;

        return view('client.summary', ['device' => $device, 'lastCode' => $lastCode]);
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

        $this->grantCodeAccess($request, $device);

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
            $device = $payment->loadMissing('device')->device;
            $this->grantCodeAccess($request, $device);

            return redirect()->route('portal.code', $device);
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
        $reference = $request->input('OrderMerchantReference', $request->query('OrderMerchantReference'));

        if ($trackingId) {
            $payments->verifyByTracking($trackingId, $reference ?: null);
        }

        return response()->json([
            'orderNotificationType' => $request->input('OrderNotificationType', 'IPNCHANGE'),
            'orderTrackingId' => $trackingId,
            'orderMerchantReference' => $request->input('OrderMerchantReference', $request->query('OrderMerchantReference')),
            'status' => 200,
        ]);
    }

    public function code(Request $request, Device $device): View|RedirectResponse
    {
        if (! $this->hasCodeAccess($request, $device)) {
            return redirect()->route('portal.summary', $device)->withErrors([
                'account_number' => 'For your security, unlock codes are only shown right after a payment. Complete a payment or contact support to retrieve your code.',
            ]);
        }

        $device->load('unlockCodes');
        $unlockCode = $device->unlockCodes()->latest()->first();

        return view('client.code', [
            'device' => $device,
            'unlockCode' => $unlockCode,
        ]);
    }

    /**
     * Mark the current portal session as authorised to view a device's unlock
     * code. Set only after a payment for that device completes, so the code
     * page cannot be reached by enumerating account numbers.
     */
    private function grantCodeAccess(Request $request, Device $device): void
    {
        $request->session()->put($this->codeAccessKey($device), now()->addMinutes(30)->getTimestamp());
    }

    private function hasCodeAccess(Request $request, Device $device): bool
    {
        $expires = $request->session()->get($this->codeAccessKey($device));

        return is_int($expires) && $expires >= now()->getTimestamp();
    }

    private function codeAccessKey(Device $device): string
    {
        return 'portal.code_access.' . $device->getKey();
    }
}
