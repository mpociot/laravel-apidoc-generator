```python
import requests
import json

url = '{{ rtrim($baseUrl, '/') }}/{{ ltrim($route['boundUri'], '/') }}'
@if(count($route['cleanBodyParameters']))
payload = {
@foreach($route['cleanBodyParameters'] as $parameter => $value)
    '{{ $parameter }}': '{{ $value }}'@if(!($loop->last)),
@endif
@endforeach

}
@endif
@if(count($route['cleanQueryParameters']))
params = {
@foreach($route['cleanQueryParameters'] as $parameter => $value)
	'{{ $parameter }}': '{{ $value }}'@if(!($loop->last)),
@endif
@endforeach

}
@endif
headers = {
@foreach($route['headers'] as $header => $value)
	'{{$header}}': '{{$value}}'@if(!($loop->last)),
@endif
@endforeach

}
response = requests.request('{{$route['methods'][0]}}', url, headers=headers{{ count($route['cleanBodyParameters']) ? ', json=payload' : '' }}{{ count($route['cleanQueryParameters']) ? ', params=params' : ''}})
response.json()
```
