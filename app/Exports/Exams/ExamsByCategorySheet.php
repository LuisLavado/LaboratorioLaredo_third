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
 * Hoja de exámenes agrupados por categoría
 */
class ExamsByCategorySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Por Categorías';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['EXÁMENES POR CATEGORÍAS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['Categoría', 'Cantidad', 'Porcentaje']
        ];
    }

    public function array(): array
    {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];

        if (empty($examenes)) {
            $rows[] = [
                'No hay exámenes disponibles para el período seleccionado.',
                '', ''
            ];
            return $rows;
        }

        // Agrupar exámenes por categoría
        $categorias = [];
        $totalGeneral = 0;

        foreach ($examenes as $examen) {
            $categoria = $this->getProperty($examen, 'categoria', 'Sin Categoría');
            $totalRealizados = $this->getProperty($examen, 'total_realizados', 0);

            if (!isset($categorias[$categoria])) {
                $categorias[$categoria] = [
                    'cantidad' => 0,
                    'total_realizados' => 0
                ];
            }

            $categorias[$categoria]['cantidad']++;
            $categorias[$categoria]['total_realizados'] += $totalRealizados;
            $totalGeneral += $totalRealizados;
        }

        // Ordenar por total realizados descendente
        uasort($categorias, function($a, $b) {
            return $b['total_realizados'] <=> $a['total_realizados'];
        });

        // Generar filas de datos
        foreach ($categorias as $nombreCategoria => $stats) {
            $porcentaje = $totalGeneral > 0 ? round(($stats['total_realizados'] / $totalGeneral) * 100, 1) : 0;

            $rows[] = [
                $nombreCategoria,
                $stats['cantidad'],
                $porcentaje . '%'
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Get the total number of rows (headers + data)
        $examenes = $this->data['examenes'] ?? [];
        $categorias = [];
        foreach ($examenes as $examen) {
            $categoria = $this->getProperty($examen, 'categoria', 'Sin Categoría');
            $categorias[$categoria] = true;
        }
        $totalRows = count($categorias) + 5; // 5 header rows

        $sheet->mergeCells('A1:C1'); // Título principal
        $sheet->mergeCells('A2:C2'); // Subtítulo
        $sheet->mergeCells('A3:C3'); // Período

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
            $styles["A{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Categoría
            $styles["B{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Cantidad
            $styles["C{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Porcentaje
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,  // Categoría
            'B' => 15,  // Cantidad
            'C' => 15,  // Porcentaje
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
