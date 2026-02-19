<?php
namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Tutor;
use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;



class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_propietario' => true,
        ]);

        // Crear usuarios alumnos
        for ($i = 1; $i <= 5; $i++) {
            $alumno = User::factory()->create([
                'name' => "Alumno $i",
                'email' => "alumno$i@example.com",
                'password' => Hash::make('password'),
                'is_alumno' => true,
            ]);

            // Crear registro en tabla alumno
            Alumno::create([
                'id_usuario' => $alumno->id,
                'direccion' => "Calle $i, Ciudad",
                'fecha_nacimiento' => now()->subYears(20 + $i)->format('Y-m-d'),
                'nivel_educativo' => 'Secundario',
            ]);
        }

        // Crear usuarios tutores
        for ($i = 1; $i <= 3; $i++) {
            $tutor = User::factory()->create([
                'name' => "Tutor $i",
                'email' => "tutor$i@example.com",
                'password' => Hash::make('password'),
                'is_tutor' => true,
            ]);

            // Crear registro en tabla tutor
            Tutor::create([
                'id_usuario' => $tutor->id,
                'especialidad' => "Especialidad $i",
                'biografia' => "Biografía del tutor $i",
                'cv_url' => null,
                'banco_nombre' => "Banco $i",
                'banco_cbu' => '1234567890123456789' . $i,
            ]);
        }

        // Crear usuario con múltiples roles
        $multirol = User::factory()->create([
            'name' => 'Usuario Multi-rol',
            'email' => 'multirol@example.com',
            'password' => Hash::make('password'),
            'is_alumno' => true,
            'is_tutor' => true,
        ]);

        Alumno::create([
            'id_usuario' => $multirol->id,
            'direccion' => 'Multi-rol Street',
            'fecha_nacimiento' => now()->subYears(25)->format('Y-m-d'),
            'nivel_educativo' => 'Universitario',
        ]);

        Tutor::create([
            'id_usuario' => $multirol->id,
            'especialidad' => 'Multi-especialidad',
            'biografia' => 'Usuario con múltiples roles',
            'cv_url' => null,
            'banco_nombre' => 'Banco Multi',
            'banco_cbu' => '9999999999999999999',
        ]);
    }
}