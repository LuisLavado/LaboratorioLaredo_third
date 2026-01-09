<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Solicitud;
use App\Models\Paciente;
use App\Models\Examen;
use App\Models\User;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SolicitudSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('es_ES');
        
        $pacienteIds = Paciente::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();
        $servicioIds = DB::table('servicios')->pluck('id')->toArray();
        $examenIds = Examen::pluck('id')->toArray();
        
        if (empty($pacienteIds) || empty($userIds) || empty($examenIds)) {
            $this->command->info('No hay suficientes datos para crear solicitudes. Asegúrate de ejecutar primero los seeders necesarios (AdminSeeder, ExamenesSeeder, PacienteSeeder).');
            return;
        }
        
        if (empty($servicioIds)) {
            $this->command->info('No hay servicios. Creando servicio por defecto...');
            DB::table('servicios')->insert(['nombre' => 'MEDICINA']);
            $servicioIds = DB::table('servicios')->pluck('id')->toArray();
        }
        
        $this->command->info('Creando 100 solicitudes de prueba...');
        for ($i = 0; $i < 100; $i++) {
            $fechaSolicitud = $faker->dateTimeBetween('-6 months', 'now');
            $fecha = Carbon::parse($fechaSolicitud)->format('Y-m-d');
            $hora = Carbon::parse($fechaSolicitud)->format('H:i:s');
            
            $solicitud = Solicitud::create([
                'fecha' => $fecha,
                'hora' => $hora,
                'servicio_id' => $faker->randomElement($servicioIds),
                'numero_recibo' => $faker->unique()->numerify('REC######'),
                'rdr' => $faker->boolean(20),
                'sis' => $faker->boolean(30),
                'exon' => $faker->boolean(10),
                'user_id' => $faker->randomElement($userIds),
                'paciente_id' => $faker->randomElement($pacienteIds)
            ]);
            
            $numExamenes = $faker->numberBetween(1, 5);
            $examenesSeleccionados = $faker->randomElements($examenIds, min($numExamenes, count($examenIds)));
            
            foreach ($examenesSeleccionados as $examenId) {
                $tieneResultado = $faker->boolean(70);
                $resultado = $tieneResultado ? $faker->randomElement(['NORMAL', 'ELEVADO', 'BAJO', 'NEGATIVO', 'POSITIVO']) : null;
                
                DB::table('detallesolicitud')->insert([
                    'examen_id' => $examenId,
                    'solicitud_id' => $solicitud->id,
                    
                    'created_at' => $fechaSolicitud,
                    'updated_at' => $tieneResultado ? $faker->dateTimeBetween($fechaSolicitud, 'now') : $fechaSolicitud
                ]);
            }
        }
        
        $this->command->info('Seeders completados correctamente.');
    }
}