<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Servicio;

class ServiciosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        // Crear servicios principales
        $serviciosPrincipales = [
            'Consulta Externa',
            'CRED',
            'PCT',
            'TARGA',
            'Lic. Enfermería',
            'Lic. Obstetricia',
            'Hospitalización',
            'Emergencia',
            'Radiología',
            'Laboratorio Clínico',
            'Farmacia',
            'Cirugía General'
        ];

        foreach ($serviciosPrincipales as $nombre) {
            Servicio::firstOrCreate(['nombre' => $nombre]);
        }

        // Obtener el ID de Consulta Externa para crear sub-servicios
        $consultaExterna = Servicio::where('nombre', 'Consulta Externa')->first();

        // Crear sub-servicios de Consulta Externa
        $subServicios = [
            'Pediatría',
            'Medicina General',
            'Ginecología',
            'Cardiología',
            'Neurología',
            'Traumatología',
            'Dermatología',
            'Oftalmología',
            'Otorrinolaringología',
            'Urología',
            'Psicología',
            'Nutrición',
            'Fisioterapia'
        ];

        foreach ($subServicios as $nombre) {
            Servicio::firstOrCreate([
                'nombre' => $nombre,
                'parent_id' => $consultaExterna->id
            ]);
        }

        // Obtener el ID de CRED para crear sub-servicios
        $cred = Servicio::where('nombre', 'CRED')->first();

        // Crear sub-servicios de CRED
        $subServiciosCRED = [
            'Crecimiento y desarrollo'
        ];

        foreach ($subServiciosCRED as $nombre) {
            Servicio::firstOrCreate([
                'nombre' => $nombre,
                'parent_id' => $cred->id
            ]);
        }

        // Obtener el ID de PCT para crear sub-servicios
        $pct = Servicio::where('nombre', 'PCT')->first();

        // Crear sub-servicios de PCT
        $subServiciosPCT = [
            'Estrategia de tuberculosis'
        ];

        foreach ($subServiciosPCT as $nombre) {
            Servicio::firstOrCreate([
                'nombre' => $nombre,
                'parent_id' => $pct->id
            ]);
        }

        // Obtener el ID de TARGA para crear sub-servicios
        $targa = Servicio::where('nombre', 'TARGA')->first();

        // Crear sub-servicios de TARGA
        $subServiciosTARGA = [
            'Infectologia'
        ];

        foreach ($subServiciosTARGA as $nombre) {
            Servicio::firstOrCreate([
                'nombre' => $nombre,
                'parent_id' => $targa->id
            ]);
        }

        $this->command->info('Servicios jerárquicos creados/verificados exitosamente.');
        $this->command->info('- Servicios principales: ' . count($serviciosPrincipales));
        $this->command->info('- Sub-servicios de Consulta Externa: ' . count($subServicios));
        $this->command->info('- Sub-servicios de CRED: ' . count($subServiciosCRED));
        $this->command->info('- Sub-servicios de PCT: ' . count($subServiciosPCT));
        $this->command->info('- Sub-servicios de TARGA: ' . count($subServiciosTARGA));
    }
}
