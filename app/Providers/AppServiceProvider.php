<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Cada permissão nomeada vira um Gate, para que a verificação seja a
        // idiomática do Laravel (`$user->can('keys.manage')`) em qualquer lugar
        // - telas, políticas ou middleware - sem espalhar leitura do array.
        foreach (array_keys(User::PERMISSIONS) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }
}
