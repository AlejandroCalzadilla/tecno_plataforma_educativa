<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use App\Models\CategoriaNivel;
use App\Models\Cuota;
use App\Models\Inscripcion;
use App\Models\Asistencia;
use App\Models\SesionProgramada;
use App\Models\Servicio;
use App\Models\Venta;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    public function index(Request $request)
    {
        $query = Servicio::query()
            ->where('estado_activo', true)
            ->with('categoria:id,nombre,id_categoria_padre') 
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
        $categorias = CategoriaNivel::get();
        return Inertia::render('Catalogo.Index', [
            'servicios' => $servicios,
            'categorias' => $categorias,
            'filters' => $request->only(['search', 'modalidad']),
        ]);
    }




    
    public function show(Request $request, Servicio $servicio)
    {

        //dd($servicio->id, "servicio recibido en el controlador show");
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

        [$fechaMinimaCita, $fechaMaximaCita] = $this->rangoCitaLibrePermitido();
        $fechasDisponiblesCitaLibre = $calendario->tipo_programacion === 'CITA_LIBRE'
            ? $this->obtenerFechasDisponiblesCitaLibre($calendario, $fechaMinimaCita, $fechaMaximaCita)
            : [];

        $fechaInicio = isset($validated['fecha_inicio'])
            ? Carbon::parse($validated['fecha_inicio'])->startOfDay()
            : now()->startOfDay();

        if ($calendario->tipo_programacion === 'CITA_LIBRE') {
            if (isset($validated['fecha_inicio']) && !in_array($fechaInicio->toDateString(), $fechasDisponiblesCitaLibre, true)) {
                return Redirect::back()->withErrors([
                    'fecha_inicio' => 'La fecha seleccionada no está disponible para este calendario.',
                ]);
            }

            if (!isset($validated['fecha_inicio']) && !empty($fechasDisponiblesCitaLibre)) {
                $fechaInicio = Carbon::parse($fechasDisponiblesCitaLibre[0])->startOfDay();
            }
        }

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
            'fechasDisponiblesCitaLibre' => $fechasDisponiblesCitaLibre,
            'rangoCitaLibre' => [
                'inicio' => $fechaMinimaCita->toDateString(),
                'fin' => $fechaMaximaCita->toDateString(),
            ],
            'params' => [
                'id_alumno' => $validated['id_alumno'] ?? '',
                'fecha_inicio' => $fechaInicio->toDateString(),
            ],
        ]);
    }

    public function pago(Request $request, Calendario $calendario)
    {


        // dd("es aca no");
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

        [$fechaMinimaCita, $fechaMaximaCita] = $this->rangoCitaLibrePermitido();
        $fechasDisponiblesCitaLibre = $calendario->tipo_programacion === 'CITA_LIBRE'
            ? $this->obtenerFechasDisponiblesCitaLibre($calendario, $fechaMinimaCita, $fechaMaximaCita)
            : [];

        $fechaInicio = isset($validated['fecha_inicio'])
            ? Carbon::parse($validated['fecha_inicio'])->startOfDay()
            : now()->startOfDay();

        if ($calendario->tipo_programacion === 'CITA_LIBRE') {
            if (!in_array($fechaInicio->toDateString(), $fechasDisponiblesCitaLibre, true)) {
                return Redirect::route('catalogo.inscripcion.preview', $calendario)
                    ->withErrors([
                        'fecha_inicio' => 'La fecha seleccionada ya no está disponible. Elige otra fecha libre.',
                    ]);
            }
        }

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
        $authUser = auth()->user();
        $alumno = $authUser?->alumno;

        if (!$alumno) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes iniciar sesión como alumno para confirmar el pago.',
                ], 401);
            }

            return Redirect::route('login');
        }

        //dd($request, "datos recibidos para confirmar pago");
        $validated = $request->validate([
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

        if ($calendario->tipo_programacion === 'CITA_LIBRE') {
            [$fechaMinimaCita, $fechaMaximaCita] = $this->rangoCitaLibrePermitido();
            $fechasDisponiblesCitaLibre = $this->obtenerFechasDisponiblesCitaLibre($calendario, $fechaMinimaCita, $fechaMaximaCita);

            if (!in_array($fechaInicio->toDateString(), $fechasDisponiblesCitaLibre, true)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La fecha seleccionada ya no está disponible para esta cita libre.',
                    ], 422);
                }

                return Redirect::back()->withErrors([
                    'pago' => 'La fecha seleccionada ya no está disponible para esta cita libre.',
                ]);
            }
        }

        $sesionesProgramadas = $this->generarSesionesProgramadasPreview($calendario, $fechaInicio);

        if ($calendario->tipo_programacion === 'CITA_LIBRE' && count($sesionesProgramadas) === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo generar la sesión para este calendario. Revisa su disponibilidad.',
                ], 422);
            }

            return Redirect::back()->withErrors([
                'pago' => 'No se pudo generar la sesión para este calendario. Revisa su disponibilidad.',
            ]);
        }
        //dd('llega al trabajo');
        try {
            $result = DB::transaction(function () use ($validated, $calendario, $sesionesProgramadas, $fechaInicio, $alumno) {
                $inscripcion = Inscripcion::create([
                    'id_alumno' => $alumno->id,
                    'id_calendario' => $calendario->id,
                    'fecha_inscripcion' => now(),
                    'estado_academico' => 'CURSANDO',
                ]);

                if ($calendario->tipo_programacion === 'PAQUETE_FIJO') {
                    $sesionIds = SesionProgramada::query()
                        ->where('id_calendario', $calendario->id)
                        ->pluck('id');

                    if ($sesionIds->isEmpty()) {
                        throw new \RuntimeException('El calendario de tipo PAQUETE_FIJO no tiene sesiones programadas.');
                    }

                    $asistencias = $sesionIds->map(fn($sesionId) => [
                        'id_sesion' => $sesionId,
                        'id_inscripcion' => $inscripcion->id,
                        'estado_asistencia' => 'PENDIENTE',
                        'observaciones' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all();

                    Asistencia::insert($asistencias);
                }

                if ($calendario->tipo_programacion === 'CITA_LIBRE') {
                    Calendario::query()
                        ->whereKey($calendario->id)
                        ->lockForUpdate()
                        ->first();

                    $yaOcupada = Asistencia::query()
                        ->join('sesion_programada', 'sesion_programada.id', '=', 'asistencia.id_sesion')
                        ->where('sesion_programada.id_calendario', $calendario->id)
                        ->whereDate('sesion_programada.fecha_sesion', $fechaInicio->toDateString())
                        ->exists();

                    if ($yaOcupada) {
                        throw new \RuntimeException('La fecha seleccionada fue ocupada por otra inscripción.');
                    }

                    $primeraSesion = $sesionesProgramadas[0];

                    $sesion = SesionProgramada::create([
                        'id_calendario' => $calendario->id,
                        'fecha_sesion' => $primeraSesion['fecha_sesion'],
                        'hora_inicio' => $primeraSesion['fecha_hora_inicio'],
                        'hora_fin' => $primeraSesion['fecha_hora_fin'],
                        'link_sesion' => null,
                        'numero_sesion' => null,
                    ]);

                    Asistencia::create([
                        'id_sesion' => $sesion->id,
                        'id_inscripcion' => $inscripcion->id,
                        'estado_asistencia' => 'PENDIENTE',
                        'observaciones' => null,
                    ]);
                }

                // ── Crear Venta ──────────────────────────────────────────
                $tipoPago       = $validated['tipo_pago_pref'];
                $cantidadCuotas = $tipoPago === 'CONTADO' ? 1 : (int) ($validated['cantidad_cuotas'] ?? 1);
                $montoTotal     = (float) $calendario->costo_total;

                $venta = Venta::create([
                    'id_inscripcion'    => $inscripcion->id,
                    'monto_total'       => $montoTotal,
                    'saldo_pendiente'   => $montoTotal,
                    'tipo_pago_pref'    => $tipoPago,
                    'estado_financiero' => 'PENDIENTE',
                ]);

                // ── Crear Cuotas ─────────────────────────────────────────
                $montoPorCuota = round($montoTotal / $cantidadCuotas, 2);
                $primeraCuota  = null;

                for ($i = 1; $i <= $cantidadCuotas; $i++) {
                    $monto = $i === $cantidadCuotas
                        ? round($montoTotal - ($montoPorCuota * ($cantidadCuotas - 1)), 2)
                        : $montoPorCuota;

                    $cuota = Cuota::create([
                        'id_venta'          => $venta->id,
                        'numero_cuota'      => $i,
                        'monto_cuota'       => $monto,
                        'fecha_vencimiento' => Carbon::parse($fechaInicio)->addMonths($i - 1)->toDateString(),
                        'estado_pago'       => 'PENDIENTE',
                    ]);

                    if ($i === 1) {
                        $primeraCuota = $cuota;
                    }
                }

                return compact('inscripcion', 'primeraCuota');
            });
        } catch (\RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return Redirect::back()->withErrors([
                'pago' => $exception->getMessage(),
            ]);
        }

        $inscripcion  = $result['inscripcion'];
        $primeraCuota = $result['primeraCuota'];

        // QR → devolver JSON para que el frontend muestre el QR en la misma página
        if ($validated['metodo_pago'] === 'QR') {
            return response()->json([
                'success'         => true,
                'id_cuota'        => $primeraCuota->id,
                'monto'           => (string) $primeraCuota->monto_cuota,
                'numero_cuota'    => 1,
                'servicio_nombre' => $calendario->servicio->nombre ?? 'Servicio',
            ]);
        }

        return Redirect::route('catalogo.index')
            ->with('success', 'Inscripción #' . $inscripcion->id . ' creada correctamente.');
    }

    private function rangoCitaLibrePermitido(): array
    {
        $inicio = now()->startOfDay();
        $fin = $inicio->copy()->addDays(30);

        return [$inicio, $fin];
    }

    private function obtenerFechasDisponiblesCitaLibre(Calendario $calendario, CarbonInterface $fechaInicio, CarbonInterface $fechaFin): array
    {
        $diasDisponibles = $calendario->disponibilidades
            ->pluck('dia_semana')
            ->filter()
            ->unique()
            ->values();

        if ($diasDisponibles->isEmpty()) {
            return [];
        }

        $fechasOcupadas = Asistencia::query()
            ->join('sesion_programada', 'sesion_programada.id', '=', 'asistencia.id_sesion')
            ->where('sesion_programada.id_calendario', $calendario->id)
            ->whereBetween('sesion_programada.fecha_sesion', [
                $fechaInicio->toDateString(),
                $fechaFin->toDateString(),
            ])
            ->pluck('sesion_programada.fecha_sesion')
            ->map(fn ($fecha) => Carbon::parse($fecha)->toDateString())
            ->unique()
            ->values()
            ->all();

        $fechasLibres = [];
        $cursor = Carbon::parse($fechaInicio->toDateString())->startOfDay();
        $ultimoDia = Carbon::parse($fechaFin->toDateString())->startOfDay();

        while ($cursor->lessThanOrEqualTo($ultimoDia)) {
            $fechaActual = $cursor->toDateString();
            $diaActual = $this->nombreDiaPorNumero($cursor->dayOfWeekIso);

            if ($diasDisponibles->contains($diaActual) && !in_array($fechaActual, $fechasOcupadas, true)) {
                $fechasLibres[] = $fechaActual;
            }

            $cursor->addDay();
        }

        return $fechasLibres;
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
                    'numero_cuota'     => 1,
                    'monto_cuota'      => number_format($montoTotal, 2, '.', ''),
                    'fecha_vencimiento'=> $fechaBase->toDateString(),
                ]
            ];
        }

        $montoCuota = round($montoTotal / $cantidadCuotas, 2);
        $cuotas     = [];

        for ($i = 1; $i <= $cantidadCuotas; $i++) {
            // La última cuota absorbe la diferencia de redondeo
            $monto = $i === $cantidadCuotas
                ? round($montoTotal - ($montoCuota * ($cantidadCuotas - 1)), 2)
                : $montoCuota;

            $cuotas[] = [
                'numero_cuota'      => $i,
                'monto_cuota'       => number_format($monto, 2, '.', ''),
                'fecha_vencimiento' => $fechaBase->copy()->addMonths($i - 1)->toDateString(),
            ];
        }

        return $cuotas;
    }
}
