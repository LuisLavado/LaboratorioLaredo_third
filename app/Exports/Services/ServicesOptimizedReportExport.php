<?php

namespace App\Exports\Services;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Exportador Optimizado para Reportes de Servicios
 * Versión mejorada con hojas eficientes y sin problemas de memoria
 */
class ServicesOptimizedReportExport implements WithMultipleSheets, WithTitle
{
    use Exportable;

    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Reporte de Servicios';
    }

    /**
     * Retorna un array de hojas optimizadas para el reporte
     */
    public function sheets(): array
    {
        return [
            new ServicesImprovedSummarySheet($this->data, $this->startDate, $this->endDate),
            new ServicesImprovedDetailSheet($this->data, $this->startDate, $this->endDate),
            new ServicesImprovedByExamsSheet($this->data, $this->startDate, $this->endDate),
            new ServicesImprovedPerformanceSheet($this->data, $this->startDate, $this->endDate),
            new ServicesImprovedStatusSheet($this->data, $this->startDate, $this->endDate),
        ];
    }
}
