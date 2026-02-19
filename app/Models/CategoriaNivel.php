<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class CategoriaNivel extends Model
{
    protected $table = 'categorianivel';
    protected $primaryKey = 'id';
    protected $fillable = ['id_categoria_padre', 'nombre', 'estado'];

    // Relación recursiva (Padre)
    public function padre()
    {
        return $this->belongsTo(CategoriaNivel::class, 'id_categoria_padre','id');
    }

    // Relación recursiva (Hijos)
    public function hijos()
    {
        return $this->hasMany(CategoriaNivel::class, 'id_categoria_padre','id');
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'id_categoria', 'id');
    }
}