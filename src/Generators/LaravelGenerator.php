<?php

namespace Mpociot\ApiDoc\Generators;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class LaravelGenerator extends AbstractGenerator
{
    /**
     * @param Route $route
     *
     * @return mixed
     */
    public function getUri(Route $route)
    {
        if (version_compare(app()->version(), '5.4', '<')) {
            return $route->getUri();
        }

        return $route->uri();
    }

    /**
     * @param Route $route
     *
     * @return mixed
     */
    public function getMethods(Route $route)
    {
        if (version_compare(app()->version(), '5.4', '<')) {
            $methods = $route->getMethods();
        } else {
            $methods = $route->methods();
        }

        return array_diff($methods, ['HEAD']);
    }

    /**
     * Prepares / Disables route middlewares.
     *
     * @param  bool $disable
     *
     * @return  void
     */
    public function prepareMiddleware($enable = true)
    {
        App::instance('middleware.disable', ! $enable);
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string  $content
     *
     * @return \Illuminate\Http\Response
     */
    public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $server = collect([
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ])->merge($server)->toArray();

        $request = Request::create(
            $uri, $method, $parameters,
            $cookies, $files, $this->transformHeadersToServerVars($server), $content
        );

        $kernel = App::make('Illuminate\Contracts\Http\Kernel');
        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);
        if (file_exists($file = App::bootstrapPath().'/app.php')) {
            $app = require $file;
            $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        }
        return $response;
    }
}
