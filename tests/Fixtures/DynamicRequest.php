<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;

class DynamicRequest extends FormRequest
{
    public function rules()
    {
        return [
            'not_in' => 'not_in:'.$this->foo,
        ];
    }
}
