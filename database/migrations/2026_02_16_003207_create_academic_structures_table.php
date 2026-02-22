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
       // 4. CategoriaNivel
        Schema::create('categorianivel', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_categoria_padre')->nullable();
            $table->string('nombre', 100);
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('id_categoria_padre')
                  ->references('id')->on('categorianivel')
                  ->onDelete('set null');
        });

        // 5. Servicio
        Schema::create('servicio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_categoria');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->enum('modalidad', ['VIRTUAL', 'PRESENCIAL', 'HIBRIDO']);
            $table->boolean('estado_activo')->default(true);
            $table->timestamps();
            $table->foreign('id_categoria')
                  ->references('id')->on('categorianivel');
        });

        // 6. Servicio_Tutor (Tabla Pivote)
        /* Schema::create('servicio_tutor', function (Blueprint $table) {
            $table->unsignedBigInteger('id_servicio');
            $table->unsignedBigInteger('id_tutor');
            
            // Clave primaria compuesta
            $table->primary(['id_servicio', 'id_tutor']);

            $table->foreign('id_servicio')->references('id')->on('servicio')->onDelete('cascade');
            $table->foreign('id_tutor')->references('id')->on('tutor')->onDelete('cascade');
        }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Schema::dropIfExists('servicio_tutor');
        Schema::dropIfExists('servicio');
        Schema::dropIfExists('categorianivel');
    }
};
