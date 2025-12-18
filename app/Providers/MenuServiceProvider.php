<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;

class MenuServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        View::composer('layouts.includes.sidebar', function ($view) {
            $user = Auth::user();
            $menu = $this->generateMenu($user);
            $view->with('menu', $menu);
        });
    }

    private function generateMenu($user)
    {
        $modules = Module::with(['translations' => function($query) {
            $query->where('locale', app()->getLocale());
        }])
        ->where('show_in_menu', 1)
        ->orderBy('sort_order')
        ->get();

        $menu = $this->buildMenuHierarchy($modules, $user);

        array_unshift($menu, [
            'id' => 'home',
            'name' => __('common.menu_label.home'),
            'url' => 'home',
            'icon' => 'mdi mdi-home',
            'slug' => 'home',
        ]);

        // Add super admin section
        if ($user->is_super_admin) {
            $menu[] = [
                'id' => 'super_admin_section',
                'name' => __('common.menu_label.dev_only'),
                'type' => 'section'
            ];

            // $menu[] = [
            //     'id' => 'modules_management',
            //     'name' => 'Modules',
            //     'url' => '/modules/list',
            //     'icon' => 'mdi mdi-view-grid-plus',
            //     'slug' => 'modules-management'
            // ];

            // $menu[] = [
            //     'id' => 'module_groups_management',
            //     'name' => 'Module Groups',
            //     'url' => '/module-groups/list',
            //     'icon' => 'mdi mdi-view-grid',
            //     'slug' => 'module-groups-management'
            // ];

            // wrap these in a menu item as these should be submenu
            $menu[] = [
                'id' => 'modules_management',
                'name' => __('common.menu_label.modules'),
                'url' => 'modules/list',
                'icon' => 'mdi mdi-view-grid-plus',
                'slug' => 'modules-management',
                'submenu' => [
                    [
                        'id' => 'module_groups_management',
                        'name' => __('common.menu_label.module_groups'),
                        'url' => 'module-groups/list',
                        'icon' => 'mdi mdi-view-grid',
                        'slug' => 'module-groups-management'
                    ],
                    [
                        'id' => 'module-generator',
                        'name' => __('common.menu_label.module_generator'),
                        'url' => 'module-generator',
                        'icon' => 'mdi mdi-cog-transfer',
                        'slug' => 'module-generator'
                    ]
                ]
            ];

        }

        return $menu;
    }

    private function buildMenuHierarchy($modules, $user, $parentId = 0)
    {
        $menu = [];

        foreach ($modules as $module) {
            if ($module->parent_id == $parentId) {
                $menuItem = $this->createMenuItem($module, $user);
                if ($menuItem) {
                    $children = $this->buildMenuHierarchy($modules, $user, $module->id);
                    if (!empty($children)) {
                        $menuItem['submenu'] = $children;
                    }
                    $menu[] = $menuItem;
                }
            }
        }

        return $menu;
    }

    private function createMenuItem($module, $user)
    {
        if ($user->role == null) {
            return null;
        }

        if (!$user->is_super_admin && !$user->hasPermission($module->id, 'view')) {
            return null;
        }

        $translation = $module->translations->first();

        return [
            'id' => $module->id,
            'name' => $translation ? $translation->plural_name : $module->name,
            'url' => $module->url,
            'icon' => 'mdi mdi-' . $module->icon,
            'slug' => $module->slug,
        ];
    }
}
