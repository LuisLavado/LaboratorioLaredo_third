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
 * Hoja de Análisis de Estado - Servicios (Versión Optimizada)
 * Análisis de servicios agrupados por estado de actividad
 */
class ServicesImprovedStatusSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
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
        return 'Análisis por Estado';
    }

    public function headings(): array
    {
        return [
            'Estado',
            'Cantidad',
            'Porcentaje (%)',
            'Total Solicitudes',
            'Promedio/Servicio',
            'Servicios Destacados',
            'Tendencia',
            'Prioridad',
            'Acción Recomendada'
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
                '0.00',
                0,
                '0.00',
                'Ninguno',
                'N/A',
                'Baja',
                'Verificar configuración'
            ]];
        }

        // Definir estados y sus criterios
        $estadosConfig = [
            'Alta Demanda' => ['min_solicitudes' => 30, 'color' => '4CAF50'],
            'Demanda Media' => ['min_solicitudes' => 10, 'max_solicitudes' => 29, 'color' => '2196F3'],
            'Baja Demanda' => ['min_solicitudes' => 1, 'max_solicitudes' => 9, 'color' => 'FF9800'],
            'Sin Actividad' => ['max_solicitudes' => 0, 'color' => 'F44336'],
            'Especializado' => ['min_examenes' => 6, 'color' => '607D8B']
        ];

        // Clasificar servicios por estados
        $grupos = [];
        $totalServicios = count($servicios);
        $totalSolicitudesGlobal = 0;

        // Inicializar grupos
        foreach ($estadosConfig as $estado => $config) {
            $grupos[$estado] = [
                'servicios' => [],
                'count' => 0,
                'solicitudes' => 0,
                'destacados' => [],
                'config' => $config
            ];
        }

        // Clasificar cada servicio
        foreach ($servicios as $servicio) {
            $solicitudes = (int)($servicio['total_solicitudes'] ?? $servicio['count'] ?? 0);
            $examenes = (int)($servicio['total_examenes'] ?? $servicio['exams_count'] ?? 1);
            $nombre = $servicio['nombre'] ?? $servicio['name'] ?? 'Sin nombre';

            $totalSolicitudesGlobal += $solicitudes;

            // Determinar estado principal del servicio
            $estadoAsignado = null;
            
            // Primero verificar estados especiales
            if ($examenes >= 6 && $solicitudes > 0) {
                $estadoAsignado = 'Especializado';
            } elseif ($solicitudes >= 30) {
                $estadoAsignado = 'Alta Demanda';
            } elseif ($solicitudes >= 10) {
                $estadoAsignado = 'Demanda Media';
            } elseif ($solicitudes >= 1) {
                $estadoAsignado = 'Baja Demanda';
            } else {
                $estadoAsignado = 'Sin Actividad';
            }

            // Asignar al grupo correspondiente
            if ($estadoAsignado && isset($grupos[$estadoAsignado])) {
                $grupos[$estadoAsignado]['servicios'][] = $servicio;
                $grupos[$estadoAsignado]['count']++;
                $grupos[$estadoAsignado]['solicitudes'] += $solicitudes;

                // Agregar a destacados si cumple criterios
                if ($solicitudes > 15) {
                    $grupos[$estadoAsignado]['destacados'][] = $nombre;
                }
            }
        }

        // Generar filas del reporte
        $rows = [];
        foreach ($grupos as $estado => $grupo) {
            if ($grupo['count'] == 0) {
                continue; // Saltar estados vacíos
            }

            $porcentaje = $totalServicios > 0 ? round(($grupo['count'] / $totalServicios) * 100, 2) : 0;
            $promedioSolicitudes = $grupo['count'] > 0 ? round($grupo['solicitudes'] / $grupo['count'], 2) : 0;

            // Lista de servicios destacados (máximo 2)
            $destacados = array_slice($grupo['destacados'], 0, 2);
            $destacadosTexto = !empty($destacados) ? implode(', ', $destacados) : 'Sin destacados';
            if (count($grupo['destacados']) > 2) {
                $destacadosTexto .= '...';
            }

            // Determinar tendencia
            $tendencia = $this->getTendencia($estado, $grupo);
            
            // Determinar prioridad
            $prioridad = $this->getPrioridad($estado, $grupo, $porcentaje);
            
            // Generar acción recomendada
            $accion = $this->getAccionRecomendada($estado, $grupo, $porcentaje);

            $rows[] = [
                $estado,
                $grupo['count'],
                number_format($porcentaje, 2),
                $grupo['solicitudes'],
                number_format($promedioSolicitudes, 2),
                $destacadosTexto,
                $tendencia,
                $prioridad,
                $accion
            ];
        }

        // Ordenar por cantidad de servicios descendente
        usort($rows, function($a, $b) {
            return $b[1] - $a[1]; // Columna 1 = Cantidad
        });

        return $rows;
    }

    private function getTendencia($estado, $grupo)
    {
        $promedioSolicitudes = $grupo['count'] > 0 ? $grupo['solicitudes'] / $grupo['count'] : 0;

        switch ($estado) {
            case 'Alta Demanda':
                return $promedioSolicitudes > 50 ? 'Creciente' : 'Estable';
            case 'Demanda Media':
                return $promedioSolicitudes > 20 ? 'Positiva' : 'Estable';
            case 'Baja Demanda':
                return $promedioSolicitudes > 5 ? 'Recuperación' : 'Declinante';
            case 'Sin Actividad':
                return 'Crítica';
            case 'Especializado':
                return $grupo['solicitudes'] > 15 ? 'Creciente' : 'Estable';
            default:
                return 'Estable';
        }
    }

    private function getPrioridad($estado, $grupo, $porcentaje)
    {
        switch ($estado) {
            case 'Sin Actividad':
                return 'Crítica';
            case 'Alta Demanda':
                return 'Alta';
            case 'Especializado':
                return $grupo['solicitudes'] > 10 ? 'Alta' : 'Media';
            case 'Baja Demanda':
                return $porcentaje > 30 ? 'Media' : 'Baja';
            case 'Demanda Media':
                return 'Media';
            default:
                return 'Baja';
        }
    }

    private function getAccionRecomendada($estado, $grupo, $porcentaje)
    {
        switch ($estado) {
            case 'Sin Actividad':
                return 'Revisar o discontinuar';
            case 'Alta Demanda':
                return 'Mantener y promocionar';
            case 'Especializado':
                return $grupo['solicitudes'] > 15 ? 'Expandir portafolio' : 'Promoción dirigida';
            case 'Baja Demanda':
                return $porcentaje > 30 ? 'Estrategia de mejora' : 'Reevaluar demanda';
            case 'Demanda Media':
                return 'Impulsar marketing';
            default:
                return 'Monitorear';
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Estado
            'B' => 10,  // Cantidad
            'C' => 12,  // Porcentaje
            'D' => 12,  // Solicitudes
            'E' => 15,  // Promedio
            'F' => 25,  // Destacados
            'G' => 12,  // Tendencia
            'H' => 10,  // Prioridad
            'I' => 20,  // Acción
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
                'startColor' => ['rgb' => '673AB7']
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
            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Estado
            $sheet->getStyle("B2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Números
            $sheet->getStyle("G2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tendencia/Prioridad

            // Colores específicos por tipo de estado
            for ($row = 2; $row <= $lastRow; $row++) {
                $estado = $sheet->getCell("A{$row}")->getValue();
                $colorFondo = 'FFFFFF'; // Blanco por defecto

                switch ($estado) {
                    case 'Alta Demanda':
                        $colorFondo = 'E8F5E8';
                        break;
                    case 'Sin Actividad':
                        $colorFondo = 'FFEBEE';
                        break;
                    case 'Baja Demanda':
                        $colorFondo = 'FFF3E0';
                        break;
                    case 'Demanda Media':
                        $colorFondo = 'E3F2FD';
                        break;
                    case 'Especializado':
                        $colorFondo = 'F1F8E9';
                        break;
                }

                $sheet->getStyle("A{$row}:I{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($colorFondo);
            }
        }

        return $sheet;
    }
}
