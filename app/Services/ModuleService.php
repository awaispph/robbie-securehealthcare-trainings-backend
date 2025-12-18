<?php

namespace App\Services;

use App\Models\Module;
use App\Http\Resources\ModulesDTR;
use App\Models\ModuleGroup;

class ModuleService extends BaseService
{

    public function __construct(Module $model)
    {
        parent::__construct($model, ModulesDTR::class);
    }

    public function getItems($request, $archived = false, $searchColumns = ['name', 'url', 'slug'])
    {
        $query = $archived ? Module::onlyTrashed() : Module::query();

        $column_index = $request->order[0]['column'];
        $columnName = $request->columns[$column_index]['data'];
        $column_sort_order = $request->order[0]['dir'];
        $search_value = $request->search['value'];
        if (!empty($search_value)) {
            $query->where(function ($q) use ($search_value, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', '%' . $search_value . '%');
                }
            });
        }

        $records_total = $archived ? Module::onlyTrashed()->count() : Module::count();
        $records_filtered = $query->count();
        $modules = $query->orderBy($columnName, $column_sort_order)->get();
        $hierarchicalModules = $this->buildModuleHierarchy($modules);

        $data = [];
        $this->flattenHierarchy($hierarchicalModules, $data);

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => ModulesDTR::collection($data)
        ]);
    }

    private function buildModuleHierarchy($modules, $parentId = 0, $level = 0)
    {
        $result = [];
        foreach ($modules as $module) {
            if ($module->parent_id == $parentId) {
                $module->level = $level;
                $children = $this->buildModuleHierarchy($modules, $module->id, $level + 1);
                if ($children) {
                    $module->children = $children;
                }
                $result[] = $module;
            }
        }
        return $result;
    }

    private function flattenHierarchy($modules, &$result, $prefix = '')
    {
        foreach ($modules as $module) {
            $module->name = $prefix . $module->name;
            $result[] = $module;
            if (isset($module->children)) {
                $this->flattenHierarchy($module->children, $result, $prefix . '&nbsp;&nbsp;&nbsp;&nbsp;');
            }
        }
    }

    public function getAllModules(){

        return Module::select('id','name')->orderBy('sort_order', 'ASC')->get();
    }

    public function getAllModuleGroups(){

        return ModuleGroup::select('id','title')->orderBy('sort_order', 'ASC')->get();
    }

    public function getSingle($id)
    {
        $modules = Module::orderBy('sort_order', 'ASC')->get();
        $module = Module::with('translations')->find($id);

        // Format translations for form
        if ($module) {
            $module->translations = $module->translations->keyBy('locale');
        }

        return [
            'data' => $module,
            'AllModules' => $modules,
            'AllModulesGroups' => $this->getAllModuleGroups()
        ];
    }

    protected function formatDataForResponse($data)
    {
        return [
            'id' => $data->id,
            'name' => $data->name,
            'url' => $data->url,
            'icon' => $data->icon,
            'slug' => $data->slug,
            'sort_order' => $data->sort_order,
            'description' => $data->description,
            'show_in_menu' => $data->show_in_menu,
            'type' => $data->type,
            'module_type' => $data->module_type,
            'group_id' => $data->group_id,
            'parent_id' => $data->parent_id,
            'readable' => $data->readable,
            'writable' => $data->writable,
            'editable' => $data->editable,
            'deletable' => $data->deletable,
            'translations' => $data->translations->keyBy('locale'),
        ];
    }

    protected function saveModuleTranslations($module, array $translations)
    {
        // Delete existing translations if updating
        $module->translations()->delete();

        // Create new translations
        foreach ($translations as $locale => $translation) {
            $module->translations()->create([
                'locale' => $translation['locale'],
                'singular_name' => $translation['singular_name'],
                'plural_name' => $translation['plural_name']
            ]);
        }
    }

    public function createItem(array $data)
    {
        // Extract translations from data
        $translations = $data['translations'];
        unset($data['translations']);

        // Set default values for nullable boolean fields
        $data = $this->setDefaultBooleanValues($data);

        // Create module
        $data['added_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;

        $module = $this->model->create($data);

        // Save translations
        $this->saveModuleTranslations($module, $translations);

        return $module;
    }

    public function updateItem(array $data)
    {
        // Extract translations from data
        $translations = $data['translations'];
        unset($data['translations']);

        // Set default values for nullable boolean fields
        $data = $this->setDefaultBooleanValues($data);

        // Update module
        $module = $this->getById($data['id']);
        $data['updated_by'] = auth()->user()->id;
        $module->update($data);

        // Save translations
        $this->saveModuleTranslations($module, $translations);

        return $module;
    }

    protected function setDefaultBooleanValues($data)
    {
        $booleanFields = [
            'readable', 'writable', 'editable', 'deletable', 'show_in_menu'
        ];

        foreach ($booleanFields as $field) {
            if (!isset($data[$field])) {
                $data[$field] = false;
            }
        }

        return $data;
    }
}
