<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\User' => 'App\Policies\UserPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('admin-only', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('staff-only', function ($user) {
            return in_array($user->role, ['admin', 'staff']);
        });

        Gate::define('active-only', function ($user) {
            return $user->status === 'active';
        });
    }
}
