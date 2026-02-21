<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\InformeClase;
use Illuminate\Database\Seeder;

class InformeClaseSeeder extends Seeder
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

        $desempenios = ['BAJO', 'MEDIO', 'ALTO', 'EXCELENTE'];
        $temas = [
            'Introducción a álgebra básica',
            'Geometría plana',
            'Ecuaciones diferenciales',
            'Programación en Python',
            'Análisis de datos',
            'Física mecánica',
            'Química orgánica',
            'Historia contemporánea',
            'Literatura clásica',
        ];

        $tareas = [
            'Resolver ejercicios del capítulo 5',
            'Preparar presentación sobre el tema',
            'Completar proyecto práctico',
            'Estudiar vocabulario nuevo',
            'Revisar material adicional',
            'Hacer resumen del tema',
        ];

        $asistenciasCompletadas = $asistencias->filter(function ($asistencia) {
            return in_array($asistencia->estado_asistencia, ['PRESENTE', 'TARDANZA', 'JUSTIFICADO']);
        });

        if ($asistenciasCompletadas->isEmpty()) {
            return;
        }

        $cantidad = max(1, (int) floor($asistenciasCompletadas->count() * 0.7));
        $asistenciasConInforme = $asistenciasCompletadas->random(min($cantidad, $asistenciasCompletadas->count()));

        foreach ($asistenciasConInforme as $asistencia) {
            InformeClase::create([
                'id_asistencia' => $asistencia->id,
                'temas_vistos' => collect($temas)->random(rand(1, 3))->implode(', '),
                'tareas_asignadas' => rand(0, 1) ? collect($tareas)->random(rand(1, 2))->implode('; ') : null,
                'desempenio' => rand(0, 1) ? $desempenios[array_rand($desempenios)] : null,
            ]);
        }
    }
}