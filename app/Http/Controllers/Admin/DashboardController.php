<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const PERIODS = [7, 30, 90];

    public function __construct(private DashboardService $dashboard)
    {
    }

    public function index(Request $request): View
    {
        $period = (int) $request->integer('period', 30);
        if (! in_array($period, self::PERIODS, true)) {
            $period = 30;
        }

        return view('admin.dashboard', [
            'period' => $period,
            'periods' => self::PERIODS,
            'metrics' => $this->dashboard->metrics($period),
            'collections' => $this->dashboard->collections(),
            'distribution' => $this->dashboard->statusDistribution(),
            'recentPayments' => $this->dashboard->recentPayments(),
            'recentUnlockCodes' => $this->dashboard->recentUnlockCodes(),
        ]);
    }
}
