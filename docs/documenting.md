# Documenting Your API
This package generates documentation from your code using mainly annotations (in doc block comments).

## Grouping endpoints
All endpoints are grouped for easy organization. Using `@group` in a controller doc block creates a Group within the API documentation. All routes handled by that controller will be grouped under this group in the table of contents shown in the sidebar. 

The short description after the `@group` should be unique to allow anchor tags to navigate to this section. A longer description can be included below. Custom formatting and `<aside>` tags are also supported. (see the [Documentarian docs](http://marcelpociot.de/documentarian/installation/markdown_syntax))

 > Note: using `@group` is optional. Ungrouped routes will be placed in a default group.

Above each route in the controller, you should have a doc block. This should include a unique short description as the first entry. An optional second entry can be added with further information. Both descriptions will appear in the API documentation in a different format as shown below.

You can also specify an `@group` on a single method to override the group defined at the controller level.

```php
/**
 * @group User management
 *
 * APIs for managing users
 */
class UserController extends Controller
{

	/**
	 * Create a user
	 *
	 * [Insert optional longer description of the API endpoint here.]
	 *
	 */
	 public function createUser()
	 {

	 }
	 
	/**
	 * @group Account management
	 *
	 */
	 public function changePassword()
	 {

	 }
}
```

**Result:** 

![Doc block result](http://headsquaredsoftware.co.uk/images/api_generator_docblock.png)

## Specifying request parameters
To specify a list of valid parameters your API route accepts, use the `@urlParam`, `@bodyParam` and `@queryParam` annotations.
- The `@urlParam` annotation is used for describing parameters in your URl. For instance, in a Laravel route with this URL: "/post/{id}/{lang?}", you would use this annotation to describe the `id` and `lang` parameters. It takes the name of the parameter, an optional "required" label, and then its description.
- The `@queryParam` annotation takes the name of the parameter, an optional "required" label, and then its description.
- The `@bodyParam` annotation takes the name of the parameter, its type, an optional "required" label, and then its description. 

Examples:

```php
/**
 * @urlParam id required The ID of the post.
 * @urlParam lang The language.
 * @bodyParam user_id int required The id of the user. Example: 9
 * @bodyParam room_id string The id of the room.
 * @bodyParam forever boolean Whether to ban the user forever. Example: false
 * @bodyParam another_one number Just need something here.
 * @bodyParam yet_another_param object required Some object params.
 * @bodyParam yet_another_param.name string required Subkey in the object param.
 * @bodyParam even_more_param array Some array params.
 * @bodyParam even_more_param.* float Subkey in the array param.
 * @bodyParam book.name string
 * @bodyParam book.author_id integer
 * @bodyParam book[pages_count] integer
 * @bodyParam ids.* integer
 * @bodyParam users.*.first_name string The first name of the user. Example: John
 * @bodyParam users.*.last_name string The last name of the user. Example: Doe
 */
public function createPost()
{
    // ...
}

/**
 * @queryParam sort Field to sort by
 * @queryParam page The page number to return
 * @queryParam fields required The fields to include
 */
public function listPosts()
{
    // ...
}
```

They will be included in the generated documentation text and example requests.

**Result:**

![](./../body_params_1.png)

![](./../body_params_2.png)

### Example parameters
For each parameter in your request, this package will generate a random value to be used in the example requests. If you'd like to specify an example value, you can do so by adding `Example: your-example` to the end of your description. For instance:

```php
    /**
     * @queryParam location_id required The id of the location.
     * @queryParam user_id required The id of the user. Example: me
     * @queryParam page required The page number. Example: 4
     * @bodyParam user_id int required The id of the user. Example: 9
     * @bodyParam room_id string The id of the room.
     * @bodyParam forever boolean Whether to ban the user forever. Example: false
     */
```

You can also exclude a particular parameter from the generated examples (for all languages) by annotating it with `No-example`. For instance:
```php
    /**
    * @queryParam location_id required The id of the location. Example: 1
    * @queryParam user_id required The id of the user. No-example
    * @queryParam page required The page number. Example: 4
    */
```
Outputs: 
```bash
curl -X GET -G "https://example.com/api?location_id=1&page=4"
```

Note: You can also add the `@queryParam` and `@bodyParam` annotations to a `\Illuminate\Foundation\Http\FormRequest` subclass instead, if you are using one in your controller method

```php
/**
 * @queryParam user_id required The id of the user. Example: me
 * @bodyParam title string required The title of the post.
 * @bodyParam body string required The content of the post.
 * @bodyParam type string The type of post to create. Defaults to 'textophonious'.
 * @bodyParam author_id int the ID of the author. Example: 2
 * @bodyParam thumbnail image This is required if the post type is 'imagelicious'.
 */
class MyRequest extends \Illuminate\Foundation\Http\FormRequest
{

}

// in your controller...
public function createPost(MyRequest $request)
{
    // ...
}
```

## Indicating authentication status
You can use the `@authenticated` annotation on a method to indicate if the endpoint is authenticated. A "Requires authentication" badge will be added to that route in the generated documentation.

Just like `@group` annotation, you can also specify an `@authenticated` on a single method to override the authenticated status defined at the controller level.

```php
/**
 * @authenticated
 *
 * APIs for managing users
 */
class UserController extends Controller
{

	/**
	 * Create a user
	 *
	 * [Insert optional longer description of the API endpoint here.]
	 *
	 */
	 public function createUser()
	 {

	 }
	 
	/**
	 * @group Account management
	 *
	 */
	 public function changePassword()
	 {

	 }
}
```

Now all the methods under this controller will have "Requires authentication" badge enabled.

## Providing an example response
You can provide an example response for a route. This will be displayed in the examples section. There are several ways of doing this.

### @response
You can provide an example response for a route by using the `@response` annotation with valid JSON:

```php
/**
 * @response {
 *  "id": 4,
 *  "name": "Jessica Jones",
 *  "roles": ["admin"]
 * }
 */
public function show($id)
{
    return User::find($id);
}
```

Moreover, you can define multiple `@response` tags as well as the HTTP status code related to a particular response (if no status code set, `200` will be assumed):
```php
/**
 * @response {
 *  "id": 4,
 *  "name": "Jessica Jones",
 *  "roles": ["admin"]
 * }
 * @response 404 {
 *  "message": "No query results for model [\App\User]"
 * }
 */
public function show($id)
{
    return User::findOrFail($id);
}
```

### @apiResource, @apiResourceCollection, and @apiResourceModel
If your controller method uses [Eloquent API resources](https://laravel.com/docs/5.8/eloquent-resources), you can use the apiResource annotations to guide the package when generating a sample response. The `@apiResource` tag specifies the name of the resource. Use `@apiResourceCollection` instead if the route returns a list. This works with both regular `JsonResource` objects and `ResourceCollection` objects.
 
 The `@apiResourceModel` specifies the Eloquent model to be passed to the resource. The package will attempt to generate an instance of the model for the resource from the Eloquent model factory. If that fails, the package will call `::first()` to retrieve the first model from the database. If that fails, it will create an instance using `new`.

Examples:

```php

/**
 * @apiResourceCollection Mpociot\ApiDoc\Tests\Fixtures\UserResource
 * @apiResourceModel Mpociot\ApiDoc\Tests\Fixtures\User
 */
public function listUsers()
{
    return UserResource::collection(User::all());
}

/**
 * @apiResourceCollection Mpociot\ApiDoc\Tests\Fixtures\UserCollection
 * @apiResourceModel Mpociot\ApiDoc\Tests\Fixtures\User
 */
public function listMoreUsers()
{
    return new UserCollection(User::all());
}

/**
 * @apiResourceCollection Mpociot\ApiDoc\Tests\Fixtures\UserResource
 * @apiResourceModel Mpociot\ApiDoc\Tests\Fixtures\User
 */
public function showUser(User $user)
{
    return new UserResource($user);
}
```

### @transformer, @transformerCollection, and @transformerModel
You can define the transformer that is used for the result of the route using the `@transformer` tag (or `@transformerCollection` if the route returns a list). The package will attempt to generate an instance of the model to be transformed using the following steps, stopping at the first successful one:

1. Check if there is a `@transformerModel` tag to define the model being transformed. If there is none, use the class of the first parameter to the transformer's `transform()` method.
2. Get an instance of the model from the Eloquent model factory
2. If the parameter is an Eloquent model, load the first from the database.
3. Create an instance using `new`.

Finally, it will pass in the model to the transformer and display the result of that as the example response.

For example:

```php
/**
 * @transformercollection \App\Transformers\UserTransformer
 * @transformerModel \App\User
 */
public function listUsers()
{
    //...
}

/**
 * @transformer \App\Transformers\UserTransformer
 */
public function showUser(User $user)
{
    //...
}

/**
 * @transformer \App\Transformers\UserTransformer
 * @transformerModel \App\User
 */
public function showUser(int $id)
{
    // ...
}
```
For the first route above, this package will generate a set of two users then pass it through the transformer. For the last two, it will generate a single user and then pass it through the transformer.

> Note: for transformer support, you need to install the league/fractal package

```bash
composer require league/fractal
```

### @responseFile

For large response bodies, you may want to use a dump of an actual response. You can put this response in a file (as a JSON string) within your Laravel storage directory and link to it. For instance, we can put this response in a file named `users.get.json` in `storage/responses`:

```
{"id":5,"name":"Jessica Jones","gender":"female"}
```

Then in your controller, link to it by:

```php
/**
 * @responseFile responses/users.get.json
 */
public function getUser(int $id)
{
  // ...
}
```
The package will parse this response and display in the examples for this route.

Similarly to `@response` tag, you can provide multiple `@responseFile` tags along with the HTTP status code of the response:
```php
/**
 * @responseFile responses/users.get.json
 * @responseFile 404 responses/model.not.found.json
 */
public function getUser(int $id)
{
  // ...
}
```

## Generating responses automatically
If you don't specify an example response using any of the above means, this package will attempt to get a sample response by making a request to the route (a "response call"). A few things to note about response calls:

- Response calls are done within a database transaction and changes are rolled back afterwards.

- The configuration for response calls is located in the `config/apidoc.php`. They are configured within the `apply.response_calls` section for each route group, allowing you to apply different settings for different sets of routes.

- By default, response calls are only made for GET routes, but you can configure this. Set the `methods` key to an array of methods or '*' to mean all methods. Leave it as an empty array to turn off response calls for that route group.

- You can set Laravel config variables. This is useful so you can prevent external services like notifications from being triggered. By default the `app.env` is set to 'documentation'. You can add more variables in the `config` key.

- By default, the package will generate dummy values for your documented body and query parameters and send in the request. If you specified example values using `@bodyParam` or `@queryParam`, those will be used instead. You can configure additional parameters or overwrite the existing ones for the request in the `queryParams`, and `bodyParams` sections.

- The `ResponseCalls` strategy will only attempt to fetch a response if there are no responses with a status code of 2xx already.
