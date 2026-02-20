<?php

namespace Database\Seeders;

use App\Models\Cuota;
use App\Models\Venta;
use Illuminate\Database\Seeder;

class CuotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ventas = Venta::all();

        if ($ventas->isEmpty()) {
            $this->command->info('No hay ventas disponibles. Por favor, ejecuta primero el seeder de ventas.');
            return;
        }

        $estadosPago = ['PENDIENTE', 'PAGADO', 'VENCIDO'];

        foreach ($ventas as $venta) {
            // Solo crear cuotas si el tipo de pago es CUOTAS
            if ($venta->tipo_pago_pref !== 'CUOTAS') {
                continue;
            }

            $numCuotas = rand(3, 12); // Entre 3 y 12 cuotas
            $montoPorCuota = round($venta->monto_total / $numCuotas, 2);

            for ($i = 1; $i <= $numCuotas; $i++) {
                $estadoPago = $estadosPago[array_rand($estadosPago)];

                Cuota::create([
                    'id_venta' => $venta->id,
                    'numero_cuota' => $i,
                    'monto_cuota' => $montoPorCuota,
                    'fecha_vencimiento' => now()->addMonths($i)->addDays(rand(-5, 5)), // Vencimiento mensual con variación
                    'estado_pago' => $estadoPago,
                ]);
            }
        }
    }
}