<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    use HasFactory;
    
    protected $table = 'servicios';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'parent_id',
        'activo',
        'fecha_desactivacion',
        'motivo_desactivacion'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_desactivacion' => 'datetime'
    ];

    /**
     * Relationship with Solicitud
     */
    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class);
    }

    /**
     * Relationship with parent service
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'parent_id');
    }

    /**
     * Relationship with child services
     */
    public function children(): HasMany
    {
        return $this->hasMany(Servicio::class, 'parent_id');
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Check if this service is a parent service
     */
    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this service is a child service
     */
    public function isChild(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Get the full name including parent if it's a child service
     */
    public function getFullNameAttribute(): string
    {
        if ($this->isChild() && $this->parent) {
            return $this->parent->nombre . ' - ' . $this->nombre;
        }
        return $this->nombre;
    }

    /**
     * Scope to get only active services
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope to get only inactive services
     */
    public function scopeInactive($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Deactivate the service
     */
    public function deactivate($motivo = null)
    {
        $this->update([
            'activo' => false,
            'fecha_desactivacion' => now(),
            'motivo_desactivacion' => $motivo
        ]);
    }

    /**
     * Activate the service
     */
    public function activate()
    {
        $this->update([
            'activo' => true,
            'fecha_desactivacion' => null,
            'motivo_desactivacion' => null
        ]);
    }
}
