<?php

namespace Database\Seeders;

use App\Models\Calendario;
use App\Models\Disponibilidad;
use Illuminate\Database\Seeder;

class DisponibilidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $calendarios = Calendario::all();

        if ($calendarios->isEmpty()) {
            $this->command->info('No hay calendarios disponibles. Por favor, ejecuta primero el seeder de calendarios.');
            return;
        }

        $diasSemana = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
        $horarios = [
            ['08:00', '12:00'],
            ['14:00', '18:00'],
            ['09:00', '13:00'],
            ['15:00', '19:00'],
            ['10:00', '14:00'],
        ];

        foreach ($calendarios as $calendario) {
            // Crear disponibilidad para 3-5 días aleatorios por calendario
            $diasSeleccionados = collect($diasSemana)->random(rand(3, 5));

            foreach ($diasSeleccionados as $dia) {
                $horario = $horarios[array_rand($horarios)];

                Disponibilidad::create([
                    'id_calendario' => $calendario->id,
                    'dia_semana' => $dia,
                    'hora_apertura' => $horario[0],
                    'hora_cierre' => $horario[1],
                ]);
            }
        }
    }
}