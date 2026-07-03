<?php

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository
{
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user')
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): AuditLog
    {
        return AuditLog::create($attributes);
    }
}
