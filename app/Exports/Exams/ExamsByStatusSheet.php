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
 * Hoja de análisis por estado de exámenes
 */
class ExamsByStatusSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Análisis por Estado';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['ANÁLISIS DE EXÁMENES POR ESTADO'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['Examen', 'Categoría', 'Pendientes', 'En Proceso', 'Completados', 'Total', 'Eficiencia']
        ];
    }

    public function array(): array
    {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];

        if (empty($examenes)) {
            $rows[] = [
                'No hay exámenes disponibles para el período seleccionado.',
                '', '', '', '', '', ''
            ];
            return $rows;
        }

        // Solo mostrar el detalle por examen ordenado por total de exámenes
        $examenesSorted = collect($examenes)->sortByDesc(function($examen) {
            return $this->getProperty($examen, 'total_realizados', 0);
        });

        foreach ($examenesSorted as $examen) {
            $pendientes = $this->getProperty($examen, 'pendientes', 0);
            $enProceso = $this->getProperty($examen, 'en_proceso', 0);
            $completados = $this->getProperty($examen, 'completados', 0);
            $total = $this->getProperty($examen, 'total_realizados', 0);

            $eficiencia = $total > 0 ? round(($completados / $total) * 100, 2) : 0;

            $rows[] = [
                $this->getProperty($examen, 'nombre', ''),
                $this->getProperty($examen, 'categoria', 'Sin Categoría'),
                $pendientes,
                $enProceso,
                $completados,
                $total,
                $eficiencia . '%'
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Get the total number of rows (headers + data)
        $examenes = $this->data['examenes'] ?? [];
        $totalRows = count($examenes) + 5; // 5 header rows

        $sheet->mergeCells('A1:G1'); // Título principal
        $sheet->mergeCells('A2:G2'); // Subtítulo
        $sheet->mergeCells('A3:G3'); // Período

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
            $styles["A{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Examen
            $styles["B{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Categoría
            $styles["C{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Pendientes
            $styles["D{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // En Proceso
            $styles["E{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Completados
            $styles["F{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Total
            $styles["G{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Eficiencia
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,  // Examen
            'B' => 20,  // Categoría
            'C' => 15,  // Pendientes
            'D' => 15,  // En Proceso
            'E' => 15,  // Completados
            'F' => 12,  // Total
            'G' => 15   // Eficiencia
        ];
    }

    private function getProperty($item, $property, $default = null)
    {
        if (is_array($item)) {
            return $item[$property] ?? $default;
        } elseif (is_object($item)) {
            return $item->$property ?? $default;
        }
        return $default;
    }
}
