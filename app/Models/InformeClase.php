<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeClase extends Model
{
    protected $table = 'informeClase';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_asistencia', 'temas_vistos', 'tareas_asignadas', 'desempenio'
    ];

    public function asistencia()
    {
        return $this->belongsTo(Asistencia::class, 'id_asistencia');
    }
}