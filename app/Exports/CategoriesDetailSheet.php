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
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class CategoriesDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $categoryStats;
    protected $topExamsByCategory;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->categoryStats = $data['categoryStats'];
        $this->topExamsByCategory = $data['topExamsByCategory'];
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Detalle por Categorías';
    }

    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE POR CATEGORÍAS'],
            ['Periodo: ' . $dateRange],
            [],
            ['ESTADÍSTICAS POR CATEGORÍA'],
            ['Categoría', 'Total Exámenes', 'Porcentaje', 'Completados', 'En Proceso', 'Pendientes', 'Tendencia'],
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        foreach ($this->categoryStats as $stat) {
            // Agregar la información principal de la categoría
            $rows[] = [
                $stat->name,
                $stat->count,
                $stat->percentage . '%',
                $stat->completados ?? 0,
                $stat->en_proceso ?? 0,
                $stat->pendientes ?? 0,
                $this->calcularTendencia($stat)
            ];

            // Agregar espacio
            $rows[] = ['', '', '', '', '', '', ''];

            // Agregar los exámenes más solicitados de esta categoría
            if (isset($this->topExamsByCategory[$stat->id]) && !empty($this->topExamsByCategory[$stat->id])) {
                $rows[] = ['EXÁMENES MÁS SOLICITADOS EN ' . strtoupper($stat->name), '', '', '', '', '', ''];
                $rows[] = ['Código', 'Nombre', 'Total', 'Porcentaje', 'Completados', 'En Proceso', 'Pendientes'];

                foreach ($this->topExamsByCategory[$stat->id] as $exam) {
                    $porcentaje = $stat->count > 0 ? round(($exam->count / $stat->count) * 100, 2) : 0;
                    $rows[] = [
                        $exam->code,
                        $exam->name,
                        $exam->count,
                        $porcentaje . '%',
                        $exam->completados ?? 0,
                        $exam->en_proceso ?? 0,
                        $exam->pendientes ?? 0
                    ];
                }

                // Agregar métricas adicionales si están disponibles
                if (isset($exam->porcentaje_fuera_rango)) {
                    $rows[] = ['', '', '', '', '', '', ''];
                    $rows[] = ['Porcentaje de resultados fuera de rango:', $exam->porcentaje_fuera_rango . '%', '', '', '', '', ''];
                }
            }

            // Agregar espacio entre categorías
            $rows[] = ['', '', '', '', '', '', ''];
            $rows[] = ['', '', '', '', '', '', ''];
        }

        return $rows;
    }

    private function calcularTendencia($stat): string
    {
        // Aquí podrías implementar la lógica para calcular la tendencia
        // Por ejemplo, comparando con períodos anteriores
        // Por ahora retornamos un valor de ejemplo
        if (isset($stat->tendencia)) {
            if ($stat->tendencia > 0) {
                return '↑ Incremento';
            } elseif ($stat->tendencia < 0) {
                return '↓ Descenso';
            }
        }
        return '→ Estable';
    }

    public function styles(Worksheet $sheet)
    {
        // Título principal
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A1:G1');

        // Periodo
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A2:G2');

        // Título de estadísticas
        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDEBF7']
            ]
        ]);

        // Encabezados de la tabla
        $sheet->getStyle('A5:G5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Procesar cada fila para aplicar estilos específicos
        $row = 6;
        foreach ($this->array() as $rowData) {
            // Si es un encabezado de "EXÁMENES MÁS SOLICITADOS"
            if (!empty($rowData[0]) && $rowData[0] === 'EXÁMENES MÁS SOLICITADOS:') {
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DDEBF7']
                    ]
                ]);
            }
            // Si es un encabezado de detalle de exámenes
            elseif (!empty($rowData[0]) && $rowData[0] === 'Código') {
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BDD7EE']
                    ]
                ]);
            }
            // Si es una fila de datos normal (no vacía)
            elseif (!empty($rowData[0])) {
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // Centrar porcentajes y números
                $sheet->getStyle("B{$row}:F{$row}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                // Colorear tendencias
                if (str_contains($rowData[6] ?? '', '↑')) {
                    $sheet->getStyle("G{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '008000']] // Verde
                    ]);
                } elseif (str_contains($rowData[6] ?? '', '↓')) {
                    $sheet->getStyle("G{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => 'FF0000']] // Rojo
                    ]);
                }
            }
            $row++;
        }

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Categoría
            'B' => 15, // Total Exámenes
            'C' => 12, // Porcentaje
            'D' => 12, // Completados
            'E' => 12, // En Proceso
            'F' => 12, // Pendientes
            'G' => 15, // Tendencia
        ];
    }
}
