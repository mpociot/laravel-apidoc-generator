```bash
curl -X {{$route['methods'][0]}} \
{{$route['methods'][0] == 'GET' ? '-G ' : ''}}"{{ rtrim($baseUrl, '/')}}/{{ ltrim($route['boundUri'], '/') }}@if(count($route['cleanQueryParameters']))?@foreach($route['cleanQueryParameters'] as $parameter => $value)
    @if (is_array($value))
        {{ urlencode($parameter) }}[]={{ urlencode(array_keys($value)[0]) }}@if(! $loop->last)&@endif
    @else
        {{ urlencode($parameter) }}={{ urlencode($value) }}@if(!$loop->last)&@endif
    @endif
@endforeach
@endif" @if(count($route['headers']))\
@foreach($route['headers'] as $header => $value)
    -H "{{$header}}: {{ addslashes($value) }}"@if(! ($loop->last) || ($loop->last && count($route['bodyParameters']))) \
@endif
@endforeach
@endif
@if(count($route['cleanBodyParameters']))
    -d '{!! json_encode($route['cleanBodyParameters']) !!}'
@endif

```
