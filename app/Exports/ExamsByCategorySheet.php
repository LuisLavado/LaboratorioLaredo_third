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
            ['EXÁMENES AGRUPADOS POR CATEGORÍAS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'Categoría',
                'Cantidad Exámenes',
                'Total Realizados',
                'Pendientes',
                'En Proceso',
                'Completados',
                '% del Total',
                'Promedio por Examen'
            ]
        ];
    }

    public function array(): array
    {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];

        if (empty($examenes)) {
            $rows[] = [
                'No hay exámenes disponibles para el período seleccionado.',
                '', '', '', '', '', '', ''
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
                    'total_realizados' => 0,
                    'pendientes' => 0,
                    'en_proceso' => 0,
                    'completados' => 0
                ];
            }
            
            $categorias[$categoria]['cantidad']++;
            $categorias[$categoria]['total_realizados'] += $totalRealizados;
            $categorias[$categoria]['pendientes'] += $this->getProperty($examen, 'pendientes', 0);
            $categorias[$categoria]['en_proceso'] += $this->getProperty($examen, 'en_proceso', 0);
            $categorias[$categoria]['completados'] += $this->getProperty($examen, 'completados', 0);
            
            $totalGeneral += $totalRealizados;
        }

        // Ordenar por total realizados descendente
        uasort($categorias, function($a, $b) {
            return $b['total_realizados'] <=> $a['total_realizados'];
        });

        // Generar filas de datos
        foreach ($categorias as $nombreCategoria => $stats) {
            $porcentaje = $totalGeneral > 0 ? round(($stats['total_realizados'] / $totalGeneral) * 100, 2) : 0;
            $promedio = $stats['cantidad'] > 0 ? round($stats['total_realizados'] / $stats['cantidad'], 2) : 0;
            
            $rows[] = [
                $nombreCategoria,
                $stats['cantidad'],
                $stats['total_realizados'],
                $stats['pendientes'],
                $stats['en_proceso'],
                $stats['completados'],
                $porcentaje . '%',
                $promedio
            ];
        }

        // Agregar fila de totales
        $rows[] = [''];
        $rows[] = [
            'TOTALES',
            count($examenes),
            $totalGeneral,
            array_sum(array_column($categorias, 'pendientes')),
            array_sum(array_column($categorias, 'en_proceso')),
            array_sum(array_column($categorias, 'completados')),
            '100%',
            count($examenes) > 0 ? round($totalGeneral / count($examenes), 2) : 0
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
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
                'font' => ['bold' => true],
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
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Categoría
            'B' => 18,  // Cantidad Exámenes
            'C' => 18,  // Total Realizados
            'D' => 15,  // Pendientes
            'E' => 15,  // En Proceso
            'F' => 15,  // Completados
            'G' => 15,  // % del Total
            'H' => 20   // Promedio por Examen
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
