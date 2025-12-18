<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAdditionalInfoDTR extends JsonResource
{
    public function toArray($request)
    {
        $array = [
            'user_id'=> $this->user_id,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'city_id' => $this->city_id,
            'street_address' => $this->street_address,
            'post_code' => $this->post_code,
            'emerg_contact_name' => $this->emerg_contact_name,
            'emerg_contact_phone' => $this->emerg_contact_phone,
            'emerg_contact_relation' => $this->emerg_contact_relation,
            'states' => $this->getStates ? $this->getStates->toArray() : [],
            'cities' => $this->getCities ? $this->getCities->toArray() : [],
        ];

        return $array;
    }
}