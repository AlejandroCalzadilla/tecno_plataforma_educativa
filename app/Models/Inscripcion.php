<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    protected $table = 'inscripcion';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_alumno',
        'id_calendario',
        'fecha_inscripcion',
        'estado_academico',
        'calificacion_final',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'datetime',
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'id_alumno','id');
    }

    

    // Una inscripción tiene UNA venta asociada
    public function venta()
    {
        return $this->hasOne(Venta::class, 'id_inscripcion', 'id');
    }

    public function sesionesProgramadas()
    {
        return $this->hasMany(SesionProgramada::class, 'id_inscripcion', 'id');
    }

    public function calendario()
    {
        return $this->belongsTo(Calendario::class, 'id_calendario', 'id');
    }
}