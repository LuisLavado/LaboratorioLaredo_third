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
 * Hoja de Lista Detallada - Servicios (Versión Optimizada)
 * Cada fila contiene información completa del servicio sin procesamiento pesado
 */
class ServicesImprovedDetailSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
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
        return 'Lista Detallada';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre del Servicio',
            'Descripción',
            'Estado',
            'Total Solicitudes',
            'Pacientes Únicos',
            'Promedio/Paciente',
            'Primera Solicitud',
            'Última Solicitud',
            'Exámenes',
            'Popularidad (%)',
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

        // Si no hay servicios, retornar fila de mensaje
        if (empty($servicios)) {
            return [[
                'N/A',
                'No hay servicios disponibles',
                'No se encontraron servicios en el período especificado',
                'Sin datos',
                0,
                0,
                '0.00',
                '0.00',
                'N/A',
                'N/A',
                0,
                '0.00',
                'Verificar configuración de servicios'
            ]];
        }

        // Calcular total de solicitudes una sola vez
        $totalSolicitudes = array_sum(array_column($servicios, 'total_solicitudes')) ?: 1;

        $rows = [];
        foreach ($servicios as $servicio) {
            // Extraer datos básicos
            $id = $servicio['id'] ?? 'N/A';
            $nombre = $servicio['nombre'] ?? $servicio['name'] ?? 'Sin nombre';
            $descripcion = $servicio['descripcion'] ?? $servicio['description'] ?? '';
            $solicitudes = (int)($servicio['total_solicitudes'] ?? $servicio['count'] ?? 0);
            $pacientes = (int)($servicio['total_pacientes'] ?? $servicio['unique_patients'] ?? 0);
            $examenes = (int)($servicio['total_examenes'] ?? $servicio['exams_count'] ?? 0);

            // Calcular métricas simples
            $promedioPorPaciente = $pacientes > 0 ? round($solicitudes / $pacientes, 2) : 0;
            $popularidad = round(($solicitudes / $totalSolicitudes) * 100, 2);

            // Determinar estado
            $estado = 'Activo';
            if ($solicitudes == 0) {
                $estado = 'Sin Actividad';
            } elseif ($solicitudes < 5) {
                $estado = 'Baja Demanda';
            } elseif ($solicitudes > 50) {
                $estado = 'Alta Demanda';
            }

            // Fechas (simplificadas)
            $primeraFecha = $servicio['primera_solicitud'] ?? 'N/A';
            $ultimaFecha = $servicio['ultima_solicitud'] ?? 'N/A';

            // Observaciones simples
            $observaciones = '';
            if ($popularidad > 20) {
                $observaciones = 'Servicio muy solicitado';
            } elseif ($popularidad < 2) {
                $observaciones = 'Revisar demanda';
            } elseif ($examenes > 10) {
                $observaciones = 'Servicio complejo';
            }

            $rows[] = [
                $id,
                $nombre,
                $descripcion ? substr($descripcion, 0, 100) : 'Sin descripción',
                $estado,
                $solicitudes,
                $pacientes,
                number_format($promedioPorPaciente, 2),
                $primeraFecha,
                $ultimaFecha,
                $examenes,
                number_format($popularidad, 2),
                $observaciones
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 25,  // Nombre
            'C' => 30,  // Descripción
            'D' => 15,  // Estado
            'E' => 12,  // Solicitudes
            'F' => 12,  // Pacientes
            'G' => 12,  // Promedio
            'H' => 15,  // Primera fecha
            'I' => 15,  // Última fecha
            'J' => 10,  // Exámenes
            'K' => 12,  // Popularidad
            'L' => 20,  // Observaciones
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
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
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Estilo de las celdas de datos
        $lastRow = count($this->array()) + 1;
        if ($lastRow > 1) {
            $sheet->getStyle("A2:L{$lastRow}")->applyFromArray([
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
            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // ID
            $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Estado
            $sheet->getStyle("E2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Números
            $sheet->getStyle("K2:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Números

            // Colores alternados en filas
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle("A{$row}:L{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F8F9FA');
                }
            }
        }

        return $sheet;
    }
}
