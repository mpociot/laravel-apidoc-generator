```python
import requests
import json

url = '{{ trim(config('app.docs_url') ?: config('app.url'), '/')}}/{{ ltrim($route['uri'], '/') }}'
@if(count($route['bodyParameters']))
payload = {
	@foreach($route['bodyParameters'] as $attribute => $parameter)
	    '{{ $attribute }}': '{{ $parameter['value'] }}'@if(!($loop->last)),@endif  {{ !$parameter['required'] ? '# optional' : '' }}
	@endforeach
}
@endif
@if(count($route['queryParameters']))
params = {
	@foreach($route['queryParameters'] as $attribute => $parameter)
	    '{{ $attribute }}': '{{ $parameter['value'] }}'@if(!($loop->last)),@endif  {{ !$parameter['required'] ? '# optional' : '' }}
	@endforeach
}
@endif
headers = {
	@foreach($route['headers'] as $header => $value)
	    '{{$header}}': '{{$value}}'@if(!($loop->last)),@endif
	@endforeach
}
response = requests.request('{{$route['methods'][0]}}', url, headers=headers{{ count($route['bodyParameters']) ? ', data=payload' : '' }}{{ count($route['queryParameters']) ? ', params=params' : ''}})
response.json()
```
