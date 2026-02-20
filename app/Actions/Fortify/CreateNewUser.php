<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Alumno;
use App\Models\Tutor;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // 1. Validar incluyendo el nuevo campo 'role'
        \Log::info('Validando nuevo usuario con input: ' . json_encode($input)); // Log para depuración
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', 'string', 'in:alumno,tutor'],
        ])->validate();


        // 2. Usar Transacción para asegurar integridad
        return DB::transaction(function () use ($input) {
            
            // Determinar booleanos
            $isAlumno = $input['role'] === 'alumno';
            $isTutor = $input['role'] === 'tutor';
             //dd ('es alumno',$isAlumno, 'es tutor',$isTutor); // Log para verificar los valores de los roles
            // 3. Crear el Usuario
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'], // Fortify se encarga del Hash automáticamente si está configurado
                'is_alumno' => $isAlumno,
                'is_tutor' => $isTutor,
                'estado' => 'ACTIVO',
            ]);

            // 4. Crear registro en tabla relacionada (con campos en null)
            if ($isAlumno) {
                Alumno::create([
                    'id_usuario' => $user->id,
                    'direccion' => null,
                    'fecha_nacimiento' => null,
                    'nivel_educativo' => null,
                ]);
            } elseif ($isTutor) {
                Tutor::create([
                    'id_usuario' => $user->id,
                    'especialidad' => null,
                    'biografia' => null,
                ]);
            }

            return $user;
        });
    }
}
