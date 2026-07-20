<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
    ];

    private const ABILITIES = [
        'manage-users' => [User::ROLE_SUPER_ADMIN],
        'manage-settings' => [User::ROLE_SUPER_ADMIN],
        'manage-devices' => [User::ROLE_SUPER_ADMIN, User::ROLE_OPERATOR],
        'manage-plans' => [User::ROLE_SUPER_ADMIN, User::ROLE_OPERATOR],
        'manage-clients' => [User::ROLE_SUPER_ADMIN, User::ROLE_OPERATOR],
        'reveal-vault' => [User::ROLE_SUPER_ADMIN, User::ROLE_OPERATOR],
        'reveal-provisioning' => [User::ROLE_SUPER_ADMIN],
        'view-audit' => [User::ROLE_SUPER_ADMIN, User::ROLE_OPERATOR],
        'issue-codes' => [User::ROLE_SUPER_ADMIN, User::ROLE_OPERATOR, User::ROLE_SUPPORT],
    ];

    public function boot()
    {
        $this->registerPolicies();

        foreach (self::ABILITIES as $ability => $roles) {
            Gate::define($ability, fn (User $user) => in_array($user->role, $roles, true));
        }
    }
}
