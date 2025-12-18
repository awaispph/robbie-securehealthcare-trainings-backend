<?php

namespace App\Services;

use App\Http\Resources\ModuleGroupDTR;
use App\Models\ModuleGroup;

class ModuleGroupService extends BaseService
{
    public function __construct(ModuleGroup $model)
    {
        parent::__construct($model, ModuleGroupDTR::class);
    }

}
