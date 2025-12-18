<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDTR extends JsonResource
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
            'photo'=> $this->getPhoto(),
            'display_name' => "<strong>{$this->name}</strong>",
            'full_name' => $this->first_name.' '.$this->last_name,
            'designation' => isset($this->designation_title) ? $this->designation_title : ($this->designation->short_title ?? ''),
            'user_role' => isset($this->role_title) ? $this->role_title : ($this->role->title ?? ''),
            'status' => $this->setStatus(),
            'created_at' => dateTimeFormat($this->created_at) ?? null,
            // 'action' => $this->when(!$this->trashed(), $this->getActionButtons()),
            // 'action' => $this->getActionButtons(),
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

    private function setStatus()
    {
        $status = $this->status==1
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">InActive</span>';
        $invite = ($this->password === null && $this->set_password_token === null)
                    ? '<span class="badge bg-warning">Not Invited</span>'
                    : ($this->set_password_token ? '<span class="badge bg-info">Email Sent</span>' : '');

        return '<div>'.$status.'</div><div>'.$invite.'</div>';
    }

    private function getPhoto()
    {
        return view('components.profile-image', [
            'imageValue' => $this->profile_photo_url,
            'size' => 'avatar-sm',
            'isEditable' => false
        ])->render();
    }
}
