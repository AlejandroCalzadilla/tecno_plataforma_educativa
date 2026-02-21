<?php

namespace Database\Seeders;

use App\Models\Calendario;
use App\Models\SesionProgramada;
use Illuminate\Database\Seeder;

class SesionProgramadaSeeder extends Seeder
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

        foreach ($calendarios as $calendario) {
            $numSesiones = $calendario->tipo_programacion === 'PAQUETE_FIJO'
                ? max(1, (int) ($calendario->numero_sesiones ?? 1))
                : rand(2, 5);

            for ($i = 1; $i <= $numSesiones; $i++) {
                $fechaSesion = $calendario->tipo_programacion === 'PAQUETE_FIJO'
                    ? now()->addDays(($i - 1) * 7)
                    : now()->addDays(rand(1, 45));

                $horaInicio = rand(0, 1) ? '09:00' : '15:00';
                $horaFin = \Carbon\Carbon::parse($horaInicio)
                    ->addMinutes((int) $calendario->duracion_sesion_minutos)
                    ->format('H:i');

                SesionProgramada::create([
                    'id_calendario' => $calendario->id,
                    'fecha_sesion' => $fechaSesion->format('Y-m-d'),
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'link_sesion' => rand(0, 1) ? 'https://meet.google.com/' . substr(md5(rand()), 0, 10) : null,
                    'numero_sesion' => $calendario->tipo_programacion === 'PAQUETE_FIJO' ? $i : null,
                ]);
            }
        }
    }
}