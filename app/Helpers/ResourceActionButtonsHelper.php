<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Gate;

class ResourceActionButtonsHelper
{
    private $model;
    private $moduleId;
    private $hasViewButton;
    private $viewButtonLabel;
    private $viewButtonType;
    private $viewButtonIcon;
    private $isModalButtons;

    public function __construct($moduleId, $model, $hasViewButton = false, $viewButtonLabel = null, $viewButtonType = 'view', $viewButtonIcon = 'mdi-eye', $isModalButtons = false)
    {
        $this->moduleId = $moduleId;
        $this->model = $model;
        $this->hasViewButton = $hasViewButton;
        $this->viewButtonLabel = $viewButtonLabel ? __($viewButtonLabel) : __('common.actions.view');
        $this->viewButtonType = $viewButtonType;
        $this->viewButtonIcon = $viewButtonIcon;
        $this->isModalButtons = $isModalButtons;
    }

    public function getActionsHtml(): string
    {
        $actions = $this->getPermittedActions($this->moduleId);

        if (empty($actions)) {
            return '';
        }

        $primaryAction = array_shift($actions);

        $html = '<div class="btn-group">';
        $html .= $this->buildButton($primaryAction);

        if (!empty($actions)) {
            $html .= $this->buildDropdown($actions);
        }

        $html .= '</div>';
        return $html;
    }

    private function getPermittedActions(int $moduleId): array
    {
        $actions = [];
        $user = auth()->user();

        $modelClass = $this->model instanceof \Illuminate\Http\Resources\Json\JsonResource ? $this->model->resource : $this->model;

        if (method_exists($modelClass, 'trashed') && $modelClass->trashed()) {
            if (Gate::allows('modulePermission', [$moduleId, 'restore'])) {
                $actions[] = [
                    'type' => 'restore',
                    'label' => __('common.actions.restore'),
                    'icon' => 'mdi-backup-restore',
                    'class' => 'btn-soft-success btn-sm',
                    'loading' => true,
                ];
            }
        } else {
            if (Gate::allows('modulePermission', [$moduleId, 'view']) && $this->hasViewButton) {
                $actions[] = [
                    'type' => $this->viewButtonType,
                    'label' => $this->viewButtonLabel,
                    'icon' => $this->viewButtonIcon,
                    'class' => 'btn-soft-secondary btn-sm',
                ];
            }

            if (Gate::allows('modulePermission', [$moduleId, 'edit'])) {
                $actions[] = [
                    'type' => 'edit',
                    'label' => __('common.actions.edit'),
                    'icon' => 'mdi-pencil',
                    'class' => 'btn-soft-secondary btn-sm',
                ];
            }

            if (Gate::allows('modulePermission', [$moduleId, 'delete'])) {
                $actions[] = [
                    'type' => 'delete',
                    'label' => __('common.actions.archive'),
                    'icon' => 'mdi-delete',
                    'class' => 'btn-soft-secondary btn-sm',
                    'isDropdown' => true,
                ];
            }
        }

        return $actions;
    }

    private function buildButton(array $action): string
    {
        $btnClass = $this->isModalButtons ? "modal-{$action['type']}-item-btn" : "{$action['type']}-item-btn";

        $html = "<button type='button' class='btn {$action['class']} {$btnClass}' data-id='{$this->model->id}'>";
        $html .= "<i class='mdi {$action['icon']} align-bottom'></i> {$action['label']}";
        $html .= '</button>';
        return $html;
    }

    private function buildDropdown(array $actions): string
    {
        $html = '<button type="button" class="btn btn-soft-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-end">';

        foreach ($actions as $action) {
            $html .= $this->buildDropdownItem($action);
        }

        $html .= '</ul>';
        return $html;
    }

    private function buildDropdownItem(array $action): string
    {
        $iconClass = ($action['type'] === 'delete') ? 'text-muted' : '';
        $btnClass = $this->isModalButtons ? "modal-{$action['type']}-item-btn" : "{$action['type']}-item-btn";
        return "
            <li>
                <a class='dropdown-item {$btnClass}' data-id='{$this->model->id}' href='javascript:void(0)'>
                    <i class='mdi {$action['icon']} align-bottom me-2 {$iconClass}'></i> {$action['label']}
                </a>
            </li>";
    }
}
