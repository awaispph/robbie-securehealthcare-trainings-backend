<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModulesDTR extends JsonResource
{
    public function toArray($request)
    {
        $translation = $this->translation();
        $array = [
            'id' => $this->id,
            'checkbox' => '',
            'name' => $this->formatName($translation ? $translation->singular_name : $this->name),
            'url' => $this->url,
            'icon' => "<i class='mdi mdi-{$this->icon} '></i>",
            'slug' => $this->slug,
            'sort_order' => $this->sort_order,
            'permissions' => $this->formatPermissions(),
            'created_at' => dateTimeFormat($this->created_at) ?? null,
            'action' => $this->when(!$this->trashed(), $this->getActionButtons()),
        ];

        if ($this->trashed()) {
            $array['action'] = $this->getRestoreButton();
        }

        return $array;
    }

    private function formatName($name)
    {
        return $this->parent_id != 0
            ? "&nbsp;&nbsp;<i class='mdi mdi-subdirectory-arrow-right '></i>&nbsp;{$name}"
            : "<strong>{$name}</strong>";
    }

    private function formatPermissions()
    {
        $permissions = [
            'readable' => ['icon' => 'eye', 'tooltip' => ['on' => 'Readable', 'off' => 'Un-readable']],
            'writable' => ['icon' => 'plus-box', 'tooltip' => ['on' => 'Createable', 'off' => 'Un-createable']],
            'editable' => ['icon' => 'pencil', 'tooltip' => ['on' => 'Editable', 'off' => 'Un-editable']],
            'deletable' => ['icon' => 'delete', 'tooltip' => ['on' => 'Deleteable', 'off' => 'Un-deleteable']],
        ];

        return collect($permissions)->map(function ($permission, $key) {
            $class = $this->$key ? 'text-success' : 'text-muted';
            $tooltip = $this->$key ? $permission['tooltip']['on'] : $permission['tooltip']['off'];
            return "<i class='mdi mdi-{$permission['icon']} {$class}' data-bs-toggle='tooltip' title='{$tooltip}'></i>";
        })->implode(' ');
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
