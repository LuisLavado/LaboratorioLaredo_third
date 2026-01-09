<?php

namespace App\Exports\Categories;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class CategoriesOverviewSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $categoryStats;
    protected $startDate;
    protected $endDate;

    /**
     * Constructor
     */
    public function __construct($categoryStats, $startDate, $endDate)
    {
        $this->categoryStats = $categoryStats;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Categorías';
    }

    /**
     * Cabeceras
     */
    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['REPORTE DE CATEGORÍAS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['Categoría', 'Cantidad', 'Porcentaje']
        ];
    }

    /**
     * Ancho de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 35,  // Categoría
            'B' => 15,  // Cantidad
            'C' => 15,  // Porcentaje
        ];
    }

    /**
     * Datos de la hoja
     */
    public function array(): array
    {
        $rows = [];

        // Si no hay datos, mostrar mensaje
        if (empty($this->categoryStats)) {
            $rows[] = [
                'No hay datos de categorías disponibles',
                '',
                ''
            ];
            return $rows;
        }

        // Ordenar por cantidad (mayor a menor)
        $sortedCategories = collect($this->categoryStats)->sortByDesc('count');
        $totalExams = $sortedCategories->sum('count');

        // Datos de categorías
        foreach ($sortedCategories as $category) {
            $count = $category->count ?? 0;
            $percentage = $totalExams > 0 ? round(($count / $totalExams) * 100, 1) : 0;

            $rows[] = [
                $category->name ?? $category->nombre ?? 'Sin nombre',
                $count,
                $percentage . '%'
            ];
        }

        return $rows;
    }

    /**
     * Estilos de la hoja
     */
    public function styles(Worksheet $sheet)
    {
        // Get the total number of rows (headers + data)
        $categories = $this->categoryStats ?? [];
        $totalRows = count($categories) + 5; // 5 header rows

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


}
