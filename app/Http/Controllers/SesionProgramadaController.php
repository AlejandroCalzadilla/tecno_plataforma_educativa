<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\SesionProgramada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SesionProgramadaController extends Controller
{
    private function resolverModo(Request $request): string
    {
        $user = $request->user();
        $modoSolicitado = $request->query('modo', $request->input('modo'));

        if ($user?->is_alumno && $user?->is_tutor) {
            return in_array($modoSolicitado, ['alumno', 'tutor'], true)
                ? $modoSolicitado
                : 'alumno';
        }

        if ($user?->is_alumno) {
            return 'alumno';
        }

        if ($user?->is_tutor) {
            return 'tutor';
        }

        return 'ninguno';
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $modo = $this->resolverModo($request);

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

        if ($modo === 'alumno') {
            $query->whereHas('asistencias.inscripcion', function ($q) use ($user) {
                $q->where('id_alumno', $user->alumno->id);
            });
        } elseif ($modo === 'tutor') {
            $query->whereHas('calendario', function ($q) use ($user) {
                $q->where('id_tutor', $user->tutor->id);
            });
        } else {
            return Inertia::render('Sesiones/Index', ['sessions' => [], 'modo' => 'ninguno']);
        }
        $sesiones = $query->get()->map(function (SesionProgramada $sesion) use ($user, $modo) {
            $asistenciaUsuario = null;
            if ($modo === 'alumno') {
                $asistenciaUsuario = $sesion->asistencias
                    ->where('inscripcion.id_alumno', (int) $user->alumno?->id)
                    ->first();
            } elseif ($modo === 'tutor') {
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
            'modo' => $modo,
        ]);
    }


    public function show(Request $request, SesionProgramada $sesion): Response
    {
        $user = $request->user();
        $modo = $this->resolverModo($request);

        $sesion->load([
            'calendario.servicio.categoria',
            'calendario.tutor.usuario',
            'asistencias.inscripcion.alumno.usuario',
            'asistencias.licencia',
            'asistencias.informe',
        ]);

        $esTutorDeSesion = (bool) ($user?->is_tutor && (int) $sesion->calendario?->id_tutor === (int) $user->tutor?->id);
        $asistenciaComoAlumno = $user?->is_alumno
            ? $sesion->asistencias
                ->where('inscripcion.id_alumno', (int) $user->alumno?->id)
                ->first()
            : null;

        $accesoTutor = $esTutorDeSesion;
        $accesoAlumno = (bool) $asistenciaComoAlumno;

        if (!$accesoTutor && !$accesoAlumno) {
            abort(403, 'No tienes permiso para ver esta sesión.');
        }

        $rolActivo = null;
        if ($modo === 'tutor' && $accesoTutor) {
            $rolActivo = 'tutor';
        } elseif ($modo === 'alumno' && $accesoAlumno) {
            $rolActivo = 'alumno';
        } elseif ($accesoTutor) {
            $rolActivo = 'tutor';
        } else {
            $rolActivo = 'alumno';
        }

        $asistenciaUsuario = $rolActivo === 'tutor'
            ? $sesion->asistencias->first()
            : $asistenciaComoAlumno;

        $sessionData = [
            'id' => $sesion->id,
            'fecha' => $sesion->fecha_sesion,
            'hora_inicio' => $sesion->hora_inicio,
            'hora_fin' => $sesion->hora_fin,
            'link_virtual' => $sesion->link_sesion,
            'status' => $asistenciaUsuario?->estado_asistencia ?? 'PENDIENTE',
            'notas' => $asistenciaUsuario?->observaciones,
            'mi_asistencia_id' => $asistenciaUsuario?->id,
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
            'asistencias' => $sesion->asistencias->map(fn ($asistencia) => [
                'id' => $asistencia->id,
                'estado_asistencia' => $asistencia->estado_asistencia,
                'observaciones' => $asistencia->observaciones,
                'alumno' => [
                    'id' => $asistencia->inscripcion?->alumno?->id,
                    'user' => [
                        'id' => $asistencia->inscripcion?->alumno?->usuario?->id,
                        'name' => $asistencia->inscripcion?->alumno?->usuario?->name,
                        'email' => $asistencia->inscripcion?->alumno?->usuario?->email,
                    ],
                ],
            ])->values(),
            'es_tutor' => $rolActivo === 'tutor',
            'es_alumno' => $rolActivo === 'alumno',
            'modo_activo' => $rolActivo,
            'informes' => $asistenciaUsuario?->informe ? [$asistenciaUsuario->informe] : []
        ];

        return Inertia::render('Sesiones/Show', [
            'session' => $sessionData,
        ]);
    }



    public function updateLink(Request $request, SesionProgramada $sesion)
    {
        $user = $request->user();

        if (!$user?->is_tutor || (int) $sesion->calendario?->id_tutor !== (int) $user->tutor?->id) {
            abort(403, 'No tienes permiso para actualizar el link de esta sesión.');
        }
        $validated = $request->validate([
            'link_sesion' => 'required|url|max:255',
        ]);
        $sesion->update([
            'link_sesion' => $validated['link_sesion'],
        ]);
      return Redirect::back()->with('success', 'Link de sesión actualizado correctamente.');
    }

    

    public function updateAsistencia(Request $request, SesionProgramada $sesion, Asistencia $asistencia)
    {
        $user = $request->user();

        if (!$user?->is_tutor || (int) $sesion->calendario?->id_tutor !== (int) $user->tutor?->id) {
            abort(403, 'No tienes permiso para registrar asistencia en esta sesión.');
        }

        if ((int) $asistencia->id_sesion !== (int) $sesion->id) {
            abort(422, 'La asistencia no pertenece a la sesión seleccionada.');
        }

        $validated = $request->validate([
            'estado_asistencia' => 'required|in:PENDIENTE,PRESENTE,AUSENTE,TARDANZA,JUSTIFICADO',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $asistencia->update([
            'estado_asistencia' => $validated['estado_asistencia'],
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        return Redirect::back()->with('success', 'Asistencia actualizada correctamente.');
    }
}