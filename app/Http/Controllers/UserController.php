<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Servicio;
use App\Models\CategoriaNivel;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $query = User::orderBy('created_at', 'desc');

        // Filtro por búsqueda (nombre o email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $role = $request->input('role');
            if ($role === 'alumno') {
                $query->where('is_alumno', true);
            } elseif ($role === 'tutor') {
                $query->where('is_tutor', true);
            } elseif ($role === 'propietario') {
                $query->where('is_propietario', true);
            }
        }

        $usuarios = $query->paginate(10);

        return Inertia::render('Users/Index', [
            'usuarios' => $usuarios,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * 2. CREATE: Muestra el formulario de creación.
     */
    public function create()
    {
        // Necesitamos las categorías para llenar el <select> del formulario
        $categorias = CategoriaNivel::all();
        return Inertia::render('Users/Create', [
            'categorias' => $categorias
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
    public function edit(User $user)
    {
        // Traemos las categorías para el select

        if ($user->is_tutor) {
            $tutor = Tutor::where('id_usuario', $user->id)->first();
        }
        if ($user->is_alumno) {
            $alumno = Alumno::where('id_usuario', $user->id)->first();
        }

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'tutor' => $tutor ?? null,
            'alumno' => $alumno ?? null,
        ]);
    }

    /**
     * 5. UPDATE: Actualiza el tutor existente.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            /* 'estado' => 'required|in:ACTIVO,INACTIVO,BLOQUEADO', */
            'is_alumno' => 'boolean',
            'is_tutor' => 'boolean',
            'is_propietario' => 'boolean',
            // Campos de alumno
            'direccion' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'nivel_educativo' => 'nullable|string|max:50',
            // Campos de tutor
            'especialidad' => 'nullable|string|max:100',
            'biografia' => 'nullable|string',
            'banco_nombre' => 'nullable|string|max:100',
            'banco_cbu' => 'nullable|string|max:50',
        ]);

        // Actualizar datos del usuario
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            /*  'estado' => $validated['estado'], */
            'is_alumno' => $validated['is_alumno'] ?? false,
            'is_tutor' => $validated['is_tutor'] ?? false,
            'is_propietario' => $validated['is_propietario'] ?? false,
        ]);

        // Actualizar datos de alumno si aplica
        if ($validated['is_alumno'] ?? false) {
            Alumno::updateOrCreate(
                ['id_usuario' => $user->id],
                [
                    'direccion' => $validated['direccion'] ?? null,
                    'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null,
                    'nivel_educativo' => $validated['nivel_educativo'] ?? null,
                ]
            );
        }

        // Actualizar datos de tutor si aplica
        if ($validated['is_tutor'] ?? false) {
            Tutor::updateOrCreate(
                ['id_usuario' => $user->id],
                [
                    'especialidad' => $validated['especialidad'] ?? null,
                    'biografia' => $validated['biografia'] ?? null,
                    'banco_nombre' => $validated['banco_nombre'] ?? null,
                    'banco_cbu' => $validated['banco_cbu'] ?? null,
                ]
            );
        }

        return Redirect::route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * 6. DESTROY: Elimina (Soft o Hard delete) el tutor.
     */
    public function destroy(User $user)
    {
        
        $user->delete();

        // Asegúrate de usar redirect() o Redirect::route()
        return Redirect::route('users.index');
    }

    
}