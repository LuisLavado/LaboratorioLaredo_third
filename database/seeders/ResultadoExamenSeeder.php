<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetalleSolicitud;
use App\Models\Examen;
use App\Models\ResultadoExamen;
use Faker\Factory as Faker;

class ResultadoExamenSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('es_ES');
        $detalles = DetalleSolicitud::all();

        foreach ($detalles as $detalle) {
            // Simula entre 1 y 3 parámetros por examen
            $parametros = $faker->numberBetween(1, 3);
            for ($i = 0; $i < $parametros; $i++) {
                ResultadoExamen::create([
                    'detalle_solicitud_id' => $detalle->id,
                    'examen_id' => $detalle->examen_id,
                    'nombre_parametro' => $faker->randomElement(['Glucosa', 'Hemoglobina', 'Colesterol', 'Leucocitos']),
                    'valor' => $faker->randomFloat(2, 1, 200),
                    'unidad' => $faker->randomElement(['mg/dL', 'g/dL', 'mmol/L']),
                    'referencia' => $faker->randomElement(['70-110', '12-16', '140-200', '4-10']),
                ]);
            }
        }
    }
}