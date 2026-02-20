<?php

namespace Database\Seeders;

use App\Models\Licencia;
use App\Models\SesionProgramada;
use Illuminate\Database\Seeder;

class LicenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sesiones = SesionProgramada::all();

        if ($sesiones->isEmpty()) {
            $this->command->info('No hay sesiones programadas disponibles. Por favor, ejecuta primero el seeder de sesiones programadas.');
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

        // Crear licencias para aproximadamente el 20% de las sesiones
        $sesionesConLicencia = $sesiones->random((int)($sesiones->count() * 0.2));

        foreach ($sesionesConLicencia as $sesion) {
            Licencia::create([
                'id_asistencia' => $sesion->id,
                'fecha_solicitud' => now()->subDays(rand(1, 7)),
                'motivo' => $motivos[array_rand($motivos)],
                'evidencia_url' => rand(0, 1) ? 'https://example.com/evidencia/' . rand(1000, 9999) . '.pdf' : null,
                'estado_aprobacion' => $estadosAprobacion[array_rand($estadosAprobacion)],
                'observacion_admin' => rand(0, 1) ? 'Licencia revisada y procesada' : null,
            ]);
        }
    }
}