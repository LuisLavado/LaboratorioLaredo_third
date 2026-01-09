<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CentroSalud;

class CentrosSaludSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $centros = [
            [
                'nombre' => 'Hospital Nacional Dos de Mayo',
                'codigo' => 'HN2M',
                'direccion' => 'Av. Grau 13, Cercado de Lima',
                'telefono' => '01-3285000',
                'distrito' => 'Cercado de Lima',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'tipo' => 'hospital'
            ],
            [
                'nombre' => 'Centro de Salud Vista Alegre',
                'codigo' => 'CSVA',
                'direccion' => 'Av. Vista Alegre 123, Los Olivos',
                'telefono' => '01-5551234',
                'distrito' => 'Los Olivos',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'tipo' => 'centro_salud'
            ],
            [
                'nombre' => 'Clínica San Juan de Dios',
                'codigo' => 'CSJD',
                'direccion' => 'Jr. Washington 1355, Cercado de Lima',
                'telefono' => '01-4285000',
                'distrito' => 'Cercado de Lima',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'tipo' => 'clinica'
            ],
            [
                'nombre' => 'Hospital Nacional Guillermo Almenara Irigoyen',
                'codigo' => 'HNGAI',
                'direccion' => 'Av. Grau 800, La Victoria',
                'telefono' => '01-3242983',
                'distrito' => 'La Victoria',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'tipo' => 'hospital'
            ],
            [
                'nombre' => 'Centro de Salud Materno Infantil El Porvenir',
                'codigo' => 'CSMEP',
                'direccion' => 'Jr. Los Claveles 456, El Porvenir',
                'telefono' => '044-482150',
                'distrito' => 'El Porvenir',
                'provincia' => 'Trujillo',
                'departamento' => 'La Libertad',
                'tipo' => 'centro_salud'
            ]
        ];

        foreach ($centros as $centro) {
            CentroSalud::create($centro);
        }
    }
}
