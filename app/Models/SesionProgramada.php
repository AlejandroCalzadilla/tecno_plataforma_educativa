<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionProgramada extends Model
{
    protected $table = 'sesion_programada';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_calendario',
        'fecha_sesion',
        'hora_inicio',
        'hora_fin',
        'link_sesion',
        'numero_sesion',
    ];

    public function calendario()
    {
        return $this->belongsTo(Calendario::class, 'id_calendario', 'id');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_sesion', 'id');
    }
}