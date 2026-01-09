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

class ExamsTop10Sheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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

    public function title(): string { return 'Top 10 Exámenes'; }

    public function headings(): array {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['TOP 10 EXÁMENES DE ALTO RENDIMIENTO'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['Pos.', 'Examen', 'Total', 'Completados', 'Eficiencia', 'Categoría', 'Calificación']
        ];
    }

    public function array(): array {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];
        $examenesSorted = collect($examenes)->filter(function($examen) {
            return ($examen->total_realizados ?? 0) > 0;
        })->sortByDesc(function($examen) {
            $total = $examen->total_realizados ?? 0;
            $completados = $examen->completados ?? 0;
            return $total > 0 ? ($completados / $total) : 0;
        })->take(10);
        $position = 1;
        foreach ($examenesSorted as $examen) {
            $total = $examen->total_realizados ?? 0;
            $completados = $examen->completados ?? 0;
            $eficiencia = $total > 0 ? round(($completados / $total) * 100, 2) : 0;
            $calificacion = 'Excelente';
            if ($eficiencia < 90) $calificacion = 'Bueno';
            if ($eficiencia < 75) $calificacion = 'Regular';
            if ($eficiencia < 50) $calificacion = 'Bajo';
            $rows[] = [
                $position++, $examen->nombre ?? '', $total, $completados, $eficiencia . '%', $examen->categoria ?? 'Sin Categoría', $calificacion
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
        return [ 'A' => 8, 'B' => 30, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 20, 'G' => 20 ];
    }
}
