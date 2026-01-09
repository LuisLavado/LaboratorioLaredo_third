<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PatientsSummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Resumen Pacientes';
    }

    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - RESUMEN DE PACIENTES'],
            ['Periodo: ' . $dateRange],
            [],
            ['ESTADÍSTICAS GENERALES'],
            [
                'Total Pacientes Atendidos:',
                count($this->patients),
                '',
                'Total Solicitudes:',
                collect($this->patients)->sum('total_solicitudes')
            ],
            [
                'Total Exámenes Realizados:',
                collect($this->patients)->sum('total_examenes'),
                '',
                'Promedio Exámenes por Paciente:',
                round(collect($this->patients)->avg('total_examenes'), 2)
            ],
            [],
            ['LISTADO DE PACIENTES'],
            ['DNI', 'Nombres', 'Apellidos', 'Historia Clínica', 'Sexo', 'Edad', 'Total Solicitudes', 'Total Exámenes', 'Estado General']
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        foreach ($this->patients as $patient) {
            // Calcular el estado general del paciente basado en sus solicitudes
            $estadoGeneral = $this->calcularEstadoGeneral($patient);
            
            $rows[] = [
                $patient->documento ?? 'N/A',
                $patient->nombres ?? 'N/A',
                $patient->apellidos ?? 'N/A',
                $patient->historia_clinica ?? 'N/A',
                ucfirst($patient->sexo ?? 'N/A'),
                $patient->edad ?? 'N/A',
                $patient->total_solicitudes ?? 0,
                $patient->total_examenes ?? 0,
                $estadoGeneral
            ];
        }
        
        return $rows;
    }

    private function calcularEstadoGeneral($patient): string
    {
        if (!isset($patient->solicitudes_detalle) || count($patient->solicitudes_detalle) === 0) {
            return 'Sin atenciones';
        }

        $totalSolicitudes = count($patient->solicitudes_detalle);
        $completadas = 0;
        $enProceso = 0;
        $pendientes = 0;

        foreach ($patient->solicitudes_detalle as $solicitud) {
            switch ($solicitud->estado) {
                case 'completado':
                    $completadas++;
                    break;
                case 'en_proceso':
                    $enProceso++;
                    break;
                case 'pendiente':
                    $pendientes++;
                    break;
            }
        }

        if ($completadas === $totalSolicitudes) {
            return 'Completado';
        } elseif ($pendientes === $totalSolicitudes) {
            return 'Pendiente';
        } elseif ($enProceso > 0) {
            return 'En Proceso';
        } else {
            return 'Mixto';
        }
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el título principal
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A1:I1');

        // Estilo para el rango de fechas
        $sheet->getStyle('A2:I2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A2:I2');

        // Estilo para el título de estadísticas
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDEBF7']
            ]
        ]);
        $sheet->mergeCells('A4:I4');

        // Estilo para las estadísticas
        foreach (['A5:B5', 'D5:E5', 'A6:B6', 'D6:E6'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2']
                ]
            ]);
        }

        // Estilo para el título de listado
        $sheet->getStyle('A8:I8')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDEBF7']
            ]
        ]);
        $sheet->mergeCells('A8:I8');

        // Estilo para los encabezados de la tabla
        $sheet->getStyle('A9:I9')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Estilo para los datos
        $lastRow = count($this->array()) + 9;
        if ($lastRow > 9) {
            $sheet->getStyle('A10:I' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);

            // Aplicar colores según el estado
            for ($i = 10; $i <= $lastRow; $i++) {
                $estado = $sheet->getCell('I' . $i)->getValue();
                $color = match ($estado) {
                    'Completado' => 'C6EFCE',  // Verde claro
                    'En Proceso' => 'FFEB9C',  // Amarillo claro
                    'Pendiente' => 'FFC7CE',   // Rojo claro
                    'Mixto' => 'E1E1E1',      // Gris claro
                    default => 'FFFFFF'        // Blanco
                };

                $sheet->getStyle('I' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $color]
                    ]
                ]);
            }
        }

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // DNI
            'B' => 20, // Nombres
            'C' => 20, // Apellidos
            'D' => 15, // Historia Clínica
            'E' => 10, // Sexo
            'F' => 8,  // Edad
            'G' => 15, // Total Solicitudes
            'H' => 15, // Total Exámenes
            'I' => 15, // Estado General
        ];
    }
}
