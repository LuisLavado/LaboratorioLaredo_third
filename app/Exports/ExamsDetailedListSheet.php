<?php

namespace App\Exports;

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
 * Hoja detallada de exámenes
 */
class ExamsDetailedListSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Listado Detallado';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['LISTADO DETALLADO DE EXÁMENES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'ID',
                'Código',
                'Nombre del Examen',
                'Categoría',
                'Total Realizados',
                'Total Pacientes',
                'Pendientes',
                'En Proceso',
                'Completados',
                'Eficiencia (%)',
                'Estado'
            ]
        ];
    }

    public function array(): array
    {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];

        if (empty($examenes)) {
            $rows[] = [
                'No hay exámenes disponibles para el período seleccionado.',
                '', '', '', '', '', '', '', '', '', ''
            ];
            return $rows;
        }

        // Ordenar exámenes por total realizados descendente
        $examenesSorted = collect($examenes)->sortByDesc(function($examen) {
            return $this->getProperty($examen, 'total_realizados', 0);
        });

        foreach ($examenesSorted as $examen) {
            $totalRealizados = $this->getProperty($examen, 'total_realizados', 0);
            $pendientes = $this->getProperty($examen, 'pendientes', 0);
            $enProceso = $this->getProperty($examen, 'en_proceso', 0);
            $completados = $this->getProperty($examen, 'completados', 0);
            
            // Calcular eficiencia (porcentaje de completados)
            $eficiencia = $totalRealizados > 0 ? round(($completados / $totalRealizados) * 100, 2) : 0;
            
            // Determinar estado basado en actividad
            $estado = 'Activo';
            if ($totalRealizados == 0) {
                $estado = 'Sin Actividad';
            } elseif ($totalRealizados < 5) {
                $estado = 'Baja Actividad';
            } elseif ($totalRealizados > 50) {
                $estado = 'Alta Demanda';
            }

            $rows[] = [
                $this->getProperty($examen, 'id', ''),
                $this->getProperty($examen, 'codigo', ''),
                $this->getProperty($examen, 'nombre', ''),
                $this->getProperty($examen, 'categoria', 'Sin Categoría'),
                $totalRealizados,
                $this->getProperty($examen, 'total_pacientes', 0),
                $pendientes,
                $enProceso,
                $completados,
                $eficiencia . '%',
                $estado
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
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
                'font' => ['bold' => true],
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
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 12,  // Código
            'C' => 35,  // Nombre del Examen
            'D' => 18,  // Categoría
            'E' => 15,  // Total Realizados
            'F' => 15,  // Total Pacientes
            'G' => 12,  // Pendientes
            'H' => 12,  // En Proceso
            'I' => 12,  // Completados
            'J' => 15,  // Eficiencia
            'K' => 18   // Estado
        ];
    }

    private function getProperty($item, $property, $default = null)
    {
        if (is_array($item)) {
            return $item[$property] ?? $default;
        } elseif (is_object($item)) {
            return $item->$property ?? $default;
        }
        return $default;
    }
}
