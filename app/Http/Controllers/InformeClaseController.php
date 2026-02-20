<?php

namespace App\Http\Controllers;

use App\Models\InformeClase;
use App\Models\SesionProgramada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class InformeClaseController extends Controller
{
    public function index(Request $request)
    {
        $query = InformeClase::with('asistencia.inscripcion.alumno.usuario')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('temas_vistos', 'like', "%{$search}%");
        }

        if ($request->filled('desempenio')) {
            $query->where('desempenio', $request->input('desempenio'));
        }

        return Inertia::render('InformesClase/Index', [
            'informes' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'desempenio']),
        ]);
    }

    public function create()
    {
        $sesiones = SesionProgramada::with('inscripcion.alumno.usuario')
            ->whereDoesntHave('informe')
            ->orderByDesc('fecha_sesion')
            ->get();

        return Inertia::render('InformesClase/Create', [
            'sesiones' => $sesiones,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asistencia' => 'required|exists:sesion_programada,id|unique:informeclase,id_asistencia',
            'temas_vistos' => 'nullable|string',
            'tareas_asignadas' => 'nullable|string',
            'desempenio' => 'nullable|in:BAJO,MEDIO,ALTO,EXCELENTE',
        ]);

        InformeClase::create($validated);

        return Redirect::route('informes-clase.index')->with('success', 'Informe de clase creado correctamente.');
    }

    public function edit(InformeClase $informes_clase)
    {
        $sesiones = SesionProgramada::with('inscripcion.alumno.usuario')
            ->where(function ($query) use ($informes_clase) {
                $query->whereDoesntHave('informe')
                    ->orWhere('id', $informes_clase->id_asistencia);
            })
            ->orderByDesc('fecha_sesion')
            ->get();

        return Inertia::render('InformesClase/Edit', [
            'informe' => $informes_clase,
            'sesiones' => $sesiones,
        ]);
    }

    public function update(Request $request, InformeClase $informes_clase)
    {
        $validated = $request->validate([
            'id_asistencia' => 'required|exists:sesion_programada,id|unique:informeclase,id_asistencia,' . $informes_clase->id_informe . ',id_informe',
            'temas_vistos' => 'nullable|string',
            'tareas_asignadas' => 'nullable|string',
            'desempenio' => 'nullable|in:BAJO,MEDIO,ALTO,EXCELENTE',
        ]);

        $informes_clase->update($validated);

        return Redirect::route('informes-clase.index')->with('success', 'Informe de clase actualizado correctamente.');
    }

    public function destroy(InformeClase $informes_clase)
    {
        $informes_clase->delete();

        return Redirect::route('informes-clase.index')->with('success', 'Informe de clase eliminado correctamente.');
    }
}
