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
    -H "Accept: application/json"@if(count($parsedRoute['parameters'])) \
@foreach($parsedRoute['parameters'] as $attribute => $parameter)
    -d "{{$attribute}}"="{{$parameter['value']}}" @if(! ($loop->last))\
    @endif
@endforeach
@endif

```

```javascript

const headers = new Headers({'Accept': 'application/json'})

const settings = {
    method: "{{$parsedRoute['methods'][0]}}" 
    credentials: 'include'
    headers,
    @if(count($parsedRoute['parameters']))
    body: {!! str_replace("\n}","\n    }", str_replace('    ','        ',json_encode(array_combine(array_keys($parsedRoute['parameters']), array_map(function($param){ return $param['value']; },$parsedRoute['parameters'])), JSON_PRETTY_PRINT))) !!},
    @endif
}

const request = new Request("{{ rtrim(config('app.docs_url') ?: config('app.url'), '/') }}/{{ ltrim($parsedRoute['uri'], '/') }}", settings)

fetch(request)
    .then(response => response.json())
    .then(json => console.log(json))
    .catch(error => console.error(error))
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
@if(count($parsedRoute['parameters']))
#### Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
@foreach($parsedRoute['parameters'] as $attribute => $parameter)
    {{$attribute}} | {{$parameter['type']}} | @if($parameter['required']) required @else optional @endif | {!! implode(' ',$parameter['description']) !!}
@endforeach
@endif

<!-- END_{{$parsedRoute['id']}} -->
