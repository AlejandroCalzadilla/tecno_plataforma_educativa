<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Tutor extends Model
{
    protected $table = 'tutor';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $fillable = ['id_usuario', 'especialidad', 'biografia', 'cv_url', 'banco_nombre', 'banco_cbu','id'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // Relación N:M con Servicio
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'servicio_tutor', 'id_tutor', 'id_servicio');
    }

    public function calendarios()
    {
        return $this->hasMany(Calendario::class, 'id_tutor', 'id');
    }
}