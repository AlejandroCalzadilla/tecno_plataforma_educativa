<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    protected $table = 'cuota';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_venta', 'numero_cuota', 'monto_cuota', 
        'fecha_vencimiento', 'estado_pago'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta','id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_cuota');
    }
}