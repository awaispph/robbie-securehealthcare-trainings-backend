<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModuleGroupDTR extends JsonResource
{
    public function toArray($request)
    {
        $array = [
            'id' => $this->id,
            'title' => "<strong>{$this->title}</strong>",
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'created_at' => dateTimeFormat($this->created_at) ?? null,
            'action' => $this->when(!$this->trashed(), $this->getActionButtons()),
        ];

        if ($this->trashed()) {
            $array['action'] = $this->getRestoreButton();
        }

        return $array;
    }

    private function getActionButtons()
    {
        return "
            <div class='btn-group'>
                <button type='button' class='btn btn-soft-secondary btn-sm edit-item-btn' data-id='{$this->id}'>
                    <i class='mdi mdi-pencil'></i> " . __('common.actions.edit') . "
                </button>
                <button type='button' class='btn btn-soft-secondary btn-sm dropdown-toggle dropdown-toggle-split' data-bs-toggle='dropdown' aria-expanded='false'>
                    <span class='visually-hidden'>Toggle Dropdown</span>
                </button>
                <ul class='dropdown-menu dropdown-menu-end'>
                    <li>
                        <a class='dropdown-item delete-item-btn' href='javascript:void(0);' data-id='{$this->id}'>
                            <i class='mdi mdi-delete align-bottom me-2 text-muted'></i> " . __('common.actions.archive') . "
                        </a>
                    </li>
                </ul>
            </div>
        ";
    }

    private function getRestoreButton()
    {
        return "
            <button type='button' class='btn btn-soft-success btn-sm restore-item-btn' data-id='{$this->id}'>
                <i class='mdi mdi-restore'></i> " . __('common.actions.restore') . "
            </button>
        ";
    }
}
