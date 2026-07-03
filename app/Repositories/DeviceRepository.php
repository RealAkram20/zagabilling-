<?php

namespace App\Repositories;

use App\Models\Device;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeviceRepository
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Device::query()
            ->with(['client', 'plan'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('account_number', 'like', "%{$search}%")
                        ->orWhere('serial', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['plan_id'] ?? null, fn ($query, $planId) => $query->where('plan_id', $planId))
            ->when($filters['model'] ?? null, fn ($query, $model) => $query->where('model', $model))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Device
    {
        return Device::with(['client', 'plan', 'payments', 'unlockCodes.issuer'])->find($id);
    }

    public function findByAccountNumber(string $accountNumber): ?Device
    {
        return Device::with(['client', 'plan'])
            ->where('account_number', $accountNumber)
            ->first();
    }

    public function statusCounts(): array
    {
        return Device::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function countRegisteredSince($from): int
    {
        return Device::where('created_at', '>=', $from)->count();
    }

    public function inventoryByModel(): \Illuminate\Support\Collection
    {
        return Device::where('status', Device::STATUS_UNASSIGNED)
            ->selectRaw('model, COUNT(*) as total')
            ->groupBy('model')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['model' => $row->model ?: 'Unspecified', 'total' => (int) $row->total]);
    }

    public function distinctModels(): array
    {
        return Device::whereNotNull('model')->distinct()->orderBy('model')->pluck('model')->all();
    }

    public function search(string $term, int $limit = 15): \Illuminate\Support\Collection
    {
        return Device::query()
            ->with('client')
            ->when($term, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('account_number', 'like', "%{$term}%")
                        ->orWhere('serial', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->limit($limit)
            ->get();
    }
}
