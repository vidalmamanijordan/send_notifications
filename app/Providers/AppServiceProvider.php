<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // El superadmin tiene acceso a todo sin importar los permisos asignados
        Gate::before(function ($user, string $ability) {
            if ($user->hasRole('superadmin')) {
                return true;
            }
        });
    }
}
