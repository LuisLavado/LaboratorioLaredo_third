<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ServicesSimpleReportExport implements WithMultipleSheets
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        // Debug: Log detallado de la estructura de datos
        \Log::info('ServicesSimpleReportExport - Datos recibidos:', [
            'data_keys' => array_keys($data),
            'servicios_type' => gettype($data['servicios'] ?? null),
            'servicios_class' => is_object($data['servicios'] ?? null) ? get_class($data['servicios']) : 'No es objeto',
            'servicios_count' => is_countable($data['servicios'] ?? null) ? count($data['servicios']) : 'No countable',
            'primer_servicio' => isset($data['servicios'][0]) ? json_encode($data['servicios'][0]) : 'No hay primer servicio'
        ]);
    }

    /**
     * Definir las hojas del archivo Excel (versión simplificada)
     */
    public function sheets(): array
    {
        $sheets = [];

        // Solo 1 hoja ultra simple para diagnosticar
        $sheets[] = new ServicesUltraSimpleSheet($this->data, $this->startDate, $this->endDate);

        return $sheets;
    }
}
