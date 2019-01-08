<!-- START_{{$route['id']}} -->
@if($route['title'] != '')## {{ $route['title']}}
@else## {{$route['uri']}}@endif
@if($route['authenticated'])

<br><small style="padding: 1px 9px 2px;font-weight: bold;white-space: nowrap;color: #ffffff;-webkit-border-radius: 9px;-moz-border-radius: 9px;border-radius: 9px;background-color: #3a87ad;">Requires authentication</small>@endif
@if($route['description'])

{!! $route['description'] !!}
@endif

> Example request:

```bash
curl -X {{$route['methods'][0]}} {{$route['methods'][0] == 'GET' ? '-G ' : ''}}"{{ trim(config('app.docs_url') ?: config('app.url'), '/')}}/{{ ltrim($route['uri'], '/') }}" @if(count($route['headers']))\
@foreach($route['headers'] as $header => $value)
    -H "{{$header}}: {{$value}}"@if(! ($loop->last) || ($loop->last && count($route['bodyParameters']))) \
@endif
@endforeach
@endif
@if(count($route['cleanBodyParameters']))
    -d '{!! json_encode($route['cleanBodyParameters']) !!}'
@endif

```

```javascript
const url = new URL("{{ rtrim(config('app.docs_url') ?: config('app.url'), '/') }}/{{ ltrim($route['uri'], '/') }}");
@if(count($route['queryParameters']))

    let params = {
    @foreach($route['queryParameters'] as $attribute => $parameter)
        "{{ $attribute }}": "{{ $parameter['value'] }}",
    @endforeach
    };
    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
@endif

let headers = {
@foreach($route['headers'] as $header => $value)
    "{{$header}}": "{{$value}}",
@endforeach
@if(!array_key_exists('Accept', $route['headers']))
    "Accept": "application/json",
@endif
@if(!array_key_exists('Content-Type', $route['headers']))
    "Content-Type": "application/json",
@endif
}
@if(count($route['bodyParameters']))

let body = {!! json_encode($route['cleanBodyParameters'], JSON_PRETTY_PRINT) !!}
@endif

fetch(url, {
    method: "{{$route['methods'][0]}}",
    headers: headers,
@if(count($route['bodyParameters']))
    body: body
@endif
})
    .then(response => response.json())
    .then(json => console.log(json));
```

@if(in_array('GET',$route['methods']) || (isset($route['showresponse']) && $route['showresponse']))
@if(is_array($route['response']))
@foreach($route['response'] as $response)
> Example response ({{$response['status']}}):

```json
@if(is_object($response['content']) || is_array($response['content']))
{!! json_encode($response['content'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@else
{!! json_encode(json_decode($response['content']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@endif
```
@endforeach
@else
> Example response:

```json
@if(is_object($route['response']) || is_array($route['response']))
{!! json_encode($route['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@else
{!! json_encode(json_decode($route['response']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@endif
```
@endif
@endif

### HTTP Request
@foreach($route['methods'] as $method)
`{{$method}} {{$route['uri']}}`

@endforeach
@if(count($route['bodyParameters']))
#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
@foreach($route['bodyParameters'] as $attribute => $parameter)
    {{$attribute}} | {{$parameter['type']}} | @if($parameter['required']) required @else optional @endif | {!! $parameter['description'] !!}
@endforeach
@endif
@if(count($route['queryParameters']))
#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
@foreach($route['queryParameters'] as $attribute => $parameter)
    {{$attribute}} | @if($parameter['required']) required @else optional @endif | {!! $parameter['description'] !!}
@endforeach
@endif

<!-- END_{{$route['id']}} -->
