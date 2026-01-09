<?php

namespace App\Exports\Results;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

// Imports para las hojas específicas de resultados
use App\Exports\Results\ResultsOverviewSheet;
use App\Exports\Results\ResultsDetailSheet;
use App\Exports\Results\ResultsDetailsSheet;
use App\Exports\Results\ResultsStatsSheet;
use App\Exports\Results\ResultsAnalysisSheet;
use App\Exports\Results\ResultsStatusSheet;

/**
 * Exportación de Resultados - Reporte completo con múltiples hojas
 * Este es el reporte especializado de resultados que incluye todas las secciones organizadas por hojas
 */
class ResultsReportExport implements WithMultipleSheets
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
     * Hojas del Excel - Organización completa por secciones de resultados
     * TODAS las hojas se incluyen siempre, mostrando "No hay datos" cuando corresponda
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // Convertir Collections a arrays donde sea necesario
        $resultados = isset($this->data['resultados']) ? 
            (is_object($this->data['resultados']) && method_exists($this->data['resultados'], 'toArray') ? 
                $this->data['resultados']->toArray() : 
                $this->data['resultados']) : [];
                
        // Convertir detailed_results a array si es una Collection
        $detailedResults = isset($this->data['detailed_results']) ? 
            (is_object($this->data['detailed_results']) && method_exists($this->data['detailed_results'], 'toArray') ? 
                $this->data['detailed_results']->toArray() : 
                $this->data['detailed_results']) : [];
                
        // Convertir dailyStats a array si es una Collection
        $dailyStats = isset($this->data['dailyStats']) ? 
            (is_object($this->data['dailyStats']) && method_exists($this->data['dailyStats'], 'toArray') ? 
                $this->data['dailyStats']->toArray() : 
                $this->data['dailyStats']) : [];
        
        // Convertir examStats a array si es una Collection
        $examStats = isset($this->data['examStats']) ? 
            (is_object($this->data['examStats']) && method_exists($this->data['examStats'], 'toArray') ? 
                $this->data['examStats']->toArray() : 
                $this->data['examStats']) : [];
        
        // Convertir categoryStats a array si es una Collection
        $categoryStats = isset($this->data['categoryStats']) ? 
            (is_object($this->data['categoryStats']) && method_exists($this->data['categoryStats'], 'toArray') ? 
                $this->data['categoryStats']->toArray() : 
                $this->data['categoryStats']) : [];
        
        // Preparar datos procesados para las hojas
        $processedData = array_merge($this->data, [
            'dailyStats' => $dailyStats,
            'examStats' => $examStats,
            'categoryStats' => $categoryStats,
            'detailed_results' => $detailedResults
        ]);
        
        $sheets[] = new ResultsOverviewSheet($detailedResults, $this->startDate, $this->endDate);

     

        $sheets[] = new ResultsDetailsSheet($detailedResults, $this->startDate, $this->endDate);


        return $sheets;
    }
}
