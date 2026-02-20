<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeClase extends Model
{
    protected $table = 'informeclase';
    protected $primaryKey = 'id_informe';
    protected $fillable = [
        'id_asistencia', 'temas_vistos', 'tareas_asignadas', 'desempenio'
    ];

    public function asistencia()
    {
        return $this->belongsTo(SesionProgramada::class, 'id_asistencia', 'id');
    }
}