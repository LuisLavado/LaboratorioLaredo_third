<?php
namespace App\Exports\Exams;

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

class ExamsTrendsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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

    public function title(): string { return 'Tendencias'; }

    public function headings(): array {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['ANÁLISIS DE TENDENCIAS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['Categoría', 'Tipos Examen', 'Vol. Total', 'Completados', 'Eficiencia', 'Demanda', 'Tendencia']
        ];
    }

    public function array(): array {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];
        $categorias = [];
        foreach ($examenes as $examen) {
            $categoria = $examen->categoria ?? 'Sin Categoría';
            if (!isset($categorias[$categoria])) {
                $categorias[$categoria] = [
                    'cantidad_examenes' => 0,
                    'total_realizados' => 0,
                    'completados' => 0,
                    'pacientes' => 0
                ];
            }
            $categorias[$categoria]['cantidad_examenes']++;
            $categorias[$categoria]['total_realizados'] += $examen->total_realizados ?? 0;
            $categorias[$categoria]['completados'] += $examen->completados ?? 0;
            $categorias[$categoria]['pacientes'] += $examen->total_pacientes ?? 0;
        }
        foreach ($categorias as $nombreCategoria => $stats) {
            $eficiencia = $stats['total_realizados'] > 0 ? round(($stats['completados'] / $stats['total_realizados']) * 100, 2) : 0;
            $demanda = $stats['total_realizados'] > 0 ? 'Alta' : 'Baja';
            if ($stats['total_realizados'] < 10) $demanda = 'Muy Baja';
            elseif ($stats['total_realizados'] < 50) $demanda = 'Baja';
            elseif ($stats['total_realizados'] < 100) $demanda = 'Media';
            elseif ($stats['total_realizados'] < 200) $demanda = 'Alta';
            else $demanda = 'Muy Alta';
            $tendencia = 'Estable';
            if ($eficiencia > 85 && $stats['total_realizados'] > 50) $tendencia = 'Creciente';
            elseif ($eficiencia < 50) $tendencia = 'Decreciente';
            $rows[] = [
                $nombreCategoria,
                $stats['cantidad_examenes'],
                $stats['total_realizados'],
                $stats['completados'],
                $eficiencia . '%',
                $demanda,
                $tendencia
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet) {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1f4e79'] ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 16]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2f5f8f'] ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 14]
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472c4'] ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            5 => [
                'font' => ['bold' => true],
                'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dae3f3'] ],
                'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '4472c4'] ] ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array {
        return [ 'A' => 25, 'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 20, 'G' => 20 ];
    }
}
