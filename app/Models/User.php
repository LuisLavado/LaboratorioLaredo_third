<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'role',
        'especialidad',
        'colegiatura',
        'webhook_url',
        'webhook_events',
        'ultimo_acceso',
        'activo',
        'fecha_desactivacion',
        'motivo_desactivacion',
        'centro_salud_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'ultimo_acceso' => 'datetime',
        'activo' => 'boolean',
        'fecha_desactivacion' => 'datetime'
    ];

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class);
    }

    // Relación con centro de salud
    public function centroSalud()
    {
        return $this->belongsTo(CentroSalud::class);
    }

    /**
     * Check if user has a specific role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is a doctor
     *
     * @return bool
     */
    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    /**
     * Check if user is a lab technician
     *
     * @return bool
     */
    public function isLabTechnician(): bool
    {
        return $this->role === 'laboratorio';
    }

    /**
     * Check if user is an administrator
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'administrador';
    }

    /**
     * Desactivar usuario
     */
    public function desactivar($motivo = null)
    {
        $this->update([
            'activo' => false,
            'fecha_desactivacion' => now(),
            'motivo_desactivacion' => $motivo
        ]);
    }

    /**
     * Activar usuario
     */
    public function activar()
    {
        $this->update([
            'activo' => true,
            'fecha_desactivacion' => null,
            'motivo_desactivacion' => null
        ]);
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }
}
