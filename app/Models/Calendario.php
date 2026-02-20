<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendario extends Model
{
    protected $table = 'calendario';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_servicio',
        'id_tutor',
        'tipo_programacion',
        'numero_sesiones',
        'duracion_sesion_minutos',
        'costo_total',
        'cupos_maximos',
    ];

    protected $casts = [
        'costo_total' => 'decimal:2',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }


    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_calendario');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'id_tutor', 'id');
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(Disponibilidad::class, 'id_calendario');
    }
}