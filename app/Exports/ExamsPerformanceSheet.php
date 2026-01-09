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
 * Hoja de análisis de rendimiento de exámenes
 */
class ExamsPerformanceSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Análisis Rendimiento';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['ANÁLISIS DE RENDIMIENTO DE EXÁMENES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['INDICADORES CLAVE DE RENDIMIENTO (KPIs)'],
            []
        ];
    }

    public function array(): array
    {
        $examenes = $this->data['examenes'] ?? [];
        $totales = $this->data['totales'] ?? [];
        $rows = [];

        if (empty($examenes)) {
            $rows[] = [
                'No hay exámenes disponibles para el período seleccionado.',
                '', '', '', ''
            ];
            return $rows;
        }

        // Calcular KPIs generales
        $totalExamenes = count($examenes);
        $totalRealizados = array_sum(array_map(function($examen) {
            return $this->getProperty($examen, 'total_realizados', 0);
        }, $examenes));
        $totalCompletados = array_sum(array_map(function($examen) {
            return $this->getProperty($examen, 'completados', 0);
        }, $examenes));
        $totalPacientes = $totales['pacientes'] ?? 0;

        // KPIs principales
        $rows[] = ['KPI', 'Valor', 'Interpretación'];
        $rows[] = [
            'Eficiencia General',
            $totalRealizados > 0 ? round(($totalCompletados / $totalRealizados) * 100, 2) . '%' : '0%',
            'Porcentaje de exámenes completados exitosamente'
        ];
        $rows[] = [
            'Productividad por Examen',
            $totalExamenes > 0 ? round($totalRealizados / $totalExamenes, 2) : 0,
            'Promedio de realizaciones por tipo de examen'
        ];
        $rows[] = [
            'Cobertura de Pacientes',
            $totalPacientes > 0 ? round($totalRealizados / $totalPacientes, 2) : 0,
            'Promedio de exámenes por paciente'
        ];
        $rows[] = [
            'Diversidad de Servicios',
            $totalExamenes,
            'Cantidad de tipos de exámenes diferentes'
        ];

        $rows[] = [''];
        $rows[] = ['TOP 10 EXÁMENES DE ALTO RENDIMIENTO'];
        $rows[] = [''];
        
        // Top exámenes por rendimiento
        $examenesSorted = collect($examenes)->filter(function($examen) {
            return $this->getProperty($examen, 'total_realizados', 0) > 0;
        })->sortByDesc(function($examen) {
            $total = $this->getProperty($examen, 'total_realizados', 0);
            $completados = $this->getProperty($examen, 'completados', 0);
            return $total > 0 ? ($completados / $total) : 0;
        })->take(10);

        $rows[] = ['Pos.', 'Examen', 'Total', 'Completados', 'Eficiencia', 'Categoría', 'Calificación'];
        
        $position = 1;
        foreach ($examenesSorted as $examen) {
            $total = $this->getProperty($examen, 'total_realizados', 0);
            $completados = $this->getProperty($examen, 'completados', 0);
            $eficiencia = $total > 0 ? round(($completados / $total) * 100, 2) : 0;
            
            $calificacion = 'Excelente';
            if ($eficiencia < 90) $calificacion = 'Bueno';
            if ($eficiencia < 75) $calificacion = 'Regular';
            if ($eficiencia < 50) $calificacion = 'Bajo';
            
            $rows[] = [
                $position++,
                $this->getProperty($examen, 'nombre', ''),
                $total,
                $completados,
                $eficiencia . '%',
                $this->getProperty($examen, 'categoria', 'Sin Categoría'),
                $calificacion
            ];
        }

        $rows[] = [''];
        $rows[] = ['EXÁMENES QUE REQUIEREN ATENCIÓN'];
        $rows[] = [''];

        // Exámenes con bajo rendimiento
        $examenesBajoRendimiento = collect($examenes)->filter(function($examen) {
            $total = $this->getProperty($examen, 'total_realizados', 0);
            $completados = $this->getProperty($examen, 'completados', 0);
            $eficiencia = $total > 0 ? ($completados / $total) * 100 : 0;
            return $total > 0 && $eficiencia < 50;
        })->sortBy(function($examen) {
            $total = $this->getProperty($examen, 'total_realizados', 0);
            $completados = $this->getProperty($examen, 'completados', 0);
            return $total > 0 ? ($completados / $total) : 0;
        });

        if ($examenesBajoRendimiento->count() > 0) {
            $rows[] = ['Examen', 'Total', 'Pendientes', 'Completados', 'Eficiencia', 'Problema Identificado'];
            
            foreach ($examenesBajoRendimiento as $examen) {
                $total = $this->getProperty($examen, 'total_realizados', 0);
                $pendientes = $this->getProperty($examen, 'pendientes', 0);
                $completados = $this->getProperty($examen, 'completados', 0);
                $eficiencia = $total > 0 ? round(($completados / $total) * 100, 2) : 0;
                
                $problema = 'Alta acumulación de pendientes';
                if ($pendientes > $completados * 2) {
                    $problema = 'Exceso de exámenes pendientes';
                } elseif ($eficiencia < 25) {
                    $problema = 'Muy baja tasa de completado';
                } elseif ($eficiencia < 50) {
                    $problema = 'Proceso de laboratorio lento';
                }
                
                $rows[] = [
                    $this->getProperty($examen, 'nombre', ''),
                    $total,
                    $pendientes,
                    $completados,
                    $eficiencia . '%',
                    $problema
                ];
            }
        } else {
            $rows[] = ['No se encontraron exámenes con bajo rendimiento en este período.'];
        }

        $rows[] = [''];
        $rows[] = ['ANÁLISIS DE TENDENCIAS'];
        $rows[] = [''];

        // Análisis de tendencias por categoría
        $categorias = [];
        foreach ($examenes as $examen) {
            $categoria = $this->getProperty($examen, 'categoria', 'Sin Categoría');
            if (!isset($categorias[$categoria])) {
                $categorias[$categoria] = [
                    'cantidad_examenes' => 0,
                    'total_realizados' => 0,
                    'completados' => 0,
                    'pacientes' => 0
                ];
            }
            
            $categorias[$categoria]['cantidad_examenes']++;
            $categorias[$categoria]['total_realizados'] += $this->getProperty($examen, 'total_realizados', 0);
            $categorias[$categoria]['completados'] += $this->getProperty($examen, 'completados', 0);
            $categorias[$categoria]['pacientes'] += $this->getProperty($examen, 'total_pacientes', 0);
        }

        $rows[] = ['Categoría', 'Tipos Examen', 'Vol. Total', 'Completados', 'Eficiencia', 'Demanda', 'Tendencia'];
        
        foreach ($categorias as $nombreCategoria => $stats) {
            $eficiencia = $stats['total_realizados'] > 0 ? round(($stats['completados'] / $stats['total_realizados']) * 100, 2) : 0;
            $demanda = $stats['total_realizados'] > 0 ? 'Alta' : 'Baja';
            
            if ($stats['total_realizados'] < 10) $demanda = 'Muy Baja';
            elseif ($stats['total_realizados'] < 50) $demanda = 'Baja';
            elseif ($stats['total_realizados'] < 100) $demanda = 'Media';
            elseif ($stats['total_realizados'] < 200) $demanda = 'Alta';
            else $demanda = 'Muy Alta';
            
            $tendencia = 'Estable';
            if ($eficiencia > 85 && $stats['total_realizados'] > 50) $tendencia = 'Creciente';
            elseif ($eficiencia < 50) $tendencia = 'Decreciente';
            
            $rows[] = [
                $nombreCategoria,
                $stats['cantidad_examenes'],
                $stats['total_realizados'],
                $stats['completados'],
                $eficiencia . '%',
                $demanda,
                $tendencia
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
            'A' => 30,  // KPI/Examen
            'B' => 15,  // Valor/Total
            'C' => 35,  // Interpretación/Completados
            'D' => 15,  // Eficiencia
            'E' => 15,  // Categoría/Demanda
            'F' => 20,  // Calificación/Tendencia
            'G' => 25   // Problema/Extra
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
