<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {



         Schema::create('sesion_programada', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_calendario')
                ->constrained('calendario')
                ->onDelete('cascade');

            $table->date('fecha_sesion');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->text('link_sesion')->nullable();

            $table->integer('numero_sesion')->nullable();
            // Para paquetes: 1,2,3...
            // Para clase privada puede ser null

            $table->timestamps();
        });
        // 14. Asistencia
        Schema::create('asistencia', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_sesion')
                ->constrained('sesion_programada')
                ->onDelete('cascade');

            $table->foreignId('id_inscripcion')
                ->constrained('inscripcion')
                ->onDelete('cascade');
            $table->enum('estado_asistencia', [
                'PENDIENTE',
                'PRESENTE',
                'AUSENTE',
                'TARDANZA',
                'JUSTIFICADO'
            ])->default('PENDIENTE');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['id_sesion', 'id_inscripcion']);
            // evita duplicados
        });

        

        // 15. Licencia
        Schema::create('licencia', function (Blueprint $table) {
            $table->id('id_licencia');
            $table->unsignedBigInteger('id_asistencia')->unique(); // 1:1 con asistencia
            $table->dateTime('fecha_solicitud')->useCurrent();
            $table->text('motivo');
            $table->string('evidencia_url', 255)->nullable();
            $table->enum('estado_aprobacion', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])->default('PENDIENTE');
            $table->text('observacion_admin')->nullable();
            $table->timestamps();

            $table->foreign('id_asistencia')->references('id')->on('asistencia');
        });

        // 16. InformeClase
        Schema::create('informeclase', function (Blueprint $table) {
            $table->id('id_informe');
            $table->unsignedBigInteger('id_asistencia')->unique(); // 1:1 con asistencia
            $table->text('temas_vistos')->nullable();
            $table->text('tareas_asignadas')->nullable();
            $table->enum('desempenio', ['BAJO', 'MEDIO', 'ALTO', 'EXCELENTE'])->nullable();
            $table->timestamps();

            $table->foreign('id_asistencia')->references('id')->on('asistencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informeclase');
        Schema::dropIfExists('licencia');
        Schema::dropIfExists('asistencia');
        Schema::dropIfExists('sesion_programada');
    }
};
