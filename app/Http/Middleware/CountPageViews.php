<?php

namespace App\Http\Middleware;

use App\Models\PageViewCounter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CountPageViews
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $next($request);
        }

        $sessionKey = 'counted_route_names';
        $countedRouteNames = $request->session()->get($sessionKey, []);

        if (!in_array($routeName, $countedRouteNames, true)) {
            $counter = PageViewCounter::query()->firstOrCreate(
                ['route_name' => $routeName],
                ['views' => 0],
            );

            $counter->increment('views');

            $countedRouteNames[] = $routeName;
            $request->session()->put($sessionKey, $countedRouteNames);
        }

        return $next($request);
    }
}
