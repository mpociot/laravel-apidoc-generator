<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TestUserApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->first_name." ".$this->last_name,
            'email' => $this->email,
        ];
    }
}
