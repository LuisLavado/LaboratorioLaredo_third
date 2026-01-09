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
 * Hoja de Análisis por Exámenes - Servicios (Versión Optimizada)
 * Análisis de servicios agrupados por cantidad de exámenes incluidos
 */
class ServicesImprovedByExamsSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
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
        return 'Análisis por Exámenes';
    }

    public function headings(): array
    {
        return [
            'Rango Exámenes',
            'Cantidad Servicios',
            'Total Solicitudes',
            'Promedio Solicitudes',
            'Servicios Populares',
            'Participación (%)',
            'Eficiencia',
            'Recomendación'
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
                'Sin datos',
                0,
                0,
                '0.00',
                '0.00',
                '0.00',
                'Ninguno',
                '0.00',
                'N/A',
                'Verificar configuración'
            ]];
        }

        // Agrupar servicios por rango de exámenes
        $rangos = [
            '1 examen' => ['min' => 1, 'max' => 1],
            '2-3 exámenes' => ['min' => 2, 'max' => 3],
            '4-5 exámenes' => ['min' => 4, 'max' => 5],
            '6-10 exámenes' => ['min' => 6, 'max' => 10],
            '11+ exámenes' => ['min' => 11, 'max' => 999]
        ];

        $grupos = [];
        $totalSolicitudesGlobal = 0;

        // Inicializar grupos
        foreach ($rangos as $nombre => $rango) {
            $grupos[$nombre] = [
                'servicios' => [],
                'count' => 0,
                'solicitudes' => 0,
                'populares' => []
            ];
        }

        // Clasificar servicios en rangos
        foreach ($servicios as $servicio) {
            $examenes = (int)($servicio['total_examenes'] ?? $servicio['exams_count'] ?? 1);
            $solicitudes = (int)($servicio['total_solicitudes'] ?? $servicio['count'] ?? 0);
            $nombre = $servicio['nombre'] ?? $servicio['name'] ?? 'Sin nombre';

            $totalSolicitudesGlobal += $solicitudes;

            // Encontrar rango correspondiente
            foreach ($rangos as $nombreRango => $rango) {
                if ($examenes >= $rango['min'] && $examenes <= $rango['max']) {
                    $grupos[$nombreRango]['servicios'][] = $servicio;
                    $grupos[$nombreRango]['count']++;
                    $grupos[$nombreRango]['solicitudes'] += $solicitudes;
                    
                    // Identificar servicios populares (más de 10 solicitudes)
                    if ($solicitudes > 10) {
                        $grupos[$nombreRango]['populares'][] = $nombre;
                    }
                    break;
                }
            }
        }

        // Generar filas del reporte
        $rows = [];
        foreach ($grupos as $nombreRango => $grupo) {
            if ($grupo['count'] == 0) {
                continue; // Saltar rangos vacíos
            }

            $promedioSolicitudes = $grupo['count'] > 0 ? round($grupo['solicitudes'] / $grupo['count'], 2) : 0;
            $participacion = $totalSolicitudesGlobal > 0 ? round(($grupo['solicitudes'] / $totalSolicitudesGlobal) * 100, 2) : 0;
            
            // Lista de servicios populares (máximo 3)
            $populares = array_slice($grupo['populares'], 0, 3);
            $popularesTexto = !empty($populares) ? implode(', ', $populares) : 'Ninguno destacado';
            if (count($grupo['populares']) > 3) {
                $popularesTexto .= '...';
            }

            // Calcular eficiencia (solicitudes promedio / número de exámenes del rango)
            $rangoPromedio = ($rangos[$nombreRango]['min'] + min($rangos[$nombreRango]['max'], 20)) / 2;
            $eficiencia = $rangoPromedio > 0 ? round($promedioSolicitudes / $rangoPromedio, 2) : 0;

            // Generar recomendación
            $recomendacion = 'Estable';
            if ($participacion > 25) {
                $recomendacion = 'Grupo dominante';
            } elseif ($participacion < 5) {
                $recomendacion = 'Revisar demanda';
            } elseif ($eficiencia > 5) {
                $recomendacion = 'Alta eficiencia';
            } elseif ($promedioSolicitudes > 20) {
                $recomendacion = 'Alto volumen';
            }

            $rows[] = [
                $nombreRango,
                $grupo['count'],
                $grupo['solicitudes'],
                number_format($promedioSolicitudes, 2),
                $popularesTexto,
                number_format($participacion, 2),
                number_format($eficiencia, 2),
                $recomendacion
            ];
        }

        // Ordenar por total de solicitudes descendente
        usort($rows, function($a, $b) {
            return $b[2] - $a[2]; // Columna 2 = Total Solicitudes
        });

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Rango
            'B' => 12,  // Cantidad
            'C' => 12,  // Total Solicitudes
            'D' => 15,  // Promedio Solicitudes
            'E' => 30,  // Servicios Populares
            'F' => 12,  // Participación
            'G' => 12,  // Eficiencia
            'H' => 18,  // Recomendación
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1976D2']
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
            $sheet->getStyle("A2:H{$lastRow}")->applyFromArray([
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
            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Rango
            $sheet->getStyle("B2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Números
            $sheet->getStyle("F2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Porcentajes
            $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Recomendación

            // Colores alternados en filas
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle("A{$row}:H{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F1F8FF');
                }
            }
        }

        return $sheet;
    }
}
