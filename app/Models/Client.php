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
        'national_id',
        'address',
    ];

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset($this->avatar_path) : null;
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
