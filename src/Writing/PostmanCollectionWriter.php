<?php

namespace Mpociot\ApiDoc\Writing;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

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
     * CollectionWriter constructor.
     *
     * @param Collection $routeGroups
     */
    public function __construct(Collection $routeGroups, $baseUrl)
    {
        $this->routeGroups = $routeGroups;
        $this->baseUrl = $baseUrl;
    }

    public function getCollection()
    {
        URL::forceRootUrl($this->baseUrl);
        if (Str::startsWith($this->baseUrl, 'https://')) {
            URL::forceScheme('https');
        }

        $collection = [
            'variables' => [],
            'info' => [
                'name' => config('apidoc.postman.name') ?: config('app.name').' API',
                '_postman_id' => Uuid::uuid4()->toString(),
                'description' => config('apidoc.postman.description') ?: '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json',
            ],
            'item' => $this->routeGroups->map(function ($routes, $groupName) {
                return [
                    'name' => $groupName,
                    'description' => '',
                    'item' => $routes->map(function ($route) {
                        $mode = $route['methods'][0] === 'PUT' ? 'urlencoded' : 'formdata';

                        return [
                            'name' => $route['metadata']['title'] != '' ? $route['metadata']['title'] : url($route['uri']),
                            'request' => [
                                'url' => url($route['uri']).(collect($route['queryParameters'])->isEmpty()
                                        ? ''
                                        : ('?'.implode('&', collect($route['queryParameters'])->map(function ($parameter, $key) {
                                            return urlencode($key).'='.urlencode($parameter['value'] ?? '');
                                        })->all()))),
                                'method' => $route['methods'][0],
                                'header' => collect($route['headers'])
                                    ->union([
                                        'Accept' => 'application/json',
                                    ])
                                    ->map(function ($value, $header) {
                                        return [
                                            'key' => $header,
                                            'value' => $value,
                                        ];
                                    })
                                    ->values()->all(),
                                'body' => [
                                    'mode' => $mode,
                                    $mode => collect($route['bodyParameters'])->map(function ($parameter, $key) {
                                        return [
                                            'key' => $key,
                                            'value' => $parameter['value'] ?? '',
                                            'type' => 'text',
                                            'enabled' => true,
                                        ];
                                    })->values()->toArray(),
                                ],
                                'description' => $route['metadata']['description'],
                                'response' => [],
                            ],
                        ];
                    })->toArray(),
                ];
            })->values()->toArray(),
        ];

        return json_encode($collection);
    }
}
