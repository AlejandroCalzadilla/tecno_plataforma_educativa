<?php

namespace Database\Seeders;

use App\Models\Inscripcion;
use App\Models\SesionProgramada;
use Illuminate\Database\Seeder;

class SesionProgramadaSeeder extends Seeder
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

        $estadosAsistencia = ['PENDIENTE', 'PRESENTE', 'AUSENTE', 'TARDANZA', 'JUSTIFICADO'];

        foreach ($inscripciones as $inscripcion) {
            $calendario = $inscripcion->calendario;

            // Crear sesiones basadas en el número de sesiones del calendario
            $numSesiones = $calendario->numero_sesiones ?? rand(4, 12);

            for ($i = 1; $i <= $numSesiones; $i++) {
                $fechaSesion = now()->addDays(rand(0, 60)); // Sesiones en los próximos 60 días
                $horaInicio = '09:00'; // Hora fija para simplificar
                $horaFin = '10:30'; // Duración de 1.5 horas

                SesionProgramada::create([
                    'id_inscripcion' => $inscripcion->id,
                    'fecha_sesion' => $fechaSesion->format('Y-m-d'),
                    'fecha_hora_inicio' => $horaInicio,
                    'fecha_hora_fin' => $horaFin,
                    'link_sesion' => rand(0, 1) ? 'https://meet.google.com/' . substr(md5(rand()), 0, 10) : null,
                    'estado_asistencia' => $estadosAsistencia[array_rand($estadosAsistencia)],
                    'numero_sesion' => $i,
                    'observaciones' => rand(0, 1) ? 'Sesión completada satisfactoriamente' : null,
                ]);
            }
        }
    }
}