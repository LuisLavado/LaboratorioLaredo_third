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
use Exception;
use App\Exports\Services\ServiceSheetHelper;

/**
 * Hoja de Análisis de Rendimiento - Servicios (Independiente)
 */
class ServicesPerformanceSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
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
        return 'Análisis Rendimiento';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['ANÁLISIS DE RENDIMIENTO DE SERVICIOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'Ranking',
                'Servicio',
                'Métrica',
                'Valor',
                'Solicitudes',
                'Observaciones'
            ]
        ];
    }

    public function array(): array
    {
        $servicios = $this->data['servicios'] ?? [];
        if ($servicios instanceof Collection) {
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

                'Sin datos'
            ]];
        }

        $rows = [];
        $serviciosConMetricas = [];
        foreach ($servicios as $servicio) {
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
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

        $topSolicitudes = $serviciosConMetricas;
        foreach ($topSolicitudes as $index => $servicio) {
            $observaciones = $this->getObservaciones($servicio, 'solicitudes');
            $rows[] = [
                $index + 1,
                $servicio['nombre'],
                'Más Solicitado',
                number_format($servicio['solicitudes']),
                $servicio['solicitudes'],

                $observaciones
            ];
        }

        $rows[] = ['', '', '', '', '', '', '', ''];

        $topRatio = array_filter($serviciosConMetricas, fn($s) => $s['ratio'] > 0);
        usort($topRatio, fn($a, $b) => $b['ratio'] <=> $a['ratio']);
        $topRatio = array_slice($topRatio, 0, 5);

        foreach ($topRatio as $index => $servicio) {
            $observaciones = $this->getObservaciones($servicio, 'ratio');
            $rows[] = [
                $index + 1,
                $servicio['nombre'],
                'Mayor Fidelidad',
                number_format($servicio['ratio'], 2) . 'x',
                $servicio['solicitudes'],

                $observaciones
            ];
        }

        return $rows;
    }

    private function getObservaciones($servicio, $tipo)
    {
        switch ($tipo) {
            case 'solicitudes':
                if ($servicio['solicitudes'] > 30 && $servicio['ratio'] > 2) return 'Servicio estrella';
                if ($servicio['solicitudes'] > 20 && $servicio['pacientes'] < 10) return 'Alta repetición';
                return 'Servicio popular';
            case 'ratio':
                if ($servicio['ratio'] > 3) return 'Muy fidelizado';
                if ($servicio['ratio'] > 2) return 'Buena fidelidad';
                return 'Repetición moderada';
        }
        return '';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 25,
            'C' => 18,
            'D' => 15,
            'E' => 15,
            'F' => 20,
            'G' => 20,
            'H' => 25
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');

        $lastRow = count($this->array()) + 5;
        if ($lastRow > 5) {
            $sheet->getStyle("A6:F{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            for ($row = 6; $row <= $lastRow; $row++) {
                $cellValue = $sheet->getCell("B{$row}")->getValue();
                if (empty($cellValue)) {
                    $sheet->getStyle("A{$row}:F{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E0E0E0');
                } elseif ($row % 2 == 0) {
                    $sheet->getStyle("A{$row}:F{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F3E5F5');
                }
            }
        }

        return [
            1 => [
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ]
            ],
            2 => [
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ]
            ],
            3 => [
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472c4']
                ]
            ],
            5 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '673AB7']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]
        ];
    }
}
