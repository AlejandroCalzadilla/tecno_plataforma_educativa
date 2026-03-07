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
        // 10. Inscripcion
        Schema::create('inscripcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_alumno')->constrained('alumno');
            $table->foreignId('id_calendario')->constrained('calendario');
            $table->dateTime('fecha_inscripcion')->useCurrent();
            $table->enum('estado_academico', [
                'CURSANDO',
                'FINALIZADO',
                'ABANDONADO',
                'PENDIENTE_PAGO'
            ])->default('PENDIENTE_PAGO');
            $table->decimal('calificacion_final', 4, 2)->nullable();
            $table->timestamps();
        });




        // 11. Venta
        Schema::create('venta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_inscripcion')->unique(); // Relación 1:1
            $table->dateTime('fecha_emision')->useCurrent();
            $table->decimal('monto_total', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->enum('tipo_pago_pref', ['CONTADO', 'CUOTAS', 'CREDITO']);
            $table->enum('estado_financiero', ['PENDIENTE', 'PARCIAL', 'PAGADO', 'ANULADO'])->default('PENDIENTE');
            $table->timestamps();

            $table->foreign('id_inscripcion')->references('id')->on('inscripcion');
        });

        // 12. Cuota
        Schema::create('cuota', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_venta');
            $table->integer('numero_cuota');
            $table->decimal('monto_cuota', 10, 2);
            $table->date('fecha_vencimiento');
            $table->enum('estado_pago', ['PENDIENTE', 'PAGADO', 'VENCIDO'])->default('PENDIENTE');
            $table->timestamps();

            $table->foreign('id_venta')->references('id')->on('venta')->onDelete('cascade');
        });

        // 13. Pago
        Schema::create('pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_alumno');
            $table->unsignedBigInteger('id_cuota');
            $table->dateTime('fecha_pago')->useCurrent();
            $table->decimal('monto_abonado', 10, 2);
            $table->enum('metodo_pago', ['EFECTIVO', 'QR', 'TRANSFERENCIA', 'TARJETA']);
            $table->string('codigo_transaccion_externo', 100)->nullable();
            $table->string('comprobante_url', 255)->nullable();
            $table->timestamps();

            $table->foreign('id_alumno')->references('id')->on('alumno');
            $table->foreign('id_cuota')->references('id')->on('cuota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago');
        Schema::dropIfExists('cuota');
        Schema::dropIfExists('venta');
        Schema::dropIfExists('inscripcion');
    }
};
