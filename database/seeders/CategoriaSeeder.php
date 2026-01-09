<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'HEMATOLOGIA'],
            ['nombre' => 'BIOQUIMICA'],
            ['nombre' => 'INMUNOLOGIA'],
            ['nombre' => 'MICROBIOLOGIA'],
            ['nombre' => 'PERFILES'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(['nombre' => $categoria['nombre']]);
        }
    }
} 