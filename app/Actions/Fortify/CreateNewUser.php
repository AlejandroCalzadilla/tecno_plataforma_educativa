<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Alumno;
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
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();


        // 2. Usar Transacción para asegurar integridad
        return DB::transaction(function () use ($input) {
            $isAlumno = true;
            $isTutor = false;

            // 3. Crear el Usuario
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
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
            }

            return $user;
        });
    }
}
