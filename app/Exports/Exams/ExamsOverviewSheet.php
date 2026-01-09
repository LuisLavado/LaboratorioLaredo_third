<?php

namespace App\Exports\Exams;

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
 * Hoja de Vista General de Exámenes
 */
class ExamsOverviewSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $exams;
    protected $startDate;
    protected $endDate;

    public function __construct($exams, $startDate, $endDate)
    {
        $this->exams = $exams;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Exámenes General';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['VISTA GENERAL DE EXÁMENES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'Nombre del Examen',
                'Categoría',
                'Estado',
                'Solicitudes Realizadas',
                'Fecha Creación',
                'Última Actualización'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];

        if (empty($this->exams)) {
            $rows[] = [
                'No hay exámenes disponibles para el período seleccionado.',
                '', '', '', '', ''
            ];
            return $rows;
        }

        foreach ($this->exams as $exam) {
            $rows[] = [
                $exam->nombre ?? '',
                $exam->categoria ?? '',
                $exam->estado ?? 'Activo',
                $exam->total_realizados ?? 0,
                isset($exam->created_at) ? Carbon::parse($exam->created_at)->format('d/m/Y') : '',
                isset($exam->updated_at) ? Carbon::parse($exam->updated_at)->format('d/m/Y') : ''
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Get the total number of rows (headers + data)
        $totalRows = count($this->exams) + 5; // 5 header rows

        $sheet->mergeCells('A1:F1'); // Título principal
        $sheet->mergeCells('A2:F2'); // Subtítulo
        $sheet->mergeCells('A3:F3'); // Período

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
            $styles["A{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Nombre
            $styles["B{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Categoría
            $styles["C{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Estado
            $styles["D{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Solicitudes
            $styles["E{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Fecha Creación
            $styles["F{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Última Actualización
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,  // Nombre del Examen
            'B' => 20,  // Categoría
            'C' => 15,  // Estado
            'D' => 20,  // Solicitudes Realizadas
            'E' => 18,  // Fecha Creación
            'F' => 22   // Última Actualización
        ];
    }
}
