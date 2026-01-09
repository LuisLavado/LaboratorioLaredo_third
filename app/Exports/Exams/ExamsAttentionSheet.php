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

class ExamsAttentionSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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

    public function title(): string { return 'Exámenes Atención'; }

    public function headings(): array {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['EXÁMENES QUE REQUIEREN ATENCIÓN'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['Examen', 'Total', 'Pendientes', 'Completados', 'Eficiencia', 'Problema Identificado']
        ];
    }

    public function array(): array {
        $examenes = $this->data['examenes'] ?? [];
        $rows = [];
        $examenesBajoRendimiento = collect($examenes)->filter(function($examen) {
            $total = $examen->total_realizados ?? 0;
            $completados = $examen->completados ?? 0;
            $eficiencia = $total > 0 ? ($completados / $total) * 100 : 0;
            return $total > 0 && $eficiencia < 50;
        })->sortBy(function($examen) {
            $total = $examen->total_realizados ?? 0;
            $completados = $examen->completados ?? 0;
            return $total > 0 ? ($completados / $total) : 0;
        });
        if ($examenesBajoRendimiento->count() > 0) {
            foreach ($examenesBajoRendimiento as $examen) {
                $total = $examen->total_realizados ?? 0;
                $pendientes = $examen->pendientes ?? 0;
                $completados = $examen->completados ?? 0;
                $eficiencia = $total > 0 ? round(($completados / $total) * 100, 2) : 0;
                $problema = 'Alta acumulación de pendientes';
                if ($pendientes > $completados * 2) {
                    $problema = 'Exceso de exámenes pendientes';
                } elseif ($eficiencia < 25) {
                    $problema = 'Muy baja tasa de completado';
                } elseif ($eficiencia < 50) {
                    $problema = 'Proceso de laboratorio lento';
                }
                $rows[] = [
                    $examen->nombre ?? '',
                    $total,
                    $pendientes,
                    $completados,
                    $eficiencia . '%',
                    $problema
                ];
            }
        } else {
            $rows[] = ['No se encontraron exámenes con bajo rendimiento en este período.', '', '', '', '', ''];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet) {
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');
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
        return [ 'A' => 30, 'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 30 ];
    }
}
