<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultadoExamen extends Model
{
    use HasFactory;

    protected $table = 'resultados_examen';

    protected $fillable = [
        'detalle_solicitud_id',
        'examen_id',
        'nombre_parametro',
        'valor',
        'unidad',
        'referencia'
    ];

    public function detalleSolicitud()
    {
        return $this->belongsTo(DetalleSolicitud::class, 'detalle_solicitud_id');
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class);
    }
}