<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cuota extends Model
{
    protected $table = 'cuota';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_venta', 'numero_cuota', 'monto_cuota',
        'fecha_vencimiento', 'estado_pago',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_cuota');
    }

    // ----------------------------------------------------------------
    // Lógica de negocio
    // ----------------------------------------------------------------

    /**
     * Devuelve el id_alumno navegando por la relación Venta → Inscripcion.
     */
    public function getIdAlumnoAttribute(): int
    {
        return $this->venta->inscripcion->id_alumno;
    }

    /**
     * Registra el pago de esta cuota dentro de una transacción DB.
     * Crea el registro Pago, marca la cuota como PAGADO y
     * pide a la Venta que recalcule su saldo.
     *
     * @param  float   $montoAbonado
     * @param  string  $metodoPago   EFECTIVO | QR | TRANSFERENCIA | TARJETA
     * @param  string|null $codigoTransaccion  Transaction ID externo (PagoFácil, etc.)
     * @param  string|null $comprobanteUrl     URL del comprobante
     * @return Pago
     */
    public function confirmarPago(
        float  $montoAbonado,
        string $metodoPago,
        ?string $codigoTransaccion = null,
        ?string $comprobanteUrl = null
    ): Pago {
        return DB::transaction(function () use ($montoAbonado, $metodoPago, $codigoTransaccion, $comprobanteUrl) {
            $pago = $this->pagos()->create([
                'id_alumno'                 => $this->id_alumno,
                'fecha_pago'                => now(),
                'monto_abonado'             => $montoAbonado,
                'metodo_pago'               => $metodoPago,
                'codigo_transaccion_externo' => $codigoTransaccion,
                'comprobante_url'           => $comprobanteUrl,
            ]);

            $this->update(['estado_pago' => 'PAGADO']);

            $this->venta->recalcularSaldo();

            return $pago;
        });
    }

    /**
     * Indica si la cuota ya está pagada.
     */
    public function estaPagada(): bool
    {
        return $this->estado_pago === 'PAGADO';
    }
}