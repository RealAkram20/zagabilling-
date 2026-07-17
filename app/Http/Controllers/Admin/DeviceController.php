<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Repositories\ClientRepository;
use App\Repositories\DeviceRepository;
use App\Repositories\PlanRepository;
use App\Services\DeviceService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function __construct(
        private DeviceRepository $devices,
        private DeviceService $deviceService,
    ) {
    }

    public function index(Request $request, PlanRepository $plans): View
    {
        $filters = $request->only('search', 'status', 'plan_id', 'model');
        $statusCounts = $this->devices->statusCounts();

        return view('admin.devices.index', [
            'devices' => $this->devices->paginateWithFilters($filters),
            'plans' => $plans->active(),
            'models' => $this->devices->distinctModels(),
            'inventoryByModel' => $this->devices->inventoryByModel(),
            'inventory' => $statusCounts[Device::STATUS_UNASSIGNED] ?? 0,
            'filters' => $filters,
        ]);
    }

    public function bulkCreate(): View
    {
        return view('admin.devices.bulk');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'model' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'serials' => ['required', 'string'],
        ]);

        $serials = collect(preg_split('/\r\n|\r|\n/', $data['serials']))
            ->map(fn ($serial) => trim($serial))
            ->filter()
            ->unique()
            ->values();

        if ($serials->isEmpty()) {
            return back()->withInput()->withErrors(['serials' => 'Enter at least one serial number.']);
        }

        $existing = Device::whereIn('serial', $serials)->pluck('serial');
        if ($existing->isNotEmpty()) {
            return back()->withInput()->withErrors(['serials' => 'Already in the system: ' . $existing->implode(', ')]);
        }

        $count = $this->deviceService->bulkRegister($data['model'], $serials->all(), $data['name'] ?? null, (float) $data['price']);

        return redirect()
            ->route('admin.devices.index', ['status' => 'unassigned', 'model' => $data['model']])
            ->with('status', "{$count} × {$data['model']} added to inventory.");
    }

    public function edit(Device $device): View
    {
        return view('admin.devices.edit', ['device' => $device]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate([
            // account_number is deliberately absent: it is the device's identity, is
            // cached in the machine's store, and cannot be changed after the fact.
            'serial' => ['required', 'string', 'max:120', Rule::unique('devices', 'serial')->ignore($device->id)],
            'name' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'bios_password' => ['nullable', 'string', 'max:255'],
            'recovery_key' => ['nullable', 'string', 'max:255'],
            'uninstall_code' => ['nullable', 'string', 'max:60'],
        ]);

        $this->deviceService->update($device, $data);

        return redirect()->route('admin.devices.show', $device)->with('status', 'Device updated.');
    }

    public function create(): View
    {
        return view('admin.devices.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Minted by the device at install and read out of the Zaga app, so the
            // machine and this record agree from the start. Generated here only as a
            // fallback for a device registered before it is installed.
            'account_number' => ['nullable', 'string', 'max:40', 'unique:devices,account_number'],
            // Optional: the device reports its real serial from firmware when it
            // enrolls, so an operator no longer has to read it off a sticker.
            'serial' => ['nullable', 'string', 'max:120', 'unique:devices,serial'],
            'name' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'bios_password' => ['nullable', 'string', 'max:255'],
            'recovery_key' => ['nullable', 'string', 'max:255'],
            'uninstall_code' => ['nullable', 'string', 'max:60'],
        ]);

        $device = $this->deviceService->register($data);

        // Issue the code straight away: registering a device is always followed by
        // provisioning the machine, and the code only becomes valid once the device
        // row exists, so this is the earliest it can honestly be shown.
        $code = $this->deviceService->issueEnrollmentCode($device);

        return redirect()
            ->route('admin.devices.show', $device)
            ->with('status', 'Device registered. Enter the enrollment code on the machine to finish setup.')
            ->with('enrollment_code', $code);
    }

    public function show(Device $device, ClientRepository $clients, PlanRepository $plans): View
    {
        $device->load(['client', 'plan', 'payments', 'unlockCodes.issuer']);

        return view('admin.devices.show', [
            'device' => $device,
            'clients' => $device->isEnrolled() ? collect() : $clients->paginateWithFilters([], 200)->getCollection(),
            'plans' => $device->isEnrolled() ? collect() : $plans->active(),
        ]);
    }

    public function enroll(Request $request, Device $device): RedirectResponse
    {
        if ($device->isEnrolled()) {
            return back()->withErrors(['device' => 'This device is already assigned to a client.']);
        }

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'next_due_at' => ['nullable', 'date'],
        ]);

        $code = $this->deviceService->enroll($device, $data);

        return redirect()
            ->route('admin.devices.show', $device)
            ->with('status', 'Device enrolled. First unlock token generated.')
            ->with('unlock_token', $code->code);
    }

    public function unlock(Device $device): RedirectResponse
    {
        $this->deviceService->unlock($device);

        return back()->with('status', "Device {$device->account_number} unlocked.");
    }

    public function collect(Request $request, Device $device, PaymentService $payments): JsonResponse
    {
        $data = $request->validate([
            'method' => ['required', 'in:cash,mobile_money'],
            'installments' => ['required', 'integer', 'min:1', 'max:120'],
            'phone' => ['required_if:method,mobile_money', 'nullable', 'string', 'max:40'],
        ]);

        if ($data['method'] === 'cash') {
            $code = $payments->collectCash($device, (int) $data['installments']);

            return response()->json(['status' => 'paid', 'code' => $code->code]);
        }

        $result = $payments->collectMobile($device, (int) $data['installments'], $data['phone']);

        if (! empty($result['simulated'])) {
            return response()->json(['status' => 'paid', 'code' => $result['code']]);
        }

        if (! empty($result['redirect_url'])) {
            return response()->json(['status' => 'pending', 'redirect_url' => $result['redirect_url'], 'reference' => $result['reference']]);
        }

        return response()->json(['status' => 'error', 'message' => $result['error'] ?? 'Payment could not be started.'], 422);
    }

    public function paymentStatus(Request $request, Device $device, PaymentService $payments): JsonResponse
    {
        return response()->json($payments->pollByReference((string) $request->query('reference', '')));
    }

    public function search(Request $request): JsonResponse
    {
        $devices = $this->devices->search((string) $request->query('q', ''));

        return response()->json(
            $devices->map(fn (Device $device) => [
                'id' => $device->id,
                'account_number' => $device->account_number,
                'model' => $device->model,
                'price' => (float) $device->price,
                'client' => $device->client->name ?? null,
                'status' => $device->status,
                'available' => $device->client_id === null,
            ])
        );
    }

    public function revealVault(Device $device): JsonResponse
    {
        return response()->json($this->deviceService->revealVault($device));
    }

    public function revealProvisioning(Device $device): JsonResponse
    {
        return response()->json($this->deviceService->revealProvisioning($device));
    }

    // Issues the one-time code a device redeems to provision itself over the API.
    // Mirrors the device:enroll-code command so onboarding never needs a terminal.
    public function issueEnrollCode(Device $device): JsonResponse
    {
        $code = $this->deviceService->issueEnrollmentCode($device);

        return response()->json([
            'code' => $code,
            'expires_at' => $device->fresh()->enrollment_expires_at?->toIso8601String(),
            'expires_human' => $device->fresh()->enrollment_expires_at?->diffForHumans(),
        ]);
    }

    public function uninstallAuthorization(Device $device): RedirectResponse
    {
        $code = $this->deviceService->revealUninstallCode($device);

        if (! $code) {
            return back()->withErrors(['uninstall' => 'No uninstall code recorded yet. Add the code shown by the device app on the Edit page.']);
        }

        return back()->with('status', 'Uninstall authorization code revealed.')->with('uninstall_code', $code);
    }

    public function destroy(Device $device): RedirectResponse
    {
        $this->deviceService->delete($device);

        return redirect()->route('admin.devices.index')->with('status', 'Device deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:devices,id'],
        ]);

        $devices = Device::whereIn('id', $data['ids'])->get();

        // Deleting a financed device takes its payment history with it, and that is
        // the record of money a customer actually handed over. One at a time an
        // operator sees what they are removing; in bulk they do not, so financed
        // devices are left behind and reported rather than quietly destroyed.
        [$financed, $removable] = $devices->partition(fn (Device $device) => $device->isEnrolled());

        foreach ($removable as $device) {
            $this->deviceService->delete($device);
        }

        $message = $removable->isEmpty()
            ? 'No devices were deleted.'
            : "{$removable->count()} device(s) deleted.";

        if ($financed->isNotEmpty()) {
            $accounts = $financed->pluck('account_number')->join(', ');

            return redirect()->route('admin.devices.index')
                ->with('status', $message)
                ->withErrors(['devices' => "Skipped {$financed->count()} device(s) assigned to a client: {$accounts}. Unassign them first — deleting them would destroy their payment history."]);
        }

        return redirect()->route('admin.devices.index')->with('status', $message);
    }
}
