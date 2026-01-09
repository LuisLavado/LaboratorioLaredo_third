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

class ExamsDetailByStatusSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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

    public function title(): string { return 'Detalle por Examen'; }

    public function headings(): array {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['DETALLE POR EXAMEN Y ESTADO'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['Examen', 'Categoría', 'Pendientes', 'En Proceso', 'Completados', 'Total', 'Eficiencia']
        ];
    }

    public function array(): array {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];
        foreach ($examenes as $examen) {
            $pendientes = $examen->pendientes ?? 0;
            $enProceso = $examen->en_proceso ?? 0;
            $completados = $examen->completados ?? 0;
            $total = $examen->total_realizados ?? 0;
            $eficiencia = $total > 0 ? round(($completados / $total) * 100, 2) : 0;
            $rows[] = [
                $examen->nombre ?? '',
                $examen->categoria ?? 'Sin Categoría',
                $pendientes,
                $enProceso,
                $completados,
                $total,
                $eficiencia . '%'
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
        return [ 'A' => 30, 'B' => 20, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15 ];
    }
}
