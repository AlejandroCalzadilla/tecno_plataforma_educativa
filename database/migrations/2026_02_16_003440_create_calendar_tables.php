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
        // 7. Calendario
        Schema::create('calendario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_servicio')->constrained('servicio');
            $table->foreignId('id_tutor')->constrained('tutor');
            $table->enum('tipo_programacion', ['CITA_LIBRE', 'PAQUETE_FIJO']);
            $table->integer('numero_sesiones')->nullable(); // Solo para paquetes
            $table->unsignedInteger('duracion_sesion_minutos');
            $table->decimal('costo_total', 10, 2);
            $table->unsignedInteger('cupos_maximos')->default(1);

            $table->timestamps();
        });


        //disponibilidad  
        Schema::create('disponibilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_calendario')->constrained('calendario')->onDelete('cascade');
            $table->enum('dia_semana', ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']);
            // Aquí el tutor dice: "Los lunes trabajo de 08:00 a 12:00"
            $table->time('hora_apertura');
            $table->time('hora_cierre');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('disponibilidad');

        Schema::dropIfExists('calendario');
    }
};
