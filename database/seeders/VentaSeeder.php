<?php

namespace Database\Seeders;

use App\Models\Inscripcion;
use App\Models\Venta;
use Illuminate\Database\Seeder;

class VentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inscripciones = Inscripcion::all();

        if ($inscripciones->isEmpty()) {
            $this->command->info('No hay inscripciones disponibles. Por favor, ejecuta primero el seeder de inscripciones.');
            return;
        }

        $tiposPago = ['CONTADO', 'CUOTAS', 'CREDITO'];
        $estadosFinancieros = ['PENDIENTE', 'PARCIAL', 'PAGADO', 'ANULADO'];

        foreach ($inscripciones as $inscripcion) {
            $montoTotal = rand(500, 2000);
            $tipoPago = $tiposPago[array_rand($tiposPago)];

            // Calcular saldo pendiente basado en estado financiero
            $estadoFinanciero = $estadosFinancieros[array_rand($estadosFinancieros)];
            $saldoPendiente = match($estadoFinanciero) {
                'PAGADO' => 0,
                'ANULADO' => $montoTotal,
                'PARCIAL' => rand(0, $montoTotal - 1),
                default => $montoTotal,
            };

            Venta::create([
                'id_inscripcion' => $inscripcion->id,
                'fecha_emision' => now()->subDays(rand(0, 30)),
                'monto_total' => $montoTotal,
                'saldo_pendiente' => $saldoPendiente,
                'tipo_pago_pref' => $tipoPago,
                'estado_financiero' => $estadoFinanciero,
            ]);
        }
    }
}