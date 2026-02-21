<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\SesionProgramada;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SesionProgramadaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = SesionProgramada::query()
            ->select([
                'id',
                'fecha_sesion',
                'hora_inicio',
                'hora_fin',
                'link_sesion',
            ])
            ->with([
                'asistencias.inscripcion.alumno.usuario',
            ]);

        if ($user?->is_alumno) {
            $query->whereHas('asistencias.inscripcion', function ($q) use ($user) {
                $q->where('id_alumno', $user->alumno->id);
            });
        } elseif ($user?->is_tutor) {
            $query->whereHas('calendario', function ($q) use ($user) {
                $q->where('id_tutor', $user->tutor->id);
            });
        } else {
            return Inertia::render('Sesiones/Index', ['sessions' => []]);
        }

        $sesiones = $query->get()->map(function (SesionProgramada $sesion) use ($user) {
            $asistenciaUsuario = null;

            if ($user?->is_alumno) {
                $asistenciaUsuario = $sesion->asistencias
                    ->where('inscripcion.id_alumno', (int) $user->alumno?->id)
                    ->first();
            } elseif ($user?->is_tutor) {
                $asistenciaUsuario = $sesion->asistencias->first();
            }

            return [
                'id' => $sesion->id,
                'fecha_sesion' => $sesion->fecha_sesion,
                'hora_inicio' => $sesion->hora_inicio,
                'hora_fin' => $sesion->hora_fin,
                'link_virtual' => $sesion->link_sesion,
                'estado_asistencia' => $asistenciaUsuario?->estado_asistencia ?? 'PENDIENTE',
            ];
        })->values();

        return Inertia::render('Sesiones/Index', [
            'sessions' => $sesiones,
        ]);
    }


    public function show(Request $request, SesionProgramada $sesion): Response
    {
        $user = $request->user();

        $sesion->load([
            'calendario.servicio.categoria',
            'calendario.tutor.usuario',
            'asistencias.inscripcion.alumno.usuario',
            'asistencias.licencia',
            'asistencias.informe',
        ]);

        $asistenciaUsuario = null;

        if ($user?->is_tutor) {
            if ((int) $sesion->calendario?->id_tutor !== (int) $user->tutor?->id) {
                abort(403, 'No tienes permiso para ver esta sesión.');
            }
            $asistenciaUsuario = $sesion->asistencias->first();
        } elseif ($user?->is_alumno) {
            $asistenciaUsuario = $sesion->asistencias
                ->where('inscripcion.id_alumno', (int) $user->alumno?->id)
                ->first();

            if (!$asistenciaUsuario) {
                abort(403, 'No tienes permiso para ver esta sesión.');
            }
        } else {
            abort(403, 'No tienes permiso para ver esta sesión.');
        }

        $sessionData = [
            'id' => $sesion->id,
            'fecha' => $sesion->fecha_sesion,
            'hora_inicio' => $sesion->hora_inicio,
            'hora_fin' => $sesion->hora_fin,
            'link_virtual' => $sesion->link_sesion,
            'status' => $asistenciaUsuario?->estado_asistencia ?? 'PENDIENTE',
            'notas' => $asistenciaUsuario?->observaciones,
            'numero_sesion' => $sesion->numero_sesion,
            'servicio' => [
                'id' => $sesion->calendario->servicio->id,
                'nombre' => $sesion->calendario->servicio->nombre,
                'categoria_nivel' => [
                    'id' => $sesion->calendario->servicio->categoria->id,
                    'nombre' => $sesion->calendario->servicio->categoria->nombre,
                ]
            ],
            'tutor' => [
                'id' => $sesion->calendario->tutor->id,
                'user' => [
                    'id' => $sesion->calendario->tutor->usuario->id,
                    'name' => $sesion->calendario->tutor->usuario->name,
                    'email' => $sesion->calendario->tutor->usuario->email,
                ]
            ],
            'alumno' => [
                'id' => $asistenciaUsuario?->inscripcion?->alumno?->id,
                'user' => [
                    'id' => $asistenciaUsuario?->inscripcion?->alumno?->usuario?->id,
                    'name' => $asistenciaUsuario?->inscripcion?->alumno?->usuario?->name,
                    'email' => $asistenciaUsuario?->inscripcion?->alumno?->usuario?->email,
                ]
            ],
            'informes' => $asistenciaUsuario?->informe ? [$asistenciaUsuario->informe] : []
        ];

        return Inertia::render('Sesiones/Show', [
            'session' => $sessionData,
        ]);
    }
}