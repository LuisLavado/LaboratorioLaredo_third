<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

/**
 * Exportación General - Reporte completo con múltiples hojas
 * Este es el reporte principal que incluye todas las secciones organizadas por hojas
 */
class GeneralReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $data;
    protected $startDate;
    protected $endDate;

    /**
     * Constructor
     */
    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    /**
     * Hojas del Excel - Organización completa por secciones
     * TODAS las hojas se incluyen siempre, mostrando "No hay datos" cuando corresponda
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // 1. Hoja de Resumen Ejecutivo (siempre presente)
        $sheets[] = new GeneralSummarySheet($this->data, $this->startDate, $this->endDate);
        
        // 2. Hoja de Pacientes (siempre presente)
        $sheets[] = new PatientsOverviewSheet($this->data['patients'] ?? [], $this->startDate, $this->endDate);
        
        // 3. Hoja de Solicitudes (siempre presente)
        $sheets[] = new RequestsOverviewSheet($this->data['solicitudes'] ?? [], $this->startDate, $this->endDate);
        
        // 4. Hoja de Exámenes (siempre presente)
        $sheets[] = new ExamsOverviewSheet($this->data['examenes'] ?? [], $this->startDate, $this->endDate);
        
        // 5. Hoja de Resultados (siempre presente)
        $sheets[] = new ResultsOverviewSheet($this->data['resultados'] ?? [], $this->startDate, $this->endDate);
        
        // 6. Hoja de Médicos (siempre presente)
        $sheets[] = new DoctorsOverviewSheet($this->data['doctores'] ?? [], $this->startDate, $this->endDate);
        
        // 7. Hoja de Servicios (siempre presente)
        $sheets[] = new ServicesOverviewSheet($this->data['servicios'] ?? [], $this->startDate, $this->endDate);
        
        // 8. Hoja de Estadísticas Avanzadas (siempre presente)
        $sheets[] = new AdvancedStatsSheet($this->data, $this->startDate, $this->endDate);
        
        return $sheets;
    }
}
