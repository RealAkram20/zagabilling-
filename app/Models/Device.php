<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Device extends Model
{
    use HasApiTokens, HasFactory;

    public const STATUS_UNASSIGNED = 'unassigned';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_GRACE = 'grace';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'account_number',
        'serial',
        'name',
        'model',
        'manufacturer',
        'hostname',
        'price',
        'client_id',
        'plan_id',
        'status',
        'balance',
        'next_due_at',
        'bios_password',
        'recovery_key',
        'hmac_secret',
        'unlock_counter',
        'uninstall_code',
        'enrollment_code',
        'enrollment_expires_at',
        'last_seen_at',
        'agent_version',
        'activated_at',
        'closed_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'balance' => 'decimal:2',
        'next_due_at' => 'date',
        'enrollment_expires_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'activated_at' => 'datetime',
        'closed_at' => 'datetime',
        'bios_password' => 'encrypted',
        'recovery_key' => 'encrypted',
        'hmac_secret' => 'encrypted',
        'uninstall_code' => 'encrypted',
    ];

    protected $hidden = [
        'bios_password',
        'recovery_key',
        'hmac_secret',
        'uninstall_code',
        'enrollment_code',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function unlockCodes(): HasMany
    {
        return $this->hasMany(UnlockCode::class);
    }

    public function getRouteKeyName(): string
    {
        return 'account_number';
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isEnrolled(): bool
    {
        return $this->client_id !== null && $this->plan_id !== null;
    }

    public function depositAmount(): float
    {
        $percentage = (float) ($this->plan?->deposit_percentage ?? 0);

        return round((float) $this->price * $percentage / 100, 2);
    }

    public function financedAmount(): float
    {
        return max((float) $this->price - $this->depositAmount(), 0);
    }

    public function installmentAmount(): float
    {
        $term = (int) ($this->plan?->term_months ?? 0);

        return $term > 0 ? round($this->financedAmount() / $term, 2) : 0.0;
    }

    public function progress(): array
    {
        $total = (int) ($this->plan?->term_months ?? 0);
        $paidInstallments = (int) $this->payments
            ->where('status', Payment::STATUS_PAID)
            ->sum('installments_count');
        $paid = min($paidInstallments, $total);

        return [
            'paid' => $paid,
            'total' => $total,
            'current' => $paid < $total ? $paid + 1 : $total,
            'remaining' => max($total - $paid, 0),
        ];
    }
}
