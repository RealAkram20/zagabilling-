<?php

namespace App\Services;

use App\Models\Device;
use App\Repositories\DeviceRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UnlockCodeRepository;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private DeviceRepository $devices,
        private PaymentRepository $payments,
        private UnlockCodeRepository $unlockCodes,
    ) {
    }

    public function metrics(int $periodDays = 30): array
    {
        $statusCounts = $this->devices->statusCounts();
        $total = array_sum($statusCounts);
        $active = $statusCounts[Device::STATUS_ACTIVE] ?? 0;

        $now = now();
        $periodStart = $now->copy()->subDays($periodDays);
        $previousStart = $now->copy()->subDays($periodDays * 2);

        $revenue = (float) $this->payments->sumPaidBetween($periodStart, $now);
        $previousRevenue = (float) $this->payments->sumPaidBetween($previousStart, $periodStart);
        $trend = $previousRevenue > 0 ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1) : null;

        return [
            'period_days' => $periodDays,
            'total_devices' => $total,
            'new_devices' => $this->devices->countRegisteredSince($periodStart),
            'active' => $active,
            'active_share' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
            'locked' => $statusCounts[Device::STATUS_LOCKED] ?? 0,
            'overdue' => ($statusCounts[Device::STATUS_OVERDUE] ?? 0) + ($statusCounts[Device::STATUS_GRACE] ?? 0),
            'revenue' => $revenue,
            'revenue_trend' => $trend,
            'today' => $this->payments->collectedToday(),
        ];
    }

    public function collections(int $months = 12): array
    {
        $collected = $this->payments->monthlyCollected($months);
        $scheduled = $this->payments->monthlyScheduled($months);

        $series = [];
        $cursor = now()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $ym = $cursor->format('Y-m');
            $series[] = [
                'label' => $cursor->format('M'),
                'collected' => (float) ($collected[$ym] ?? 0),
                'scheduled' => (float) ($scheduled[$ym] ?? 0),
            ];
            $cursor->addMonth();
        }

        $max = 0;
        foreach ($series as $point) {
            $max = max($max, $point['collected'], $point['scheduled']);
        }

        return [
            'series' => $series,
            'max' => $max ?: 1,
            'total' => array_sum(array_column($series, 'collected')),
        ];
    }

    public function statusDistribution(): array
    {
        $counts = $this->devices->statusCounts();
        $total = array_sum($counts);
        $divisor = $total ?: 1;

        $palette = [
            Device::STATUS_ACTIVE => ['Active', '#2FA372'],
            Device::STATUS_GRACE => ['Grace period', '#C69214'],
            Device::STATUS_LOCKED => ['Locked', '#C2453D'],
            Device::STATUS_OVERDUE => ['Overdue', '#B23A30'],
            Device::STATUS_CLOSED => ['Closed', '#C3C7CE'],
        ];

        $segments = [];
        foreach ($palette as $status => [$label, $color]) {
            $count = $counts[$status] ?? 0;
            $segments[] = [
                'label' => $label,
                'color' => $color,
                'count' => $count,
                'percent' => round(($count / $divisor) * 100, 2),
            ];
        }

        return ['total' => $total, 'segments' => $segments];
    }

    public function recentPayments(int $limit = 5): Collection
    {
        return $this->payments->recent($limit);
    }

    public function recentUnlockCodes(int $limit = 5): Collection
    {
        return $this->unlockCodes->recent($limit);
    }
}
