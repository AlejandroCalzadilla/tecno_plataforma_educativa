<?php

namespace Database\Seeders;

use App\Models\Calendario;
use App\Models\Servicio;
use App\Models\Tutor;
use Illuminate\Database\Seeder;

class CalendarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicios = Servicio::all();
        $tutores = Tutor::all();

        if ($servicios->isEmpty()) {
            $this->command->info('No hay servicios disponibles. Por favor, ejecuta primero el seeder de servicios.');
            return;
        }

        if ($tutores->isEmpty()) {
            $this->command->info('No hay tutores disponibles. Por favor, ejecuta primero el seeder de usuarios.');
            return;
        }

        $tiposProgramacion = ['CITA_LIBRE', 'PAQUETE_FIJO'];
        $duraciones = [60, 90, 120]; // minutos

        foreach ($servicios as $servicio) {
            // Crear 1-2 calendarios por servicio
            $numCalendarios = rand(1, 2);

            for ($i = 0; $i < $numCalendarios; $i++) {
                $tipo = $tiposProgramacion[array_rand($tiposProgramacion)];
                $tutor = $tutores->random();

                Calendario::create([
                    'id_servicio' => $servicio->id,
                    'id_tutor' => $tutor->id,
                    'tipo_programacion' => $tipo,
                    'numero_sesiones' => $tipo === 'PAQUETE_FIJO' ? rand(4, 12) : null,
                    'duracion_sesion_minutos' => $duraciones[array_rand($duraciones)],
                    'costo_total' => rand(500, 2000),
                    'cupos_maximos' => rand(1, 5),
                ]);
            }
        }
    }
}