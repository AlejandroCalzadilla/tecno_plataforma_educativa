<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Alumno extends Model
{
    protected $table = 'alumno';
    protected $primaryKey = 'id'; // PK es también FK
    public $incrementing = false; // Importante porque no es auto-increment (hereda)
    protected $fillable = ['id_usuario', 'direccion', 'fecha_nacimiento', 'nivel_educativo'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_alumno', 'id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_alumno', 'id');
    }
}