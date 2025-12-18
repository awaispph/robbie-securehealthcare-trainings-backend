<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleDTR extends JsonResource
{
    public static $moduleId;

    public static function collection($resource)
    {
        self::$moduleId = session()->get('page_data')->module_id;
        return parent::collection($resource);
    }

    public function toArray($request)
    {
        $array = [
            'id' => $this->id,
            'title' => "<strong>{$this->title}</strong>",
            'description' => $this->description,
            'created_at' => dateTimeFormat($this->created_at) ?? null,
            // 'action' => $this->when(!$this->trashed(), $this->getActionButtons()),
            'action' => getResourceActionButtons(self::$moduleId, $this, true, __('common.actions.permissions'), 'permissions', 'mdi-shield-account', false),
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
    //             <button type='button' class='btn btn-soft-secondary btn-sm permissions-item-btn' data-id='{$this->id}'>
    //                 <i class='mdi mdi-shield-account'></i> Permissions
    //             </button>
    //             <button type='button' class='btn btn-soft-secondary btn-sm dropdown-toggle dropdown-toggle-split' data-bs-toggle='dropdown' aria-expanded='false'>
    //                 <span class='visually-hidden'>Toggle Dropdown</span>
    //             </button>
    //             <ul class='dropdown-menu dropdown-menu-end'>
    //                 <li>
    //                     <a class='dropdown-item edit-item-btn' href='javascript:void(0);' data-id='{$this->id}'>
    //                         <i class='mdi mdi-pencil align-bottom me-2 text-muted'></i> Edit
    //                     </a>
    //                 </li>
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
