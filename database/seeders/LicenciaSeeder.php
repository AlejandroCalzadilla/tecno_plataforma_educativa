<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Licencia;
use Illuminate\Database\Seeder;

class LicenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $asistencias = Asistencia::all();

        if ($asistencias->isEmpty()) {
            $this->command->info('No hay asistencias disponibles. Por favor, ejecuta primero el seeder de asistencias.');
            return;
        }

        $motivos = [
            'Enfermedad',
            'Problemas familiares',
            'Compromisos laborales',
            'Viaje',
            'Exámenes académicos',
            'Razones personales',
        ];

        $estadosAprobacion = ['PENDIENTE', 'APROBADA', 'RECHAZADA'];

        $cantidad = max(1, (int) floor($asistencias->count() * 0.2));
        $asistenciasConLicencia = $asistencias->random(min($cantidad, $asistencias->count()));

        foreach ($asistenciasConLicencia as $asistencia) {
            Licencia::create([
                'id_asistencia' => $asistencia->id,
                'fecha_solicitud' => now()->subDays(rand(1, 7)),
                'motivo' => $motivos[array_rand($motivos)],
                'evidencia_url' => rand(0, 1) ? 'https://example.com/evidencia/' . rand(1000, 9999) . '.pdf' : null,
                'estado_aprobacion' => $estadosAprobacion[array_rand($estadosAprobacion)],
                'observacion_admin' => rand(0, 1) ? 'Licencia revisada y procesada' : null,
            ]);
        }
    }
}