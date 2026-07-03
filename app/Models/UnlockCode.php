<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnlockCode extends Model
{
    use HasFactory;

    public const TYPE_FULL = 'full';
    public const TYPE_GRACE = 'grace';

    protected $fillable = [
        'device_id',
        'payment_id',
        'issued_by',
        'code',
        'counter',
        'duration_days',
        'type',
        'expires_at',
        'redeemed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    protected $hidden = [
        'code',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
