```php

$client = new \GuzzleHttp\Client();
$response = $client->{{ strtolower($route['methods'][0]) }}(
    '{{ rtrim($baseUrl, '/') . '/' . ltrim($route['boundUri'], '/') }}',
    [
@if(!empty($route['headers']))
        'headers' => {!! \Mpociot\ApiDoc\Tools\Utils::printPhpValue($route['headers'], 8) !!},
@endif
@if(!empty($route['cleanQueryParameters']))
        'query' => [
@foreach($route['cleanQueryParameters'] as $parameter => $value)
            '{{$parameter}}' => '{{$value}}',
@endforeach
        ],
@endif
@if(!empty($route['cleanBodyParameters']))
        'json' => {!! \Mpociot\ApiDoc\Tools\Utils::printPhpValue($route['cleanBodyParameters'], 8) !!},
@endif
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```
