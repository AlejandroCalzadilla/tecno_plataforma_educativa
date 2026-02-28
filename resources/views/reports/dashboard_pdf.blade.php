<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Reporte Dashboard</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .header { margin-bottom: 14px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; margin: 0; }
        .subtitle { font-size: 11px; color: #475569; margin-top: 4px; }
        .kpi-grid { width: 100%; border-collapse: separate; border-spacing: 8px; margin-top: 10px; }
        .kpi { border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px; }
        .kpi-label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .kpi-value { font-size: 18px; font-weight: bold; margin-top: 4px; }
        .section-title { margin-top: 18px; margin-bottom: 8px; font-size: 13px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 6px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; }
        .badge { display: inline-block; border: 1px solid #cbd5e1; border-radius: 20px; padding: 3px 8px; font-size: 10px; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Reporte Ejecutivo Dashboard</p>
        <p class="subtitle">
            Período: {{ $filtros['desde'] }} a {{ $filtros['hasta'] }}
            @if($filtros['servicio']) · Servicio ID: {{ $filtros['servicio'] }} @endif
            @if($filtros['modalidad']) · Modalidad: {{ $filtros['modalidad'] }} @endif
            @if($filtros['estado_academico']) · Estado: {{ $filtros['estado_academico'] }} @endif
        </p>
    </div>

    <table class="kpi-grid">
        <tr>
            <td class="kpi">
                <div class="kpi-label">Ingresos período</div>
                <div class="kpi-value">{{ $moneda($kpis['ingresos_periodo']) }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">Saldo pendiente</div>
                <div class="kpi-value">{{ $moneda($kpis['saldo_pendiente']) }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">Inscripciones</div>
                <div class="kpi-value">{{ $kpis['inscripciones_periodo'] }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">Tasa asistencia</div>
                <div class="kpi-value">{{ $kpis['tasa_asistencia'] }}%</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Resumen visual (KPIs principales)</div>
    <table>
        <thead>
            <tr>
                <th>Indicador</th>
                <th>Valor</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Tasa de finalización</td>
                <td><span class="badge">{{ $kpis['tasa_finalizacion'] }}%</span></td>
                <td class="muted">Finalizados sobre cohortes activas.</td>
            </tr>
            <tr>
                <td>Ocupación de calendarios</td>
                <td><span class="badge">{{ $kpis['pct_ocupacion'] }}%</span></td>
                <td class="muted">{{ $kpis['inscripciones_activas'] }} / {{ $kpis['cupos_totales'] }} cupos.</td>
            </tr>
            <tr>
                <td>Tutores activos</td>
                <td><span class="badge">{{ $kpis['tutores_activos'] }} / {{ $kpis['total_tutores'] }}</span></td>
                <td class="muted">Tutores con alumnos cursando.</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Distribuciones y tablas resumidas</div>
    <table>
        <thead>
            <tr>
                <th>Bloque</th>
                <th>Categoría</th>
                <th>Total</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventasPorEstado as $estado => $row)
                <tr>
                    <td>Ventas</td>
                    <td>{{ $estado }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $moneda($row['monto']) }}</td>
                </tr>
            @endforeach
            @foreach($metodosPago as $item)
                <tr>
                    <td>Método pago</td>
                    <td>{{ $item['metodo'] }}</td>
                    <td>{{ $item['total'] }}</td>
                    <td>{{ $moneda($item['monto']) }}</td>
                </tr>
            @endforeach
            @foreach($topServicios as $item)
                <tr>
                    <td>Top servicio</td>
                    <td>{{ $item['nombre'] }} ({{ $item['modalidad'] }})</td>
                    <td>{{ $item['inscripciones'] }}</td>
                    <td>—</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
