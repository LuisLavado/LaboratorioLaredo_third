<?php

namespace App\Exports\Patients;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

/**
 * Exportación Detallada de Resultados por Paciente
 * Genera una hoja por cada paciente con todos sus resultados de exámenes
 */
class PatientsDetailedResultsExport implements WithMultipleSheets
{
    use Exportable;

    protected $reportData;
    protected $patients;
    protected $startDate;
    protected $endDate;

    public function __construct($reportData, $startDate, $endDate)
    {
        $this->reportData = $reportData;
        $this->patients = $reportData['patients'] ?? [];
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    /**
     * Crear una hoja por cada paciente
     */
    public function sheets(): array
    {
        $sheets = [];

        // Crear hoja de resumen general primero
        $sheets[] = new PatientsResultsSummarySheet($this->reportData, $this->startDate, $this->endDate);

        // Crear una hoja por cada paciente
        foreach ($this->patients as $index => $patient) {
            $patientName = $this->getPatientName($patient);
            $sheets[] = new PatientResultsDetailSheet($patient, $this->startDate, $this->endDate, $index + 1);
        }

        return $sheets;
    }

    /**
     * Obtener nombre del paciente de forma segura
     */
    private function getPatientName($patient)
    {
        if (is_array($patient)) {
            $name = trim(($patient['nombres'] ?? '') . ' ' . ($patient['apellidos'] ?? ''));
            return !empty($name) ? $name : 'Paciente ' . ($patient['id'] ?? 'Sin ID');
        } else {
            $name = trim(($patient->nombres ?? '') . ' ' . ($patient->apellidos ?? ''));
            return !empty($name) ? $name : 'Paciente ' . ($patient->id ?? 'Sin ID');
        }
    }
}
