<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientRepository
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Client::query()
            ->withCount('devices')
            ->withSum('devices as total_balance', 'balance')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Client
    {
        return Client::with('devices.plan')->find($id);
    }

    public function create(array $attributes): Client
    {
        return Client::create($attributes);
    }
}
