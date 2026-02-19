<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disponibilidad extends Model
{
    protected $table = 'disponibilidad';

    protected $fillable = [
        'id_calendario',
        'dia_semana',
        'hora_apertura',
        'hora_cierre',
    ];

    public function calendario(): BelongsTo
    {
        return $this->belongsTo(Calendario::class, 'id_calendario');
    }
}
