<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Console\Command;

class IssueEnrollmentCode extends Command
{
    protected $signature = 'device:enroll-code {account : The device account number, e.g. ZG-40000}';

    protected $description = 'Issue a one-time enrollment code a device redeems to provision itself over the API';

    public function handle(DeviceService $devices): int
    {
        $device = Device::where('account_number', $this->argument('account'))->first();

        if ($device === null) {
            $this->error("No device found for {$this->argument('account')}.");
            return self::FAILURE;
        }

        $code = $devices->issueEnrollmentCode($device);

        $this->info("Enrollment code for {$device->account_number}: {$code}");
        $this->line('Valid for 24 hours. Redeem it once from the device installer.');

        return self::SUCCESS;
    }
}
