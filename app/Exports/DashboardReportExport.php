<?php

namespace App\Exports;

use App\Exports\Sheets\DashboardArraySheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardReportExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly array $resumenRows,
        private readonly array $pagosRows,
        private readonly array $ventasRows,
        private readonly array $inscripcionesRows,
        private readonly array $asistenciasRows,
    ) {
    }

    public function sheets(): array
    {
        return [
            new DashboardArraySheet(
                'Resumen',
                ['Métrica', 'Valor'],
                $this->resumenRows,
                [2],
            ),
            new DashboardArraySheet(
                'Pagos',
                ['ID Pago', 'Fecha', 'Alumno', 'Servicio', 'Modalidad', 'Método', 'Monto abonado'],
                $this->pagosRows,
                [7],
            ),
            new DashboardArraySheet(
                'Ventas',
                ['ID Venta', 'Fecha emisión', 'Servicio', 'Modalidad', 'Tipo pago', 'Estado', 'Monto total', 'Saldo pendiente'],
                $this->ventasRows,
                [7, 8],
            ),
            new DashboardArraySheet(
                'Inscripciones',
                ['ID Inscripción', 'Fecha', 'Alumno', 'Servicio', 'Modalidad', 'Estado académico', 'Calificación final'],
                $this->inscripcionesRows,
            ),
            new DashboardArraySheet(
                'Asistencias',
                ['ID Asistencia', 'ID Sesión', 'Fecha sesión', 'Alumno', 'Servicio', 'Estado asistencia'],
                $this->asistenciasRows,
            ),
        ];
    }
}
