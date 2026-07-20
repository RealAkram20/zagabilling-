<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BillingStatusService
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function statusFor(Device $device, ?Carbon $today = null): ?string
    {
        if (! $device->isEnrolled() || $device->status === Device::STATUS_CLOSED) {
            return null;
        }

        if ($device->next_due_at === null) {
            return null;
        }

        $today = ($today ?? Carbon::today())->startOfDay();
        $due = $device->next_due_at->copy()->startOfDay();

        if ($today->lte($due)) {
            return Device::STATUS_ACTIVE;
        }

        $graceDays = (int) ($device->plan?->grace_days ?? 0);

        return $today->lte($due->copy()->addDays($graceDays))
            ? Device::STATUS_GRACE
            : Device::STATUS_OVERDUE;
    }

    public function daysPastDue(Device $device, ?Carbon $today = null): int
    {
        if ($device->next_due_at === null) {
            return 0;
        }

        $today = ($today ?? Carbon::today())->startOfDay();
        $due = $device->next_due_at->copy()->startOfDay();

        return $today->gt($due) ? $due->diffInDays($today) : 0;
    }

    public function refreshAll(?Carbon $today = null): Collection
    {
        $changed = collect();

        Device::with('plan')
            ->whereNotNull('client_id')
            ->whereNotNull('plan_id')
            ->where('status', '!=', Device::STATUS_CLOSED)
            ->chunkById(200, function (Collection $devices) use ($today, $changed) {
                foreach ($devices as $device) {
                    $status = $this->statusFor($device, $today);

                    if ($status === null || $status === $device->status) {
                        continue;
                    }

                    $from = $device->status;
                    $device->forceFill(['status' => $status])->save();

                    $this->auditLogger->record(
                        'device.status_' . $status,
                        "Device {$device->account_number} moved from {$from} to {$status}",
                        $device,
                        ['from' => $from, 'to' => $status, 'days_past_due' => $this->daysPastDue($device, $today)],
                    );

                    $changed->push($device);
                }
            });

        return $changed;
    }

    public function behind(): Collection
    {
        return Device::with(['client', 'plan'])
            ->whereNotNull('client_id')
            ->whereIn('status', [Device::STATUS_GRACE, Device::STATUS_OVERDUE, Device::STATUS_LOCKED])
            ->orderBy('next_due_at')
            ->get();
    }
}
