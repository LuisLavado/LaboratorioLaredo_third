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
use Carbon\Carbon;

/**
 * Hoja de Lista Detallada - Servicios (Independiente)
 */
class ServicesDetailSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
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
            ['LABORATORIO CLÍNICO LAREDO'],
            ['SERVICIOS - LISTA DETALLADA'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'Nombre del Servicio',
                'Estado',
                'Total Solicitudes',
                'Pacientes Únicos',
                'Exámenes Totales',
                'Completados',
                'Pendientes',
                'Eficiencia (%)',
                'Promedio/Paciente',
                'Popularidad (%)',
                'Fecha Creación'
            ]
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

        // Si no hay servicios, retornar fila de mensaje
        if (empty($servicios)) {
            return [[
                'No hay servicios disponibles',
                'Sin datos',
                0,
                0,
                0,
                0,
                0,
                '0%',
                '0.00',
                '0%',
                'N/A'
            ]];
        }

        // Calcular total de solicitudes una sola vez
        $totalSolicitudes = 0;
        foreach ($servicios as $servicio) {
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
            $totalSolicitudes += (int)($servicio['total_solicitudes'] ?? 0);
        }
        
        if ($totalSolicitudes == 0) {
            $totalSolicitudes = 1; // Evitar división por cero
        }

        $rows = [];
        foreach ($servicios as $servicio) {
            // Convertir objeto a array si es necesario
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
            
            // Extraer datos básicos
            $nombre = $servicio['nombre_completo'] ?? $servicio['nombre'] ?? $servicio['name'] ?? 'Sin nombre';
            $solicitudes = (int)($servicio['total_solicitudes'] ?? $servicio['count'] ?? 0);
            $pacientes = (int)($servicio['total_pacientes_unicos'] ?? $servicio['total_pacientes'] ?? $servicio['unique_patients'] ?? 0);
            $examenes = (int)($servicio['total_examenes'] ?? $servicio['exams_count'] ?? 0);
            $completados = (int)($servicio['completados'] ?? 0);
            $pendientes = (int)($servicio['pendientes'] ?? 0);
            $activo = $servicio['activo'] ?? true;
            $fechaCreacion = $servicio['created_at'] ?? '';

            // Calcular métricas
            $promedioPorPaciente = $pacientes > 0 ? round($solicitudes / $pacientes, 2) : 0;
            $popularidad = $totalSolicitudes > 0 ? round(($solicitudes / $totalSolicitudes) * 100, 2) : 0;
            $eficiencia = $examenes > 0 ? round(($completados / $examenes) * 100, 1) : 0;

            // Determinar estado
            if (!$activo) {
                $estado = 'Inactivo';
            } elseif ($solicitudes == 0) {
                $estado = 'Sin Actividad';
            } elseif ($solicitudes < 5) {
                $estado = 'Baja Demanda';
            } elseif ($solicitudes > 20) {
                $estado = 'Alta Demanda';
            } else {
                $estado = 'Activo';
            }

            // Formatear fecha de creación
            $fechaFormateada = '';
            if ($fechaCreacion) {
                try {
                    $fechaFormateada = \Carbon\Carbon::parse($fechaCreacion)->format('d/m/Y');
                } catch (\Exception $e) {
                    $fechaFormateada = 'N/A';
                }
            }

            $rows[] = [
                $nombre,
                $estado,
                $solicitudes,
                $pacientes,
                $examenes,
                $completados,
                $pendientes,
                $eficiencia . '%',
                number_format($promedioPorPaciente, 2),
                $popularidad . '%',
                $fechaFormateada
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,  // Nombre del Servicio
            'B' => 15,  // Estado
            'C' => 15,  // Total Solicitudes
            'D' => 15,  // Pacientes Únicos
            'E' => 15,  // Exámenes Totales
            'F' => 15,  // Completados
            'G' => 15,  // Pendientes
            'H' => 15,  // Eficiencia
            'I' => 15,  // Promedio/Paciente
            'J' => 15,  // Popularidad
            'K' => 18,  // Fecha Creación
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Get the total number of rows
        $servicios = $this->data['servicios'] ?? [];
        if ($servicios instanceof Collection) {
            $servicios = $servicios->toArray();
        }
        $totalRows = count($servicios) + 5; // 5 header rows

        $sheet->mergeCells('A1:K1'); // Título principal
        $sheet->mergeCells('A2:K2'); // Subtítulo
        $sheet->mergeCells('A3:K3'); // Período

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

        // Apply styles to data rows
        for ($row = 6; $row <= $totalRows; $row++) {
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
            $styles["A{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];   // Nombre
            $styles["B{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Estado
            $styles["C{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Solicitudes
            $styles["D{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Pacientes
            $styles["E{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Exámenes
            $styles["F{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Completados
            $styles["G{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Pendientes
            $styles["H{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Eficiencia
            $styles["I{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Promedio
            $styles["J{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Popularidad
            $styles["K{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Fecha
        }

        return $styles;
    }
}
