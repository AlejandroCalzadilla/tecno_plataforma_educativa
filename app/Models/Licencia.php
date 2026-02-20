<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    protected $table = 'licencia';
    protected $primaryKey = 'id_licencia';
    protected $fillable = [
        'id_asistencia', 'fecha_solicitud', 'motivo', 
        'evidencia_url', 'estado_aprobacion', 'observacion_admin'
    ];

    public function asistencia()
    {
        return $this->belongsTo(SesionProgramada::class, 'id_asistencia', 'id');
    }
}