<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenCompuesto extends Model
{
    use HasFactory;

    protected $table = 'examenes_compuestos';

    protected $fillable = [
        'examen_padre_id',
        'examen_hijo_id',
        'orden',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function examenPadre(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_padre_id');
    }

    public function examenHijo(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_hijo_id');
    }

    /**
     * Scope para obtener relaciones activas ordenadas
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
