<?php

namespace App\Exports\Patients;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class PatientsDetailSheet implements WithMultipleSheets
{
    protected $patients;
    protected $startDate;
    protected $endDate;

    public function __construct($patients, $startDate, $endDate)
    {
        $this->patients = $patients;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function sheets(): array
    {
        $sheets = [];


        // Luego una hoja individual para cada paciente
        foreach ($this->patients as $patient) {
            $sheets[] = new SinglePatientSheet($patient, $this->startDate, $this->endDate);
        }

        return $sheets;
    }
}
