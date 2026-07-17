<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientRepository
{
    /** How many rows a page may show. Anything else falls back to the default. */
    public const PER_PAGE_OPTIONS = [15, 30, 60, 100];

    public const DEFAULT_PER_PAGE = 15;

    /** Sort key => [label, column, direction]. */
    public const SORTS = [
        'recent' => ['Newest first', 'created_at', 'desc'],
        'name' => ['Name A–Z', 'name', 'asc'],
        'balance' => ['Highest balance', 'total_balance', 'desc'],
        'devices' => ['Most devices', 'devices_count', 'desc'],
    ];

    public function paginateWithFilters(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::DEFAULT_PER_PAGE;

        [, $column, $direction] = self::SORTS[$filters['sort'] ?? 'recent'] ?? self::SORTS['recent'];

        return Client::query()
            ->withCount('devices')
            ->withSum('devices as total_balance', 'balance')
            ->when($filters['search'] ?? null, function ($query, $search) {
                // Whoever is looking for a client has one of these to hand: a name, a
                // number off a form, or an ID from a contract. The alternate number is
                // included because a client is often found by whoever answered for them.
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%")
                        ->orWhere('alt_contact_phone', 'like', "%{$search}%")
                        ->orWhere('alt_contact_name', 'like', "%{$search}%");
                });
            })
            // total_balance is an aggregate alias, so it has no table to qualify it.
            ->orderBy($column, $direction)
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
