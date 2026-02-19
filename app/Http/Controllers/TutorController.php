<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\CategoriaNivel;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class TutorController extends Controller
{
    
    public function index()
    {
        $servicios = Tutor::orderBy('created_at', 'desc')
            ->paginate(10);
        // Retornamos la vista de Inertia con los datos como "props"
        return Inertia::render('Tutores/Index', [
            'tutores' => $servicios
        ]);
    }

    /**
     * 2. CREATE: Muestra el formulario de creación.
     */
    public function create()
    {
        // Necesitamos las categorías para llenar el <select> del formulario
        $tutores = Tutor::all();
        return Inertia::render('Tutores/Create', [
            'tutores' => $tutores
        ]);
    }

    /**
     * 3. STORE: Guarda el nuevo tutor en la BD.
     */
    public function store(Request $request)
    {
        // A. Validación
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_categoria' => 'required|exists:CategoriaNivel,id', // Valida que la FK exista
            'costo_base' => 'required|numeric|min:0',
            'modalidad' => 'required|in:VIRTUAL,PRESENCIAL,HIBRIDO',
            'descripcion' => 'nullable|string',
            'duracion_semanas' => 'nullable|integer',
            'duracion_horas' => 'nullable|integer',
        ]);
        // B. Creación
        Tutor::create($validated);
        // C. Redirección con Mensaje Flash
        // Inertia intercepta esto y lo pasa al frontend sin recargar
        return Redirect::route('tutores.index')->with('success', 'Tutor creado correctamente.');
    }

    /**
     * 4. EDIT: Muestra el formulario de edición con datos cargados.
     */
    public function edit(Tutor $tutor)
    {
        // Traemos las categorías para el select
        $categorias = CategoriaNivel::all();
        return Inertia::render('Tutores/Edit', [
            'tutor' => $tutor,
            'categorias' => $categorias
        ]);
    }

    /**
     * 5. UPDATE: Actualiza el tutor existente.
     */
    public function update(Request $request, Tutor $tutor)
    {
        // Validación similar al store
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_categoria' => 'required|exists:CategoriaNivel,id',
            'costo_base' => 'required|numeric|min:0',
            'modalidad' => 'required|in:VIRTUAL,PRESENCIAL,HIBRIDO',
            'estado_activo' => 'boolean', // Campo extra que suele editarse
            'descripcion' => 'nullable|string',
        ]);

        $tutor->update($validated);
        return Redirect::route('tutores.index')->with('success', 'Tutor actualizado correctamente.');
    }

    /**
     * 6. DESTROY: Elimina (Soft o Hard delete) el tutor.
     */
    public function destroy(Tutor $tutor)
    {
        // Opcional: Validar si tiene inscripciones activas antes de borrar
        if ($tutor->inscripciones()->exists()) {
            return Redirect::back()->with('error', 'No puedes eliminar un tutor con alumnos inscritos.');
        }
        $tutor->delete();

        return Redirect::route('tutores.index')->with('success', 'Tutor eliminado.');
    }
}