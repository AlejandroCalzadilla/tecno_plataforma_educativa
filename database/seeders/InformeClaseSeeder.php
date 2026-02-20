<?php

namespace Database\Seeders;

use App\Models\InformeClase;
use App\Models\SesionProgramada;
use Illuminate\Database\Seeder;

class InformeClaseSeeder extends Seeder
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

        // Crear informes para aproximadamente el 70% de las sesiones completadas
        $sesionesCompletadas = $sesiones->filter(function($sesion) {
            return in_array($sesion->estado_asistencia, ['PRESENTE', 'TARDANZA', 'JUSTIFICADO']);
        });

        $sesionesConInforme = $sesionesCompletadas->random((int)($sesionesCompletadas->count() * 0.7));

        foreach ($sesionesConInforme as $sesion) {
            InformeClase::create([
                'id_asistencia' => $sesion->id,
                'temas_vistos' => collect($temas)->random(rand(1, 3))->implode(', '),
                'tareas_asignadas' => rand(0, 1) ? collect($tareas)->random(rand(1, 2))->implode('; ') : null,
                'desempenio' => rand(0, 1) ? $desempenios[array_rand($desempenios)] : null,
            ]);
        }
    }
}