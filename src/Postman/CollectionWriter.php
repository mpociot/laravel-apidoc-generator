<?php

namespace Mpociot\ApiDoc\Postman;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class CollectionWriter
{
    /**
     * @var Collection
     */
    private $routeGroups;

    /**
     * CollectionWriter constructor.
     *
     * @param Collection $routeGroups
     */
    public function __construct(Collection $routeGroups)
    {
        $this->routeGroups = $routeGroups;
    }

    public function getCollection()
    {
        URL::forceRootUrl(config('app.url'));

        $collection = [
            'variables' => [],
            'info' => [
                'name' => config('apidoc.postman.name') ?: config('app.name').' API',
                '_postman_id' => Uuid::uuid4()->toString(),
                'description' => config('apidoc.postman.description') ?: '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json',
            ],
            'item' => $this->routeGroups->map(function ($routes, $group) {
                list($groupName, $groupDescription) = explode("\n\n", $group);
                return [
                    'name' => $groupName,
                    'description' => $groupDescription,
                    'item' => $routes->map(function ($route) {
                        $mode = $route['methods'][0] === 'PUT' ? 'urlencoded' : 'formdata';

                        return [
                            'name' => $route['title'] != '' ? $route['title'] : '{{baseUrl}}/' . $route['uri'],
                            'event' => [[
                                'listen' => 'test',
                                'script' => [
                                    'id' => Uuid::uuid4()->toString(),
                                    'exec' => [
                                        'var response = JSON.parse(responseBody);',
                                        'postman.setEnvironmentVariable("authToken", response.data.access_token);',
                                        'tests["Successfull POST request"] = responseCode.code === 200;',
                                        'tests["TokenType of correct type"] = response.data.token_type === "Bearer";',
                                    ],
                                    'type' => 'text/javascript',
                                ],
                            ]],
                            'request' => [
                                'auth' => [
                                    'type' => 'bearer',
                                    'bearer' => [
                                        'token' => '{{authToken}}',
                                    ],
                                ],
                                'url' => '{{baseUrl}}/' . $route['uri'],
                                'method' => $route['methods'][0],
                                'header' => collect($route['headers'])->map(function ($header, $key) use ($route) {
                                    return [
                                        'key' => $key,
                                        'name' => $key,
                                        'type' => 'text',
                                        'value' => $header,
                                    ];
                                })->values()->toArray(),
                                'body' => [
                                    'mode' => $mode,
                                    $mode => collect($route['bodyParameters'])->map(function ($parameter, $key) {
                                        return [
                                            'key' => $key,
                                            'value' => isset($parameter['value']) ? ($parameter['type'] === 'boolean' ? (string) $parameter['value'] : $parameter['value']) : '',
                                            'type' => 'text',
                                            'enabled' => true,
                                        ];
                                    })->values()->toArray(),
                                ],
                                'description' => $route['description'],
                            ],
                            'response' => [
                                [
                                    'name' => $route['title'] != '' ? $route['title'] : '{{baseUrl}}/' . $route['uri'],
                                    'originalRequest' => [
                                        'auth' => [
                                            'type' => 'bearer',
                                            'bearer' => [
                                                'token' => '{{authToken}}',
                                            ],
                                        ],
                                        'url' => '{{baseUrl}}/' . $route['uri'],
                                        'method' => $route['methods'][0],
                                        'header' => collect($route['headers'])->map(function ($header, $key) use ($route) {
                                            return [
                                                'key' => $key,
                                                'name' => $key,
                                                'type' => 'text',
                                                'value' => $header,
                                            ];
                                        })->values()->toArray(),
                                        'body' => [
                                            'mode' => $mode,
                                            $mode => collect($route['bodyParameters'])->map(function ($parameter, $key) {
                                                return [
                                                    'key' => $key,
                                                    'value' => isset($parameter['value']) ? ($parameter['type'] === 'boolean' ? (string) $parameter['value'] : $parameter['value']) : '',
                                                    'type' => 'text',
                                                    'enabled' => true,
                                                ];
                                            })->values()->toArray(),
                                        ],
                                        'description' => $route['description'],
                                    ],
                                    'status' => $route['response'][0]['statusText'],
                                    'code' => $route['response'][0]['status'],
                                    '_postman_previewlanguage' => 'json',
                                    'header' => collect($route['response'][0]['headers'])->map(function ($header, $key) use ($route) {
                                        return [
                                            'key' => $key,
                                            'name' => $key,
                                            'type' => 'text',
                                            'value' => $header,
                                        ];
                                    })->values()->toArray(),
                                    'body' => json_encode(json_decode($route['response'][0]['content']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                                ],
                            ],
                        ];
                    })->toArray(),
                ];
            })->values()->toArray(),
        ];

        return json_encode($collection);
    }
}
