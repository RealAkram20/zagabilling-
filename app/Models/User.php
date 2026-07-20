<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_OPERATOR = 'operator';
    public const ROLE_SUPPORT = 'support';

    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'role',
        'two_factor_enabled',
        'password',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset($this->avatar_path) : null;
    }

    public function roleLabel(): string
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_OPERATOR => 'Operator',
            self::ROLE_SUPPORT => 'Support',
        ][$this->role] ?? 'Operator';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
    ];
}
