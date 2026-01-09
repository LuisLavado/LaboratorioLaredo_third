<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use HasFactory;
    
    protected $table = 'categorias';
    
    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre'
    ];
    
    /**
     * Obtiene los exámenes relacionados con la categoría.
     */
    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class);
    }
}
