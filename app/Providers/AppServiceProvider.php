<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale('ar');

        // Register Shield-generated policies, including RolePolicy.
        FilamentShield::enforcePolicies();

        // Admins have full access; policies only gate the other roles.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
    }
}
