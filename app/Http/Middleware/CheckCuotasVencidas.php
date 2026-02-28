<?php

namespace App\Http\Middleware;

use App\Models\Cuota;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCuotasVencidas
{
    /**
     * Rutas a las que el alumno SÍ puede acceder aunque tenga cuotas vencidas.
     * Se comprueban con routeIs() (soporta wildcards).
     */
    private const RUTAS_PERMITIDAS = [
        'pagos.forzado',
        'pagofacil.*',
        'logout',
        'password.*',
        'two-factor.*',
        'profile.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Solo aplica a alumnos autenticados
        if (! $user || ! $user->is_alumno) {
            return $next($request);
        }

        // Rutas siempre permitidas (pago, logout, etc.)
        if ($request->routeIs(...self::RUTAS_PERMITIDAS)) {
            return $next($request);
        }

        // Comprueba si el alumno tiene cuotas VENCIDO
        $alumno = $user->alumno;

        if (! $alumno) {
            return $next($request);
        }

        $tieneVencidas = Cuota::whereHas('venta.inscripcion', fn ($q) =>
            $q->where('id_alumno', $alumno->id)
        )
            ->where('estado_pago', 'VENCIDO')
            ->exists();

        if ($tieneVencidas) {
            return redirect()->route('pagos.forzado');
        }

        return $next($request);
    }
}
