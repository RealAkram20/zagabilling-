<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Works out where each financed device stands against its payment schedule.
 *
 * The device locks itself offline, from the deadline baked into the last unlock
 * token it redeemed, and never asks the portal's permission. This is the other half:
 * the portal's own view of who has fallen behind, so an admin has someone to call
 * before the machine locks and the customer is stranded.
 */
class BillingStatusService
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    /**
     * The status a device should be in today, or null when its schedule says nothing.
     *
     * Unassigned and closed devices are left alone: neither is on a payment schedule,
     * so neither can be behind on one.
     */
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

        // The plan's grace window is a real part of the agreement, not a rounding
        // allowance: a device is only overdue once it has run out.
        $graceDays = (int) ($device->plan?->grace_days ?? 0);

        return $today->lte($due->copy()->addDays($graceDays))
            ? Device::STATUS_GRACE
            : Device::STATUS_OVERDUE;
    }

    /**
     * How many days past due, counting from the due date itself (0 when not past).
     */
    public function daysPastDue(Device $device, ?Carbon $today = null): int
    {
        if ($device->next_due_at === null) {
            return 0;
        }

        $today = ($today ?? Carbon::today())->startOfDay();
        $due = $device->next_due_at->copy()->startOfDay();

        return $today->gt($due) ? $due->diffInDays($today) : 0;
    }

    /**
     * Brings every financed device's status in line with its schedule.
     *
     * Returns the devices whose status actually moved, so a caller can report the
     * change rather than the whole fleet.
     */
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

                    // Worth an audit line: this is the record of when a customer fell
                    // behind, which is exactly what gets questioned later.
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

    /**
     * Everyone who is behind, worst first, with the client loaded so an admin can
     * pick up the phone without another query.
     */
    public function behind(): Collection
    {
        return Device::with(['client', 'plan'])
            ->whereNotNull('client_id')
            ->whereIn('status', [Device::STATUS_GRACE, Device::STATUS_OVERDUE, Device::STATUS_LOCKED])
            ->orderBy('next_due_at')
            ->get();
    }
}
