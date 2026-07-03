<?php

namespace App\Repositories;

use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PlanRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Plan::withCount('devices')->latest()->paginate($perPage);
    }

    public function active(): Collection
    {
        return Plan::where('is_active', true)->orderBy('term_months')->get();
    }

    public function find(int $id): ?Plan
    {
        return Plan::find($id);
    }

    public function create(array $attributes): Plan
    {
        return Plan::create($attributes);
    }
}
