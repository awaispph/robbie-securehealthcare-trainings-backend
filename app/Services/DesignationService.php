<?php

namespace App\Services;

use App\Http\Resources\DesignationDTR;
use App\Models\Designation;

class DesignationService extends BaseService
{
    public function __construct(Designation $model)
    {
        parent::__construct($model, DesignationDTR::class);
    }

    public function getSingle($id)
    {
        $designations = Designation::orderBy('sort_order', 'ASC')->get();
        $designation = collect($designations)->where('id', $id)->first();
        return ['data' => $designation, 'AllDesignations' => $designations];
    }

    public function getParents(){

        return Designation::select('id','title', 'short_title')->orderBy('sort_order', 'ASC')->get();
    }
}
