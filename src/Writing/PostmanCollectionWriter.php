<?php

namespace Mpociot\ApiDoc\Writing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;

class PostmanCollectionWriter
{
    /**
     * @var Collection
     */
    private $routeGroups;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var array|null
     */
    private $auth;

    /**
     * CollectionWriter constructor.
     *
     * @param Collection $routeGroups
     */
    public function __construct(Collection $routeGroups, $baseUrl)
    {
        $this->routeGroups = $routeGroups;
        $this->protocol = Str::startsWith($baseUrl, 'https') ? 'https' : 'http';
        $this->baseUrl = $this->getBaseUrl($baseUrl);
        $this->auth = config('apidoc.postman.auth');
    }

    public function getCollection()
    {
        $collection = [
            'variables' => [],
            'info' => [
                'name' => config('apidoc.postman.name') ?: config('app.name') . ' API',
                '_postman_id' => Uuid::uuid4()->toString(),
                'description' => config('apidoc.postman.description') ?: '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json',
            ],
            'item' => $this->routeGroups->map(function (Collection $routes, $groupName) {
                return [
                    'name' => $groupName,
                    'description' => $routes->first()['metadata']['groupDescription'],
                    'item' => $routes->map(\Closure::fromCallable([$this, 'generateEndpointItem']))->toArray(),
                ];
            })->values()->toArray(),
        ];

        if (! empty($this->auth)) {
            $collection['auth'] = $this->auth;
        }

        return json_encode($collection, JSON_PRETTY_PRINT);
    }

    protected function generateEndpointItem($route)
    {
        $mode = 'raw';

        $method = $route['methods'][0];

        return [
            'name' => $route['metadata']['title'] != '' ? $route['metadata']['title'] : $route['uri'],
            'request' => [
                'url' => $this->makeUrlData($route),
                'method' => $method,
                'header' => $this->resolveHeadersForRoute($route),
                'body' => [
                    'mode' => $mode,
                    $mode => json_encode($route['cleanBodyParameters'], JSON_PRETTY_PRINT),
                ],
                'description' => $route['metadata']['description'] ?? null,
                'response' => [],
            ],
        ];
    }

    protected function resolveHeadersForRoute($route)
    {
        $headers = collect($route['headers']);

        // Exclude authentication headers if they're handled by Postman auth
        $authHeader = $this->getAuthHeader();
        if (! empty($authHeader)) {
            $headers = $headers->except($authHeader);
        }

        return $headers
            ->union([
                'Accept' => 'application/json',
            ])
            ->map(function ($value, $header) {
                return [
                    'key' => $header,
                    'value' => $value,
                ];
            })
            ->values()
            ->all();
    }

    protected function makeUrlData($route)
    {
        // URL Parameters are collected by the `UrlParameters` strategies, but only make sense if they're in the route
        // definition. Filter out any URL parameters that don't appear in the URL.
        $urlParams = collect($route['urlParameters'])->filter(function ($_, $key) use ($route) {
            return Str::contains($route['uri'], '{' . $key . '}');
        });

        /** @var Collection $queryParams */
        $base = [
            'protocol' => $this->protocol,
            'host' => $this->baseUrl,
            // Substitute laravel/symfony query params ({example}) to Postman style, prefixed with a colon
            'path' => preg_replace_callback('/\/{(\w+)\??}(?=\/|$)/', function ($matches) {
                return '/:' . $matches[1];
            }, $route['uri']),
            'query' => collect($route['queryParameters'])->map(function ($parameter, $key) {
                return [
                    'key' => $key,
                    'value' => urlencode($parameter['value']),
                    'description' => $parameter['description'],
                    // Default query params to disabled if they aren't required and have empty values
                    'disabled' => ! $parameter['required'] && empty($parameter['value']),
                ];
            })->values()->toArray(),
        ];

        // If there aren't any url parameters described then return what we've got
        /** @var $urlParams Collection */
        if ($urlParams->isEmpty()) {
            return $base;
        }

        $base['variable'] = $urlParams->map(function ($parameter, $key) {
            return [
                'id' => $key,
                'key' => $key,
                'value' => urlencode($parameter['value']),
                'description' => $parameter['description'],
            ];
        })->values()->toArray();

        return $base;
    }

    protected function getAuthHeader()
    {
        $auth = $this->auth;
        if (empty($auth) || ! is_string($auth['type'] ?? null)) {
            return null;
        }

        switch ($auth['type']) {
            case 'bearer':
                return 'Authorization';
            case 'apikey':
                $spec = $auth['apikey'];

                if (isset($spec['in']) && $spec['in'] !== 'header') {
                    return null;
                }

                return $spec['key'];
            default:
                return null;
        }
    }

    protected function getBaseUrl($baseUrl)
    {
        if (Str::contains(app()->version(), 'Lumen')) { //Is Lumen
            $reflectionMethod = new ReflectionMethod(\Laravel\Lumen\Routing\UrlGenerator::class, 'getRootUrl');
            $reflectionMethod->setAccessible(true);
            $url = app('url');

            return $reflectionMethod->invokeArgs($url, ['', $baseUrl]);
        }

        return URL::formatRoot('', $baseUrl);
    }
}
