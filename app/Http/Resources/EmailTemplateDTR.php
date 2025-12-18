<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateDTR extends JsonResource
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
            'email_template_subject' =>
                (strlen($this->email_template_subject) > 30)
                    ? substr("<strong>{$this->email_template_subject}</strong>", 0, 30) . '...'
                    : "<strong>{$this->email_template_subject}</strong>",
            'email_template_event' => $this->email_template_event,
            // 'email_template_body' =>
            //     (strlen($this->email_template_body) > 20)
            //         ? substr($this->email_template_body, 0, 20) . '...'
            //         : $this->email_template_body,
            'created_at' => dateTimeFormat($this->created_at) ?? null,
            // 'action' => $this->getActionButtons(),
            'action' => getResourceActionButtons(self::$moduleId, $this),
        ];

        return $array;
    }

    // private function getActionButtons()
    // {
    //     return "
    //         <div class='btn-group'>
    //             <button type='button' class='btn btn-soft-secondary btn-sm edit-item-btn' data-id='{$this->id}'>
    //                 <i class='mdi mdi-pencil'></i> Edit
    //             </button>
    //         </div>
    //     ";
    // }

}
