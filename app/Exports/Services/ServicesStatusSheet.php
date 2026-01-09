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
use Illuminate\Support\Collection;

/**
 * Hoja de Análisis por Estado - Servicios (Independiente)
 */
class ServicesStatusSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
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
        // Obtener servicios de manera segura
        $servicios = $this->data['servicios'] ?? [];
        
        // Convertir Collection a array si es necesario
        if ($servicios instanceof Collection) {
            $servicios = $servicios->toArray();
        }
        
        // Si no es array, convertir
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
            'Premium' => ['min_precio' => 300, 'color' => '9C27B0'],
            'Especializado' => ['min_examenes' => 6, 'color' => '607D8B']
        ];

        // Clasificar servicios por estados
        $grupos = [];
        $totalServicios = count($servicios);

        // Inicializar grupos
        foreach ($estadosConfig as $estado => $config) {
            $grupos[$estado] = [
                'servicios' => [],
                'count' => 0,
                'solicitudes' => 0,
                'ingresos' => 0,
                'destacados' => [],
                'config' => $config
            ];
        }

        // Clasificar cada servicio
        foreach ($servicios as $servicio) {
            // Convertir objeto a array si es necesario
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
            
            $solicitudes = (int)($servicio['total_solicitudes'] ?? $servicio['count'] ?? 0);
            $precio = (float)($servicio['precio'] ?? $servicio['price'] ?? 0);
            $examenes = (int)($servicio['total_examenes'] ?? $servicio['exams_count'] ?? 1);
            $ingresos = $solicitudes * $precio;
            $nombre = $servicio['nombre'] ?? $servicio['name'] ?? 'Sin nombre';

            // Determinar estado principal del servicio
            $estadoAsignado = null;
            
            // Primero verificar estados especiales
            if ($precio >= 300 && $solicitudes > 0) {
                $estadoAsignado = 'Premium';
            } elseif ($examenes >= 6 && $solicitudes > 0) {
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
                $grupos[$estadoAsignado]['ingresos'] += $ingresos;

                // Agregar a destacados si cumple criterios
                if ($solicitudes > 15 || $ingresos > 2000) {
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
                number_format($grupo['ingresos'], 2),
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
            case 'Premium':
                return $grupo['solicitudes'] > 20 ? 'Consolidada' : 'Nicho';
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
            case 'Premium':
                return $grupo['solicitudes'] > 10 ? 'Alta' : 'Media';
            case 'Baja Demanda':
                return $porcentaje > 30 ? 'Media' : 'Baja';
            case 'Demanda Media':
                return 'Media';
            case 'Especializado':
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
            case 'Premium':
                return $grupo['solicitudes'] > 15 ? 'Expandir portafolio' : 'Mantener exclusividad';
            case 'Baja Demanda':
                return $porcentaje > 30 ? 'Estrategia de mejora' : 'Reevaluar precio';
            case 'Demanda Media':
                return 'Impulsar marketing';
            case 'Especializado':
                return 'Promoción dirigida';
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
            'F' => 15,  // Ingresos
            'G' => 25,  // Destacados
            'H' => 12,  // Tendencia
            'I' => 10,  // Prioridad
            'J' => 20,  // Acción
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '795548']
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
            $sheet->getStyle("A2:J{$lastRow}")->applyFromArray([
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
            $sheet->getStyle("B2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Números
            $sheet->getStyle("H2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tendencia/Prioridad

            // Colores específicos por tipo de estado
            for ($row = 2; $row <= $lastRow; $row++) {
                $estado = $sheet->getCell("A{$row}")->getValue();
                $colorFondo = 'FFFFFF'; // Blanco por defecto

                switch ($estado) {
                    case 'Alta Demanda':
                        $colorFondo = 'E8F5E8';
                        break;
                    case 'Premium':
                        $colorFondo = 'F3E5F5';
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

                $sheet->getStyle("A{$row}:J{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($colorFondo);
            }
        }

        return $sheet;
    }
}
