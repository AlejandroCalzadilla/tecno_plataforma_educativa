<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Licencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LicenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Licencia::with('asistencia.inscripcion.alumno.usuario')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('motivo', 'like', "%{$search}%");
        }

        if ($request->filled('estado_aprobacion')) {
            $query->where('estado_aprobacion', $request->input('estado_aprobacion'));
        }

        return Inertia::render('Licencias/Index', [
            'licencias' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'estado_aprobacion']),
        ]);
    }

    public function create()
    {
        $sesiones = Asistencia::with('sesion', 'inscripcion.alumno.usuario')
            ->whereDoesntHave('licencia')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Licencias/Create', [
            'sesiones' => $sesiones->map(fn($asistencia) => [
                'id' => $asistencia->id,
                'fecha_sesion' => $asistencia->sesion?->fecha_sesion,
                'numero_sesion' => $asistencia->sesion?->numero_sesion,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asistencia' => 'required|exists:asistencia,id|unique:licencia,id_asistencia',
            'motivo' => 'required|string',
            'evidencia_url' => 'nullable|string|max:255',
            'estado_aprobacion' => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
            'observacion_admin' => 'nullable|string',
        ]);

        Licencia::create($validated);

        return Redirect::route('licencias.index')->with('success', 'Licencia creada correctamente.');
    }

    public function edit(Licencia $licencia)
    {
        $sesiones = Asistencia::with('sesion', 'inscripcion.alumno.usuario')
            ->where(function ($query) use ($licencia) {
                $query->whereDoesntHave('licencia')
                    ->orWhere('id', $licencia->id_asistencia);
            })
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Licencias/Edit', [
            'licencia' => $licencia,
            'sesiones' => $sesiones->map(fn($asistencia) => [
                'id' => $asistencia->id,
                'fecha_sesion' => $asistencia->sesion?->fecha_sesion,
                'numero_sesion' => $asistencia->sesion?->numero_sesion,
            ])->values(),
        ]);
    }

    public function update(Request $request, Licencia $licencia)
    {
        $validated = $request->validate([
            'id_asistencia' => 'required|exists:asistencia,id|unique:licencia,id_asistencia,' . $licencia->id_licencia . ',id_licencia',
            'motivo' => 'required|string',
            'evidencia_url' => 'nullable|string|max:255',
            'estado_aprobacion' => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
            'observacion_admin' => 'nullable|string',
        ]);

        $licencia->update($validated);

        return Redirect::route('licencias.index')->with('success', 'Licencia actualizada correctamente.');
    }

    public function destroy(Licencia $licencia)
    {
        $licencia->delete();

        return Redirect::route('licencias.index')->with('success', 'Licencia eliminada correctamente.');
    }
}
