<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Module;
use App\Models\RolePermission;
use App\Http\Resources\RoleDTR;

class RoleService extends BaseService
{
    public function __construct(Role $model)
    {
        parent::__construct($model, RoleDTR::class);
    }

    public function createItem(array $data)
    {
        $role = parent::createItem($data);
        $this->savePermissions($role, $data['permissions'] ?? []);
        return $role;
    }

    public function updateItem(array $data)
    {
        $role = parent::updateItem($data);
        $this->savePermissions($role, $data['permissions'] ?? []);
        return $role;
    }

    public function updatePermissions(array $data)
    {
        $role = $this->getById($data['id']);
        $this->savePermissions($role, $data['permissions'] ?? []);
        return $role;
    }

    public function getAllModules()
    {
        $modules = Module::select('id', 'parent_id', 'type', 'name', 'readable', 'writable', 'editable', 'deletable', 'sort_order')
            ->orderBy('sort_order')
            ->get();
        return $this->buildModuleHierarchy($modules);
    }

    public function getSingle($id)
    {
        $role = $this->getById($id);
        $permissions = $this->getRolePermissions($role);
        return ['data' => $role, 'permissions' => $permissions];
    }

    private function buildModuleHierarchy($modules, $parentId = 0)
    {
        $result = [];
        foreach ($modules as $module) {
            if ($module->parent_id == $parentId) {
                $children = $this->buildModuleHierarchy($modules, $module->id);
                if ($children) {
                    $module->children = $children;
                }
                $result[] = $module;
            }
        }
        return $result;
    }

    private function savePermissions(Role $role, array $permissions)
    {
        RolePermission::where('role_id', $role->id)->delete();

        foreach ($permissions as $moduleId => $modulePermissions) {
            RolePermission::create([
                'role_id' => $role->id,
                'module_id' => $moduleId,
                'view' => isset($modulePermissions['view']),
                'add' => isset($modulePermissions['add']),
                'edit' => isset($modulePermissions['edit']),
                'delete' => isset($modulePermissions['delete']),
                'added_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }
    }

    private function getRolePermissions(Role $role)
    {
        $allModules = Module::select('id', 'parent_id', 'type', 'name', 'readable', 'writable', 'editable', 'deletable', 'sort_order')
            ->orderBy('sort_order')
            ->get();
        $rolePermissions = RolePermission::where('role_id', $role->id)->get()->keyBy('module_id');

        return $this->buildPermissionHierarchy($allModules, $rolePermissions);
    }

    private function buildPermissionHierarchy($modules, $rolePermissions, $parentId = 0)
    {
        $result = [];
        foreach ($modules as $module) {
            if ($module->parent_id == $parentId) {
                $permission = $rolePermissions->get($module->id);
                $moduleData = [
                    'id' => $module->id,
                    'name' => $module->name,
                    'type' => $module->type,
                    'parent_id' => $module->parent_id,
                    'readable' => $module->readable,
                    'writable' => $module->writable,
                    'editable' => $module->editable,
                    'deletable' => $module->deletable,
                    'view' => $permission ? $permission->view : false,
                    'add' => $permission ? $permission->add : false,
                    'edit' => $permission ? $permission->edit : false,
                    'delete' => $permission ? $permission->delete : false,
                ];

                $children = $this->buildPermissionHierarchy($modules, $rolePermissions, $module->id);
                if ($children) {
                    $moduleData['children'] = $children;
                }
                $result[] = $moduleData;
            }
        }
        return $result;
    }
}
