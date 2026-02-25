<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Calendario;
use App\Models\Inscripcion;
use App\Models\Pago;
use App\Models\Servicio;
use App\Models\Tutor;
use App\Models\Venta;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
}
