<?php

namespace App\Console\Commands;

use App\Services\BillingStatusService;
use Illuminate\Console\Command;

class RefreshDeviceStatuses extends Command
{
    protected $signature = 'devices:refresh-statuses';

    protected $description = 'Move financed devices to grace or overdue as their payment dates pass';

    public function handle(BillingStatusService $billing): int
    {
        $changed = $billing->refreshAll();

        if ($changed->isEmpty()) {
            $this->info('No device statuses changed.');
            return self::SUCCESS;
        }

        $this->info("{$changed->count()} device status(es) changed:");
        $this->table(
            ['Account', 'Client', 'Status', 'Due', 'Days past'],
            $changed->map(fn ($device) => [
                $device->account_number,
                $device->client?->name ?? '—',
                $device->status,
                $device->next_due_at?->toDateString() ?? '—',
                $billing->daysPastDue($device),
            ])->all(),
        );

        return self::SUCCESS;
    }
}
