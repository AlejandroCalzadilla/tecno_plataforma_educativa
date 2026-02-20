<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Calendario;
use App\Models\Inscripcion;
use Illuminate\Database\Seeder;

class InscripcionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alumnos = Alumno::all();
        $calendarios = Calendario::all();

        if ($alumnos->isEmpty()) {
            $this->command->info('No hay alumnos disponibles. Por favor, ejecuta primero el seeder de usuarios.');
            return;
        }

        if ($calendarios->isEmpty()) {
            $this->command->info('No hay calendarios disponibles. Por favor, ejecuta primero el seeder de calendarios.');
            return;
        }

        $estados = ['CURSANDO', 'FINALIZADO', 'ABANDONADO', 'PENDIENTE_PAGO'];

        foreach ($alumnos as $alumno) {
            // Cada alumno se inscribe en 1-3 calendarios
            $numInscripciones = rand(1, 3);
            $calendariosSeleccionados = $calendarios->random(min($numInscripciones, $calendarios->count()));

            foreach ($calendariosSeleccionados as $calendario) {
                Inscripcion::create([
                    'id_alumno' => $alumno->id,
                    'id_calendario' => $calendario->id,
                    'fecha_inscripcion' => now()->subDays(rand(0, 30)),
                    'estado_academico' => $estados[array_rand($estados)],
                    'calificacion_final' => rand(0, 1) ? rand(60, 100) / 10 : null, // 50% de probabilidad de tener calificación
                ]);
            }
        }
    }
}