<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pago';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_alumno', 'id_cuota', 'fecha_pago', 'monto_abonado',
        'metodo_pago', 'codigo_transaccion_externo', 'comprobante_url',
    ];

    public function cuota()
    {
        return $this->belongsTo(Cuota::class, 'id_cuota');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'id_alumno', 'id');
    }

    // ----------------------------------------------------------------
    // Helpers de búsqueda
    // ----------------------------------------------------------------

    /**
     * Busca un pago por referencia de transacción externa.
     * Útil para el callback de PagoFácil.
     */
    public static function buscarPorReferencia(string $referencia): ?self
    {
        return static::where('codigo_transaccion_externo', 'LIKE', "%{$referencia}%")->first();
    }
}