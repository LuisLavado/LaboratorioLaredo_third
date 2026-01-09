<?php

namespace App\Exports\Services;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Exportador Principal para Reportes de Servicios
 * Sistema completamente independiente y modular
 */
class ServicesMainReportExport implements WithMultipleSheets, WithTitle
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
     * Retorna un array de hojas especializadas para servicios
     */
    public function sheets(): array
    {
        return [
            new ServicesSummarySheet($this->data, $this->startDate, $this->endDate),
            new ServicesDetailSheet($this->data, $this->startDate, $this->endDate),
            new ServicesExamsAnalysisSheet($this->data, $this->startDate, $this->endDate),
            new ServicesPerformanceSheet($this->data, $this->startDate, $this->endDate),
            new ServicesStatusSheet($this->data, $this->startDate, $this->endDate),
        ];
    }
}
