<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class HorarioController extends Controller
{
    /**
     * Crear un nuevo horario.
     */
    public function store(Request $request)
    {
        // A. Validación
        $validated = $request->validate([
            'dia_semana' => 'required|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ], [
            'hora_fin.after' => 'La hora fin debe ser posterior a la hora inicio.',
        ]);

        // B. Verificar si ya existe un horario con los mismos datos
        $existe = Horario::where('dia_semana', $validated['dia_semana'])
            ->where('hora_inicio', $validated['hora_inicio'])
            ->where('hora_fin', $validated['hora_fin'])
            ->exists();

        if ($existe) {
            return Redirect::back()
                ->with('error', 'Este horario ya existe en el sistema.');
        }

        // C. Creación
        $horario = Horario::create([
            'dia_semana' => $validated['dia_semana'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin' => $validated['hora_fin'],
            'estado_disponibilidad' => true,
        ]);

        // D. Responder con el nuevo horario
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'horario' => $horario,
            ]);
        }

        return Redirect::back()->with('success', 'Horario creado correctamente.');
    }
}
