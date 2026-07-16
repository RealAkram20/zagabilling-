<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\UnlockCode;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceApiController extends Controller
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function enroll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string',
            'agent_version' => 'nullable|string',
            // Reported by the client from its own firmware (SMBIOS).
            'serial' => 'nullable|string|max:120',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'hostname' => 'nullable|string|max:255',
        ]);

        $device = Device::where('enrollment_code', $data['code'])
            ->where('enrollment_expires_at', '>', now())
            ->first();

        if ($device === null) {
            return response()->json(['message' => 'Invalid or expired enrollment code.'], 422);
        }

        // The machine knows what it is better than whoever typed the record, so its
        // firmware wins. Refuse only when the serial already belongs to a different
        // device, which would otherwise break the unique index.
        $reported = $this->reportedHardware($data);

        if (isset($reported['serial'])) {
            $clash = Device::where('serial', $reported['serial'])
                ->whereKeyNot($device->getKey())
                ->exists();

            if ($clash) {
                return response()->json([
                    'message' => "Serial {$reported['serial']} is already registered to another device.",
                ], 422);
            }
        }

        $device->forceFill($reported + [
            'enrollment_code' => null,
            'enrollment_expires_at' => null,
            'last_seen_at' => now(),
            'agent_version' => $data['agent_version'] ?? $device->agent_version,
        ])->save();

        $token = $device->createToken('device:' . $device->account_number)->plainTextToken;

        $this->auditLogger->record(
            'device.enroll_api',
            "Device {$device->account_number} enrolled over the API",
            $device,
        );

        $device->refresh();

        return response()->json([
            'token' => $token,
            'account_number' => $device->account_number,
            'hmac_secret' => $device->hmac_secret,
            'serial' => $device->serial,
            'model' => $device->model,
            'manufacturer' => $device->manufacturer,
            // The friendly label an operator gave the unit, falling back to the
            // machine's own hostname so the lock screen always has something to show.
            'name' => $device->name ?: $device->hostname,
        ]);
    }

    // Firmware fields the client reported, keeping only the ones it actually knows.
    private function reportedHardware(array $data): array
    {
        $reported = [];

        foreach (['serial', 'model', 'manufacturer', 'hostname'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value !== '') {
                $reported[$field] = $value;
            }
        }

        return $reported;
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $device = $request->user();

        $data = $request->validate([
            'status' => 'nullable|string',
            'lock_deadline' => 'nullable|integer',
            'agent_version' => 'nullable|string',
        ]);

        $device->forceFill([
            'last_seen_at' => now(),
            'agent_version' => $data['agent_version'] ?? $device->agent_version,
        ])->save();

        return response()->json([
            'account_number' => $device->account_number,
            'status' => $device->status,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function token(Request $request): JsonResponse
    {
        $device = $request->user();

        $unlockCode = UnlockCode::where('device_id', $device->id)
            ->orderByDesc('id')
            ->first();

        if ($unlockCode === null) {
            return response()->json(['message' => 'No unlock code is available for this device.'], 404);
        }

        return response()->json([
            'token' => $unlockCode->code,
            'type' => $unlockCode->type,
            'duration_days' => $unlockCode->duration_days,
            'expires_at' => optional($unlockCode->expires_at)->toIso8601String(),
        ]);
    }
}
