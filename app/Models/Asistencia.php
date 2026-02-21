<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencia';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_sesion',
        'id_inscripcion',
        'estado_asistencia',
        'observaciones',
    ];

    public function sesion()
    {
        return $this->belongsTo(SesionProgramada::class, 'id_sesion', 'id');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id');
    }

    public function licencia()
    {
        return $this->hasOne(Licencia::class, 'id_asistencia', 'id');
    }

    public function informe()
    {
        return $this->hasOne(InformeClase::class, 'id_asistencia', 'id');
    }
}
