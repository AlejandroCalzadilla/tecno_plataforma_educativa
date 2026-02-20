<?php

namespace Database\Seeders;

use App\Models\Cuota;
use App\Models\Pago;
use Illuminate\Database\Seeder;

class PagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuotas = Cuota::all();

        if ($cuotas->isEmpty()) {
            $this->command->info('No hay cuotas disponibles. Por favor, ejecuta primero el seeder de cuotas.');
            return;
        }

        $metodosPago = ['EFECTIVO', 'QR', 'TRANSFERENCIA', 'TARJETA'];

        foreach ($cuotas as $cuota) {
            // Solo crear pagos para cuotas que están pagadas
            if ($cuota->estado_pago !== 'PAGADO') {
                continue;
            }

            // Obtener el alumno de la venta relacionada
            $alumno = $cuota->venta->inscripcion->alumno;

            Pago::create([
                'id_alumno' => $alumno->id,
                'id_cuota' => $cuota->id,
                'fecha_pago' => now()->subDays(rand(0, 30)),
                'monto_abonado' => $cuota->monto_cuota,
                'metodo_pago' => $metodosPago[array_rand($metodosPago)],
                'codigo_transaccion_externo' => 'TXN' . rand(100000, 999999),
                'comprobante_url' => rand(0, 1) ? 'https://example.com/comprobante/' . rand(1000, 9999) . '.pdf' : null,
            ]);
        }
    }
}