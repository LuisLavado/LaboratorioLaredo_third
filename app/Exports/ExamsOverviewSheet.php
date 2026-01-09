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
 * Hoja de Vista General de Exámenes
 */
class ExamsOverviewSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $exams;
    protected $startDate;
    protected $endDate;

    public function __construct($exams, $startDate, $endDate)
    {
        $this->exams = $exams;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Exámenes General';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['VISTA GENERAL DE EXÁMENES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'ID Examen',
                'Nombre del Examen',
                'Categoría',
                'Tipo',
                'Estado',
                'Valor Normal',
                'Unidad',
                'Método',
                'Total Realizados',
                'Fecha Creación',
                'Última Actualización'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];

        if (empty($this->exams)) {
            $rows[] = [
                'No hay exámenes disponibles para el período seleccionado.',
                '', '', '', '', '', '', '', '', '', ''
            ];
            return $rows;
        }

        foreach ($this->exams as $exam) {
            $rows[] = [
                $exam->id ?? '',
                $exam->nombre ?? '',
                $exam->categoria ?? '',
                $exam->tipo ?? '',
                $exam->estado ?? 'Activo',
                $exam->valor_normal ?? '',
                $exam->unidad ?? '',
                $exam->metodo ?? '',
                $exam->total_realizados ?? 0,
                isset($exam->created_at) ? Carbon::parse($exam->created_at)->format('d/m/Y') : '',
                isset($exam->updated_at) ? Carbon::parse($exam->updated_at)->format('d/m/Y') : ''
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 16]
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 14]
            ],
            // Período
            3 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472c4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
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
            'A' => 12,  // ID Examen
            'B' => 30,  // Nombre del Examen
            'C' => 20,  // Categoría
            'D' => 15,  // Tipo
            'E' => 12,  // Estado
            'F' => 20,  // Valor Normal
            'G' => 10,  // Unidad
            'H' => 20,  // Método
            'I' => 15,  // Total Realizados
            'J' => 15,  // Fecha Creación
            'K' => 15   // Última Actualización
        ];
    }
}
