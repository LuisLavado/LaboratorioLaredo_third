<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

/**
 * Exportador principal para reportes de servicios - Sistema Clean
 * 
 * Este exportador genera un archivo Excel con múltiples hojas especializadas
 * para análisis completo de servicios del laboratorio.
 */
class ServicesCleanReportExport implements WithMultipleSheets
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

    /**
     * Definir las hojas del archivo Excel
     */
    public function sheets(): array
    {
        $sheets = [];

        // 1. Hoja de Resumen Ejecutivo
        $sheets[] = new ServicesCleanSummarySheet($this->data, $this->startDate, $this->endDate);

        // 2. Hoja de Lista Detallada de Servicios
        $sheets[] = new ServicesCleanDetailSheet($this->data, $this->startDate, $this->endDate);

        // 3. Hoja de Servicios por Exámenes Incluidos
        $sheets[] = new ServicesCleanByExamsSheet($this->data, $this->startDate, $this->endDate);

        // 4. Hoja de Análisis de Rendimiento
        $sheets[] = new ServicesCleanPerformanceSheet($this->data, $this->startDate, $this->endDate);

        // 5. Hoja de Estados y Progreso
        $sheets[] = new ServicesCleanStatusSheet($this->data, $this->startDate, $this->endDate);

        return $sheets;
    }
}
