<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategoriaSeeder::class,
            ServiceSeeder::class,
            CalendarioSeeder::class,
            DisponibilidadSeeder::class,
            InscripcionSeeder::class,
            VentaSeeder::class,
            CuotaSeeder::class,
            PagoSeeder::class,
            SesionProgramadaSeeder::class,
            AsistenciaSeeder::class,
            LicenciaSeeder::class,
            InformeClaseSeeder::class,
        ]);
    }
}
