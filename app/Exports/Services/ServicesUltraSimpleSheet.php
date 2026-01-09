<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ServicesUltraSimpleSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $servicios = $this->data['servicios'] ?? collect();
        
        \Log::info('ServicesUltraSimpleSheet - collection() llamado');
        
        // Crear datos ultra simples
        $result = collect([
            ['Resumen de Servicios'],
            ['Total servicios:', $servicios->count()],
            [''],
            ['Listado de servicios:']
        ]);
        
        // Agregar servicios uno por uno de forma simple
        foreach ($servicios as $servicio) {
            $result->push([
                $servicio->nombre ?? 'Sin nombre',
                $servicio->total_solicitudes ?? 0
            ]);
        }
        
        \Log::info('ServicesUltraSimpleSheet - resultado con ' . $result->count() . ' filas');
        
        return $result;
    }

    public function headings(): array
    {
        return ['Concepto', 'Valor'];
    }

    public function title(): string
    {
        return 'Resumen Servicios';
    }
}
