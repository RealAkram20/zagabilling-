<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Device;
use App\Models\Plan;
use App\Repositories\ClientRepository;
use App\Repositories\PlanRepository;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(private ClientRepository $clients)
    {
    }

    public function index(Request $request, PlanRepository $plans): View
    {
        $filters = $request->only('search');

        return view('admin.clients.index', [
            'clients' => $this->clients->paginateWithFilters($filters),
            'plans' => $plans->active(),
            'filters' => $filters,
        ]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $email = (string) $request->query('email', '');
        $valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        return response()->json([
            'valid' => $valid,
            'available' => $valid && ! Client::where('email', $email)->exists(),
        ]);
    }

    public function panel(Client $client, PlanRepository $plans): View
    {
        $client->load(['devices.plan', 'payments' => fn ($query) => $query->latest()->limit(5)]);

        return view('admin.clients._drawer', ['client' => $client, 'plans' => $plans->active()]);
    }

    public function enroll(Request $request, Client $client, DeviceService $deviceService): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', Rule::exists('devices', 'id')->whereNull('client_id')],
            'plan_id' => ['required', 'exists:plans,id'],
            'first_installment_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $device = Device::findOrFail($data['device_id']);
        $plan = Plan::findOrFail($data['plan_id']);

        $code = $deviceService->enroll($device, [
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'next_due_at' => now()->addDays((int) ($data['first_installment_days'] ?? 30)),
        ]);

        return redirect()
            ->route('admin.devices.show', $device)
            ->with('status', 'Installment started — first token generated.')
            ->with('unlock_token', $code->code);
    }

    public function store(Request $request, DeviceService $deviceService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:clients,email'],
            'phone' => ['required', 'string', 'max:40'],
            'national_id' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:1000'],
            'device_id' => ['nullable', Rule::exists('devices', 'id')->whereNull('client_id')],
            'plan_id' => ['nullable', 'required_with:device_id', 'exists:plans,id'],
            'first_installment_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $directory = public_path('uploads/clients');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $file = $request->file('avatar');
            $filename = 'cl_' . now()->timestamp . '_' . Str::random(6) . '.' . $file->extension();
            $file->move($directory, $filename);
            $avatarPath = 'uploads/clients/' . $filename;
        }

        $enrolledDevice = null;
        $token = null;

        DB::transaction(function () use ($data, $deviceService, $avatarPath, &$enrolledDevice, &$token) {
            $client = $this->clients->create([
                'name' => $data['name'],
                'avatar_path' => $avatarPath,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'national_id' => $data['national_id'],
                'address' => $data['address'],
            ]);

            if (! empty($data['device_id'])) {
                $device = Device::findOrFail($data['device_id']);
                $plan = Plan::findOrFail($data['plan_id']);

                $code = $deviceService->enroll($device, [
                    'client_id' => $client->id,
                    'plan_id' => $plan->id,
                    'next_due_at' => now()->addDays((int) ($data['first_installment_days'] ?? 30)),
                ]);

                $enrolledDevice = $device;
                $token = $code->code;
            }
        });

        if ($enrolledDevice) {
            return redirect()
                ->route('admin.devices.show', $enrolledDevice)
                ->with('status', 'Deal closed — device enrolled and the first token is ready to share.')
                ->with('unlock_token', $token);
        }

        return redirect()->route('admin.clients.index')->with('status', 'Client added.');
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('clients', 'email')->ignore($client->id)],
            'phone' => ['required', 'string', 'max:40'],
            'national_id' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $attributes = collect($data)->except('avatar')->all();

        if ($request->hasFile('avatar')) {
            $directory = public_path('uploads/clients');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $file = $request->file('avatar');
            $filename = 'cl_' . now()->timestamp . '_' . Str::random(6) . '.' . $file->extension();
            $file->move($directory, $filename);
            $attributes['avatar_path'] = 'uploads/clients/' . $filename;
        }

        $client->update($attributes);

        return redirect()->route('admin.clients.index')->with('status', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->releaseAndDelete([$client->id]);

        return redirect()->route('admin.clients.index')->with('status', 'Client deleted. Their devices returned to inventory.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:clients,id'],
        ]);

        $this->releaseAndDelete($data['ids']);

        $count = count($data['ids']);

        return redirect()->route('admin.clients.index')->with('status', "{$count} client(s) deleted. Their devices returned to inventory.");
    }

    private function releaseAndDelete(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            Device::whereIn('client_id', $ids)->update([
                'client_id' => null,
                'plan_id' => null,
                'status' => Device::STATUS_UNASSIGNED,
                'balance' => 0,
                'next_due_at' => null,
            ]);

            Client::whereIn('id', $ids)->delete();
        });
    }
}
