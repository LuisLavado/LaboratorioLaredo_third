<?php

namespace App\Exports\Patients;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

/**
 * Hoja de Resumen de Pacientes con Resultados
 */
class PatientsResultsSummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
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

    public function title(): string
    {
        return 'Resumen General';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['RESUMEN DE PACIENTES CON RESULTADOS DETALLADOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            ['Generado: ' . Carbon::now()->format('d/m/Y H:i:s')],
            [],
            ['ESTADÍSTICAS GENERALES'],
            ['Total de Pacientes', $this->reportData['totalPatients'] ?? 0],
            ['Total de Solicitudes', $this->reportData['totalRequests'] ?? 0],
            ['Total de Exámenes', $this->reportData['totalExams'] ?? 0],
            ['Exámenes Completados', $this->reportData['completedCount'] ?? 0],
            ['Exámenes Pendientes', $this->reportData['pendingCount'] ?? 0],
            [],
            ['LISTA DE PACIENTES'],
            ['#', 'Nombre Completo', 'Documento', 'Edad', 'Sexo', 'Total Solicitudes', 'Total Exámenes', 'Última Visita', 'Hoja de Detalle']
        ];
    }

    public function array(): array
    {
        $data = [];
        
        foreach ($this->patients as $index => $patient) {
            $patientName = $this->getPatientName($patient);
            $documento = $this->getPatientField($patient, ['documento', 'dni']);
            $edad = $this->getPatientField($patient, ['edad']);
            $sexo = $this->getPatientField($patient, ['sexo']);
            $totalSolicitudes = $this->getPatientField($patient, ['total_solicitudes']);
            $totalExamenes = $this->getPatientField($patient, ['total_examenes']);
            $ultimaVisita = $this->getPatientField($patient, ['ultima_visita']);
            
            $data[] = [
                $index + 1,
                $patientName,
                $documento ?: 'Sin documento',
                $edad ?: 'N/A',
                $sexo ?: 'N/A',
                $totalSolicitudes ?: 0,
                $totalExamenes ?: 0,
                $ultimaVisita ? Carbon::parse($ultimaVisita)->format('d/m/Y') : 'Sin visitas',
                'Ver hoja: ' . $this->sanitizeSheetName($patientName)
            ];
        }

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,   // #
            'B' => 35,  // Nombre
            'C' => 15,  // Documento
            'D' => 8,   // Edad
            'E' => 10,  // Sexo
            'F' => 12,  // Solicitudes
            'G' => 15,  // Exámenes
            'H' => 15,  // Última Visita
            'I' => 35,  // Hoja de Detalle
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1f4e79']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Período
            3 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Fecha generación
            4 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Título estadísticas
            6 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'ffffff']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2f5f8f']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Título lista
            13 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'ffffff']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2f5f8f']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Headers de tabla
            14 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1f4e79']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'dae3f3']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '4472c4']],
                ],
            ],
        ];
    }

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

    private function getPatientField($patient, $fields)
    {
        foreach ($fields as $field) {
            if (is_array($patient)) {
                if (isset($patient[$field]) && !empty($patient[$field])) {
                    return $patient[$field];
                }
            } else {
                if (isset($patient->$field) && !empty($patient->$field)) {
                    return $patient->$field;
                }
            }
        }
        return null;
    }

    private function sanitizeSheetName($name)
    {
        // Limpiar caracteres no válidos para nombres de hojas de Excel
        $name = preg_replace('/[\\\\\/\?\*\[\]:]+/', '', $name);
        return substr($name, 0, 31); // Máximo 31 caracteres
    }
}
