<?php

namespace App\Exports\Categories;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

// Imports para las hojas específicas
use App\Exports\Categories\CategoriesSummarySheet;
use App\Exports\Categories\CategoriesOverviewSheet;
use App\Exports\Categories\CategoryExamsSheet;

/**
 * Exportación de Categorías - Reporte completo con múltiples hojas
 * Basado en el diseño del reporte general
 */
class CategoriesReportExport implements WithMultipleSheets
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
     * Siguiendo el formato del reporte general
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // 1. Hoja de Resumen Ejecutivo (siempre presente)
        $sheets[] = new CategoriesSummarySheet($this->data, $this->startDate, $this->endDate);
        
        // 2. Hoja con distribución de categorías
        $sheets[] = new CategoriesOverviewSheet(
            $this->data['categoryStats'] ?? [],
            $this->startDate,
            $this->endDate
        );
        
        // 3. Hoja con detalle de exámenes por categoría
        $sheets[] = new CategoryExamsSheet(
            $this->data['topExamsByCategory'] ?? [],
            $this->startDate,
            $this->endDate
        );
        
        return $sheets;
    }
}
