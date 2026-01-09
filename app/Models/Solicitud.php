<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solicitud extends Model
{
    public $table = "solicitudes";
    use HasFactory;

    protected $fillable = [
        'fecha',
        'hora',
        'servicio_id',
        'numero_recibo',
        'rdr',
        'sis',
        'exon',
        'user_id',
        'paciente_id',
        'estado'
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime',
        'rdr' => 'boolean',
        'sis' => 'boolean',
        'exon' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(Examen::class, 'detallesolicitud', 'solicitud_id', 'examen_id')
            ->withTimestamps();
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleSolicitud::class);
    }
}