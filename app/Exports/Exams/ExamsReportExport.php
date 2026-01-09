<?php

namespace App\Exports\Exams;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;

/**
 * Exportación completa de reporte de exámenes
 * Incluye múltiples hojas con diferentes vistas de los datos
 */
class ExamsReportExport implements WithMultipleSheets, WithTitle
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
        return 'Reporte de Exámenes';
    }

    /**
     * Generar múltiples hojas para el reporte de exámenes
     */
    public function sheets(): array
    {
        $sheets = [];

        // Hoja 1: Resumen Ejecutivo de Exámenes
               $sheets[] = new ExamsOverviewSheet($this->data['examenes'] ?? [], $this->startDate, $this->endDate);

        // Hoja 2: Exámenes por Categoría
        $sheets[] = new ExamsByCategorySheet($this->data, $this->startDate, $this->endDate);

        // Hoja 3: Exámenes por Estado
        $sheets[] = new ExamsByStatusSheet($this->data, $this->startDate, $this->endDate);


        return $sheets;
    }
}
