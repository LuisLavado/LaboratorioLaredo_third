<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorResultado extends Model
{
    use HasFactory;

    protected $table = 'valores_resultado';

    protected $fillable = [
        'detalle_solicitud_id',
        'campo_examen_id',
        'valor',
        'observaciones',
        'fuera_rango'
    ];

    protected $casts = [
        'fuera_rango' => 'boolean'
    ];

    public function detalleSolicitud(): BelongsTo
    {
        return $this->belongsTo(DetalleSolicitud::class);
    }

    public function campoExamen(): BelongsTo
    {
        return $this->belongsTo(CampoExamen::class);
    }

    /**
     * Boot method para validar automáticamente si está fuera de rango
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($valorResultado) {
            if ($valorResultado->campoExamen) {
                $valorResultado->fuera_rango = !$valorResultado->campoExamen->validarRango($valorResultado->valor);
            }
        });
    }
}
