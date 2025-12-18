<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResetPasswordHistoryDTR extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'created_by' => $this->created_by,
            'email' => $this->email,
            'ip' => $this->ip,
            'agent' => $this->agent,
            'status' => $this->getVisitedStatusBadge(),
            'created_at' => dateTimeFormat($this->created_at) ?? null,
        ];
    }

    protected function getVisitedStatusBadge()
    {
        $statuses = [
            0 => ['label' => 'Inactive', 'class' => 'badge rounded-pill bg-primary-subtle text-primary'],
            1 => ['label' => 'Actived', 'class' => 'badge rounded-pill bg-success-subtle text-success'],
            2 => ['label' => 'Expired', 'class' => 'badge rounded-pill bd-danger-subtle text-danger'],
        ];

        $status = $statuses[$this->is_visited] ?? ['label' => 'unknown', 'class' => 'badge badge-dark'];
        return '<span class="' . $status['class'] . '">' . $status['label'] . '</span>';
    }
}
