<?php

namespace App\Services;

use App\Models\Module;
use App\Models\UserDocument;
use App\Http\Resources\UserDocumentDTR;
use Illuminate\Support\Facades\Storage;

class UserDocumentService
{
    protected $model;
    protected $moduleUrl = 'user-document/list';

    public function __construct(UserDocument $model)
    {
        $this->model = $model;
    }

    public function getModuleFromUrl()
    {
        $cacheKey = 'module_' . $this->moduleUrl . '_' . app()->getLocale();

        return cache()->remember($cacheKey, now()->addMinutes(20), function () {
            $module = Module::with(['translations' => function($query) {
                $query->where('locale', app()->getLocale());
            }])->where('url', $this->moduleUrl)->first();

            if ($module) {
                $translation = $module->translations->first();
                return (object)[
                    'id' => $module->id,
                    'singular_name' => $translation ? $translation->singular_name : $module->name,
                    'plural_name' => $translation ? $translation->plural_name : $module->name
                ];
            }

            // Fallback if no module or translation found
            return (object)[
                'id' => null,
                'singular_name' => 'User Document',
                'plural_name' => 'User Documents'
            ];
        });
    }

    public function createItem(array $data)
    {
        if (isset($data['document'])) {
            $file = $data['document'];
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_path'] = $this->uploadDocument($file);
            unset($data['document']);
        }

        $data['created_by'] = auth()->id();
        return $this->model->create($data);
    }

    private function uploadDocument($file)
    {
        $path = $file->store('user-documents', 'public');
        return $path;
    }

    public function downloadDocument($id)
    {
        $document = $this->model->findOrFail($id);
        $path = storage_path('app/public/' . $document->file_path);

        if (!file_exists($path)) {
            throw new \Exception('File not found');
        }

        return response()->download($path, $document->file_name);
    }

    public function restoreItem($id)
    {
        $item = $this->model->withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    public function archiveItem($id)
    {
        $item = $this->model->find($id);
        if ($item) {
            Storage::disk('public')->delete($item->file_path);
            $item->delete();
            return true;
        }

        return false;
    }

    public function getItems($request, $archived = false)
    {

        $documents = $this->model->where('user_id', $request->id)->get();
        // return response()->json($documents);
        return UserDocumentDTR::collection($documents);

        // $query = $archived ? $this->model::onlyTrashed() : $this->model::query();
        // $query->where('user_id', $request->id);

        // $query->with(['user', 'creator']);

        // // Handle search
        // if ($request->search['value']) {
        //     $searchValue = $request->search['value'];
        //     $query->where(function ($q) use ($searchValue) {
        //         $q->where('title', 'like', "%{$searchValue}%")
        //             ->orWhere('document_type', 'like', "%{$searchValue}%")
        //             ->orWhere('description', 'like', "%{$searchValue}%");
        //     });
        // }

        // // Handle sorting
        // $columnIndex = $request->order[0]['column'];
        // $columnName = $request->columns[$columnIndex]['data'];
        // $columnDirection = $request->order[0]['dir'];
        // $query->orderBy($columnName, $columnDirection);

        // $total = $query->count();

        // $results = $query->skip($request->start)
        //     ->take($request->length)
        //     ->get();

        // return UserDocumentDTR::collection($results)->additional([
        //     'draw' => $request->draw,
        //     'recordsTotal' => $total,
        //     'recordsFiltered' => $total,
        // ]);
    }
}
