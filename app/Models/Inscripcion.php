<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    protected $table = 'inscripcion';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_alumno', 'id_servicio', 'fecha_alta', 
        'estado_academico', 'calificacion_final'
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'id_alumno','id');
    }

    

    // Una inscripción tiene UNA venta asociada
    public function venta()
    {
        return $this->hasOne(Venta::class, 'id_inscripcion', 'id_inscripcion');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_inscripcion');
    }

    public function calendario()
    {
        return $this->belongsTo(Calendario::class, 'id_calendario', 'id');
    }
}