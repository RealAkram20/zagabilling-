<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentRepository
{
    public function recent(int $limit = 5): Collection
    {
        return Payment::with(['client', 'device'])->latest()->limit($limit)->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()
            ->with(['client', 'device'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->where('method_label', $method))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function methodLabels(): array
    {
        return Payment::whereNotNull('method_label')
            ->distinct()
            ->orderBy('method_label')
            ->pluck('method_label')
            ->all();
    }

    public function summaryTotals(): array
    {
        return [
            'collected_today' => (string) Payment::where('status', Payment::STATUS_PAID)
                ->whereDate('paid_at', today())->sum('amount'),
            'pending' => (string) Payment::where('status', Payment::STATUS_PENDING)->sum('amount'),
            'failed_7d' => (string) Payment::where('status', Payment::STATUS_FAILED)
                ->where('created_at', '>=', now()->subDays(7))->sum('amount'),
        ];
    }

    public function monthlyCollected(int $months): array
    {
        return Payment::where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->all();
    }

    public function monthlyScheduled(int $months): array
    {
        return Payment::where('status', Payment::STATUS_PENDING)
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->all();
    }

    public function sumPaidBetween($from, $to): string
    {
        return (string) Payment::where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');
    }

    public function create(array $attributes): Payment
    {
        return Payment::create($attributes);
    }

    public function findByTracking(string $orderTrackingId): ?Payment
    {
        return Payment::where('pesapal_tracking_id', $orderTrackingId)->first();
    }

    public function findByReference(string $reference): ?Payment
    {
        return Payment::where('merchant_reference', $reference)->first();
    }

    public function monthToDateTotal(): string
    {
        return (string) Payment::where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    public function collectedToday(): array
    {
        $base = Payment::where('status', Payment::STATUS_PAID)->whereDate('paid_at', today());

        return [
            'count' => (clone $base)->count(),
            'amount' => (string) (clone $base)->sum('amount'),
        ];
    }
}
