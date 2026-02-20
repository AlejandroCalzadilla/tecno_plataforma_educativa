<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use App\Models\Disponibilidad;
use App\Models\Servicio;
use App\Models\Tutor;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Calendario::with(['servicio', 'tutor.usuario', 'disponibilidades'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('servicio', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%");
            });
        }

        if ($request->filled('id_servicio')) {
            $query->where('id_servicio', $request->input('id_servicio'));
        }

        if ($request->filled('id_tutor')) {
            $query->where('id_tutor', $request->input('id_tutor'));
        }

        if ($request->filled('tipo_programacion')) {
            $query->where('tipo_programacion', $request->input('tipo_programacion'));
        }

        $calendarios = $query->paginate(10)->withQueryString();
        $servicios = Servicio::all();
        $tutores = Tutor::with('usuario:id,name')->get();

        return Inertia::render('Calendarios/Index', [
            'calendarios' => $calendarios,
            'servicios' => $servicios,
            'tutores' => $tutores,
            'filters' => $request->only(['search', 'id_servicio', 'id_tutor', 'tipo_programacion']),
        ]);
    }

    public function create()
    {
        $servicios = Servicio::all();
        $tutores = Tutor::with('usuario:id,name')->get();

        return Inertia::render('Calendarios/Create', [
            'servicios' => $servicios,
            'tutores' => $tutores,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_servicio' => 'required|exists:servicio,id',
            'id_tutor' => 'required|exists:tutor,id',
            'tipo_programacion' => 'required|in:CITA_LIBRE,PAQUETE_FIJO',
            'numero_sesiones' => 'nullable|integer|min:1|required_if:tipo_programacion,PAQUETE_FIJO',
            'duracion_sesion_minutos' => 'required|integer|min:15',
            'costo_total' => 'required|numeric|min:0',
            'cupos_maximos' => 'required|integer|min:1',
            'disponibilidades' => 'required|array|min:1',
            'disponibilidades.*.dia_semana' => 'required|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
            'disponibilidades.*.hora_apertura' => 'required|date_format:H:i',
            'disponibilidades.*.hora_cierre' => 'required|date_format:H:i|after:disponibilidades.*.hora_apertura',
        ], [
            'disponibilidades.required' => 'Debes registrar al menos una disponibilidad.',
            'disponibilidades.min' => 'Debes registrar al menos una disponibilidad.',
            'numero_sesiones.required_if' => 'El número de sesiones es obligatorio para paquetes fijos.',
            
        ]);

        if ($this->tieneCruceDisponibilidadTutor($validated['id_tutor'], $validated['disponibilidades'])) {
            return Redirect::back()
                ->withErrors(['disponibilidades' => 'El tutor tiene conflicto de disponibilidad en uno o más rangos.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated) {
            $isPaquete = $validated['tipo_programacion'] === 'PAQUETE_FIJO';

            $calendario = Calendario::create([
                'id_servicio' => $validated['id_servicio'],
                'id_tutor' => $validated['id_tutor'],
                'tipo_programacion' => $validated['tipo_programacion'],
                'numero_sesiones' => $isPaquete ? $validated['numero_sesiones'] : null,
                'duracion_sesion_minutos' => $validated['duracion_sesion_minutos'],
                'costo_total' => $validated['costo_total'],
                'cupos_maximos' => $validated['cupos_maximos'],
            ]);
            $rows = collect($validated['disponibilidades'])
                ->map(fn ($item) => [
                    'id_calendario' => $calendario->id,
                    'dia_semana' => $item['dia_semana'],
                    'hora_apertura' => $item['hora_apertura'],
                    'hora_cierre' => $item['hora_cierre'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();
            Disponibilidad::insert($rows);
        });
        return Redirect::route('calendarios.index')->with('success', 'Calendario creado correctamente.');
    }

    public function edit(Calendario $calendario)
    {
        $calendario->load(['disponibilidades']);
        $servicios = Servicio::all();
        $tutores = Tutor::with('usuario:id,name')->get();

        return Inertia::render('Calendarios/Edit', [
            'calendario' => $calendario,
            'servicios' => $servicios,
            'tutores' => $tutores,
        ]);
    }

    public function update(Request $request, Calendario $calendario)
    {
        $validated = $request->validate([
            'id_servicio' => 'required|exists:servicio,id',
            'id_tutor' => 'required|exists:tutor,id',
            'tipo_programacion' => 'required|in:CITA_LIBRE,PAQUETE_FIJO',
            'numero_sesiones' => 'nullable|integer|min:1|required_if:tipo_programacion,PAQUETE_FIJO',
            'duracion_sesion_minutos' => 'required|integer|min:15',
            'costo_total' => 'required|numeric|min:0',
            'cupos_maximos' => 'required|integer|min:1',
            'disponibilidades' => 'required|array|min:1',
            'disponibilidades.*.dia_semana' => 'required|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
            'disponibilidades.*.hora_apertura' => 'required|date_format:H:i',
            'disponibilidades.*.hora_cierre' => 'required|date_format:H:i|after:disponibilidades.*.hora_apertura',
        ]);

        if ($this->tieneCruceDisponibilidadTutor($validated['id_tutor'], $validated['disponibilidades'], $calendario->id)) {
            return Redirect::back()
                ->withErrors(['disponibilidades' => 'El tutor tiene conflicto de disponibilidad en uno o más rangos.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $calendario) {
            $isPaquete = $validated['tipo_programacion'] === 'PAQUETE_FIJO';

            $calendario->update([
                'id_servicio' => $validated['id_servicio'],
                'id_tutor' => $validated['id_tutor'],
                'tipo_programacion' => $validated['tipo_programacion'],
                'numero_sesiones' => $isPaquete ? $validated['numero_sesiones'] : null,
                'duracion_sesion_minutos' => $validated['duracion_sesion_minutos'],
                'costo_total' => $validated['costo_total'],
                'cupos_maximos' => $validated['cupos_maximos'],
            ]);

            $calendario->disponibilidades()->delete();
            $rows = collect($validated['disponibilidades'])
                ->map(fn ($item) => [
                    'id_calendario' => $calendario->id,
                    'dia_semana' => $item['dia_semana'],
                    'hora_apertura' => $item['hora_apertura'],
                    'hora_cierre' => $item['hora_cierre'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

            Disponibilidad::insert($rows);
        });

        return Redirect::route('calendarios.index')->with('success', 'Calendario actualizado correctamente.');
    }

    public function destroy(Calendario $calendario)
    {
        if ($calendario->inscripciones()->exists()) {
            return Redirect::back()
                ->with('error', 'No puedes eliminar un calendario con inscripciones activas.');
        }

        $calendario->delete();

        return Redirect::route('calendarios.index')->with('success', 'Calendario eliminado correctamente.');
    }

    private function tieneCruceDisponibilidadTutor(int $idTutor, array $nuevasDisponibilidades, ?int $calendarioId = null): bool
    {
        $query = Calendario::where('id_tutor', $idTutor)->with('disponibilidades');

        if ($calendarioId) {
            $query->where('id', '!=', $calendarioId);
        }

        $otrosCalendarios = $query->get();

        foreach ($nuevasDisponibilidades as $nueva) {
            $nuevoInicio = $nueva['hora_apertura'];
            $nuevoFin = $nueva['hora_cierre'];
            $nuevoDia = $nueva['dia_semana'];

            foreach ($otrosCalendarios as $calendario) {
                foreach ($calendario->disponibilidades as $existente) {
                    if ($existente->dia_semana !== $nuevoDia) {
                        continue;
                    }

                    $existeCruce = $nuevoInicio < $existente->hora_cierre
                        && $nuevoFin > $existente->hora_apertura;

                    if ($existeCruce) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
