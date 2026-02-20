<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionProgramada extends Model
{
    protected $table = 'sesion_programada';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_inscripcion',
        'fecha_sesion',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'link_sesion',
        'estado_asistencia',
        'numero_sesion',
        'observaciones',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion','id');
    }

    public function licencia()
    {
        return $this->hasOne(Licencia::class, 'id_asistencia');
    }

    public function informe()
    {
        return $this->hasOne(InformeClase::class, 'id_asistencia');
    }
}