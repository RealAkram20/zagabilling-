<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Payment;
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
        private OfflineEnrollCodec $offlineEnrollCodec,
    ) {
    }

    public function offlineEnrollCode(Device $device): string
    {
        if (! $device->hmac_secret) {
            $device->update(['hmac_secret' => $this->generateHmacSecret()]);
        }

        $this->auditLogger->record(
            'device.offline_enroll_code',
            "Issued an offline enrollment code for {$device->account_number}",
            $device,
        );

        return $this->offlineEnrollCodec->encode(
            $device->hmac_secret,
            $device->account_number,
            (int) ($device->plan?->grace_days ?? 0),
        );
    }

    public function register(array $data): Device
    {
        $device = Device::create([
            'account_number' => ! empty($data['account_number'])
                ? strtoupper(trim($data['account_number']))
                : $this->generateAccountNumber(),
            'serial' => ! empty($data['serial']) ? $data['serial'] : null,
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
        $device->setRelation('plan', $plan);
        $deposit = $device->depositAmount();
        $financed = $device->financedAmount();

        $device->update([
            'client_id' => $data['client_id'],
            'plan_id' => $plan->id,
            'balance' => $financed,
            'status' => Device::STATUS_ACTIVE,
            'activated_at' => $device->activated_at ?? now(),
        ]);

        $this->auditLogger->record('device.enroll', "Enrolled {$device->account_number} on a plan", $device);

        $payment = Payment::create([
            'device_id' => $device->id,
            'client_id' => $data['client_id'],
            'amount' => $deposit,
            'installments_count' => 1,
            'status' => Payment::STATUS_PAID,
            'method' => 'deposit',
            'method_label' => 'Deposit',
            'paid_at' => now(),
        ]);

        return $this->unlockCodes->issue($device, UnlockCode::TYPE_FULL, $payment, auth()->user());
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
            'grace_days' => (int) ($device->plan?->grace_days ?? 0),
        ];
    }

    public function exportProvisioningBundle(Device $device): array
    {
        if (! $device->hmac_secret) {
            $device->update(['hmac_secret' => $this->generateHmacSecret()]);
        }

        $this->auditLogger->record(
            'device.provisioning_export',
            "Exported an offline provisioning bundle for {$device->account_number}",
            $device,
        );

        return [
            'format' => 'zaga.provisioning.v1',
            'account_number' => $device->account_number,
            'hmac_secret' => $device->hmac_secret,
            'serial' => $device->serial,
            'model' => $device->model,
            'name' => $device->name,
            'grace_days' => (int) ($device->plan?->grace_days ?? 0),
            'issued_at' => now()->toIso8601String(),
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
        // Non-enumerable: ~4.3 billion values (16^8) instead of a 5-digit space,
        // so account numbers can't be brute-forced against the public portal.
        do {
            $accountNumber = 'ZG-' . strtoupper(bin2hex(random_bytes(4)));
        } while (Device::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }

    private function generateHmacSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}
