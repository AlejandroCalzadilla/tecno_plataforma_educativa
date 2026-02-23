<?php

namespace App\Http\Middleware;

use App\Models\PageViewCounter;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    // Agregamos esto para facilitar el acceso en Vue
                    'roles' => [
                        'propietario' => (bool) $request->user()->is_propietario,
                        'tutor' => (bool) $request->user()->is_tutor,
                        'alumno' => (bool) $request->user()->is_alumno,
                    ],
                ] : null,
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'pageViews' => function () use ($request) {
                $routeNames = $request->session()->get('counted_route_names', []);

                if (empty($routeNames)) {
                    return [];
                }

                return PageViewCounter::query()
                    ->whereIn('route_name', $routeNames)
                    ->orderBy('route_name')
                    ->get(['route_name', 'views'])
                    ->map(fn (PageViewCounter $counter) => [
                        'route_name' => $counter->route_name,
                        'views' => (int) $counter->views,
                    ])
                    ->values();
            },
        ];

    }
}
