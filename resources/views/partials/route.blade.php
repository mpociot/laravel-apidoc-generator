<!-- START_{{$route['id']}} -->
@if($route['metadata']['title'] != '')## {{ $route['metadata']['title']}}
@else## {{$route['uri']}}@endif
@if($route['metadata']['authenticated'])

<br><small style="padding: 1px 9px 2px;font-weight: bold;white-space: nowrap;color: #ffffff;-webkit-border-radius: 9px;-moz-border-radius: 9px;border-radius: 9px;background-color: #3a87ad;">Requires authentication</small>@endif
@if($route['metadata']['description'])

{!! $route['metadata']['description'] !!}
@endif

> Example request:

@foreach($settings['languages'] as $language)
@include("apidoc::partials.example-requests.$language")

@endforeach

@if(in_array('GET',$route['methods']) || (isset($route['showresponse']) && $route['showresponse']))
@foreach($route['responses'] as $response)
> Example response ({{$response['status']}}):

```json
@if(is_object($response['content']) || is_array($response['content']))
{!! json_encode($response['content'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@else
{!! json_encode(json_decode($response['content']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@endif
```
@endforeach
@endif

### HTTP Request
@foreach($route['methods'] as $method)
`{{$method}} {{$route['uri']}}`

@endforeach
@if(count($route['urlParameters']))
#### URL Parameters

Parameter | Status | Description
--------- | ------- | ------- | -------
@foreach($route['urlParameters'] as $attribute => $parameter)
    `{{$attribute}}` | @if($parameter['required']) required @else optional @endif | {!! $parameter['description'] !!}
@endforeach
@endif
@if(count($route['queryParameters']))
#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
@foreach($route['queryParameters'] as $attribute => $parameter)
    `{{$attribute}}` | @if($parameter['required']) required @else optional @endif | {!! $parameter['description'] !!}
@endforeach
@endif
@if(count($route['bodyParameters']))
#### Body Parameters
Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
@foreach($route['bodyParameters'] as $attribute => $parameter)
    `{{$attribute}}` | {{$parameter['type']}} | @if($parameter['required']) required @else optional @endif | {!! $parameter['description'] !!}
    @endforeach
@endif

<!-- END_{{$route['id']}} -->
