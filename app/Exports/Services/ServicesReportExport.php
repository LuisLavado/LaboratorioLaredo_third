<?php

namespace App\Exports\Services;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;

/**
 * Exportación completa de reporte de servicios
 * Incluye múltiples hojas con diferentes vistas de los datos de servicios
 */
class ServicesReportExport implements WithMultipleSheets, WithTitle
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Reporte de Servicios';
    }

    /**
     * Generar múltiples hojas para el reporte de servicios
     */
    public function sheets(): array
    {
        $sheets = [];

        // Hoja 1: Resumen Ejecutivo de Servicios
        $sheets[] = new ServicesOverviewSheet($this->data['servicios'] ?? [], $this->startDate, $this->endDate);

        // Hoja 3: Servicios por Estado
        $sheets[] = new ServicesByStatusSheet($this->data, $this->startDate, $this->endDate);


        // Puedes agregar más hojas especializadas aquí según necesidades futuras

        return $sheets;
    }
}
