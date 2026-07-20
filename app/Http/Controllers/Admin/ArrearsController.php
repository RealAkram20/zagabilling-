<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\BillingStatusService;
use Illuminate\View\View;

class ArrearsController extends Controller
{
    public function __construct(private BillingStatusService $billing)
    {
    }

    public function index(): View
    {
        $devices = $this->billing->behind()
            ->map(function (Device $device) {
                $device->days_past_due = $this->billing->daysPastDue($device);
                return $device;
            })
            ->sortByDesc('days_past_due')
            ->values();

        return view('admin.arrears', [
            'devices' => $devices,
            'graceCount' => $devices->where('status', Device::STATUS_GRACE)->count(),
            'overdueCount' => $devices->where('status', Device::STATUS_OVERDUE)->count(),
            'lockedCount' => $devices->where('status', Device::STATUS_LOCKED)->count(),
        ]);
    }
}
