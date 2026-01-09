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
use Carbon\Carbon;

/**
 * Hoja de Vista General de Médicos/Doctores
 */
class DoctorsOverviewSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $doctors;
    protected $startDate;
    protected $endDate;

    public function __construct($doctors, $startDate, $endDate)
    {
        $this->doctors = $doctors;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Médicos General';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['VISTA GENERAL DE MÉDICOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'ID Médico',
                'Nombres',
                'Apellidos',
                'Especialidad',
                'CMP',
                'Email',
                'Rol Sistema',
                'Estado',
                'Total Solicitudes',
                'Última Actividad',
                'Fecha Registro'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];

        if (empty($this->doctors)) {
            $rows[] = [
                'No hay médicos disponibles para el período seleccionado.',
                '', '', '', '', '', '', '', '', '', ''
            ];
            return $rows;
        }

        foreach ($this->doctors as $doctor) {
            $rows[] = [
                $doctor->id ?? '',
                $doctor->nombres ?? '',
                $doctor->apellidos ?? '',
                $doctor->especialidad ?? '',
                $doctor->cmp ?? '',
                $doctor->email ?? '',
                $doctor->role_sistema ?? '',
                $doctor->estado ?? 'Activo',
                $doctor->total_solicitudes ?? 0,
                isset($doctor->ultima_actividad) ? Carbon::parse($doctor->ultima_actividad)->format('d/m/Y H:i') : '',
                isset($doctor->created_at) ? Carbon::parse($doctor->created_at)->format('d/m/Y') : ''
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Get the total number of rows (headers + data)
        $totalRows = count($this->doctors) + 5; // 5 header rows
        
        // Apply styles
        $styles = [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ]
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ]
            ],
            // Período
            3 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472c4']
                ]
            ],
            // Encabezados de columnas
            5 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'dae3f3']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '4472c4']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]
        ];

        // Apply styles to data rows
        for ($row = 6; $row <= $totalRows; $row++) {
            $styles[$row] = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '4472c4']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => false
                ]
            ];
            
            // Specific alignment for each column type
            $styles["A{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // ID
            $styles["B{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Nombres
            $styles["C{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Apellidos
            $styles["D{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Especialidad
            $styles["E{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // CMP
            $styles["F{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Email
            $styles["G{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Rol Sistema
            $styles["H{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Estado
            $styles["I{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Total Solicitudes
            $styles["J{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Última Actividad
            $styles["K{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Fecha Registro
        }

        // Merge cells for headers
        $sheet->mergeCells('A1:K1'); // Título principal
        $sheet->mergeCells('A2:K2'); // Subtítulo
        $sheet->mergeCells('A3:K3'); // Período

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // ID Médico
            'B' => 22,  // Nombres
            'C' => 22,  // Apellidos
            'D' => 28,  // Especialidad
            'E' => 18,  // CMP
            'F' => 30,  // Email
            'G' => 18,  // Rol Sistema
            'H' => 15,  // Estado
            'I' => 20,  // Total Solicitudes
            'J' => 22,  // Última Actividad
            'K' => 18   // Fecha Registro
        ];
    }
}
