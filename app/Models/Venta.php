<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'venta';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_inscripcion', 'fecha_emision', 'monto_total', 
        'saldo_pendiente', 'tipo_pago_pref', 'estado_financiero'
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion','id');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'id_venta','id');
    }
}