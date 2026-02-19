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
       
    }
}
