```python
import requests
import json

url = '{{ rtrim($baseUrl, '/') }}/{{ ltrim($route['boundUri'], '/') }}'
@if(count($route['cleanBodyParameters']))
payload = {!! json_encode($route['cleanBodyParameters'], JSON_PRETTY_PRINT) !!}
@endif
@if(count($route['cleanQueryParameters']))
params = {!! \Mpociot\ApiDoc\Tools\Utils::printQueryParamsAsKeyValue($route['cleanQueryParameters'], "'", ":", 2, "{}") !!}
@endif
@if(!empty($route['headers']))
headers = {
@foreach($route['headers'] as $header => $value)
  '{{$header}}': '{{$value}}'@if(!($loop->last)),
@endif
@endforeach

}
@endif
response = requests.request('{{$route['methods'][0]}}', url{{ count($route['headers']) ?', headers=headers' : '' }}{{ count($route['cleanBodyParameters']) ? ', json=payload' : '' }}{{ count($route['cleanQueryParameters']) ? ', params=params' : ''}})
response.json()
```
