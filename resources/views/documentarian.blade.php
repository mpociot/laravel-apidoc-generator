---
{!! $frontmatter !!}
---
<!-- START_INFO -->
{!! $infoText !!}
<!-- END_INFO -->
{!! $prependMd !!}
@foreach($parsedRoutes as $groupName => $routes)
#{!! $groupName !!}
{{-- We pick the first non-empty description we see. --}}
{!! \Illuminate\Support\Arr::first($routes, function ($route) { return $route['metadata']['groupDescription'] !== ''; })['metadata']['groupDescription'] ?? '' !!}
@foreach($routes as $parsedRoute)
@if($writeCompareFile === true)
{!! $parsedRoute['output'] !!}
@else
{!! isset($parsedRoute['modified_output']) ? $parsedRoute['modified_output'] : $parsedRoute['output'] !!}
@endif
@endforeach
@endforeach{!! $appendMd !!}
