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
{!! $parsedRoute['output'] !!}
@endforeach
@endforeach
