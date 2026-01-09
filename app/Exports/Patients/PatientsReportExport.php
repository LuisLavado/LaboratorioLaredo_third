<?php

namespace App\Exports\Patients;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

/**
 * Export Principal de Reportes de Pacientes
 * Combina múltiples hojas con diferentes vistas de los datos de pacientes
 */
class PatientsReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $reportData;
    protected $startDate;
    protected $endDate;

    public function __construct($reportData, $startDate, $endDate)
    {
        $this->reportData = $reportData;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    /**
     * Define todas las hojas del reporte
     */
    public function sheets(): array
    {
        $sheets = [];

        // Extraer los pacientes del array de datos del reporte
        $patients = $this->reportData['patients'] ?? [];

        // 1. Hoja de Resumen Ejecutivo
        $sheets[] = new PatientsExecutiveSummarySheet($this->reportData, $this->startDate, $this->endDate);

        // 2. Hoja de Vista General (Overview)
        $sheets[] = new PatientsOverviewSheet($patients, $this->startDate, $this->endDate);

        // 3. Hoja de Resumen de Resultados por Paciente
        $sheets[] = new PatientsResultsSummarySheet($this->reportData, $this->startDate, $this->endDate);

        // 4. Hojas individuales de resultados por cada paciente
        foreach ($patients as $index => $patient) {
            $sheets[] = new PatientResultsDetailSheet($patient, $this->startDate, $this->endDate, $index + 1);
        }

        return $sheets;
    }
}
