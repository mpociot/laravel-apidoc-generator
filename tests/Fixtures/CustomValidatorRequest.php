<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;

class CustomValidatorRequest extends FormRequest
{
    /**
     * Validate the input.
     *
     * @param \Illuminate\Validation\Factory $factory
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validator($factory)
    {
        return $factory->make(
            $this->validationData(), $this->container->call([$this, 'foo']),
            $this->messages(), $this->attributes()
        );
    }

    public function foo()
    {
        return [
            'required' => 'required',
        ];
    }
}
