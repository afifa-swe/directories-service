<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\\Models\\Model' => 'App\\Policies\\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // In this version of Passport the routes are registered by the
        // Laravel\Passport\PassportServiceProvider automatically. Calling
        // Passport::routes() is not available and causes a fatal error.
        // If you want to disable automatic route registration, call:
        // Passport::ignoreRoutes();
    }
}
