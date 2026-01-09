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
 * Hoja de Estadísticas de Resultados
 */
class ResultsStatsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Estadísticas';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['ESTADÍSTICAS DE RESULTADOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['SECCIÓN', 'VALOR'],
        ];
    }

    public function array(): array
    {
        $rows = [];
        

        // Estadísticas de exámenes
        if (isset($this->data['examStats']) && !empty($this->data['examStats'])) {
            $examStats = $this->data['examStats'];
            
            // Convertir a array si es una Collection
            if (is_object($examStats) && method_exists($examStats, 'toArray')) {
                $examStats = $examStats->toArray();
            }
            
            $rows[] = ['ESTADÍSTICAS DE EXÁMENES', ''];
            $rows[] = ['Total de exámenes únicos', count($examStats)];
            $rows[] = ['Total de resultados', array_sum(array_column($examStats, 'total_count'))];
            $rows[] = ['Exámenes más solicitados', ''];
            
            if (!empty($examStats)) {
                // Tomar los primeros 10 exámenes
                $topExams = array_slice($examStats, 0, 10);
                foreach ($topExams as $exam) {
                    // Convertir objeto a array si es necesario
                    if (is_object($exam)) {
                        $exam = (array) $exam;
                    }
                    $rows[] = ['  ' . ($exam['name'] ?? $exam['nombre'] ?? 'N/D'), ($exam['total_count'] ?? $exam['total'] ?? 0) . ' resultados'];
                }
            } else {
                $rows[] = ['  Sin datos disponibles', ''];
            }
            $rows[] = [''];
        }
        
        // Estadísticas por categoría
        if (isset($this->data['categoryStats']) && !empty($this->data['categoryStats'])) {
            $categoryStats = $this->data['categoryStats'];
            
            // Convertir a array si es una Collection
            if (is_object($categoryStats) && method_exists($categoryStats, 'toArray')) {
                $categoryStats = $categoryStats->toArray();
            }
            
            $rows[] = ['DISTRIBUCIÓN POR CATEGORÍA', ''];
            
            if (!empty($categoryStats)) {
                foreach ($categoryStats as $category) {
                    // Convertir objeto a array si es necesario
                    if (is_object($category)) {
                        $category = (array) $category;
                    }
                    
                    $total = $category['total_count'] ?? $category['total'] ?? 0;
                    $totalGeneral = array_sum(array_column($categoryStats, 'total_count'));
                    $porcentaje = $totalGeneral > 0 ? round(($total / $totalGeneral) * 100, 1) : 0;
                    
                    $rows[] = [
                        '  ' . ($category['categoria'] ?? $category['categoria_nombre'] ?? 'Sin categoría'),
                        $total . ' resultados (' . number_format($porcentaje, 1) . '%)'
                    ];
                }
            } else {
                $rows[] = ['  Sin datos de categorías disponibles', ''];
            }
            $rows[] = [''];
        }
        
        // Resumen general
        if (isset($this->data['dailyStats']) && !empty($this->data['dailyStats'])) {
            $dailyStats = $this->data['dailyStats'];
            
            // Convertir a array si es una Collection
            if (is_object($dailyStats) && method_exists($dailyStats, 'toArray')) {
                $dailyStats = $dailyStats->toArray();
            }
            
            $totalDays = count($dailyStats);
            
            // Inicializar variables para sumar
            $totalExams = 0;
            $totalCompleted = 0;
            $totalInProcess = 0;
            $totalPending = 0;
            
            // Recorrer cada día y sumar los valores, manejando tanto objetos como arrays
            foreach ($dailyStats as $day) {
                // Convertir a array si es un objeto
                if (is_object($day)) {
                    $day = (array) $day;
                }
                
                $totalExams += $day['count'] ?? $day['total'] ?? 0;
                $totalCompleted += $day['completed'] ?? $day['completados'] ?? $day['completado'] ?? 0;
                $totalInProcess += $day['in_process'] ?? $day['en_proceso'] ?? 0;
                $totalPending += $day['pending'] ?? $day['pendientes'] ?? $day['pendiente'] ?? 0;
            }
            
            $rows[] = ['RESUMEN GENERAL DEL PERÍODO', ''];
            $rows[] = ['Días analizados', $totalDays];
            $rows[] = ['Total de exámenes', $totalExams];
            $rows[] = ['Completados', $totalCompleted . ' (' . ($totalExams > 0 ? number_format(($totalCompleted / $totalExams) * 100, 1) : 0) . '%)'];
            $rows[] = ['En proceso', $totalInProcess . ' (' . ($totalExams > 0 ? number_format(($totalInProcess / $totalExams) * 100, 1) : 0) . '%)'];
            $rows[] = ['Pendientes', $totalPending . ' (' . ($totalExams > 0 ? number_format(($totalPending / $totalExams) * 100, 1) : 0) . '%)'];
        } else {
            $rows[] = ['Sin datos estadísticos para el período seleccionado', ''];
        }
        
        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:B1'); // Título principal
        $sheet->mergeCells('A2:B2'); // Subtítulo
        $sheet->mergeCells('A3:B3'); // Período
        
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

        $sheet->getStyle('A2:B2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:B3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Encabezados de la tabla
        $sheet->getStyle('A5:B5')->applyFromArray([
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
        $sheet->getStyle('A5:B' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Merge cells para los títulos
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->mergeCells('A3:B3');

        return $sheet;
    }
}
