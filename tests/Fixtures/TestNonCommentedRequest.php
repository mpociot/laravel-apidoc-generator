<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;

class TestNonCommentedRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => ['required', 'integer'],
            'room_id' => 'required|string',
            'forever' => 'bool',
            'accepted' => 'accepted|bool',
            'another_one' => 'numeric',
            'yet_another_param' => 'json',
            'even_more_param' => 'array',
            'book.name' => 'string',
            'book.author_id' => 'integer',
            'ids.*' => 'integer',
            'users.*.first_name' => 'string',
            'users.*.last_name' => 'string',
        ];
    }
}
