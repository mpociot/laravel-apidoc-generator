<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Dingo\Api\Http\FormRequest;

class DingoTestRequest extends FormRequest
{
    public function rules()
    {
        return [
            'required' => 'required',
            'accepted' => 'accepted',
            'after' => 'after:2016-04-23 14:31:00',
            'active_url' => 'active_url',
            'alpha' => 'alpha',
            'alpha_dash' => 'alpha_dash',
            'alpha_num' => 'alpha_num',
            'array' => 'array',
            'before' => 'before:2016-04-23 14:31:00',
            'between' => 'between:5,200',
            'string_between' => 'string|between:5,200',
            'boolean' => 'boolean',
            'date' => 'date',
            'date_format' => 'date_format:j.n.Y H:iP',
            'different' => 'different:alpha_num',
            'digits' => 'digits:2',
            'digits_between' => 'digits_between:2,10',
            'exists' => 'exists:users,firstname',
            'single_exists' => 'exists:users',
            'file' => 'file',
            'in' => 'in:jpeg,png,bmp,gif,svg',
            'integer' => 'integer',
            'image' => 'image',
            'ip' => 'ip',
            'json' => 'json',
            'min' => 'min:20',
            'max' => 'max:10',
            'mimes' => 'mimes:jpeg,bmp,png',
            'not_in' => 'not_in:foo,bar',
            'numeric' => 'numeric',
            'regex' => 'regex:(.*)',
            'required_if' => 'required_if:foo,bar',
            'multiple_required_if' => 'required_if:foo,bar,baz,qux',
            'required_unless' => 'required_unless:foo,bar',
            'required_with' => 'required_with:foo,bar,baz',
            'required_with_all' => 'required_with_all:foo,bar,baz',
            'required_without' => 'required_without:foo,bar,baz',
            'required_without_all' => 'required_without_all:foo,bar,baz',
            'same' => 'same:foo',
            'size' => 'size:51',
            'timezone' => 'timezone',
            'url' => 'url',
        ];
    }
}
