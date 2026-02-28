<?php

namespace App\Http\Controllers;

use App\Exports\DashboardReportExport;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Calendario;
use App\Models\Inscripcion;
use App\Models\Pago;
use App\Models\Servicio;
use App\Models\Tutor;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // --- Filtros ---
        $desde   = $request->get('desde', now()->startOfMonth()->toDateString());
        $hasta   = $request->get('hasta', now()->toDateString());
        $servicio = $request->get('servicio');     // id_servicio
        $modalidad = $request->get('modalidad');   // VIRTUAL | PRESENCIAL | HIBRIDO
        $estado_academico = $request->get('estado_academico'); // CURSANDO, FINALIZADO, etc.

        // ============================================================
        // KPIs FINANCIEROS
        // ============================================================

        // Ingresos del período (pagos realizados)
        $pagosQuery = Pago::whereBetween('fecha_pago', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        if ($servicio || $modalidad) {
            $pagosQuery->whereHas('cuota.venta.inscripcion.calendario', function ($q) use ($servicio, $modalidad) {
                if ($servicio) $q->where('id_servicio', $servicio);
                if ($modalidad) $q->whereHas('servicio', fn($s) => $s->where('modalidad', $modalidad));
            });
        }
        $ingresos_periodo = (float) $pagosQuery->sum('monto_abonado');

        // Saldo pendiente total (global, sin filtro de fecha)
        $saldo_pendiente = (float) Venta::whereIn('estado_financiero', ['PENDIENTE', 'PARCIAL'])->sum('saldo_pendiente');

        // Ventas por estado en el período
        $ventasQuery = Venta::whereBetween('fecha_emision', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        if ($servicio || $modalidad) {
            $ventasQuery->whereHas('inscripcion.calendario', function ($q) use ($servicio, $modalidad) {
                if ($servicio) $q->where('id_servicio', $servicio);
                if ($modalidad) $q->whereHas('servicio', fn($s) => $s->where('modalidad', $modalidad));
            });
        }
        $ventas_por_estado = $ventasQuery->selectRaw('estado_financiero, count(*) as total, sum(monto_total) as monto')
            ->groupBy('estado_financiero')
            ->get()
            ->keyBy('estado_financiero')
            ->map(fn($v) => ['total' => (int)$v->total, 'monto' => (float)$v->monto]);

        // Distribución métodos de pago en el período
        $metodos_pago = $pagosQuery->selectRaw('metodo_pago, count(*) as total, sum(monto_abonado) as monto')
            ->groupBy('metodo_pago')
            ->get()
            ->map(fn($v) => ['metodo' => $v->metodo_pago, 'total' => (int)$v->total, 'monto' => (float)$v->monto]);

        // ============================================================
        // KPIs ACADÉMICOS
        // ============================================================

        $total_alumnos = Alumno::count();

        // Inscripciones en el período
        $inscripcionesQuery = Inscripcion::whereBetween('fecha_inscripcion', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        if ($servicio) {
            $inscripcionesQuery->whereHas('calendario', fn($q) => $q->where('id_servicio', $servicio));
        }
        if ($modalidad) {
            $inscripcionesQuery->whereHas('calendario.servicio', fn($q) => $q->where('modalidad', $modalidad));
        }
        if ($estado_academico) {
            $inscripcionesQuery->where('estado_academico', $estado_academico);
        }
        $inscripciones_periodo = $inscripcionesQuery->count();

        // Distribución por estado académico (en período)
        $estados_query = Inscripcion::whereBetween('fecha_inscripcion', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        if ($servicio) {
            $estados_query->whereHas('calendario', fn($q) => $q->where('id_servicio', $servicio));
        }
        if ($modalidad) {
            $estados_query->whereHas('calendario.servicio', fn($q) => $q->where('modalidad', $modalidad));
        }
        $distribucion_estados = $estados_query
            ->selectRaw('estado_academico, count(*) as total')
            ->groupBy('estado_academico')
            ->pluck('total', 'estado_academico');

        // Promedio calificación final (inscripciones finalizadas)
        $promedio_calificacion = round(
            Inscripcion::where('estado_academico', 'FINALIZADO')
                ->whereNotNull('calificacion_final')
                ->avg('calificacion_final') ?? 0,
            2
        );

        // Tasa de finalización (finalizados / total con estado no PENDIENTE_PAGO)
        $total_activos = Inscripcion::whereIn('estado_academico', ['CURSANDO', 'FINALIZADO', 'ABANDONADO'])->count();
        $total_finalizados = Inscripcion::where('estado_academico', 'FINALIZADO')->count();
        $tasa_finalizacion = $total_activos > 0 ? round(($total_finalizados / $total_activos) * 100, 1) : 0;







        
        // ============================================================
        // KPIs DE ASISTENCIA
        // ============================================================

        $asistenciasQuery = Asistencia::whereHas('sesion.calendario', function ($q) use ($desde, $hasta, $servicio, $modalidad) {
            $q->whereHas('sesionesProgramadas', fn($s) => $s->whereBetween('fecha_sesion', [$desde, $hasta]));
            if ($servicio) $q->where('id_servicio', $servicio);
            if ($modalidad) $q->whereHas('servicio', fn($s) => $s->where('modalidad', $modalidad));
        });

        $total_asistencias = $asistenciasQuery->count();
        $presentes         = (clone $asistenciasQuery)->where('estado_asistencia', 'PRESENTE')->count();
        $tasa_asistencia   = $total_asistencias > 0 ? round(($presentes / $total_asistencias) * 100, 1) : 0;

        $distribucion_asistencia = $asistenciasQuery
            ->selectRaw('estado_asistencia, count(*) as total')
            ->groupBy('estado_asistencia')
            ->pluck('total', 'estado_asistencia');





        // ============================================================
        // KPIs OFERTA EDUCATIVA
        // ============================================================

        $servicios_activos = Servicio::where('estado_activo', true)->count();

        $calendariosQuery = Calendario::query();
        if ($servicio) $calendariosQuery->where('id_servicio', $servicio);
        if ($modalidad) $calendariosQuery->whereHas('servicio', fn($q) => $q->where('modalidad', $modalidad));

        $calendarios_activos = $calendariosQuery->count();

        // Distribución por modalidad
        $distribucion_modalidad = Servicio::where('estado_activo', true)
            ->selectRaw('modalidad, count(*) as total')
            ->groupBy('modalidad')
            ->pluck('total', 'modalidad');

        // Ocupación de calendarios (inscripciones activas vs cupos totales)
        $ocupacion = Calendario::selectRaw('
                sum(cupos_maximos) as cupos_totales,
                count(distinct inscripcion.id) as inscripciones_activas
            ')
            ->leftJoin('inscripcion', function ($join) {
                $join->on('inscripcion.id_calendario', '=', 'calendario.id')
                    ->whereIn('inscripcion.estado_academico', ['CURSANDO', 'PENDIENTE_PAGO']);
            })
            ->first();

        $cupos_totales = (int)($ocupacion->cupos_totales ?? 0);
        $inscripciones_activas = (int)($ocupacion->inscripciones_activas ?? 0);
        $pct_ocupacion = $cupos_totales > 0 ? round(($inscripciones_activas / $cupos_totales) * 100, 1) : 0;

        // Top 5 servicios por inscripciones
        $top_servicios = Servicio::withCount(['calendarios as inscripciones_count' => function ($q) {
            $q->join('inscripcion', 'inscripcion.id_calendario', '=', 'calendario.id');
        }])
            ->where('estado_activo', true)
            ->orderByDesc('inscripciones_count')
            ->limit(5)
            ->get(['id', 'nombre', 'modalidad'])
            ->map(fn($s) => ['nombre' => $s->nombre, 'modalidad' => $s->modalidad, 'inscripciones' => (int)$s->inscripciones_count]);

        // ============================================================
        // KPIs DE TUTORES
        // ============================================================

        $total_tutores = Tutor::count();

        // Tutores con calendarios activos (con al menos un alumno cursando)
        $tutores_activos = Tutor::whereHas('calendarios.inscripciones', fn($q) => $q->where('estado_academico', 'CURSANDO'))->count();

        // ============================================================
        // SERVICIOS Y OPCIONES PARA FILTROS
        // ============================================================
        $servicios_lista = Servicio::where('estado_activo', true)
            ->select('id', 'nombre', 'modalidad')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Dashboard', [
            'filtros' => [
                'desde'            => $desde,
                'hasta'            => $hasta,
                'servicio'         => $servicio ? (int)$servicio : null,
                'modalidad'        => $modalidad,
                'estado_academico' => $estado_academico,
            ],
            'kpis' => [
                'financiero' => [
                    'ingresos_periodo'  => $ingresos_periodo,
                    'saldo_pendiente'   => $saldo_pendiente,
                    'ventas_por_estado' => $ventas_por_estado,
                    'metodos_pago'      => $metodos_pago,
                ],
                'academico' => [
                    'total_alumnos'        => $total_alumnos,
                    'inscripciones_periodo' => $inscripciones_periodo,
                    'distribucion_estados'  => $distribucion_estados,
                    'promedio_calificacion' => $promedio_calificacion,
                    'tasa_finalizacion'     => $tasa_finalizacion,
                ],
                'asistencia' => [
                    'tasa_asistencia'         => $tasa_asistencia,
                    'total_asistencias'       => $total_asistencias,
                    'presentes'               => $presentes,
                    'distribucion_asistencia' => $distribucion_asistencia,
                ],
                'oferta' => [
                    'servicios_activos'     => $servicios_activos,
                    'calendarios_activos'   => $calendarios_activos,
                    'distribucion_modalidad' => $distribucion_modalidad,
                    'cupos_totales'         => $cupos_totales,
                    'inscripciones_activas' => $inscripciones_activas,
                    'pct_ocupacion'         => $pct_ocupacion,
                    'top_servicios'         => $top_servicios,
                ],
                'tutores' => [
                    'total_tutores'    => $total_tutores,
                    'tutores_activos'  => $tutores_activos,
                ],
            ],
            'servicios_lista' => $servicios_lista,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filtros = $this->obtenerFiltros($request);
        $kpis = $this->construirKpis($filtros);

        $pdf = Pdf::loadView('reports.dashboard_pdf', [
            'filtros' => $filtros,
            'kpis' => [
                'ingresos_periodo' => $kpis['financiero']['ingresos_periodo'],
                'saldo_pendiente' => $kpis['financiero']['saldo_pendiente'],
                'inscripciones_periodo' => $kpis['academico']['inscripciones_periodo'],
                'tasa_asistencia' => $kpis['asistencia']['tasa_asistencia'],
                'tasa_finalizacion' => $kpis['academico']['tasa_finalizacion'],
                'pct_ocupacion' => $kpis['oferta']['pct_ocupacion'],
                'inscripciones_activas' => $kpis['oferta']['inscripciones_activas'],
                'cupos_totales' => $kpis['oferta']['cupos_totales'],
                'tutores_activos' => $kpis['tutores']['tutores_activos'],
                'total_tutores' => $kpis['tutores']['total_tutores'],
            ],
            'ventasPorEstado' => $kpis['financiero']['ventas_por_estado'],
            'metodosPago' => $kpis['financiero']['metodos_pago'],
            'topServicios' => $kpis['oferta']['top_servicios'],
            'moneda' => fn(float $v) => number_format($v, 2, '.', ','),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('dashboard_reporte_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filtros = $this->obtenerFiltros($request);
        $kpis = $this->construirKpis($filtros);

        $pagos = $this->detallePagos($filtros);
        $ventas = $this->detalleVentas($filtros);
        $inscripciones = $this->detalleInscripciones($filtros);
        $asistencias = $this->detalleAsistencias($filtros);

        $resumenRows = [
            ['Ingresos del período', (float) $kpis['financiero']['ingresos_periodo']],
            ['Saldo pendiente', (float) $kpis['financiero']['saldo_pendiente']],
            ['Inscripciones período', (int) $kpis['academico']['inscripciones_periodo']],
            ['Total alumnos', (int) $kpis['academico']['total_alumnos']],
            ['Tasa finalización (%)', (float) $kpis['academico']['tasa_finalizacion']],
            ['Tasa asistencia (%)', (float) $kpis['asistencia']['tasa_asistencia']],
            ['Servicios activos', (int) $kpis['oferta']['servicios_activos']],
            ['Calendarios activos', (int) $kpis['oferta']['calendarios_activos']],
            ['Total tutores', (int) $kpis['tutores']['total_tutores']],
            ['Tutores activos', (int) $kpis['tutores']['tutores_activos']],
        ];

        $export = new DashboardReportExport(
            $resumenRows,
            $pagos,
            $ventas,
            $inscripciones,
            $asistencias,
        );

        return Excel::download($export, 'dashboard_reporte_' . now()->format('Ymd_His') . '.xlsx');
    }

    private function obtenerFiltros(Request $request): array
    {
        return [
            'desde' => $request->get('desde', now()->startOfMonth()->toDateString()),
            'hasta' => $request->get('hasta', now()->toDateString()),
            'servicio' => $request->get('servicio'),
            'modalidad' => $request->get('modalidad'),
            'estado_academico' => $request->get('estado_academico'),
        ];
    }

    private function construirKpis(array $filtros): array
    {
        $desde = $filtros['desde'];
        $hasta = $filtros['hasta'];
        $servicio = $filtros['servicio'];
        $modalidad = $filtros['modalidad'];
        $estadoAcademico = $filtros['estado_academico'];

        $pagosQuery = Pago::whereBetween('fecha_pago', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        if ($servicio || $modalidad) {
            $pagosQuery->whereHas('cuota.venta.inscripcion.calendario', function ($q) use ($servicio, $modalidad) {
                if ($servicio) {
                    $q->where('id_servicio', $servicio);
                }
                if ($modalidad) {
                    $q->whereHas('servicio', fn($s) => $s->where('modalidad', $modalidad));
                }
            });
        }
        $ingresosPeriodo = (float) $pagosQuery->sum('monto_abonado');

        $saldoPendiente = (float) Venta::whereIn('estado_financiero', ['PENDIENTE', 'PARCIAL'])->sum('saldo_pendiente');

        $ventasQuery = Venta::whereBetween('fecha_emision', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        if ($servicio || $modalidad) {
            $ventasQuery->whereHas('inscripcion.calendario', function ($q) use ($servicio, $modalidad) {
                if ($servicio) {
                    $q->where('id_servicio', $servicio);
                }
                if ($modalidad) {
                    $q->whereHas('servicio', fn($s) => $s->where('modalidad', $modalidad));
                }
            });
        }

        $ventasPorEstado = $ventasQuery->selectRaw('estado_financiero, count(*) as total, sum(monto_total) as monto')
            ->groupBy('estado_financiero')
            ->get()
            ->keyBy('estado_financiero')
            ->map(fn($v) => ['total' => (int) $v->total, 'monto' => (float) $v->monto])
            ->all();

        $metodosPago = $pagosQuery->selectRaw('metodo_pago, count(*) as total, sum(monto_abonado) as monto')
            ->groupBy('metodo_pago')
            ->get()
            ->map(fn($v) => ['metodo' => $v->metodo_pago, 'total' => (int) $v->total, 'monto' => (float) $v->monto])
            ->all();

        $inscripcionesQuery = Inscripcion::whereBetween('fecha_inscripcion', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        if ($servicio) {
            $inscripcionesQuery->whereHas('calendario', fn($q) => $q->where('id_servicio', $servicio));
        }
        if ($modalidad) {
            $inscripcionesQuery->whereHas('calendario.servicio', fn($q) => $q->where('modalidad', $modalidad));
        }
        if ($estadoAcademico) {
            $inscripcionesQuery->where('estado_academico', $estadoAcademico);
        }

        $inscripcionesPeriodo = $inscripcionesQuery->count();

        $estadosQuery = Inscripcion::whereBetween('fecha_inscripcion', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        if ($servicio) {
            $estadosQuery->whereHas('calendario', fn($q) => $q->where('id_servicio', $servicio));
        }
        if ($modalidad) {
            $estadosQuery->whereHas('calendario.servicio', fn($q) => $q->where('modalidad', $modalidad));
        }

        $distribucionEstados = $estadosQuery
            ->selectRaw('estado_academico, count(*) as total')
            ->groupBy('estado_academico')
            ->pluck('total', 'estado_academico')
            ->toArray();

        $promedioCalificacion = round(
            Inscripcion::where('estado_academico', 'FINALIZADO')
                ->whereNotNull('calificacion_final')
                ->avg('calificacion_final') ?? 0,
            2,
        );

        $totalActivos = Inscripcion::whereIn('estado_academico', ['CURSANDO', 'FINALIZADO', 'ABANDONADO'])->count();
        $totalFinalizados = Inscripcion::where('estado_academico', 'FINALIZADO')->count();
        $tasaFinalizacion = $totalActivos > 0 ? round(($totalFinalizados / $totalActivos) * 100, 1) : 0;

        $asistenciasQuery = Asistencia::whereHas('sesion.calendario', function ($q) use ($desde, $hasta, $servicio, $modalidad) {
            $q->whereHas('sesionesProgramadas', fn($s) => $s->whereBetween('fecha_sesion', [$desde, $hasta]));
            if ($servicio) {
                $q->where('id_servicio', $servicio);
            }
            if ($modalidad) {
                $q->whereHas('servicio', fn($s) => $s->where('modalidad', $modalidad));
            }
        });

        $totalAsistencias = $asistenciasQuery->count();
        $presentes = (clone $asistenciasQuery)->where('estado_asistencia', 'PRESENTE')->count();
        $tasaAsistencia = $totalAsistencias > 0 ? round(($presentes / $totalAsistencias) * 100, 1) : 0;

        $distribucionAsistencia = $asistenciasQuery
            ->selectRaw('estado_asistencia, count(*) as total')
            ->groupBy('estado_asistencia')
            ->pluck('total', 'estado_asistencia')
            ->toArray();

        $serviciosActivos = Servicio::where('estado_activo', true)->count();

        $calendariosQuery = Calendario::query();
        if ($servicio) {
            $calendariosQuery->where('id_servicio', $servicio);
        }
        if ($modalidad) {
            $calendariosQuery->whereHas('servicio', fn($q) => $q->where('modalidad', $modalidad));
        }
        $calendariosActivos = $calendariosQuery->count();

        $distribucionModalidad = Servicio::where('estado_activo', true)
            ->selectRaw('modalidad, count(*) as total')
            ->groupBy('modalidad')
            ->pluck('total', 'modalidad')
            ->toArray();

        $ocupacion = Calendario::selectRaw('sum(cupos_maximos) as cupos_totales, count(distinct inscripcion.id) as inscripciones_activas')
            ->leftJoin('inscripcion', function ($join) {
                $join->on('inscripcion.id_calendario', '=', 'calendario.id')
                    ->whereIn('inscripcion.estado_academico', ['CURSANDO', 'PENDIENTE_PAGO']);
            })
            ->first();

        $cuposTotales = (int) ($ocupacion->cupos_totales ?? 0);
        $inscripcionesActivas = (int) ($ocupacion->inscripciones_activas ?? 0);
        $pctOcupacion = $cuposTotales > 0 ? round(($inscripcionesActivas / $cuposTotales) * 100, 1) : 0;

        $topServicios = Servicio::withCount(['calendarios as inscripciones_count' => function ($q) {
            $q->join('inscripcion', 'inscripcion.id_calendario', '=', 'calendario.id');
        }])
            ->where('estado_activo', true)
            ->orderByDesc('inscripciones_count')
            ->limit(5)
            ->get(['id', 'nombre', 'modalidad'])
            ->map(fn($s) => ['nombre' => $s->nombre, 'modalidad' => $s->modalidad, 'inscripciones' => (int) $s->inscripciones_count])
            ->all();

        $totalTutores = Tutor::count();
        $tutoresActivos = Tutor::whereHas('calendarios.inscripciones', fn($q) => $q->where('estado_academico', 'CURSANDO'))->count();

        return [
            'financiero' => [
                'ingresos_periodo' => $ingresosPeriodo,
                'saldo_pendiente' => $saldoPendiente,
                'ventas_por_estado' => $ventasPorEstado,
                'metodos_pago' => $metodosPago,
            ],
            'academico' => [
                'total_alumnos' => Alumno::count(),
                'inscripciones_periodo' => $inscripcionesPeriodo,
                'distribucion_estados' => $distribucionEstados,
                'promedio_calificacion' => $promedioCalificacion,
                'tasa_finalizacion' => $tasaFinalizacion,
            ],
            'asistencia' => [
                'tasa_asistencia' => $tasaAsistencia,
                'total_asistencias' => $totalAsistencias,
                'presentes' => $presentes,
                'distribucion_asistencia' => $distribucionAsistencia,
            ],
            'oferta' => [
                'servicios_activos' => $serviciosActivos,
                'calendarios_activos' => $calendariosActivos,
                'distribucion_modalidad' => $distribucionModalidad,
                'cupos_totales' => $cuposTotales,
                'inscripciones_activas' => $inscripcionesActivas,
                'pct_ocupacion' => $pctOcupacion,
                'top_servicios' => $topServicios,
            ],
            'tutores' => [
                'total_tutores' => $totalTutores,
                'tutores_activos' => $tutoresActivos,
            ],
        ];
    }

    private function detallePagos(array $filtros): array
    {
        $query = Pago::with(['alumno.usuario', 'cuota.venta.inscripcion.calendario.servicio'])
            ->whereBetween('fecha_pago', [$filtros['desde'] . ' 00:00:00', $filtros['hasta'] . ' 23:59:59']);

        if ($filtros['servicio'] || $filtros['modalidad']) {
            $query->whereHas('cuota.venta.inscripcion.calendario', function ($q) use ($filtros) {
                if ($filtros['servicio']) {
                    $q->where('id_servicio', $filtros['servicio']);
                }
                if ($filtros['modalidad']) {
                    $q->whereHas('servicio', fn($s) => $s->where('modalidad', $filtros['modalidad']));
                }
            });
        }

        return $query->orderByDesc('fecha_pago')->get()->map(function ($p) {
            $servicio = $p->cuota?->venta?->inscripcion?->calendario?->servicio;
            return [
                $p->id,
                (string) $p->fecha_pago,
                $p->alumno?->usuario?->name ?? '—',
                $servicio?->nombre ?? '—',
                $servicio?->modalidad ?? '—',
                $p->metodo_pago,
                (float) $p->monto_abonado,
            ];
        })->all();
    }

    private function detalleVentas(array $filtros): array
    {
        $query = Venta::with(['inscripcion.calendario.servicio'])
            ->whereBetween('fecha_emision', [$filtros['desde'] . ' 00:00:00', $filtros['hasta'] . ' 23:59:59']);

        if ($filtros['servicio'] || $filtros['modalidad']) {
            $query->whereHas('inscripcion.calendario', function ($q) use ($filtros) {
                if ($filtros['servicio']) {
                    $q->where('id_servicio', $filtros['servicio']);
                }
                if ($filtros['modalidad']) {
                    $q->whereHas('servicio', fn($s) => $s->where('modalidad', $filtros['modalidad']));
                }
            });
        }

        return $query->orderByDesc('fecha_emision')->get()->map(function ($v) {
            $servicio = $v->inscripcion?->calendario?->servicio;
            return [
                $v->id,
                (string) $v->fecha_emision,
                $servicio?->nombre ?? '—',
                $servicio?->modalidad ?? '—',
                $v->tipo_pago_pref,
                $v->estado_financiero,
                (float) $v->monto_total,
                (float) $v->saldo_pendiente,
            ];
        })->all();
    }

    private function detalleInscripciones(array $filtros): array
    {
        $query = Inscripcion::with(['alumno.usuario', 'calendario.servicio'])
            ->whereBetween('fecha_inscripcion', [$filtros['desde'] . ' 00:00:00', $filtros['hasta'] . ' 23:59:59']);

        if ($filtros['servicio']) {
            $query->whereHas('calendario', fn($q) => $q->where('id_servicio', $filtros['servicio']));
        }
        if ($filtros['modalidad']) {
            $query->whereHas('calendario.servicio', fn($q) => $q->where('modalidad', $filtros['modalidad']));
        }
        if ($filtros['estado_academico']) {
            $query->where('estado_academico', $filtros['estado_academico']);
        }

        return $query->orderByDesc('fecha_inscripcion')->get()->map(function ($i) {
            $servicio = $i->calendario?->servicio;
            return [
                $i->id,
                (string) $i->fecha_inscripcion,
                $i->alumno?->usuario?->name ?? '—',
                $servicio?->nombre ?? '—',
                $servicio?->modalidad ?? '—',
                $i->estado_academico,
                $i->calificacion_final,
            ];
        })->all();
    }

    private function detalleAsistencias(array $filtros): array
    {
        $query = Asistencia::with(['sesion.calendario.servicio', 'inscripcion.alumno.usuario'])
            ->whereHas('sesion', fn($q) => $q->whereBetween('fecha_sesion', [$filtros['desde'], $filtros['hasta']]));

        if ($filtros['servicio']) {
            $query->whereHas('sesion.calendario', fn($q) => $q->where('id_servicio', $filtros['servicio']));
        }
        if ($filtros['modalidad']) {
            $query->whereHas('sesion.calendario.servicio', fn($q) => $q->where('modalidad', $filtros['modalidad']));
        }

        return $query->orderByDesc('id')->get()->map(function ($a) {
            return [
                $a->id,
                $a->id_sesion,
                $a->sesion?->fecha_sesion,
                $a->inscripcion?->alumno?->usuario?->name ?? '—',
                $a->sesion?->calendario?->servicio?->nombre ?? '—',
                $a->estado_asistencia,
            ];
        })->all();
    }
}
