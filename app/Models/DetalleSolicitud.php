<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetalleSolicitud extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'detallesolicitud';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */    protected $fillable = [
        'solicitud_id',
        'examen_id',
        'estado',
        'observaciones',
        'resultado',
        'fecha_resultado',
        'completed_at',
        'registrado_por',
    ];

    /**
     * Los atributos que deben convertirse.
     *s
     * @var array<string, string>
     */
    protected $casts = [
        'resultados' => 'array',
        'fecha_resultado' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Obtener la solicitud a la que pertenece este detalle.
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    /**
     * Obtener el examen asociado a este detalle.
     */
    public function examen()
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    /**
     * Obtener el usuario que registró el resultado.
     */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function resultados()
    {
        return $this->hasMany(ResultadoExamen::class, 'detalle_solicitud_id');
    }

    /**
     * Valores de resultado dinámicos
     */
    public function valoresResultado(): HasMany
    {
        return $this->hasMany(ValorResultado::class);
    }

    /**
     * Obtener valores de resultado agrupados por sección
     * Incluye campos históricos (inactivos) que tienen valores
     */
    public function valoresPorSeccion()
    {
        return $this->valoresResultado()
            ->with(['campoExamen' => function($query) {
                // Incluir campos inactivos que tienen valores para esta solicitud
                $query->paraMostrarResultados($this->id);
            }])
            ->get()
            ->groupBy(function ($valor) {
                return $valor->campoExamen->seccion ?? 'General';
            });
    }

    /**
     * Verificar si el detalle tiene resultados completos
     */
    public function tieneResultadosCompletos(): bool
    {
        $camposRequeridos = $this->examen->todosLosCampos()->where('requerido', true);

        // Obtener los IDs de campos requeridos
        $camposRequeridosIds = $camposRequeridos->pluck('id')->toArray();

        // Verificar que todos los campos requeridos tengan valores
        $valoresRequeridosIngresados = $this->valoresResultado()
            ->whereIn('campo_examen_id', $camposRequeridosIds)
            ->whereNotNull('valor')
            ->where('valor', '!=', '')
            ->count();

        return $valoresRequeridosIngresados >= $camposRequeridos->count();
    }

    /**
     * Verificar si el detalle tiene resultados completos - VERSIÓN OPTIMIZADA
     * Usa caché y consultas más eficientes
     */
    public function tieneResultadosCompletosOptimizado(): bool
    {
        // Si no hay examen, no puede estar completo
        if (!$this->examen) {
            return false;
        }

        // Para exámenes sin campos definidos, verificar resultado clásico
        $camposRequeridos = $this->examen->campos()->where('requerido', true)->get();

        if ($camposRequeridos->isEmpty()) {
            // Examen sin campos definidos - verificar resultado clásico
            return !empty($this->resultado);
        }

        // Obtener IDs de campos requeridos
        $camposRequeridosIds = $camposRequeridos->pluck('id')->toArray();

        // Contar valores ingresados de forma más eficiente
        $valoresIngresados = $this->valoresResultado()
            ->whereIn('campo_examen_id', $camposRequeridosIds)
            ->whereNotNull('valor')
            ->where('valor', '!=', '')
            ->count();

        return $valoresIngresados >= count($camposRequeridosIds);
    }
}