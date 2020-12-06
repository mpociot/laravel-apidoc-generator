<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam user_id int required The id of the user. Example: 9
 * @bodyParam room_id string The id of the room.
 * @bodyParam forever boolean Whether to ban the user forever. Example: false
 * @bodyParam another_one number Just need something here.
 * @bodyParam yet_another_param object required
 * @bodyParam even_more_param array
 * @bodyParam book.name string
 * @bodyParam book.author_id integer
 * @bodyParam book[pages_count] integer
 * @bodyParam ids.* integer
 * @bodyParam users.*.first_name string The first name of the user. Example: John
 * @bodyParam users.*.last_name string The last name of the user. Example: Doe
 */
class TestRequest extends FormRequest
{
}
