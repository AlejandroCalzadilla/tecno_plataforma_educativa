<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'venta';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_inscripcion', 'fecha_emision', 'monto_total',
        'saldo_pendiente', 'tipo_pago_pref', 'estado_financiero',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'id_venta', 'id');
    }

    // ----------------------------------------------------------------
    // Lógica de negocio
    // ----------------------------------------------------------------

    /**
     * Recalcula el saldo_pendiente y el estado_financiero
     * sumando las cuotas ya pagadas.
     */
    public function recalcularSaldo(): void
    {
        $totalPagado = (float) $this->cuotas()
            ->where('estado_pago', 'PAGADO')
            ->sum('monto_cuota');

        $saldo = max(0, (float) $this->monto_total - $totalPagado);

        $estado = match (true) {
            $saldo <= 0          => 'PAGADO',
            $totalPagado > 0     => 'PARCIAL',
            default              => 'PENDIENTE',
        };

        $this->update([
            'saldo_pendiente'   => $saldo,
            'estado_financiero' => $estado,
        ]);
    }
}