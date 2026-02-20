<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use App\Models\Inscripcion;
use App\Models\SesionProgramada;
use App\Models\Servicio;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Laravel\Pail\ValueObjects\Origin\Console;

class CatalogoController extends Controller
{
    public function index(Request $request)
    {
        $query = Servicio::query()
            ->where('estado_activo', true)
            ->withCount('calendarios')
            ->orderBy('nombre');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if ($request->filled('modalidad')) {
            $modalidad = $request->input('modalidad');

            if ($modalidad === 'FISICO') {
                $query->whereIn('modalidad', ['PRESENCIAL', 'HIBRIDO']);
            } else {
                $query->where('modalidad', $modalidad);
            }
        }

        $servicios = $query->paginate(9)->withQueryString();

        return Inertia::render('Catalogo/Index', [
            'servicios' => $servicios,
            'filters' => $request->only(['search', 'modalidad']),
        ]);
    }

    public function show(Request $request, Servicio $servicio)
    {
        $calendariosQuery = Calendario::query()
            ->where('id_servicio', $servicio->id)
            ->with(['tutor.usuario:id,name', 'disponibilidades'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('id_tutor')) {
            $calendariosQuery->where('id_tutor', $request->input('id_tutor'));
        }

        if ($request->filled('tipo_programacion')) {
            $calendariosQuery->where('tipo_programacion', $request->input('tipo_programacion'));
        }

        $calendarios = $calendariosQuery->paginate(10)->withQueryString();

        $tutores = Calendario::query()
            ->where('id_servicio', $servicio->id)
            ->with('tutor.usuario:id,name')
            ->get()
            ->pluck('tutor')
            ->filter()
            ->unique('id')
            ->values();

        return Inertia::render('Catalogo/Show', [
            'servicio' => $servicio,
            'calendarios' => $calendarios,
            'tutores' => $tutores,
            'filters' => $request->only(['id_tutor', 'tipo_programacion']),
        ]);
    }

    public function previewInscripcion(Request $request, Calendario $calendario)
    {
        $validated = $request->validate([
            'id_alumno' => 'nullable|integer|exists:alumno,id',
            'fecha_inicio' => 'nullable|date',
        ]);

        $calendario->load(['servicio', 'tutor.usuario:id,name', 'disponibilidades']);

        $fechaInicio = isset($validated['fecha_inicio'])
            ? Carbon::parse($validated['fecha_inicio'])->startOfDay()
            : now()->startOfDay();

        $sesionesProgramadas = $this->generarSesionesProgramadasPreview($calendario, $fechaInicio);

        $inscripcionPreview = [
            'id_alumno' => $validated['id_alumno'] ?? null,
            'id_calendario' => $calendario->id,
            'estado_academico' => 'PENDIENTE_PAGO',
            'fecha_inscripcion' => now()->toDateTimeString(),
            'tipo_programacion' => $calendario->tipo_programacion,
            'total_sesiones' => count($sesionesProgramadas),
            'costo_total' => $calendario->costo_total,
            'nota' => 'Vista previa. No se guarda inscripción ni sesiones hasta confirmar pago.',
        ];

        return Inertia::render('Catalogo/PreviewInscripcion', [
            'calendario' => $calendario,
            'inscripcionPreview' => $inscripcionPreview,
            'sesionesProgramadas' => $sesionesProgramadas,
            'params' => [
                'id_alumno' => $validated['id_alumno'] ?? '',
                'fecha_inicio' => $fechaInicio->toDateString(),
            ],
        ]);
    }

    public function pago(Request $request, Calendario $calendario)
    {

        //dd($request->all(), "que llega al controlador");

        $validated = $request->validate([
            'id_alumno' => 'nullable|integer|exists:alumno,id',
            'fecha_inicio' => 'nullable|date',
            'tipo_pago_pref' => 'nullable|in:CONTADO,CUOTAS',
            'cantidad_cuotas' => 'nullable|integer|min:2|max:24',
            'metodo_pago' => 'nullable|in:EFECTIVO,QR,TRANSFERENCIA,TARJETA',
        ]);
        //dd($validated, "que llega al controlador");

        $calendario->load(['servicio', 'tutor.usuario:id,name', 'disponibilidades']);

        $fechaInicio = isset($validated['fecha_inicio'])
            ? Carbon::parse($validated['fecha_inicio'])->startOfDay()
            : now()->startOfDay();

        $sesionesProgramadas = $this->generarSesionesProgramadasPreview($calendario, $fechaInicio);
        $tipoPago = $validated['tipo_pago_pref'] ?? 'CONTADO';
        $cantidadCuotas = $tipoPago === 'CUOTAS'
            ? (int) ($validated['cantidad_cuotas'] ?? 2)
            : 1;

        return Inertia::render('Catalogo/PagoFake', [
            'calendario' => $calendario,
            'sesionesProgramadas' => $sesionesProgramadas,
            'params' => [
                'id_alumno' => $validated['id_alumno'] ?? '',
                'fecha_inicio' => $fechaInicio->toDateString(),
                'tipo_pago_pref' => $tipoPago,
                'cantidad_cuotas' => $cantidadCuotas,
                'metodo_pago' => $validated['metodo_pago'] ?? 'QR',
            ],
            'cuotasPreview' => $this->generarCuotasPreview((float) $calendario->costo_total, $cantidadCuotas, $fechaInicio),
        ]);
    }

    public function confirmarPago(Request $request, Calendario $calendario)
    {

        //dd($request, "datos recibidos para confirmar pago");
        $validated = $request->validate([
            'id_alumno' => 'required|integer',
            'fecha_inicio' => 'required|date',
            'tipo_pago_pref' => 'required|in:CONTADO,CUOTAS',

            // 👇 Usamos exclude_if para que si es CONTADO, no importe qué valor venga
            'cantidad_cuotas' => [
                'exclude_if:tipo_pago_pref,CONTADO',
                'required_if:tipo_pago_pref,CUOTAS',
                'integer',
                'min:2',
                'max:24'
            ],

            'metodo_pago' => 'required|in:EFECTIVO,QR,TRANSFERENCIA,TARJETA',
        ]);
        //dd($validated, "datos recibidos para confirmar pago despues de validación");

        $calendario->load('disponibilidades');
        $fechaInicio = Carbon::parse($validated['fecha_inicio'])->startOfDay();
        $sesionesProgramadas = $this->generarSesionesProgramadasPreview($calendario, $fechaInicio);

        if (count($sesionesProgramadas) === 0) {
            dd('no se pudieron generar sesiones para este calendario. Revisa su disponibilidad.', "error en generación de sesiones");
            return Redirect::back()->withErrors([
                'pago' => 'No se pudo generar sesiones para este calendario. Revisa su disponibilidad.',
            ]);
        }
        //dd('llega al trabajo');
        $inscripcion = DB::transaction(function () use ($validated, $calendario, $sesionesProgramadas) {
            $inscripcion = Inscripcion::create([
                'id_alumno' => $validated['id_alumno'],
                'id_calendario' => $calendario->id,
                'fecha_inscripcion' => now(),
                'estado_academico' => 'CURSANDO',
            ]);

            $rows = collect($sesionesProgramadas)->map(function ($sesion) use ($inscripcion) {
                return [
                    'id_inscripcion' => $inscripcion->id,
                    'fecha_sesion' => $sesion['fecha_sesion'],
                    'fecha_hora_inicio' => $sesion['fecha_hora_inicio'],
                    'fecha_hora_fin' => $sesion['fecha_hora_fin'],
                    'estado_asistencia' => 'PENDIENTE',
                    'numero_sesion' => $sesion['numero_sesion'],
                    'observaciones' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            SesionProgramada::insert($rows);

            return $inscripcion;
        });

        return Redirect::route('catalogo.index')
            ->with('success', 'Pago simulado correctamente. Inscripción #' . $inscripcion->id . ' guardada con sus sesiones programadas.');
    }

    private function generarSesionesProgramadasPreview(Calendario $calendario, CarbonInterface $fechaInicio): array
    {
        $disponibilidades = $calendario->disponibilidades
            ->sortBy(fn($item) => $this->ordenDia($item->dia_semana) * 10000 + (int) str_replace(':', '', substr($item->hora_apertura, 0, 5)))
            ->values();

        if ($disponibilidades->isEmpty()) {
            return [];
        }

        $totalSesiones = $calendario->tipo_programacion === 'PAQUETE_FIJO'
            ? max(1, (int) ($calendario->numero_sesiones ?? 1))
            : 1;

        $sesiones = [];
        $cursor = $fechaInicio->copy();
        $maxDiasBusqueda = 730;

        while (count($sesiones) < $totalSesiones && $maxDiasBusqueda > 0) {
            $dia = $this->nombreDiaPorNumero($cursor->dayOfWeekIso);

            $bloque = $disponibilidades->firstWhere('dia_semana', $dia);

            if ($bloque) {
                $inicio = Carbon::parse($cursor->format('Y-m-d') . ' ' . substr($bloque->hora_apertura, 0, 5));
                $finDisponibilidad = Carbon::parse($cursor->format('Y-m-d') . ' ' . substr($bloque->hora_cierre, 0, 5));

                $fin = $inicio->copy()->addMinutes((int) $calendario->duracion_sesion_minutos);

                if ($fin->greaterThan($finDisponibilidad)) {
                    $fin = $finDisponibilidad;
                }

                $sesiones[] = [
                    'numero_sesion' => count($sesiones) + 1,
                    'fecha_sesion' => $cursor->toDateString(),
                    'dia_semana' => $dia,
                    'fecha_hora_inicio' => $inicio->format('H:i'),
                    'fecha_hora_fin' => $fin->format('H:i'),
                    'estado_asistencia' => 'PENDIENTE',
                ];
            }

            $cursor->addDay();
            $maxDiasBusqueda--;
        }

        return $sesiones;
    }

    private function ordenDia(string $dia): int
    {
        return match ($dia) {
            'LUNES' => 1,
            'MARTES' => 2,
            'MIERCOLES' => 3,
            'JUEVES' => 4,
            'VIERNES' => 5,
            'SABADO' => 6,
            'DOMINGO' => 7,
            default => 8,
        };
    }

    private function nombreDiaPorNumero(int $numero): string
    {
        return match ($numero) {
            1 => 'LUNES',
            2 => 'MARTES',
            3 => 'MIERCOLES',
            4 => 'JUEVES',
            5 => 'VIERNES',
            6 => 'SABADO',
            7 => 'DOMINGO',
            default => 'LUNES',
        };
    }

    private function generarCuotasPreview(float $montoTotal, int $cantidadCuotas, CarbonInterface $fechaBase): array
    {
        if ($cantidadCuotas <= 1) {
            return [
                [
                    'numero_cuota' => 1,
                    'monto_cuota' => number_format($montoTotal, 2, '.', ''),
                    'fecha_vencimiento' => $fechaBase->toDateString(),
                ]
            ];
        }

        $montoCuota = round($montoTotal / $cantidadCuotas, 2);
        $cuotas = [];

        for ($index = 1; $index <= $cantidadCuotas; $index++) {
            $cuotas[] = [
                'numero_cuota' => $index,
                'monto_cuota' => number_format($montoCuota, 2, '.', ''),
                'fecha_vencimiento' => $fechaBase->copy()->addMonths($index - 1)->toDateString(),
            ];
        }

        return $cuotas;
    }
}
