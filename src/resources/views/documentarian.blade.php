---
{!! $frontmatter !!}
---
<!-- START_INFO -->
{!! $infoText !!}
<!-- END_INFO -->

@foreach($parsedRoutes as $group => $routes)
@if($group)
#{{$group}}
@endif
@foreach($routes as $parsedRoute)
@if($writeCompareFile === true)
{!! $parsedRoute['output']!!}
@else
{!! isset($parsedRoute['modified_output']) ? $parsedRoute['modified_output'] : $parsedRoute['output']!!}
@endif
@endforeach
@endforeach
