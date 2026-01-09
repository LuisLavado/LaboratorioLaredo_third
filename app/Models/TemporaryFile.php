<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TemporaryFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'file_path',
        'file_type',
        'original_name',
        'file_size',
        'expires_at',
        'download_count',
        'max_downloads',
        'created_by'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * Relación con el usuario que creó el archivo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Verificar si el archivo ha expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verificar si se ha alcanzado el límite de descargas
     */
    public function hasReachedDownloadLimit(): bool
    {
        return $this->download_count >= $this->max_downloads;
    }

    /**
     * Verificar si el archivo está disponible para descarga
     */
    public function isAvailable(): bool
    {
        return !$this->isExpired() && !$this->hasReachedDownloadLimit() && Storage::exists($this->file_path);
    }

    /**
     * Incrementar contador de descargas
     */
    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    /**
     * Obtener URL pública temporal
     */
    public function getPublicUrl(): string
    {
        return route('temporary-file.download', ['token' => $this->token]);
    }

    /**
     * Scope para archivos no expirados
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope para archivos disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->notExpired()
                    ->whereRaw('download_count < max_downloads');
    }
}
