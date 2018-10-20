<!-- START_{{$parsedRoute['id']}} -->
@if($parsedRoute['title'] != '')## {{ $parsedRoute['title']}}
@else## {{$parsedRoute['uri']}}
@endif
@if($parsedRoute['description'])

{!! $parsedRoute['description'] !!}
@endif

> Example request:

```bash
curl -X {{$parsedRoute['methods'][0]}} {{$parsedRoute['methods'][0] == 'GET' ? '-G ' : ''}}"{{ trim(config('app.docs_url') ?: config('app.url'), '/')}}/{{ ltrim($parsedRoute['uri'], '/') }}" \
    -H "Accept: application/json"@if(count($parsedRoute['headers'])) \
@foreach($parsedRoute['headers'] as $header => $value)
    -H "{{$header}}"="{{$value}}" @if(! ($loop->last))\
    @endif
@endforeach
@endif
@if(count($parsedRoute['bodyParameters'])) \
@foreach($parsedRoute['bodyParameters'] as $attribute => $parameter)
    -d "{{$attribute}}"="{{$parameter['value']}}" @if(! ($loop->last))\
    @endif
@endforeach
@endif

```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "{{ rtrim(config('app.docs_url') ?: config('app.url'), '/') }}/{{ ltrim($parsedRoute['uri'], '/') }}",
    "method": "{{$parsedRoute['methods'][0]}}",
    @if(count($parsedRoute['bodyParameters']))
"data": {!! str_replace("\n}","\n    }", str_replace('    ','        ',json_encode(array_combine(array_keys($parsedRoute['bodyParameters']), array_map(function($param){ return $param['value']; },$parsedRoute['bodyParameters'])), JSON_PRETTY_PRINT))) !!},
    @endif
"headers": {
        "accept": "application/json",
@foreach($parsedRoute['headers'] as $header => $value)
        "{{$header}}": "{{$value}}",
@endforeach
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```

@if(in_array('GET',$parsedRoute['methods']) || (isset($parsedRoute['showresponse']) && $parsedRoute['showresponse']))
> Example response:

```json
@if(is_object($parsedRoute['response']) || is_array($parsedRoute['response']))
{!! json_encode($parsedRoute['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@else
{!! json_encode(json_decode($parsedRoute['response']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@endif
```
@endif

### HTTP Request
@foreach($parsedRoute['methods'] as $method)
`{{$method}} {{$parsedRoute['uri']}}`

@endforeach
@if(count($parsedRoute['bodyParameters']))
#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
@foreach($parsedRoute['bodyParameters'] as $attribute => $parameter)
    {{$attribute}} | {{$parameter['type']}} | @if($parameter['required']) required @else optional @endif | {!! implode(' ',$parameter['description']) !!}
@endforeach
@endif
@if(count($parsedRoute['queryParameters']))
#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
@foreach($parsedRoute['queryParameters'] as $attribute => $parameter)
    {{$attribute}} | @if($parameter['required']) required @else optional @endif | {!! implode(' ',$parameter['description']) !!}
@endforeach
@endif

<!-- END_{{$parsedRoute['id']}} -->
