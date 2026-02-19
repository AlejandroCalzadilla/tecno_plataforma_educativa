<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicio';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_categoria', 'nombre', 'descripcion', 'costo_base', 
        'modalidad', 'estado_activo'
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaNivel::class, 'id_categoria');
    }

    // Tutores calificados para este servicio
    public function tutores()
    {
        return $this->belongsToMany(Tutor::class, 'servicio_tutor', 'id_servicio', 'id_tutor');
    }
    
    // Oferta concreta (Calendarios abiertos)
    public function calendarios()
    {
        return $this->hasMany(Calendario::class, 'id_servicio');
    }
}