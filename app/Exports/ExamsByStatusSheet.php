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
            ['RESUMEN POR ESTADOS'],
            []
        ];
    }

    public function array(): array
    {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];

        if (empty($examenes)) {
            $rows[] = [
                'No hay exámenes disponibles para el período seleccionado.',
                '', '', '', ''
            ];
            return $rows;
        }

        // Calcular totales por estado
        $totalPendientes = 0;
        $totalEnProceso = 0;
        $totalCompletados = 0;
        $totalRealizados = 0;

        foreach ($examenes as $examen) {
            $pendientes = $this->getProperty($examen, 'pendientes', 0);
            $enProceso = $this->getProperty($examen, 'en_proceso', 0);
            $completados = $this->getProperty($examen, 'completados', 0);
            
            $totalPendientes += $pendientes;
            $totalEnProceso += $enProceso;
            $totalCompletados += $completados;
            $totalRealizados += $this->getProperty($examen, 'total_realizados', 0);
        }

        // Resumen general
        $rows[] = ['Estado', 'Cantidad', 'Porcentaje', 'Descripción'];
        $rows[] = [
            'Pendientes',
            $totalPendientes,
            $totalRealizados > 0 ? round(($totalPendientes / $totalRealizados) * 100, 2) . '%' : '0%',
            'Exámenes solicitados pero no iniciados'
        ];
        $rows[] = [
            'En Proceso',
            $totalEnProceso,
            $totalRealizados > 0 ? round(($totalEnProceso / $totalRealizados) * 100, 2) . '%' : '0%',
            'Exámenes en proceso de realización'
        ];
        $rows[] = [
            'Completados',
            $totalCompletados,
            $totalRealizados > 0 ? round(($totalCompletados / $totalRealizados) * 100, 2) . '%' : '0%',
            'Exámenes finalizados con resultados'
        ];
        $rows[] = ['TOTAL', $totalRealizados, '100%', 'Total de exámenes procesados'];
        
        $rows[] = [''];
        $rows[] = ['DETALLE POR EXAMEN Y ESTADO'];
        $rows[] = [''];
        $rows[] = ['Examen', 'Categoría', 'Pendientes', 'En Proceso', 'Completados', 'Total', 'Eficiencia'];

        // Detalle por examen
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

        $rows[] = [''];
        $rows[] = ['ANÁLISIS DE EFICIENCIA POR CATEGORÍA'];
        $rows[] = [''];

        // Análisis por categoría
        $categorias = [];
        foreach ($examenes as $examen) {
            $categoria = $this->getProperty($examen, 'categoria', 'Sin Categoría');
            if (!isset($categorias[$categoria])) {
                $categorias[$categoria] = [
                    'pendientes' => 0,
                    'en_proceso' => 0,
                    'completados' => 0,
                    'total' => 0
                ];
            }
            
            $categorias[$categoria]['pendientes'] += $this->getProperty($examen, 'pendientes', 0);
            $categorias[$categoria]['en_proceso'] += $this->getProperty($examen, 'en_proceso', 0);
            $categorias[$categoria]['completados'] += $this->getProperty($examen, 'completados', 0);
            $categorias[$categoria]['total'] += $this->getProperty($examen, 'total_realizados', 0);
        }

        $rows[] = ['Categoría', 'Pendientes', 'En Proceso', 'Completados', 'Total', 'Eficiencia', 'Estado'];
        
        foreach ($categorias as $nombreCategoria => $stats) {
            $eficiencia = $stats['total'] > 0 ? round(($stats['completados'] / $stats['total']) * 100, 2) : 0;
            
            // Determinar estado de la categoría
            $estado = 'Normal';
            if ($eficiencia >= 90) {
                $estado = 'Excelente';
            } elseif ($eficiencia >= 75) {
                $estado = 'Bueno';
            } elseif ($eficiencia >= 50) {
                $estado = 'Regular';
            } else {
                $estado = 'Necesita Atención';
            }
            
            $rows[] = [
                $nombreCategoria,
                $stats['pendientes'],
                $stats['en_proceso'],
                $stats['completados'],
                $stats['total'],
                $eficiencia . '%',
                $estado
            ];
        }

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
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,  // Examen/Categoría
            'B' => 18,  // Categoría/Pendientes
            'C' => 15,  // Pendientes/En Proceso
            'D' => 15,  // En Proceso/Completados
            'E' => 15,  // Completados/Total
            'F' => 15,  // Total/Eficiencia
            'G' => 18   // Eficiencia/Estado
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
