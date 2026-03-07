<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        $allowed = collect($roles)->contains(function (string $role) use ($user) {
            return match ($role) {
                'propietario' => (bool) $user->is_propietario,
                'tutor' => (bool) $user->is_tutor,
                'alumno' => (bool) $user->is_alumno,
                default => false,
            };
        });

        if (!$allowed) {
            abort(403);
        }

        return $next($request);
    }
}
