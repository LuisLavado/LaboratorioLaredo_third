<?php

namespace App\Exports\Services;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Hoja de Análisis de Rendimiento - Servicios (Versión Optimizada)
 * Top servicios por diferentes métricas de rendimiento
 */
class ServicesImprovedPerformanceSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Análisis Rendimiento';
    }

    public function headings(): array
    {
        return [
            'Ranking',
            'Servicio',
            'Métrica',
            'Valor',
            'Total Solicitudes',
            'Pacientes Únicos',
            'Ratio Solicitud/Paciente',
            'Calificación',
            'Observaciones'
        ];
    }

    public function array(): array
    {
        // Obtener datos de manera eficiente
        $servicios = $this->data['servicios'] ?? [];
        if (is_object($servicios) && method_exists($servicios, 'toArray')) {
            $servicios = $servicios->toArray();
        }
        if (!is_array($servicios)) {
            $servicios = [];
        }

        if (empty($servicios)) {
            return [[
                1,
                'Sin datos',
                'N/A',
                '0',
                0,
                '0.00',
                0,
                '0.00',
                'Sin datos',
                'Verificar configuración'
            ]];
        }

        $rows = [];

        // Preparar datos de servicios con métricas calculadas
        $serviciosConMetricas = [];
        foreach ($servicios as $servicio) {
            $solicitudes = (int)($servicio['total_solicitudes'] ?? $servicio['count'] ?? 0);
            $pacientes = (int)($servicio['total_pacientes'] ?? $servicio['unique_patients'] ?? 0);
            $ratio = $pacientes > 0 ? round($solicitudes / $pacientes, 2) : 0;

            $serviciosConMetricas[] = [
                'nombre' => $servicio['nombre'] ?? $servicio['name'] ?? 'Sin nombre',
                'solicitudes' => $solicitudes,
                'pacientes' => $pacientes,
                'ratio' => $ratio
            ];
        }

        // Top 5 por Solicitudes
        $topSolicitudes = $serviciosConMetricas;
        usort($topSolicitudes, function($a, $b) { return $b['solicitudes'] - $a['solicitudes']; });
        $topSolicitudes = array_slice($topSolicitudes, 0, 5);

        foreach ($topSolicitudes as $index => $servicio) {
            $calificacion = $this->getCalificacion($servicio['solicitudes'], 'solicitudes');
            $observaciones = $this->getObservaciones($servicio, 'solicitudes');
            
            $rows[] = [
                $index + 1,
                $servicio['nombre'],
                'Más Solicitado',
                number_format($servicio['solicitudes']),
                $servicio['solicitudes'],
                $servicio['pacientes'],
                number_format($servicio['ratio'], 2),
                $calificacion,
                $observaciones
            ];
        }

        // Separador
        $rows[] = ['', '', '', '', '', '', '', '', ''];

        // Top 5 por Ratio Solicitud/Paciente
        $topRatio = array_filter($serviciosConMetricas, function($s) { return $s['ratio'] > 0; });
        usort($topRatio, function($a, $b) { return $b['ratio'] <=> $a['ratio']; });
        $topRatio = array_slice($topRatio, 0, 5);

        foreach ($topRatio as $index => $servicio) {
            $calificacion = $this->getCalificacion($servicio['ratio'], 'ratio');
            $observaciones = $this->getObservaciones($servicio, 'ratio');
            
            $rows[] = [
                $index + 1,
                $servicio['nombre'],
                'Mayor Fidelidad',
                number_format($servicio['ratio'], 2) . 'x',
                $servicio['solicitudes'],
                $servicio['pacientes'],
                number_format($servicio['ratio'], 2),
                $calificacion,
                $observaciones
            ];
        }

        return $rows;
    }

    private function getCalificacion($valor, $tipo)
    {
        switch ($tipo) {
            case 'solicitudes':
                if ($valor > 50) return 'Excelente';
                if ($valor > 20) return 'Muy Bueno';
                if ($valor > 10) return 'Bueno';
                if ($valor > 5) return 'Regular';
                return 'Bajo';
                
            case 'ratio':
                if ($valor > 3) return 'Excelente';
                if ($valor > 2) return 'Muy Bueno';
                if ($valor > 1.5) return 'Bueno';
                if ($valor > 1) return 'Regular';
                return 'Bajo';
        }
        
        return 'N/A';
    }

    private function getObservaciones($servicio, $tipo)
    {
        switch ($tipo) {
            case 'solicitudes':
                if ($servicio['solicitudes'] > 30 && $servicio['ratio'] > 2) {
                    return 'Servicio estrella';
                } elseif ($servicio['solicitudes'] > 20 && $servicio['pacientes'] < 10) {
                    return 'Alta repetición';
                } else {
                    return 'Servicio popular';
                }
                
            case 'ratio':
                if ($servicio['ratio'] > 3) {
                    return 'Muy fidelizado';
                } elseif ($servicio['ratio'] > 2) {
                    return 'Buena fidelidad';
                } else {
                    return 'Repetición moderada';
                }
        }
        
        return '';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // Ranking
            'B' => 25,  // Servicio
            'C' => 15,  // Métrica
            'D' => 15,  // Valor
            'E' => 12,  // Solicitudes
            'F' => 12,  // Pacientes
            'G' => 15,  // Ratio
            'H' => 12,  // Calificación
            'I' => 20,  // Observaciones
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF6F00']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Ajustar altura del encabezado
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Estilo de las celdas de datos
        $lastRow = count($this->array()) + 1;
        if ($lastRow > 1) {
            $sheet->getStyle("A2:I{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            // Alineación específica por columnas
            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Ranking
            $sheet->getStyle("D2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Valores numéricos
            $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Calificación

            // Colores alternados en filas y destacar separadores
            for ($row = 2; $row <= $lastRow; $row++) {
                $cellValue = $sheet->getCell("B{$row}")->getValue();
                
                if (empty($cellValue)) {
                    // Fila separadora
                    $sheet->getStyle("A{$row}:I{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E0E0E0');
                } elseif ($row % 2 == 0) {
                    $sheet->getStyle("A{$row}:I{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFF8E1');
                }
            }
        }

        return $sheet;
    }
}
