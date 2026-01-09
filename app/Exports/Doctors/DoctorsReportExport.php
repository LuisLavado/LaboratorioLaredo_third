<?php

namespace App\Exports\Doctors;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

/**
 * Exportador principal para reportes de doctores
 * Este exportador maneja múltiples hojas con diferentes estadísticas sobre doctores
 */
class DoctorsReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $data;
    protected $startDate;
    protected $endDate;

    /**
     * Constructor
     *
     * @param array $data Datos del reporte
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     */
    public function __construct(array $data, string $startDate, string $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Define las hojas que compondrán el reporte de doctores
     *
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // Hoja de resumen general
        $sheets[] = new DoctorsSummarySheet($this->data, $this->startDate, $this->endDate);

        // Hoja de visión general (esta clase ya existe)
        $doctorStats = $this->data['doctorStats'] ?? [];
        // Asegurarse de que sea un array y no una Collection
        if ($doctorStats instanceof \Illuminate\Support\Collection) {
            $doctorStats = $doctorStats->toArray();
        }
        $sheets[] = new DoctorsOverviewSheet($doctorStats, $this->startDate, $this->endDate);
        
        // Hoja con detalles de actividad médica
        $sheets[] = new DoctorsDetailSheet($this->data, $this->startDate, $this->endDate);

        // Hoja con análisis de resultados procesados por doctores
        if (isset($this->data['resultStats']) && !empty($this->data['resultStats'])) {
            $resultStats = $this->data['resultStats'];
            // Asegurarse de que sea un array y no una Collection
            if ($resultStats instanceof \Illuminate\Support\Collection) {
                $resultStats = $resultStats->toArray();
            }
            $sheets[] = new DoctorsResultsSheet($resultStats, $this->startDate, $this->endDate);
        }
        
        return $sheets;
    }
}
