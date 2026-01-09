<?php

namespace App\Exports\Results;

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
 * Hoja de Análisis por Examen
 */
class ResultsAnalysisSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Análisis por Examen';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['ANÁLISIS DETALLADO POR EXAMEN'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'Examen',
                'Categoría',
                'Total Resultados',
                'Completados',
                '% Completados',
                'En Proceso',
                '% En Proceso',
                'Pendientes',
                '% Pendientes',

                'Recomendación'
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        // Verificar si hay datos de exámenes
        if (!isset($this->data['examsByCategory']) || empty($this->data['examsByCategory'])) {
            $rows[] = [
                'No hay datos de exámenes para el período seleccionado.',
                '', '', '', '', '', '', '', '', '', ''
            ];
            return $rows;
        }
        
        // Si examsByCategory es un objeto, convertirlo a array
        $examsByCategory = $this->data['examsByCategory'];
        if (is_object($examsByCategory) && method_exists($examsByCategory, 'toArray')) {
            $examsByCategory = $examsByCategory->toArray();
        }
        
        // Procesar datos por categoría
        foreach ($examsByCategory as $categoryName => $categoryData) {
            // Si no hay campo "examenes", probablemente sea un objeto directo
            if (!isset($categoryData['examenes'])) {
                // Si hay más de un examen, usamos la categoría como nombre
                if (is_array($categoryData) || (is_object($categoryData) && count((array)$categoryData) > 1)) {
                    $exams = $categoryData;
                } else {
                    continue;
                }
            } else {
                $exams = $categoryData['examenes'] ?? [];
                if (empty($exams)) {
                    continue;
                }
            }
            
            // Título de categoría
            $rows[] = [
                'CATEGORÍA: ' . strtoupper($categoryName),
                '', '', '', '', '', '', '', '', '', ''
            ];
            
            // Procesar cada examen
            foreach ($exams as $exam) {
                // Convertir a array si es un objeto
                if (is_object($exam)) {
                    $exam = (array) $exam;
                }
                
                $examName = $exam['examen_nombre'] ?? $exam['name'] ?? $exam['nombre'] ?? 'N/D';
                $total = $exam['total'] ?? $exam['total_count'] ?? 0;
                $completados = $exam['completados'] ?? $exam['completed_count'] ?? $exam['completed'] ?? 0;
                $enProceso = $exam['en_proceso'] ?? $exam['in_process_count'] ?? $exam['in_process'] ?? 0;
                $pendientes = $exam['pendientes'] ?? $exam['pending_count'] ?? $exam['pending'] ?? 0;

                
                // Calcular porcentajes
                $pctCompletados = $total > 0 ? number_format(($completados / $total) * 100, 1) : '0.0';
                $pctEnProceso = $total > 0 ? number_format(($enProceso / $total) * 100, 1) : '0.0';
                $pctPendientes = $total > 0 ? number_format(($pendientes / $total) * 100, 1) : '0.0';
                
                // Calcular tiempo promedio (simulado por ahora)
                $tiempoPromedio = $completados > 0 ? '2-4 horas' : 'N/D';

                // Generar recomendación
                $recomendacion = $this->generateRecommendation($completados, $enProceso, $pendientes, $total);

                $rows[] = [
                    $examName,
                    $categoryName,
                    $total,
                    $completados,
                    $pctCompletados . '%',
                    $enProceso,
                    $pctEnProceso . '%',
                    $pendientes,
                    $pctPendientes . '%',
                    $tiempoPromedio,
                    $recomendacion
                ];
            }
            
            // Línea vacía entre categorías
            $rows[] = ['', '', '', '', '', '', '', '', '', '', ''];
        }
        
        // Agregar resumen y recomendaciones generales
        $rows[] = ['RESUMEN Y RECOMENDACIONES GENERALES', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', '', '', '', ''];
        
        $generalRecommendations = $this->generateGeneralRecommendations();
        foreach ($generalRecommendations as $recommendation) {
            $rows[] = [$recommendation, '', '', '', '', '', '', '', '', '', ''];
        }
        
        return $rows;
    }

    /**
     * Generar recomendación para un examen específico
     */
    private function generateRecommendation($completados, $enProceso, $pendientes, $total)
    {
        if ($total == 0) {
            return 'Sin datos suficientes';
        }
        
        $pctCompletados = ($completados / $total) * 100;
        $pctPendientes = ($pendientes / $total) * 100;
        
        // Recomendaciones basadas en el estado
        if ($pctCompletados >= 90) {
            return 'Excelente rendimiento';
        } elseif ($pctCompletados >= 70) {
            return 'Buen rendimiento';
        } elseif ($pctPendientes > 50) {
            return 'CRÍTICO: Muchos pendientes';
        } elseif ($pctPendientes > 30) {
            return 'ATENCIÓN: Revisar pendientes';
        } else {
            return 'Rendimiento regular';
        }
    }

    /**
     * Generar recomendaciones generales
     */
    private function generateGeneralRecommendations()
    {
        $recommendations = [];
        
        // Analizar datos generales
        if (isset($this->data['examsByCategory'])) {
            $totalExams = 0;
            $totalCompleted = 0;
            $totalPending = 0;
            $criticalExams = [];
            
            foreach ($this->data['examsByCategory'] as $categoryData) {
                foreach ($categoryData['examenes'] ?? [] as $exam) {
                    $total = $exam['total'] ?? 0;
                    $completados = $exam['completados'] ?? 0;
                    $pendientes = $exam['pendientes'] ?? 0;
                    
                    $totalExams += $total;
                    $totalCompleted += $completados;
                    $totalPending += $pendientes;
                    
                    // Identificar exámenes críticos
                    if ($total > 0 && ($pendientes / $total) > 0.5) {
                        $criticalExams[] = $exam['examen_nombre'] ?? 'N/D';
                    }
                }
            }
            
            $overallCompletionRate = $totalExams > 0 ? ($totalCompleted / $totalExams) * 100 : 0;
            
            // Generar recomendaciones
            if ($overallCompletionRate >= 90) {
                $recommendations[] = '✓ Excelente rendimiento general del laboratorio';
            } elseif ($overallCompletionRate >= 70) {
                $recommendations[] = '• Buen rendimiento general, continuar con las buenas prácticas';
            } else {
                $recommendations[] = '⚠ Rendimiento general bajo, revisar procesos';
            }
            
            if (!empty($criticalExams)) {
                $recommendations[] = '🔴 CRÍTICO: Exámenes con alta carga pendiente: ' . implode(', ', array_slice($criticalExams, 0, 3));
                if (count($criticalExams) > 3) {
                    $recommendations[] = '   y ' . (count($criticalExams) - 3) . ' exámenes más';
                }
            }
            
            if ($totalPending > ($totalExams * 0.3)) {
                $recommendations[] = '⚠ Más del 30% de resultados están pendientes';
                $recommendations[] = '  Recomendación: Revisar capacidad de procesamiento';
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Sin datos suficientes para generar recomendaciones';
        }
        
        return $recommendations;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Examen
            'B' => 20, // Categoría
            'C' => 12, // Total
            'D' => 12, // Completados
            'E' => 12, // % Completados
            'F' => 12, // En Proceso
            'G' => 12, // % En Proceso
            'H' => 12, // Pendientes
            'I' => 12, // % Pendientes
            'J' => 15, // Tiempo Promedio
            'K' => 30, // Recomendación
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells para los títulos
        $sheet->mergeCells('A1:K1'); // Título principal
        $sheet->mergeCells('A2:K2'); // Subtítulo
        $sheet->mergeCells('A3:K3'); // Período
        
        // Estilos para encabezados
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f4e79']
            ]
        ]);
        
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2f5f8f']
            ]
        ]);
        
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472c4']
            ]
        ]);

        // Encabezados de la tabla
        $sheet->getStyle('A5:K5')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFE5E7EB',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Aplicar bordes a todas las celdas con datos
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A5:K' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Merge cells para los títulos
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');

        return $sheet;
    }
}
