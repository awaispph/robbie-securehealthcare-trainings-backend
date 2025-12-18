<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    protected $model;
    protected $dtrClass;

    public function __construct(Model $model, string $dtrClass = null)
    {
        $this->model = $model;
        $this->dtrClass = $dtrClass;
    }

    public function createItem(array $data)
    {
        $data['added_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;
        return $this->model->create($data);
    }

    public function getById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function updateItem(array $data)
    {
        $item = $this->getById($data['id']);
        $data['updated_by'] = auth()->user()->id;
        $item->update($data);
        return $item;
    }

    public function archiveItem($id)
    {
        $item = $this->getById($id);
        $item->delete();
        return $item;
    }

    public function restoreItem($id)
    {
        $item = $this->model->withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    public function getItems($request, $archived = false)
    {
        $query = $archived ? $this->model::onlyTrashed() : $this->model::query();

        // Apply joins specific to the child service
        $this->addJoins($query);

        $column_index = $request->order[0]['column'];
        $columnName = $request->columns[$column_index]['data'];
        $columnName = $this->mapColumnName($columnName); // Call dynamically
        $column_sort_order = $request->order[0]['dir'];
        $search_value = $request->search['value'];

        $searchColumns = $this->getDefaultSearchColumns(); // Get columns dynamically

        if (!empty($search_value)) {
            $query->where(function ($q) use ($search_value, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', '%' . $search_value . '%');
                }
            });
        }

        $cacheKey = 'records_total_' . ($archived ? 'archived' : 'active');

        $records_total = cache()->remember($cacheKey, 600, function () use ($archived) {
            return $archived ? $this->model::onlyTrashed()->count() : $this->model::count();
        });

        $records_filtered = $query->count();

        $items = $query->orderBy($columnName, $column_sort_order)
            ->skip($request->start)
            ->take($request->length)
            ->get();

        // get page_data from session get module_id
        // $pageData = session()->get('page_data');
        // $moduleId = $pageData->module_id;

        $resourceClass = $this->dtrClass;

        return $resourceClass::collection($items)->additional([
            "draw" => intval($request->draw),
            "recordsTotal" => $records_total,
            "recordsFiltered" => $records_filtered,
            // "moduleId" => $moduleId,
        ]);
    }

    // Placeholder for child-specific joins
    protected function addJoins($query)
    {
        // Default: no joins applied
    }

    protected function mapColumnName($columnName)
    {
        // Default behavior: return the column name as-is
        return $columnName;
    }

    protected function getDefaultSearchColumns()
    {
        return ['title']; // Default columns for search
    }

    public function getModule()
    {
        $url = request()->route()->uri;
        $module = $this->checkURL($url) ?: Module::with(['translations' => function($query) {
            $query->where('locale', app()->getLocale());
        }])->where('url', $url)->first();

        if ($module) {
            if ($module instanceof Module) {
                // Get translation for current locale
                $translation = $module->translations->first();
                return (object)[
                    'id' => $module->id,
                    'singular_name' => $translation ? $translation->singular_name : $module->name,
                    'plural_name' => $translation ? $translation->plural_name : $module->name
                ];
            }
            // If it's already an object from checkURL
            return $module;
        }

        // Fallback if no module found
        return (object)[
            'id' => null,
            'singular_name' => 'Item',
            'plural_name' => 'Items'
        ];
    }

    private function checkURL($url)
    {
        $currentLocale = app()->getLocale();

        switch ($url) {
            case 'module-groups/list':
                return (object)[
                    'id' => null,
                    'singular_name' => __('Module/form.static_modules.module_group.singular'),
                    'plural_name' => __('Module/form.static_modules.module_group.plural')
                ];
            case 'modules/list':
                return (object)[
                    'id' => null,
                    'singular_name' => __('Module/form.static_modules.module.singular'),
                    'plural_name' => __('Module/form.static_modules.module.plural')
                ];
            // Add more cases as needed
            default:
                return false;
        }
    }

    public function getSingle($id)
    {
        return ['data' => $this->getById($id)];
    }

}
