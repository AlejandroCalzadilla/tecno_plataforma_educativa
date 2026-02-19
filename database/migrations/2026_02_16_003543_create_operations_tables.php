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
        // 14. Asistencia
      /*   Schema::create('asistencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_inscripcion');
            $table->date('fecha');
            $table->enum('estado_asistencia', ['PRESENTE', 'AUSENTE', 'TARDANZA', 'JUSTIFICADO']);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->foreign('id_inscripcion')->references('id')->on('inscripcion');
        }); */

        Schema::create('sesion_programada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_inscripcion')->constrained('inscripcion')->onDelete('cascade');
            $table->date('fecha_sesion');
            $table->time('fecha_hora_inicio');
            $table->time('fecha_hora_fin');
            $table->text('link_sesion')->nullable(); // Para sesiones virtuales
            $table->enum('estado_asistencia', ['PENDIENTE','PRESENTE', 'AUSENTE', 'TARDANZA', 'JUSTIFICADO']);
            $table->integer('numero_sesion'); // Ej: Sesión 1 de 4
             $table->text('observaciones')->nullable();
            $table->timestamps();
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

            $table->foreign('id_asistencia')->references('id')->on('sesion_programada');
        });

        // 16. InformeClase
        Schema::create('informeclase', function (Blueprint $table) {
            $table->id('id_informe');
            $table->unsignedBigInteger('id_asistencia')->unique(); // 1:1 con asistencia
            $table->text('temas_vistos')->nullable();
            $table->text('tareas_asignadas')->nullable();
            $table->enum('desempenio', ['BAJO', 'MEDIO', 'ALTO', 'EXCELENTE'])->nullable();
            $table->timestamps();

            $table->foreign('id_asistencia')->references('id')->on('sesion_programada');
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
    }
};
