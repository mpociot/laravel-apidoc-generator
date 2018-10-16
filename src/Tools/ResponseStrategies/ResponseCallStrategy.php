<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Dingo\Api\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;

/**
 * Make a call to the route and retrieve its response
 */
class ResponseCallStrategy
{
    public function __invoke(Route $route, array $tags, array $rulesToApply)
    {
        $rulesToApply = $rulesToApply['response_calls'] ?? [];
        if (! $this->shouldMakeApiCall($route, $rulesToApply)) {
            return;
        }

        $this->configureEnvironment($rulesToApply);
        $request = $this->prepareRequest($route, $rulesToApply);
        try {
            $response = $this->makeApiCall($request);
        } catch (\Exception $e) {
            $response = null;
        } finally {
            $this->finish();
        }

        return $response;
    }

    private function configureEnvironment(array $rulesToApply)
    {
        $this->enableDbTransactions();
        $this->setEnvironmentVariables($rulesToApply['env'] ?? []);
    }

    private function prepareRequest(Route $route, array $rulesToApply)
    {
        $uri = $this->replaceUrlParameterBindings($route, $rulesToApply['bindings'] ?? []);
        $routeMethods = $this->getMethods($route);
        $method = array_shift($routeMethods);
        $request = Request::create($uri, $method, [], [], [], $this->transformHeadersToServerVars($rulesToApply['headers'] ?? []));
        $request = $this->addHeaders($request, $route, $rulesToApply['headers'] ?? []);
        $request = $this->addQueryParameters($request, $rulesToApply['query'] ?? []);
        $request = $this->addBodyParameters($request, $rulesToApply['body'] ?? []);

        return $request;
    }

    /**
     * Transform parameters in URLs into real values (/users/{user} -> /users/2).
     * Uses bindings specified by caller, otherwise just uses '1'
     *
     * @param Route $route
     * @param array $bindings
     *
     * @return mixed
     */
    protected function replaceUrlParameterBindings(Route $route, $bindings)
    {
        $uri = $route->uri();
        foreach ($bindings as $parameter => $binding) {
            $uri = str_replace($parameter, $binding, $uri);
        }
        // Replace any unbound parameters with '1'
        $uri = preg_replace('/{(.*?)}/', 1, $uri);

        return $uri;
    }

    private function setEnvironmentVariables(array $env)
    {
        foreach ($env as $name => $value) {
            putenv("$name=$value");

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    private function enableDbTransactions()
    {
        try {
            app('db')->beginTransaction();
        } catch (\Exception $e) {

        }
    }

    private function disableDbTransactions()
    {
        try {
            app('db')->rollBack();
        } catch (\Exception $e) {

        }
    }

    private function finish()
    {
        $this->disableDbTransactions();
    }

    /**
     * {@inheritdoc}
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
        if (!empty($query)) {
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

    public function getMethods(Route $route)
    {
        return array_diff($route->methods(), ['HEAD']);
    }

    private function addHeaders(Request $request, Route $route, $headers)
    {
        // set the proper domain
        if ($route->getDomain()) {
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

    private function addQueryParameters(Request $request, array $query)
    {
        $request->query->add($query);
        $request->server->add(['QUERY_STRING' => http_build_query($query)]);

        return $request;
    }

    private function addBodyParameters(Request $request, array $body)
    {
        $request->request->add($body);

        return $request;
    }

    private function makeApiCall(Request $request)
    {
        if (config('apidoc.router') == 'dingo') {
            $response = $this->callDingoRoute($request);
        } else {
            $response = $this->callLaravelRoute($request);
        }

        return $response;
    }

    /**
     * @param $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function callLaravelRoute($request): \Symfony\Component\HttpFoundation\Response
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    private function shouldMakeApiCall(Route $route, array $rulesToApply): bool
    {
        $allowedMethods = $rulesToApply['methods'] ?? [];
        if (empty($allowedMethods)) {
            return false;
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
