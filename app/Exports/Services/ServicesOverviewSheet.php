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
use Exception;
use App\Exports\Services\ServiceSheetHelper;

/**
 */
class ServicesOverviewSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    use ServiceSheetHelper;

    protected $services;
    protected $startDate;
    protected $endDate;

    public function __construct($services, $startDate, $endDate)
    {
        $this->services = $services;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Servicios General';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['VISTA GENERAL DE SERVICIOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [

                'Nombre del Servicio',
                'Estado',
                'Total de Solicitudes',
                'Pacientes Únicos',
                'Promedio Solicitudes/Paciente',
                'Fecha Creación',
                'Última Actualización'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];

        if (empty($this->services)) {
            $rows[] = [
                'No hay servicios disponibles para el período seleccionado.',
                '', '', '', '', '', '', ''
            ];
            return $rows;
        }

        foreach ($this->services as $service) {
            $totalSolicitudes = $this->getProperty($service, 'total_solicitudes', 0);
            $pacientesUnicos = $this->getProperty($service, 'pacientes_unicos', 0);
            $promedioSolicitudes = $pacientesUnicos > 0 ? round($totalSolicitudes / $pacientesUnicos, 2) : 0;

            $rows[] = [

                $this->getProperty($service, 'nombre', ''),
                $this->getProperty($service, 'activo', false) ? 'Activo' : 'Inactivo',
                $totalSolicitudes,
                $pacientesUnicos,
                $promedioSolicitudes,
                $this->formatDate($this->getProperty($service, 'created_at')),
                $this->formatDate($this->getProperty($service, 'updated_at'))
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Calcular el número real de filas basado en los datos
        $dataRows = $this->array();
        $totalDataRows = count($dataRows);
        $totalRows = $totalDataRows + 5; // 5 filas de header

        $sheet->mergeCells('A1:G1'); // Título principal
        $sheet->mergeCells('A2:G2'); // Subtítulo
        $sheet->mergeCells('A3:G3'); // Período

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
        if ($totalDataRows > 0) {
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
                $styles["E{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Promedio
                $styles["F{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Fecha Creación
                $styles["G{$row}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]; // Última Actualización
            }
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,  // Nombre del Servicio
            'B' => 15,  // Estado
            'C' => 20,  // Total Solicitudes
            'D' => 18,  // Pacientes Únicos
            'E' => 25,  // Promedio Solicitudes/Paciente
            'F' => 18,  // Fecha Creación
            'G' => 22   // Última Actualización
        ];
    }

    /**
     * Helper method to format dates safely
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return '';
        }
        
        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (Exception $e) {
            return '';
        }
    }
}
