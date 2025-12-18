<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
class ModulePermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
       //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('modulePermission', function ($user, $moduleId, $action) {
            $permissionMap = [
                'view' => 'view',
                'add' => 'add',
                'edit' => 'edit',
                'delete' => 'delete',
                'restore' => 'delete',
            ];

            $permission = $permissionMap[$action] ?? null;

            if (!$permission) {
                return false;
            }

            // if ($user->is_super_admin) {
            //     return true;
            // }

            return $user->hasPermission($moduleId, $permission);
        });
    }
}
