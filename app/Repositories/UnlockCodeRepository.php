<?php

namespace App\Repositories;

use App\Models\UnlockCode;
use Illuminate\Support\Collection;

class UnlockCodeRepository
{
    public function recent(int $limit = 5): Collection
    {
        return UnlockCode::with(['device.client', 'issuer'])->latest()->limit($limit)->get();
    }

    public function create(array $attributes): UnlockCode
    {
        return UnlockCode::create($attributes);
    }
}
