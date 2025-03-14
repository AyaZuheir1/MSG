<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('manage-article', function ($user) {
            return $user->role == 'admin';
        });
        Gate::define('manage-doctor-requests', function ($user) {
            return $user->role == 'admin';
        });
        Gate::define('manage-schedule', function ($user) {
            return $user->role == 'doctor';
        });
        Gate::define('can-rate', function ($user) {
            return $user->role == 'patient';
        });
        Gate::define('manage-their-schedule', function ($user) {
            return $user->role == 'patient';
        });
    }
}
