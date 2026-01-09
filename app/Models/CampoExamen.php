<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampoExamen extends Model
{
    use HasFactory;

    protected $table = 'campos_examen';

    protected $fillable = [
        'examen_id',
        'nombre',
        'tipo',
        'unidad',
        'valor_referencia',
        'opciones',
        'requerido',
        'orden',
        'seccion',
        'descripcion',
        'activo',
        'version',
        'fecha_desactivacion',
        'motivo_cambio'
    ];

    protected $casts = [
        'opciones' => 'array',
        'requerido' => 'boolean',
        'activo' => 'boolean',
        'fecha_desactivacion' => 'datetime'
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }

    public function valoresResultado(): HasMany
    {
        return $this->hasMany(ValorResultado::class);
    }

    /**
     * Scope para obtener campos activos ordenados
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    /**
     * Scope para obtener campos por sección
     */
    public function scopePorSeccion($query, $seccion)
    {
        return $query->where('seccion', $seccion);
    }

    /**
     * Scope para obtener la versión actual (activa) de los campos
     */
    public function scopeVersionActual($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para obtener campos que tienen resultados registrados
     */
    public function scopeConResultados($query)
    {
        return $query->whereHas('valoresResultado');
    }

    /**
     * Obtener todos los campos relevantes para mostrar resultados
     * (incluye campos inactivos que tienen valores registrados)
     */
    public function scopeParaMostrarResultados($query, $detalleSolicitudId = null)
    {
        if ($detalleSolicitudId) {
            // Incluir campos activos + campos inactivos que tienen valores para esta solicitud
            return $query->where(function($q) use ($detalleSolicitudId) {
                $q->where('activo', true)
                  ->orWhereHas('valoresResultado', function($subQ) use ($detalleSolicitudId) {
                      $subQ->where('detalle_solicitud_id', $detalleSolicitudId);
                  });
            });
        }

        return $query->where('activo', true);
    }

    /**
     * Validar si un valor está dentro del rango de referencia
     */
    public function validarRango($valor)
    {
        // Si no hay valor de referencia o no es un campo numérico, siempre es válido
        if (!$this->valor_referencia || $this->tipo !== 'number') {
            return true;
        }

        // Validar que el valor sea numérico
        if (!is_numeric($valor)) {
            \Log::warning("Valor no numérico recibido para validación", [
                'campo_id' => $this->id,
                'campo_nombre' => $this->nombre,
                'valor' => $valor,
                'tipo_valor' => gettype($valor)
            ]);
            return false;
        }

        $referencia = trim($this->valor_referencia);
        $valorNum = floatval($valor);

        // Validar que la conversión fue exitosa
        if ($valorNum === 0.0 && $valor !== '0' && $valor !== '0.0') {
            \Log::warning("Error en conversión de valor a float", [
                'campo_id' => $this->id,
                'valor_original' => $valor,
                'valor_convertido' => $valorNum
            ]);
            return false;
        }

        try {
            // Patrón para rangos (ej: "10-50", "10.5 - 20.3")
            if (preg_match('/(\d+\.?\d*)\s*-\s*(\d+\.?\d*)/', $referencia, $matches)) {
                $min = floatval($matches[1]);
                $max = floatval($matches[2]);

                return $valorNum >= $min && $valorNum <= $max;
            }

            // Patrón para mayor que (ej: ">5", "> 10.5")
            if (preg_match('/>\s*(\d+\.?\d*)/', $referencia, $matches)) {
                $limite = floatval($matches[1]);
                return $valorNum > $limite;
            }

            // Patrón para menor que (ej: "<100", "< 50.5")
            if (preg_match('/<\s*(\d+\.?\d*)/', $referencia, $matches)) {
                $limite = floatval($matches[1]);
                return $valorNum < $limite;
            }

            // Patrón para mayor o igual que (ej: ">=5")
            if (preg_match('/>=\s*(\d+\.?\d*)/', $referencia, $matches)) {
                $limite = floatval($matches[1]);
                return $valorNum >= $limite;
            }

            // Patrón para menor o igual que (ej: "<=100")
            if (preg_match('/<=\s*(\d+\.?\d*)/', $referencia, $matches)) {
                $limite = floatval($matches[1]);
                return $valorNum <= $limite;
            }

            // Si no coincide con ningún patrón, considerar válido
            return true;

        } catch (\Exception $e) {
            \Log::error("Error en validación de rango", [
                'campo_id' => $this->id,
                'valor' => $valor,
                'referencia' => $referencia,
                'error' => $e->getMessage()
            ]);
            return true; // En caso de error, considerar válido para no bloquear
        }
    }

    /**
     * Desactivar campo en lugar de eliminarlo (para mantener compatibilidad)
     */
    public function desactivar($motivo = null)
    {
        $this->update([
            'activo' => false,
            'fecha_desactivacion' => now(),
            'motivo_cambio' => $motivo
        ]);
    }

    /**
     * Crear nueva versión de un campo
     */
    public function crearNuevaVersion($nuevosDatos, $motivo = null)
    {
        // Desactivar versión actual
        $this->desactivar($motivo);

        // Crear nueva versión
        $nuevaVersion = $this->replicate();
        $nuevaVersion->fill($nuevosDatos);
        $nuevaVersion->version = $this->version + 1;
        $nuevaVersion->activo = true;
        $nuevaVersion->fecha_desactivacion = null;
        $nuevaVersion->motivo_cambio = $motivo;
        $nuevaVersion->save();

        return $nuevaVersion;
    }

    /**
     * Verificar si este campo tiene resultados registrados
     */
    public function tieneResultados()
    {
        return $this->valoresResultado()->exists();
    }
}
