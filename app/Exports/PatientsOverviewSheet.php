<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Hoja de Vista General de Pacientes
 */
class PatientsOverviewSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $patients;
    protected $startDate;
    protected $endDate;

    public function __construct($patients, $startDate, $endDate)
    {
        $this->patients = $patients;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Pacientes';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - PACIENTES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'DNI',
                'Nombres',
                'Apellidos',
                'Edad',
                'Género',
                'Teléfono',
                'Total Solicitudes',
                'Total Exámenes',
                'Última Visita'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        if (empty($this->patients)) {
            $rows[] = ['No hay pacientes registrados en el período seleccionado', '', '', '', '', '', '', '', ''];
            return $rows;
        }
        
        foreach ($this->patients as $patient) {
            $rows[] = [
                $patient->documento ?? 'No registrado',
                $patient->nombres ?? 'Sin nombre',
                $patient->apellidos ?? 'Sin apellido',
                $patient->edad ?? 'No especificada',
                $this->formatGender($patient->sexo ?? ''),
                $patient->celular ?? 'No registrado',
                $patient->total_solicitudes ?? 0,
                $patient->total_examenes ?? 0,
                isset($patient->ultima_visita) ? date('d/m/Y', strtotime($patient->ultima_visita)) : 'No disponible'
            ];
        }
        
        // Agregar resumen al final
        $rows[] = ['', '', '', '', '', '', '', '', ''];
        $rows[] = ['RESUMEN', '', '', '', '', '', '', '', ''];
        $rows[] = ['Total de pacientes', count($this->patients), '', '', '', '', '', '', ''];
        
        $totalSolicitudes = collect($this->patients)->sum('total_solicitudes');
        $totalExamenes = collect($this->patients)->sum('total_examenes');
        
        $rows[] = ['Total de solicitudes', $totalSolicitudes, '', '', '', '', '', '', ''];
        $rows[] = ['Total de exámenes', $totalExamenes, '', '', '', '', '', '', ''];
        
        if (count($this->patients) > 0) {
            $promedioSolicitudes = round($totalSolicitudes / count($this->patients), 1);
            $promedioExamenes = round($totalExamenes / count($this->patients), 1);
            $rows[] = ['Promedio solicitudes por paciente', $promedioSolicitudes, '', '', '', '', '', '', ''];
            $rows[] = ['Promedio exámenes por paciente', $promedioExamenes, '', '', '', '', '', '', ''];
        }
        
        return $rows;
    }

    private function formatGender($gender)
    {
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['m', 'masculino', 'hombre'])) {
            return 'Masculino';
        } elseif (in_array($gender, ['f', 'femenino', 'mujer'])) {
            return 'Femenino';
        } else {
            return 'No especificado';
        }
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado principal
        $sheet->getStyle('A1:I2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Encabezados de columnas
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // DNI
            'B' => 20, // Nombres
            'C' => 20, // Apellidos
            'D' => 8,  // Edad
            'E' => 12, // Género
            'F' => 15, // Teléfono
            'G' => 12, // Total Solicitudes
            'H' => 12, // Total Exámenes
            'I' => 15  // Última Visita
        ];
    }
}
