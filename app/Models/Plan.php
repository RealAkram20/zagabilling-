<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'term_months',
        'deposit_percentage',
        'cadence',
        'grace_days',
        'is_active',
    ];

    public function cadenceLabel(): string
    {
        return [
            'monthly' => 'monthly',
            'biweekly' => 'bi-weekly',
            'weekly' => 'weekly',
        ][$this->cadence] ?? 'monthly';
    }

    public function cadenceDays(): int
    {
        return [
            'monthly' => 30,
            'biweekly' => 14,
            'weekly' => 7,
        ][$this->cadence] ?? 30;
    }

    public function periodLabel(): string
    {
        return [
            'monthly' => 'month',
            'biweekly' => 'fortnight',
            'weekly' => 'week',
        ][$this->cadence] ?? 'month';
    }

    protected $casts = [
        'deposit_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
