<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'avatar_path',
        'email',
        'phone',
        'alt_contact_name',
        'alt_contact_phone',
        'alt_contact_relationship',
        'national_id',
        'address',
    ];

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset($this->avatar_path) : null;
    }

    public function hasAltContact(): bool
    {
        return filled($this->alt_contact_phone);
    }

    public function altContactLabel(): ?string
    {
        if (! $this->hasAltContact()) {
            return null;
        }

        $label = $this->alt_contact_name ?: $this->alt_contact_phone;

        return $this->alt_contact_relationship
            ? "{$label} ({$this->alt_contact_relationship})"
            : $label;
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
