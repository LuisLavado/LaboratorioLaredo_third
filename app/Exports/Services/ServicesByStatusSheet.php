<?php

namespace App\Exports\Services;

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
use App\Exports\Services\ServiceSheetHelper;

class ServicesByStatusSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    use ServiceSheetHelper;
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
        return 'Por Estado';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['SERVICIOS POR ESTADO DETALLADO'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['Estado', 'Servicio', 'Solicitudes', 'Exámenes', 'Completados', 'Pendientes', 'Eficiencia', 'Fecha Creación']
        ];
    }

    public function array(): array
    {
        $rows = [];

        if (!empty($this->data['servicios'])) {
            $servicios = is_object($this->data['servicios']) && method_exists($this->data['servicios'], 'toArray')
                ? $this->data['servicios']->toArray()
                : $this->data['servicios'];

            // Separar servicios por estado
            $serviciosActivos = [];
            $serviciosInactivos = [];

            foreach ($servicios as $servicio) {
                $activo = $this->getProperty($servicio, 'activo', null);
                if ($activo === 1 || $activo === '1' || $activo === true) {
                    $serviciosActivos[] = $servicio;
                } else {
                    $serviciosInactivos[] = $servicio;
                }
            }

            // Ordenar por nombre completo (incluyendo padre)
            usort($serviciosActivos, function($a, $b) {
                $nombreA = $this->getProperty($a, 'nombre_completo', $this->getProperty($a, 'nombre', ''));
                $nombreB = $this->getProperty($b, 'nombre_completo', $this->getProperty($b, 'nombre', ''));
                return strcmp($nombreA, $nombreB);
            });
            usort($serviciosInactivos, function($a, $b) {
                $nombreA = $this->getProperty($a, 'nombre_completo', $this->getProperty($a, 'nombre', ''));
                $nombreB = $this->getProperty($b, 'nombre_completo', $this->getProperty($b, 'nombre', ''));
                return strcmp($nombreA, $nombreB);
            });

            // Variables para totales generales
            $totalSolicitudesGeneral = 0;
            $totalExamenesGeneral = 0;
            $totalCompletadosGeneral = 0;
            $totalPendientesGeneral = 0;

            // Variables para totales por estado
            $totalSolicitudesActivos = 0;
            $totalExamenesActivos = 0;
            $totalCompletadosActivos = 0;
            $totalPendientesActivos = 0;

            $totalSolicitudesInactivos = 0;
            $totalExamenesInactivos = 0;
            $totalCompletadosInactivos = 0;
            $totalPendientesInactivos = 0;

            // Agregar servicios activos
            foreach ($serviciosActivos as $servicio) {
                $solicitudes = $this->getProperty($servicio, 'total_solicitudes', 0);
                $examenes = $this->getProperty($servicio, 'total_examenes', 0);
                $completados = $this->getProperty($servicio, 'completados', 0);
                $pendientes = $this->getProperty($servicio, 'pendientes', 0);
                $eficiencia = $examenes > 0 ? round(($completados / $examenes) * 100, 1) : 0;
                $fechaCreacion = $this->getProperty($servicio, 'created_at', '');

                // Formatear fecha
                $fechaFormateada = '';
                if ($fechaCreacion) {
                    try {
                        $fechaFormateada = Carbon::parse($fechaCreacion)->format('d/m/Y');
                    } catch (\Exception $e) {
                        $fechaFormateada = 'N/A';
                    }
                }

                $rows[] = [
                    'Activo',
                    $this->getProperty($servicio, 'nombre_completo', $this->getProperty($servicio, 'nombre', 'Sin nombre')),
                    $solicitudes,
                    $examenes,
                    $completados,
                    $pendientes,
                    $eficiencia . '%',
                    $fechaFormateada
                ];

                // Sumar a totales de activos
                $totalSolicitudesActivos += $solicitudes;
                $totalExamenesActivos += $examenes;
                $totalCompletadosActivos += $completados;
                $totalPendientesActivos += $pendientes;
            }

            // Agregar servicios inactivos
            foreach ($serviciosInactivos as $servicio) {
                $solicitudes = $this->getProperty($servicio, 'total_solicitudes', 0);
                $examenes = $this->getProperty($servicio, 'total_examenes', 0);
                $completados = $this->getProperty($servicio, 'completados', 0);
                $pendientes = $this->getProperty($servicio, 'pendientes', 0);
                $eficiencia = $examenes > 0 ? round(($completados / $examenes) * 100, 1) : 0;
                $fechaCreacion = $this->getProperty($servicio, 'created_at', '');

                // Formatear fecha
                $fechaFormateada = '';
                if ($fechaCreacion) {
                    try {
                        $fechaFormateada = Carbon::parse($fechaCreacion)->format('d/m/Y');
                    } catch (\Exception $e) {
                        $fechaFormateada = 'N/A';
                    }
                }

                $rows[] = [
                    'Inactivo',
                    $this->getProperty($servicio, 'nombre_completo', $this->getProperty($servicio, 'nombre', 'Sin nombre')),
                    $solicitudes,
                    $examenes,
                    $completados,
                    $pendientes,
                    $eficiencia . '%',
                    $fechaFormateada
                ];

                // Sumar a totales de inactivos
                $totalSolicitudesInactivos += $solicitudes;
                $totalExamenesInactivos += $examenes;
                $totalCompletadosInactivos += $completados;
                $totalPendientesInactivos += $pendientes;
            }

            // Calcular totales generales
            $totalSolicitudesGeneral = $totalSolicitudesActivos + $totalSolicitudesInactivos;
            $totalExamenesGeneral = $totalExamenesActivos + $totalExamenesInactivos;
            $totalCompletadosGeneral = $totalCompletadosActivos + $totalCompletadosInactivos;
            $totalPendientesGeneral = $totalPendientesActivos + $totalPendientesInactivos;

            // Fila de separación
            $rows[] = ['', '', '', '', '', '', '', ''];

            // Resumen por estado
            $rows[] = ['RESUMEN POR ESTADO', '', '', '', '', '', '', ''];
            $rows[] = ['Estado', 'Cantidad Servicios', 'Total Solicitudes', 'Total Exámenes', 'Completados', 'Pendientes', 'Eficiencia Promedio', ''];

            // Calcular totales por estado
            $activosCount = count($serviciosActivos);
            $inactivosCount = count($serviciosInactivos);

            // Calcular eficiencias por estado
            $eficienciaActivos = $totalExamenesActivos > 0 ? round(($totalCompletadosActivos / $totalExamenesActivos) * 100, 1) : 0;
            $eficienciaInactivos = $totalExamenesInactivos > 0 ? round(($totalCompletadosInactivos / $totalExamenesInactivos) * 100, 1) : 0;
            $eficienciaGeneral = $totalExamenesGeneral > 0 ? round(($totalCompletadosGeneral / $totalExamenesGeneral) * 100, 1) : 0;

            $rows[] = [
                'Activos',
                $activosCount,
                $totalSolicitudesActivos,
                $totalExamenesActivos,
                $totalCompletadosActivos,
                $totalPendientesActivos,
                $eficienciaActivos . '%',
                ''
            ];

            $rows[] = [
                'Inactivos',
                $inactivosCount,
                $totalSolicitudesInactivos,
                $totalExamenesInactivos,
                $totalCompletadosInactivos,
                $totalPendientesInactivos,
                $eficienciaInactivos . '%',
                ''
            ];

            // Fila de totales generales
            $rows[] = [
                'TOTAL GENERAL',
                $activosCount + $inactivosCount,
                $totalSolicitudesGeneral,
                $totalExamenesGeneral,
                $totalCompletadosGeneral,
                $totalPendientesGeneral,
                $eficienciaGeneral . '%',
                ''
            ];

        } else {
            $rows[] = ['No hay servicios disponibles para mostrar', '', '', '', '', '', '', ''];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Calcular el número real de filas basado en los datos actuales
        $dataRows = $this->array();
        $totalDataRows = count($dataRows);
        $totalRows = $totalDataRows + 5; // 5 filas de header

        $sheet->mergeCells('A1:H1'); // Título principal
        $sheet->mergeCells('A2:H2'); // Subtítulo
        $sheet->mergeCells('A3:H3'); // Período

        $styles = [
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
                'font' => ['bold' => true, 'size' => 11],
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
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]
        ];

        // Apply styles only to rows that actually have data
        if ($totalDataRows > 0) {
            // Calcular posiciones de las secciones del resumen
            $servicios = !empty($this->data['servicios']) ?
                (is_object($this->data['servicios']) && method_exists($this->data['servicios'], 'toArray')
                    ? $this->data['servicios']->toArray()
                    : $this->data['servicios']) : [];

            $serviciosActivos = [];
            $serviciosInactivos = [];
            foreach ($servicios as $servicio) {
                $activo = $this->getProperty($servicio, 'activo', null);
                if ($activo === 1 || $activo === '1' || $activo === true) {
                    $serviciosActivos[] = $servicio;
                } else {
                    $serviciosInactivos[] = $servicio;
                }
            }

            $numServiciosData = count($serviciosActivos) + count($serviciosInactivos);
            $resumenTituloRow = 6 + $numServiciosData + 1; // +1 por fila de separación
            $resumenHeaderRow = $resumenTituloRow + 1;
            $resumenDataStartRow = $resumenHeaderRow + 1;
            $totalGeneralRow = $resumenDataStartRow + 2; // Activos + Inactivos

            // Estilos para filas de datos de servicios
            for ($row = 6; $row < $resumenTituloRow; $row++) {
                $styles[$row] = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '4472c4']
                        ]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => false
                    ]
                ];

                // Specific alignment for each column type
                $styles["A{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Estado
                $styles["B{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Servicio
                $styles["C{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Solicitudes
                $styles["D{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Exámenes
                $styles["E{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Completados
                $styles["F{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Pendientes
                $styles["G{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Eficiencia
                $styles["H{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Fecha
            }

            // Estilo para título del resumen "RESUMEN POR ESTADO"
            $styles[$resumenTituloRow] = [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '4472c4']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];

            // Estilo para encabezados del resumen "Estado", "Cantidad Servicios", etc.
            $styles[$resumenHeaderRow] = [
                'font' => ['bold' => true, 'size' => 11],
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
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];

            // Estilos para filas de datos del resumen (Activos, Inactivos)
            for ($row = $resumenDataStartRow; $row < $totalGeneralRow; $row++) {
                $styles[$row] = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '4472c4']
                        ]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ];

                // Alineaciones específicas para el resumen
                $styles["A{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Estado
                $styles["B{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Cantidad
                $styles["C{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Solicitudes
                $styles["D{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Exámenes
                $styles["E{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Completados
                $styles["F{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Pendientes
                $styles["G{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Eficiencia
            }

            // Estilo especial para fila "TOTAL GENERAL"
            $styles[$totalGeneralRow] = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '4472c4']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Estado
            'B' => 40,  // Servicio
            'C' => 20,  // Solicitudes
            'D' => 15,  // Exámenes
            'E' => 20,  // Completados
            'F' => 20,  // Pendientes
            'G' => 25,  // Eficiencia
            'H' => 18   // Fecha Creación
        ];
    }
}
