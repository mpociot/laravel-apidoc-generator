```javascript
const url = new URL(
    "{{ rtrim($baseUrl, '/') }}/{{ ltrim($route['boundUri'], '/') }}"
);
@if(count($route['cleanQueryParameters']))

let params = {!! \Mpociot\ApiDoc\Tools\Utils::printQueryParamsAsKeyValue($route['cleanQueryParameters'], "\"", ":", 4, "{}") !!};
Object.keys(params)
    .forEach(key => url.searchParams.append(key, params[key]));
@endif

@if(!empty($route['headers']))
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
};
@endif
@if(count($route['cleanBodyParameters']))

let body = {!! json_encode($route['cleanBodyParameters'], JSON_PRETTY_PRINT) !!}
@endif

fetch(url, {
    method: "{{$route['methods'][0]}}",
@if(count($route['headers']))
    headers: headers,
@endif
@if(count($route['bodyParameters']))
    body: body
@endif
})
    .then(response => response.json())
    .then(json => console.log(json));
```
