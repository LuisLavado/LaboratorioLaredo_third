<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dni',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'celular',
        'historia_clinica',
        'sexo',
        'edad_gestacional',
        'solicitud_con_datos_completos'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_registro' => 'datetime',
        'solicitud_con_datos_completos' => 'boolean'
    ];

    protected $appends = ['edad'];

    // Campos que se generan automáticamente
    protected $guarded = ['codigo', 'fecha_registro'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($paciente) {
            // Obtener el último código y sumarle 1
            $ultimoCodigo = self::max('codigo') ?? 0;
            $paciente->codigo = $ultimoCodigo + 1;
            // Establecer fecha y hora actual
            $paciente->fecha_registro = now();
        });
    }

    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }

        $fechaNacimiento = $this->fecha_nacimiento;
        $hoy = now();

        $edad = $hoy->year - $fechaNacimiento->year;

        if ($hoy->month < $fechaNacimiento->month ||
            ($hoy->month === $fechaNacimiento->month && $hoy->day < $fechaNacimiento->day)) {
            $edad--;
        }

        return $edad;
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class);
    }
}