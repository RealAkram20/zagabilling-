<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Device;
use App\Repositories\AuditLogRepository;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    private const NOTIFY = [
        'device.lock' => ['device.locked', 'Device locked'],
        'device.enroll' => ['device.enrolled', 'Device enrolled'],
        'device.register' => ['device.registered', 'Device registered'],
        'device.unlock' => ['device.unlocked', 'Device unlocked'],
        'payment.verified' => ['payment.received', 'Payment received'],
        'unlock_code.issue' => ['unlock.issued', 'Unlock code issued'],
        'device.uninstall_auth' => ['device.uninstall', 'Uninstall authorized'],
    ];

    public function __construct(private AuditLogRepository $auditLogs)
    {
    }

    public function record(string $action, string $description, ?Model $auditable = null, array $metadata = []): AuditLog
    {
        $log = $this->auditLogs->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'ip_address' => request()->ip(),
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);

        $this->maybeNotify($action, $description, $auditable);

        return $log;
    }

    private function maybeNotify(string $action, string $description, ?Model $auditable): void
    {
        if (! isset(self::NOTIFY[$action])) {
            return;
        }

        [$type, $title] = self::NOTIFY[$action];
        $link = $auditable instanceof Device ? route('admin.devices.show', $auditable) : null;

        app(NotificationService::class)->push($type, $title, $description, $link, auth()->id());
    }
}
