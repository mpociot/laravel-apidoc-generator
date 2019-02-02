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

    private $usePostmanEnvironment;

    private $addResponseExample;

    /**
     * CollectionWriter constructor.
     *
     * @param Collection $routeGroups
     */
    public function __construct(Collection $routeGroups)
    {
        $this->routeGroups = $routeGroups;
        $this->usePostmanEnvironment = (bool) $this->getPostmanEnvironment();
        $this->addResponseExample = config('apidoc.postman.add_response_example', false);
    }

    protected function getPostmanEnvironment()
    {
        return config('apidoc.postman.environment');
    }

    /**
     * @param $route
     * @return string
     */
    function getRouteUri($route)
    {
        if ($this->usePostmanEnvironment) {
            $baseUrl = config('apidoc.postman.environment.variables.baseUrl');
            if ($baseUrl) {
                return '{{' . $baseUrl . '}}' . '/' . $route['uri'];
            } else {
                return url($route['uri']);
            }
        }

        return url($route['uri']);
    }

    /**
     * Returns Auth response object key with access token string
     *
     * @return string
     */
    protected function getResponseAccessTokenKey()
    {
        return config('apidoc.postman.environment.auth_response_access_token_key', 'data');
    }

    /**
     * Returns Auth response object key with refresh token string
     *
     * @return string
     */
    protected function getResponseRefreshTokenKey()
    {
        return config('apidoc.postman.environment.auth_response_refresh_token_key', 'data');
    }

    protected function getAccessTokenVariable()
    {
        return config('apidoc.postman.environment.variables.accessToken');
    }

    protected function getRefreshTokenVariable()
    {
        return config('apidoc.postman.environment.variables.refreshToken');
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
                            'name' => $route['title'] != '' ? $route['title'] : $this->getRouteUri($route),
                            'event' => [[
                                'listen' => 'test',
                                'script' => $this->usePostmanEnvironment ? [
                                    'id' => Uuid::uuid4()->toString(),
                                    'exec' => [
                                        'var response = JSON.parse(responseBody);',
                                        'tests["Successfull POST request"] = responseCode.code === 200;',
                                        'if (response.' . $this->getResponseAccessTokenKey() . ') { postman.setEnvironmentVariable("'. $this->getAccessTokenVariable() .'", response.' . $this->getResponseAccessTokenKey() . '); }',
                                        'if (response.' . $this->getResponseRefreshTokenKey() . ') { postman.setEnvironmentVariable("'. $this->getRefreshTokenVariable() .'", response.' . $this->getResponseRefreshTokenKey() . '); }',
                                    ],
                                    'type' => 'text/javascript',
                                ] : [],
                            ]],
                            'request' => [
                                'auth' => [
                                    'type' => 'bearer',
                                    'bearer' => [
                                        'token' => '{{'. $this->getAccessTokenVariable() .'}}',
                                    ],
                                ],
                                'url' => $this->getRouteUri($route),
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
                            'response' => $this->addResponseExample ? [
                                [
                                    'name' => $route['title'] != '' ? $route['title'] : $this->getRouteUri($route),
                                    'originalRequest' => [
                                        'auth' => [
                                            'type' => 'bearer',
                                            'bearer' => [
                                                'token' => '{{'. $this->getAccessTokenVariable() .'}}',
                                            ],
                                        ],
                                        'url' => $this->getRouteUri($route),
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
                            ] : [],
                        ];
                    })->toArray(),
                ];
            })->values()->toArray(),
        ];

        return json_encode($collection);
    }
}
