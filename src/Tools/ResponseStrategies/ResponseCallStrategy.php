<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Dingo\Api\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Tools\Flags;
use Mpociot\ApiDoc\Tools\Utils;
use Mpociot\ApiDoc\Tools\Traits\ParamHelpers;

/**
 * Make a call to the route and retrieve its response.
 */
class ResponseCallStrategy
{
    use ParamHelpers;

    /**
     * @param Route $route
     * @param array $tags
     * @param array $routeProps
     *
     * @return array|null
     */
    public function __invoke(Route $route, array $tags, array $routeProps)
    {
        $rulesToApply = $routeProps['rules']['response_calls'] ?? [];
        if (! $this->shouldMakeApiCall($route, $rulesToApply)) {
            return null;
        }

        $this->configureEnvironment($rulesToApply);
        $request = $this->prepareRequest($route, $rulesToApply, $routeProps['body'], $routeProps['query']);

        try {
            $response = [$this->makeApiCall($request)];
        } catch (\Exception $e) {
            echo 'Exception thrown during response call for ['.implode(',', $route->methods)."] {$route->uri}.\n";
            if (Flags::$shouldBeVerbose) {
                Utils::dumpException($e);
            } else {
                echo "Run this again with the --verbose flag to see the exception.\n";
            }
            $response = null;
        } finally {
            $this->finish();
        }

        return $response;
    }

    /**
     * @param array $rulesToApply
     *
     * @return void
     */
    private function configureEnvironment(array $rulesToApply)
    {
        $this->startDbTransaction();
        $this->setEnvironmentVariables($rulesToApply['env'] ?? []);
        $this->setLaravelConfigs($rulesToApply['config'] ?? []);
    }

    /**
     * @param Route $route
     * @param array $rulesToApply
     * @param array $bodyParams
     * @param array $queryParams
     *
     * @return Request
     */
    protected function prepareRequest(Route $route, array $rulesToApply, array $bodyParams, array $queryParams)
    {
        $uri = Utils::getFullUrl($route, $rulesToApply['bindings'] ?? []);
        $routeMethods = $this->getMethods($route);
        $method = array_shift($routeMethods);
        $cookies = isset($rulesToApply['cookies']) ? $rulesToApply['cookies'] : [];
        $request = Request::create($uri, $method, [], $cookies, [], $this->transformHeadersToServerVars($rulesToApply['headers'] ?? []));
        $request = $this->addHeaders($request, $route, $rulesToApply['headers'] ?? []);

        // Mix in parsed parameters with manually specified parameters.
        $queryParams = collect($this->cleanParams($queryParams))->merge($rulesToApply['query'] ?? [])->toArray();
        $bodyParams = collect($this->cleanParams($bodyParams))->merge($rulesToApply['body'] ?? [])->toArray();

        $request = $this->addQueryParameters($request, $queryParams);
        $request = $this->addBodyParameters($request, $bodyParams);

        return $request;
    }

    /**
     * @param array $env
     *
     * @return void
     *
     * @deprecated in favour of Laravel config variables
     */
    private function setEnvironmentVariables(array $env)
    {
        foreach ($env as $name => $value) {
            putenv("$name=$value");

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    /**
     * @param array $config
     *
     * @return void
     */
    private function setLaravelConfigs(array $config)
    {
        if (empty($config)) {
            return;
        }

        foreach ($config as $name => $value) {
            config([$name => $value]);
        }
    }

    /**
     * @return void
     */
    private function startDbTransaction()
    {
        try {
            app('db')->beginTransaction();
        } catch (\Exception $e) {
        }
    }

    /**
     * @return void
     */
    private function endDbTransaction()
    {
        try {
            app('db')->rollBack();
        } catch (\Exception $e) {
        }
    }

    /**
     * @return void
     */
    private function finish()
    {
        $this->endDbTransaction();
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function callDingoRoute(Request $request)
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = app(\Dingo\Api\Dispatcher::class);

        foreach ($request->headers as $header => $value) {
            $dispatcher->header($header, $value);
        }

        // set domain and body parameters
        $dispatcher->on($request->header('SERVER_NAME'))
            ->with($request->request->all());

        // set URL and query parameters
        $uri = $request->getRequestUri();
        $query = $request->getQueryString();
        if (! empty($query)) {
            $uri .= "?$query";
        }
        $response = call_user_func_array([$dispatcher, strtolower($request->method())], [$uri]);

        // the response from the Dingo dispatcher is the 'raw' response from the controller,
        // so we have to ensure it's JSON first
        if (! $response instanceof Response) {
            $response = response()->json($response);
        }

        return $response;
    }

    /**
     * @param Route $route
     *
     * @return array
     */
    public function getMethods(Route $route)
    {
        return array_diff($route->methods(), ['HEAD']);
    }

    /**
     * @param Request $request
     * @param Route $route
     * @param array|null $headers
     *
     * @return Request
     */
    private function addHeaders(Request $request, Route $route, $headers)
    {
        // set the proper domain
        if ($route->getDomain()) {
            $request->headers->add([
                'HOST' => $route->getDomain(),
            ]);
            $request->server->add([
                'HTTP_HOST' => $route->getDomain(),
                'SERVER_NAME' => $route->getDomain(),
            ]);
        }

        $headers = collect($headers);

        if (($headers->get('Accept') ?: $headers->get('accept')) === 'application/json') {
            $request->setRequestFormat('json');
        }

        return $request;
    }

    /**
     * @param Request $request
     * @param array $query
     *
     * @return Request
     */
    private function addQueryParameters(Request $request, array $query)
    {
        $request->query->add($query);
        $request->server->add(['QUERY_STRING' => http_build_query($query)]);

        return $request;
    }

    /**
     * @param Request $request
     * @param array $body
     *
     * @return Request
     */
    private function addBodyParameters(Request $request, array $body)
    {
        $request->request->add($body);

        return $request;
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return \Illuminate\Http\JsonResponse|mixed|\Symfony\Component\HttpFoundation\Response
     */
    protected function makeApiCall(Request $request)
    {
        if (config('apidoc.router') == 'dingo') {
            $response = $this->callDingoRoute($request);
        } else {
            $response = $this->callLaravelRoute($request);
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function callLaravelRoute(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    /**
     * @param Route $route
     * @param array $rulesToApply
     *
     * @return bool
     */
    private function shouldMakeApiCall(Route $route, array $rulesToApply): bool
    {
        $allowedMethods = $rulesToApply['methods'] ?? [];
        if (empty($allowedMethods)) {
            return false;
        }

        if (is_string($allowedMethods) && $allowedMethods == '*') {
            return true;
        }

        if (array_search('*', $allowedMethods) !== false) {
            return true;
        }

        $routeMethods = $this->getMethods($route);
        if (in_array(array_shift($routeMethods), $allowedMethods)) {
            return true;
        }

        return false;
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array  $headers
     *
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';
        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');
            if (! starts_with($name, $prefix) && $name !== 'CONTENT_TYPE') {
                $name = $prefix.$name;
            }
            $server[$name] = $value;
        }

        return $server;
    }
}
