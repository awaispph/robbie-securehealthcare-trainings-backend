<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DesignationDTR extends JsonResource
{
    private static $moduleId;

    public static function collection($resource)
    {
        // self::$moduleId = request()->route('module_id') ?? session()->get('page_data')->module_id;
        self::$moduleId = session()->get('page_data')->module_id;
        return parent::collection($resource);
    }

    public function toArray($request)
    {
        $array = [
            'id' => $this->id,
            'parent_id' => ($this->parent_id == 0)
                            ? 'Root'
                            : (($this->parent_id == $this->id)
                                ? $this->title
                                : ($this->parent ? $this->parent->title : 'No Parent')),
            'title' => "<strong>{$this->title}</strong> (".$this->short_title.")",
            'sort_order' => $this->sort_order,
            'created_at' => dateTimeFormat($this->created_at) ?? null,
            // 'action' => $this->when(!$this->trashed(), $this->getActionButtons()),
            'action' => getResourceActionButtons(self::$moduleId, $this),
        ];

        // if ($this->trashed()) {
        //     $array['action'] = $this->getRestoreButton();
        // }

        return $array;
    }

    // private function getActionButtons()
    // {
    //     return "
    //         <div class='btn-group'>
    //             <button type='button' class='btn btn-soft-secondary btn-sm edit-item-btn' data-id='{$this->id}'>
    //                 <i class='mdi mdi-pencil'></i> Edit
    //             </button>
    //             <button type='button' class='btn btn-soft-secondary btn-sm dropdown-toggle dropdown-toggle-split' data-bs-toggle='dropdown' aria-expanded='false'>
    //                 <span class='visually-hidden'>Toggle Dropdown</span>
    //             </button>
    //             <ul class='dropdown-menu dropdown-menu-end'>
    //                 <li>
    //                     <a class='dropdown-item delete-item-btn' href='javascript:void(0);' data-id='{$this->id}'>
    //                         <i class='mdi mdi-delete align-bottom me-2 text-muted'></i> Archive
    //                     </a>
    //                 </li>
    //             </ul>
    //         </div>
    //     ";
    // }

    // private function getRestoreButton()
    // {
    //     return "
    //         <button type='button' class='btn btn-soft-success btn-sm restore-item-btn' data-id='{$this->id}'>
    //             <i class='mdi mdi-restore'></i> Restore
    //         </button>
    //     ";
    // }
}
