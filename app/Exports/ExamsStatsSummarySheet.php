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
 * Hoja de resumen estadístico de exámenes
 */
class ExamsStatsSummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Resumen Estadístico';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['RESUMEN ESTADÍSTICO DE EXÁMENES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            ['Generado: ' . Carbon::now()->format('d/m/Y H:i:s')],
            [],
            ['ESTADÍSTICAS GENERALES'],
            []
        ];
    }

    public function array(): array
    {
        $examenes = $this->data['examenes'] ?? [];
        $totales = $this->data['totales'] ?? [];
        
        $rows = [];

        // Estadísticas generales
        $rows[] = ['RESUMEN GENERAL', 'CANTIDAD'];
        $rows[] = ['Total de Exámenes Disponibles', count($examenes)];
        $rows[] = ['Total de Solicitudes', $totales['solicitudes'] ?? 0];
        $rows[] = ['Total de Pacientes', $totales['pacientes'] ?? 0];
        $rows[] = ['Total de Exámenes Realizados', $totales['examenes_realizados'] ?? 0];
        $rows[] = [''];

        // Estadísticas por categoría
        $categorias = [];
        foreach ($examenes as $examen) {
            $categoria = $this->getProperty($examen, 'categoria', 'Sin Categoría');
            if (!isset($categorias[$categoria])) {
                $categorias[$categoria] = [
                    'cantidad' => 0,
                    'realizados' => 0,
                    'pendientes' => 0,
                    'completados' => 0
                ];
            }
            $categorias[$categoria]['cantidad']++;
            $categorias[$categoria]['realizados'] += $this->getProperty($examen, 'total_realizados', 0);
            $categorias[$categoria]['pendientes'] += $this->getProperty($examen, 'pendientes', 0);
            $categorias[$categoria]['completados'] += $this->getProperty($examen, 'completados', 0);
        }

        $rows[] = ['ESTADÍSTICAS POR CATEGORÍA', ''];
        $rows[] = ['Categoría', 'Exámenes', 'Realizados', 'Pendientes', 'Completados'];
        foreach ($categorias as $categoria => $stats) {
            $rows[] = [
                $categoria,
                $stats['cantidad'],
                $stats['realizados'],
                $stats['pendientes'],
                $stats['completados']
            ];
        }
        $rows[] = [''];

        // Top 10 exámenes más solicitados
        $examenesSorted = collect($examenes)->sortByDesc(function($examen) {
            return $this->getProperty($examen, 'total_realizados', 0);
        })->take(10);

        $rows[] = ['TOP 10 EXÁMENES MÁS SOLICITADOS', ''];
        $rows[] = ['Posición', 'Examen', 'Total Realizados', 'Categoría'];
        $position = 1;
        foreach ($examenesSorted as $examen) {
            $rows[] = [
                $position++,
                $this->getProperty($examen, 'nombre', 'Sin nombre'),
                $this->getProperty($examen, 'total_realizados', 0),
                $this->getProperty($examen, 'categoria', 'Sin categoría')
            ];
        }
        $rows[] = [''];

        // Análisis de eficiencia
        $totalExamenes = count($examenes);
        $totalRealizados = array_sum(array_map(function($examen) {
            return $this->getProperty($examen, 'total_realizados', 0);
        }, $examenes));
        $totalPendientes = array_sum(array_map(function($examen) {
            return $this->getProperty($examen, 'pendientes', 0);
        }, $examenes));
        $totalCompletados = array_sum(array_map(function($examen) {
            return $this->getProperty($examen, 'completados', 0);
        }, $examenes));

        $rows[] = ['ANÁLISIS DE EFICIENCIA', ''];
        $rows[] = ['Métrica', 'Valor', 'Porcentaje'];
        $rows[] = ['Exámenes Completados', $totalCompletados, $totalRealizados > 0 ? round(($totalCompletados / $totalRealizados) * 100, 2) . '%' : '0%'];
        $rows[] = ['Exámenes Pendientes', $totalPendientes, $totalRealizados > 0 ? round(($totalPendientes / $totalRealizados) * 100, 2) . '%' : '0%'];
        $rows[] = ['Promedio Exámenes por Paciente', $totales['pacientes'] > 0 ? round($totalRealizados / $totales['pacientes'], 2) : 0, ''];

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
            // Generado
            4 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'e7e6e6']
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15
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
