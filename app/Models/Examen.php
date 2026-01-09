<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'examenes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'categoria_id',
        'activo',
        'tipo',
        'es_perfil',
        'instrucciones_muestra',
        'metodo_analisis'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'activo' => 'boolean',
        'es_perfil' => 'boolean',
    ];
    
    /**
     * Get the categoria that owns the examen.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Obtener los detalles de solicitudes donde se incluye este examen.
     */
    public function detallesSolicitudes(): HasMany
    {
        return $this->hasMany(DetalleSolicitud::class);
    }

    /**
     * Obtener las solicitudes asociadas a este examen a través de detalles.
     */
    public function solicitudes()
    {
        return $this->belongsToMany(Solicitud::class, 'detallesolicitud', 'examen_id', 'solicitud_id');
    }

    /**
     * Campos dinámicos del examen
     */
    public function campos(): HasMany
    {
        return $this->hasMany(CampoExamen::class)->orderBy('orden');
    }

    /**
     * Exámenes hijos (para exámenes compuestos)
     */
    public function examenesHijos(): BelongsToMany
    {
        return $this->belongsToMany(
            Examen::class,
            'examenes_compuestos',
            'examen_padre_id',
            'examen_hijo_id'
        )->withPivot('orden', 'activo')->orderBy('examenes_compuestos.orden');
    }

    /**
     * Exámenes padres (exámenes que incluyen este como hijo)
     */
    public function examenesPadres(): BelongsToMany
    {
        return $this->belongsToMany(
            Examen::class,
            'examenes_compuestos',
            'examen_hijo_id',
            'examen_padre_id'
        )->withPivot('orden', 'activo');
    }

    /**
     * Scope para exámenes simples
     */
    public function scopeSimples($query)
    {
        return $query->where('tipo', 'simple');
    }

    /**
     * Scope para exámenes compuestos
     */
    public function scopeCompuestos($query)
    {
        return $query->where('tipo', 'compuesto');
    }

    /**
     * Verificar si es un examen compuesto
     */
    public function esCompuesto(): bool
    {
        return $this->tipo === 'compuesto';
    }

    /**
     * Verificar si es un examen híbrido (tiene campos propios Y exámenes hijos)
     */
    public function esHibrido(): bool
    {
        return $this->tipo === 'hibrido';
    }

    /**
     * Verificar si puede tener campos propios
     */
    public function puedeTenerCampos(): bool
    {
        return in_array($this->tipo, ['simple', 'hibrido']);
    }

    /**
     * Verificar si puede tener exámenes hijos
     */
    public function puedeTenerHijos(): bool
    {
        return in_array($this->tipo, ['compuesto', 'hibrido']);
    }

    /**
     * Verificar si es un perfil
     */
    public function esPerfil(): bool
    {
        return $this->es_perfil === true;
    }

    /**
     * Scope para exámenes que NO son perfiles
     */
    public function scopeNoPerfiles($query)
    {
        return $query->where('es_perfil', false);
    }

    /**
     * Scope para perfiles únicamente
     */
    public function scopePerfiles($query)
    {
        return $query->where('es_perfil', true);
    }

    /**
     * Obtener todos los campos incluyendo los de exámenes hijos
     */
    public function todosLosCampos()
    {
        $campos = collect();

        // Agregar campos propios si el examen puede tenerlos (simple o híbrido)
        if ($this->puedeTenerCampos()) {
            $camposPropios = $this->campos->map(function ($campo) {
                $campo->examen_origen_nombre = $this->nombre;
                $campo->es_campo_propio = true;
                return $campo;
            });
            $campos = $campos->merge($camposPropios);
        }

        // Agregar campos de exámenes hijos si el examen puede tenerlos (compuesto o híbrido)
        if ($this->puedeTenerHijos()) {
            foreach ($this->examenesHijos as $examenHijo) {
                $camposHijo = $examenHijo->campos->map(function ($campo) use ($examenHijo) {
                    $campo->examen_origen_nombre = $examenHijo->nombre;
                    $campo->es_campo_propio = false;
                    return $campo;
                });
                $campos = $campos->merge($camposHijo);
            }
        }

        return $campos->sortBy('orden');
    }
}