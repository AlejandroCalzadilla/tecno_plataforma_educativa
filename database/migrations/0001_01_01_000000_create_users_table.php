<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_propietario')->default(false);
            $table->boolean('is_alumno')->default(false);
            $table->boolean('is_tutor')->default(false);
            $table->enum('estado', ['ACTIVO', 'INACTIVO', 'BLOQUEADO'])->default('ACTIVO');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });



        // 2. Tabla Alumno (1:1 con Usuario)
        Schema::create('alumno', function (Blueprint $table) {
            // PK y FK manual porque es 1:1
            $table->id();
            $table->string('direccion', 255)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('nivel_educativo', 50)->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('id_usuario')->unique(); // 1:1 con users
            $table->foreign('id_usuario')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });

        // 3. Tabla Tutor (1:1 con Usuario)
        Schema::create('tutor', function (Blueprint $table) {
            $table->id();
            $table->string('especialidad', 100)->nullable();
            $table->text('biografia')->nullable();
            $table->string('cv_url', 255)->nullable();
            $table->string('banco_nombre', 100)->nullable();
            $table->string('banco_cbu', 50)->nullable();
            $table->timestamps(); // Aunque tu SQL no lo tenía, Laravel lo requiere para updated_at
            $table->unsignedBigInteger('id_usuario')->unique(); // 1:1 con users
            $table->foreign('id_usuario')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('tutor');
        Schema::dropIfExists('alumno');
        Schema::dropIfExists('users');
    }
};
