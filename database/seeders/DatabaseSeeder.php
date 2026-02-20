<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Tutor;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario administrador

         $userSeeder = new UserSeeder();
         $userSeeder->run();
         $categoriaSeeder = new CategoriaSeeder();
        $categoriaSeeder->run();
        $serviceSeeder = new ServiceSeeder();
        $serviceSeeder->run();
        $calendarioSeeder = new CalendarioSeeder();
        $calendarioSeeder->run();
        $disponibilidadSeeder = new DisponibilidadSeeder();
        $disponibilidadSeeder->run();
        $inscripcionSeeder = new InscripcionSeeder();
        $inscripcionSeeder->run();
        $ventaSeeder = new VentaSeeder();
        $ventaSeeder->run();
        $cuotaSeeder = new CuotaSeeder();
        $cuotaSeeder->run();
        $pagoSeeder = new PagoSeeder();
        $pagoSeeder->run();
        $sesionProgramadaSeeder = new SesionProgramadaSeeder();
        $sesionProgramadaSeeder->run();
        $licenciaSeeder = new LicenciaSeeder();
        $licenciaSeeder->run();
        $informeClaseSeeder = new InformeClaseSeeder();
        $informeClaseSeeder->run();
       
    }
}
