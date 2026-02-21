<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\SesionProgramada;
use Illuminate\Database\Seeder;

class AsistenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inscripciones = Inscripcion::with('calendario')->get();

        if ($inscripciones->isEmpty()) {
            $this->command->info('No hay inscripciones disponibles. Por favor, ejecuta primero el seeder de inscripciones.');
            return;
        }

        $estadosAsistencia = ['PENDIENTE', 'PRESENTE', 'AUSENTE', 'TARDANZA', 'JUSTIFICADO'];

        foreach ($inscripciones as $inscripcion) {
            $sesiones = SesionProgramada::query()
                ->where('id_calendario', $inscripcion->id_calendario)
                ->get();

            foreach ($sesiones as $sesion) {
                Asistencia::firstOrCreate(
                    [
                        'id_sesion' => $sesion->id,
                        'id_inscripcion' => $inscripcion->id,
                    ],
                    [
                        'estado_asistencia' => $estadosAsistencia[array_rand($estadosAsistencia)],
                        'observaciones' => rand(0, 1) ? 'Registro generado por seeder' : null,
                    ]
                );
            }
        }
    }
}
