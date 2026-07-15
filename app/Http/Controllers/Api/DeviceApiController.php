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
        ]);

        $device = Device::where('enrollment_code', $data['code'])
            ->where('enrollment_expires_at', '>', now())
            ->first();

        if ($device === null) {
            return response()->json(['message' => 'Invalid or expired enrollment code.'], 422);
        }

        $device->forceFill([
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

        return response()->json([
            'token' => $token,
            'account_number' => $device->account_number,
            'hmac_secret' => $device->hmac_secret,
            'serial' => $device->serial,
            'model' => $device->model,
            'name' => $device->name,
        ]);
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
