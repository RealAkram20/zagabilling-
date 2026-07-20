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
    private const ABILITY_HEARTBEAT = 'device:heartbeat';
    private const ABILITY_TOKEN = 'device:token';

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function enroll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string',
            'agent_version' => 'nullable|string',
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

        if (! $device->isEnrolled()) {
            return response()->json([
                'message' => "Device {$device->account_number} is not on a payment plan yet. "
                    . 'Assign a client and plan on the portal, then enroll.',
            ], 422);
        }

        $reported = $this->reportedHardware($data);

        // Bind enrollment to the hardware it was issued for: if the device was
        // registered with a known serial, the machine redeeming the code must
        // present that same serial. Stops a leaked code being used to enroll a
        // different machine (silent account takeover of the real device).
        if (filled($device->serial) && isset($reported['serial'])
            && strcasecmp(trim($device->serial), $reported['serial']) !== 0) {
            $this->auditLogger->record(
                'device.enroll_serial_mismatch',
                "Rejected enrollment for {$device->account_number}: reported serial does not match the registered serial.",
                $device,
            );

            return response()->json([
                'message' => 'This enrollment code is registered to a different device. Contact support.',
            ], 422);
        }

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

        $device->tokens()->delete();

        // Scope the token to exactly what the offline agent needs — checking in
        // and pulling its own unlock code — so a leaked token can do nothing more.
        $token = $device->createToken(
            'device:' . $device->account_number,
            [self::ABILITY_HEARTBEAT, self::ABILITY_TOKEN],
        )->plainTextToken;

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
            'grace_days' => (int) ($device->plan?->grace_days ?? 0),
            'name' => $device->name ?: $device->hostname,
        ]);
    }

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

        if (! $device->tokenCan(self::ABILITY_HEARTBEAT)) {
            return response()->json(['message' => 'This token is not permitted to check in.'], 403);
        }

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
            // Repeated on every check-in so a later plan change still reaches
            // devices that enrolled long ago.
            'grace_days' => (int) ($device->plan?->grace_days ?? 0),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function token(Request $request): JsonResponse
    {
        $device = $request->user();

        if (! $device->tokenCan(self::ABILITY_TOKEN)) {
            return response()->json(['message' => 'This token is not permitted to fetch unlock codes.'], 403);
        }

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
