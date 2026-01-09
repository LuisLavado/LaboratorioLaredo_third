<?php

namespace App\Exports\Services;

use Maatwebsite\Excel\Concerns\FromCollection;
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
 * Hoja de Resumen Ejecutivo - Servicios (Independiente)
 */
class ServicesSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
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
        return 'Resumen Ejecutivo';
    }

    public function headings(): array
    {
        return ['Indicador', 'Valor', 'Detalle'];
    }

    public function collection()
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

        $totalServicios = count($servicios);
        $totalSolicitudes = 0;
        $totalIngresos = 0;
        $servicioTop = null;
        $maxSolicitudes = 0;

        // Procesar servicios de manera segura
        foreach ($servicios as $servicio) {
            // Convertir objeto a array si es necesario
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
            
            $solicitudes = (int)($servicio['total_solicitudes'] ?? 0);
            $precio = (float)($servicio['precio'] ?? 0);
            
            $totalSolicitudes += $solicitudes;
            $totalIngresos += ($solicitudes * $precio);
            
            if ($solicitudes > $maxSolicitudes) {
                $maxSolicitudes = $solicitudes;
                $servicioTop = $servicio;
            }
        }

        $promedioSolicitudes = $totalServicios > 0 ? round($totalSolicitudes / $totalServicios, 2) : 0;
        $ingresoPromedio = $totalServicios > 0 ? round($totalIngresos / $totalServicios, 2) : 0;

        // Contar servicios por actividad
        $altaActividad = 0;
        $mediaActividad = 0;
        $bajaActividad = 0;
        $sinActividad = 0;

        foreach ($servicios as $servicio) {
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
            
            $solicitudes = (int)($servicio['total_solicitudes'] ?? 0);
            
            if ($solicitudes > 20) {
                $altaActividad++;
            } elseif ($solicitudes >= 6) {
                $mediaActividad++;
            } elseif ($solicitudes >= 1) {
                $bajaActividad++;
            } else {
                $sinActividad++;
            }
        }

        // Construir datos del resumen
        $data = collect([
            ['RESUMEN EJECUTIVO DE SERVICIOS', '', ''],
            ['Período', ($this->startDate ?? 'N/A') . ' al ' . ($this->endDate ?? 'N/A'), ''],
            ['Generado', date('d/m/Y H:i:s'), ''],
            ['', '', ''],
            
            ['ESTADÍSTICAS GENERALES', '', ''],
            ['Total de Servicios Activos', $totalServicios, 'Servicios disponibles en el sistema'],
            ['Total de Solicitudes', $totalSolicitudes, 'Solicitudes realizadas en el período'],
            ['Promedio por Servicio', $promedioSolicitudes, 'Solicitudes promedio por servicio'],
            ['Ingresos Totales', 'S/ ' . number_format($totalIngresos, 2), 'Ingresos estimados del período'],
            ['Ingreso Promedio', 'S/ ' . number_format($ingresoPromedio, 2), 'Ingreso promedio por servicio'],
            ['', '', ''],

            ['SERVICIO MÁS SOLICITADO', '', ''],
            ['Nombre', $servicioTop['nombre'] ?? 'N/A', 'Servicio con mayor demanda'],
            ['Solicitudes', $maxSolicitudes, 'Número de solicitudes'],
            ['Porcentaje del Total', $totalSolicitudes > 0 ? round(($maxSolicitudes / $totalSolicitudes) * 100, 2) . '%' : '0%', 'Participación en el total'],
            ['', '', ''],

            ['DISTRIBUCIÓN POR ACTIVIDAD', '', ''],
            ['Alta actividad (>20)', $altaActividad, round($totalServicios > 0 ? ($altaActividad / $totalServicios) * 100 : 0, 1) . '%'],
            ['Media actividad (6-20)', $mediaActividad, round($totalServicios > 0 ? ($mediaActividad / $totalServicios) * 100 : 0, 1) . '%'],
            ['Baja actividad (1-5)', $bajaActividad, round($totalServicios > 0 ? ($bajaActividad / $totalServicios) * 100 : 0, 1) . '%'],
            ['Sin actividad (0)', $sinActividad, round($totalServicios > 0 ? ($sinActividad / $totalServicios) * 100 : 0, 1) . '%'],
            ['', '', ''],

            ['TOP 5 SERVICIOS MÁS SOLICITADOS', '', ''],
            ['Posición', 'Servicio', 'Solicitudes']
        ]);

        // Agregar top 5 servicios
        $serviciosOrdenados = collect($servicios)->sortByDesc(function($servicio) {
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
            return (int)($servicio['total_solicitudes'] ?? 0);
        })->take(5);

        $posicion = 1;
        foreach ($serviciosOrdenados as $servicio) {
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
            
            $solicitudesServicio = (int)($servicio['total_solicitudes'] ?? 0);
            $porcentaje = $totalSolicitudes > 0 ? round(($solicitudesServicio / $totalSolicitudes) * 100, 2) . '%' : '0%';
            
            $data->push([
                $posicion++,
                $servicio['nombre'] ?? 'Sin nombre',
                $solicitudesServicio . ' (' . $porcentaje . ')'
            ]);
        }

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
            'C' => 35,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E7D32']
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
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Estilo para títulos de sección
        $lastRow = $this->collection()->count() + 1;
        for ($row = 2; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();
            
            if (str_contains($cellValue, 'RESUMEN EJECUTIVO') || 
                str_contains($cellValue, 'ESTADÍSTICAS GENERALES') ||
                str_contains($cellValue, 'SERVICIO MÁS SOLICITADO') ||
                str_contains($cellValue, 'DISTRIBUCIÓN') ||
                str_contains($cellValue, 'TOP 5')) {
                
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F5E8']
                    ]
                ]);
            }
        }

        // Bordes para todas las celdas
        $sheet->getStyle("A2:C{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        return $sheet;
    }
}
