<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Paciente;
use Faker\Factory as Faker;
use Carbon\Carbon;

class PacienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('es_ES');
        
        // Crear 50 pacientes de prueba
        for ($i = 0; $i < 50; $i++) {
            $sexo = $faker->randomElement(['masculino', 'femenino']);
            $fechaNacimiento = $faker->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d');
            $edad = Carbon::parse($fechaNacimiento)->age;
            
            // Edad gestacional solo para mujeres en edad fértil (entre 15 y 45 años)
            $edadGestacional = null;
            if ($sexo === 'femenino' && $edad >= 15 && $edad <= 45 && $faker->boolean(20)) {
                $edadGestacional = $faker->numberBetween(4, 40);
            }
            
            Paciente::create([
                'dni' => $faker->unique()->numerify('########'),
                'nombres' => $faker->firstName,
                'apellidos' => $faker->lastName . ' ' . $faker->lastName,
                'fecha_nacimiento' => $fechaNacimiento,
                'celular' => $faker->numerify('9########'),
                'historia_clinica' => $faker->unique()->numerify('HC######'),
                'sexo' => $sexo,
                'edad' => $edad,
                'edad_gestacional' => $edadGestacional,
            ]);
        }
    }
} 