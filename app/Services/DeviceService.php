<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Plan;
use App\Models\UnlockCode;
use Illuminate\Support\Str;

class DeviceService
{
    private const ENROLLMENT_VALID_HOURS = 24;

    public function issueEnrollmentCode(Device $device): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (Device::where('enrollment_code', $code)->exists());

        $device->update([
            'enrollment_code' => $code,
            'enrollment_expires_at' => now()->addHours(self::ENROLLMENT_VALID_HOURS),
        ]);

        $this->auditLogger->record(
            'device.enroll_code',
            "Issued an enrollment code for {$device->account_number}",
            $device,
        );

        return $code;
    }

    public function __construct(
        private AuditLogger $auditLogger,
        private UnlockCodeService $unlockCodes,
    ) {
    }

    public function register(array $data): Device
    {
        $device = Device::create([
            'account_number' => ! empty($data['account_number']) ? $data['account_number'] : $this->generateAccountNumber(),
            'serial' => $data['serial'],
            'name' => $data['name'] ?? null,
            'model' => $data['model'] ?? null,
            'price' => $data['price'] ?? 0,
            'status' => Device::STATUS_UNASSIGNED,
            'balance' => 0,
            'bios_password' => $data['bios_password'] ?? null,
            'recovery_key' => $data['recovery_key'] ?? null,
            'hmac_secret' => $this->generateHmacSecret(),
            'uninstall_code' => $data['uninstall_code'] ?? null,
        ]);

        $this->auditLogger->record('device.register', "Registered device {$device->account_number}", $device);

        return $device;
    }

    public function bulkRegister(string $model, array $serials, ?string $name = null, float $price = 0): int
    {
        foreach ($serials as $serial) {
            Device::create([
                'account_number' => $this->generateAccountNumber(),
                'serial' => $serial,
                'name' => $name,
                'model' => $model,
                'price' => $price,
                'status' => Device::STATUS_UNASSIGNED,
                'balance' => 0,
                'hmac_secret' => $this->generateHmacSecret(),
            ]);
        }

        $count = count($serials);
        $this->auditLogger->record('device.bulk_register', "Added {$count} × {$model} to inventory");

        return $count;
    }

    public function update(Device $device, array $data): void
    {
        $attributes = [
            'account_number' => $data['account_number'],
            'serial' => $data['serial'],
            'name' => $data['name'] ?? null,
            'model' => $data['model'] ?? null,
            'price' => $data['price'] ?? $device->price,
        ];

        if (! empty($data['bios_password'])) {
            $attributes['bios_password'] = $data['bios_password'];
        }

        if (! empty($data['recovery_key'])) {
            $attributes['recovery_key'] = $data['recovery_key'];
        }

        if (array_key_exists('uninstall_code', $data)) {
            $attributes['uninstall_code'] = $data['uninstall_code'] ?: null;
        }

        $device->update($attributes);
        $this->auditLogger->record('device.update', "Updated device {$device->account_number}", $device);
    }

    public function enroll(Device $device, array $data): UnlockCode
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $deposit = round((float) $device->price * (float) $plan->deposit_percentage / 100, 2);
        $financed = max((float) $device->price - $deposit, 0);

        $device->update([
            'client_id' => $data['client_id'],
            'plan_id' => $plan->id,
            'balance' => $financed,
            'status' => Device::STATUS_ACTIVE,
            'next_due_at' => $data['next_due_at'] ?? now()->addMonth(),
            'activated_at' => $device->activated_at ?? now(),
        ]);

        $this->auditLogger->record('device.enroll', "Enrolled {$device->account_number} on a plan", $device);

        return $this->unlockCodes->issue($device, UnlockCode::TYPE_FULL, null, auth()->user());
    }

    public function unlock(Device $device): void
    {
        $device->update(['status' => Device::STATUS_ACTIVE]);
        $this->auditLogger->record('device.unlock', "Unlocked device {$device->account_number}", $device);
    }

    public function revealVault(Device $device): array
    {
        $this->auditLogger->record('vault.reveal', "Revealed recovery credentials for {$device->account_number}", $device);

        return [
            'bios_password' => $device->bios_password,
            'recovery_key' => $device->recovery_key,
        ];
    }

    public function revealProvisioning(Device $device): array
    {
        if (! $device->hmac_secret) {
            $device->update(['hmac_secret' => $this->generateHmacSecret()]);
        }

        $this->auditLogger->record('device.provisioning', "Revealed provisioning bundle for {$device->account_number}", $device);

        return [
            'account_number' => $device->account_number,
            'hmac_secret' => $device->hmac_secret,
        ];
    }

    public function revealUninstallCode(Device $device): ?string
    {
        $this->auditLogger->record('device.uninstall_auth', "Revealed uninstall authorization for {$device->account_number}", $device);

        return $device->uninstall_code;
    }

    public function delete(Device $device): void
    {
        $this->auditLogger->record('device.delete', "Deleted device {$device->account_number}", $device);
        $device->delete();
    }

    private function generateAccountNumber(): string
    {
        do {
            $accountNumber = 'ZG-' . random_int(10000, 99999);
        } while (Device::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }

    private function generateHmacSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}
