<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use App\Models\Servicio;
use App\Models\Horario;
use App\Models\Tutor;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CalendarioController extends Controller
{
    /**
     * 1. INDEX: Muestra la lista de calendarios.
     */
    public function index(Request $request)
    {
        $query = Calendario::with(['servicio', 'tutor', 'horarios'])
            ->orderBy('created_at', 'desc');

        // Filtro por búsqueda (nombre de servicio)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('servicio', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%");
            });
        }

        // Filtro por servicio
        if ($request->filled('id_servicio')) {
            $query->where('id_servicio', $request->input('id_servicio'));
        }

        // Filtro por tutor
        if ($request->filled('id_tutor')) {
            $query->where('id_tutor', $request->input('id_tutor'));
        }

        $calendarios = $query->paginate(10);
        $servicios = Servicio::all();
        $tutores = Tutor::all();
        $horarios = Horario::all();

        return Inertia::render('Calendarios/Index', [
            'calendarios' => $calendarios,
            'servicios' => $servicios,
            'tutores' => $tutores,
            'horarios' => $horarios,
            'filters' => $request->only(['search', 'id_servicio', 'id_tutor']),
        ]);
    }

    /**
     * 2. CREATE: Muestra el formulario de creación.
     */
    public function create()
    {
        
        $servicios = Servicio::all();
        $tutores = Tutor::all();
        $horarios = Horario::all();

        return Inertia::render('Calendarios/Create', [
            'servicios' => $servicios,
            'tutores' => $tutores,
            'horarios' => $horarios,
        ]);
    }

    /**
     * 3. STORE: Guarda el nuevo calendario en la BD.
     */
    public function store(Request $request)
    {
        // A. Validación
        $validated = $request->validate([
            'id_servicio' => 'required|exists:servicio,id',
            'id_tutor' => 'required|exists:tutor,id',
            'costo_base' => 'required|numeric|min:0',
            'cupos_MAX' => 'required|integer|min:1',
            'horarios' => 'required|array|min:1',
            'horarios.*' => 'exists:horario,id',
        ], [
            'horarios.required' => 'Debes seleccionar al menos un horario.',
            'horarios.min' => 'Debes seleccionar al menos un horario.',
        ]);

        // B. Validar que no haya conflicto de horarios con el tutor
        if (!$this->validarHorariosDisponibles($validated['id_tutor'], $validated['horarios'])) {
            return Redirect::back()
                ->with('error', 'El tutor tiene conflicto de horarios. Selecciona horarios sin conflicto.');
        }

        // C. Creación
        $calendario = Calendario::create([
            'id_servicio' => $validated['id_servicio'],
            'id_tutor' => $validated['id_tutor'],
            'costo_base' => $validated['costo_base'],
            'cupos_MAX' => $validated['cupos_MAX'],
            'cupos_actual' => 0,
        ]);

        // D. Vincular horarios (relación N:M)
        $calendario->horarios()->attach($validated['horarios']);

        // E. Redirección con Mensaje Flash
        return Redirect::route('calendarios.index')->with('success', 'Calendario creado correctamente.');
    }

    /**
     * 4. EDIT: Muestra el formulario de edición con datos cargados.
     */
    public function edit(Calendario $calendario)
    {
        $calendario->load(['horarios']);
        $servicios = Servicio::all();
        $tutores = Tutor::all();
        $horarios = Horario::all();

        return Inertia::render('Calendarios/Edit', [
            'calendario' => $calendario,
            'servicios' => $servicios,
            'tutores' => $tutores,
            'horarios' => $horarios,
        ]);
    }

    /**
     * 5. UPDATE: Actualiza el calendario existente.
     */
    public function update(Request $request, Calendario $calendario)
    {
        // Validación
        $validated = $request->validate([
            'id_servicio' => 'required|exists:servicio,id',
            'id_tutor' => 'required|exists:tutor,id',
            'costo_base' => 'required|numeric|min:0',
            'cupos_MAX' => 'required|integer|min:1',
            'horarios' => 'required|array|min:1',
            'horarios.*' => 'exists:horario,id',
        ]);

        // Validar que no haya conflicto de horarios
        if (!$this->validarHorariosDisponibles($validated['id_tutor'], $validated['horarios'], $calendario->id)) {
            return Redirect::back()
                ->with('error', 'El tutor tiene conflicto de horarios. Selecciona horarios sin conflicto.');
        }

        // Actualizar datos
        $calendario->update([
            'id_servicio' => $validated['id_servicio'],
            'id_tutor' => $validated['id_tutor'],
            'costo_base' => $validated['costo_base'],
            'cupos_MAX' => $validated['cupos_MAX'],
        ]);

        // Actualizar horarios (sincronizar)
        $calendario->horarios()->sync($validated['horarios']);

        return Redirect::route('calendarios.index')->with('success', 'Calendario actualizado correctamente.');
    }

    /**
     * 6. DESTROY: Elimina el calendario.
     */
    public function destroy(Calendario $calendario)
    {
        // Validar si tiene inscripciones activas
        if ($calendario->inscripciones()->exists()) {
            return Redirect::back()
                ->with('error', 'No puedes eliminar un calendario con inscripciones activas.');
        }

        $calendario->delete();

        return Redirect::route('calendarios.index')->with('success', 'Calendario eliminado correctamente.');
    }

    /**
     * Validar que los horarios no choquen con otros calendarios del mismo tutor.
     */
    private function validarHorariosDisponibles($id_tutor, $horarios, $calendario_id = null)
    {
        // Obtener los horarios del tutor en otros calendarios
        $query = Calendario::where('id_tutor', $id_tutor);

        // Excluir el calendario actual si estamos editando
        if ($calendario_id) {
            $query->where('id', '!=', $calendario_id);
        }

        $otros_calendarios = $query->with('horarios')->get();

        // Obtener los horarios que el tutor ya tiene
        $horarios_ocupados = [];
        foreach ($otros_calendarios as $cal) {
            foreach ($cal->horarios as $horario) {
                $horarios_ocupados[] = $horario->id;
            }
        }

        // Verificar que ninguno de los nuevos horarios esté ocupado
        foreach ($horarios as $horario_id) {
            if (in_array($horario_id, $horarios_ocupados)) {
                return false;
            }
        }

        return true;
    }
}
