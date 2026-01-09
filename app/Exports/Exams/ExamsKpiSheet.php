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

class ExamsKpiSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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

    public function title(): string { return 'KPIs Exámenes'; }

    public function headings(): array {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['INDICADORES CLAVE DE RENDIMIENTO (KPIs)'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['KPI', 'Valor', 'Interpretación']
        ];
    }

    public function array(): array {
        $examenes = $this->data['examenes'] ?? [];
        $totales = $this->data['totales'] ?? [];
        $rows = [];
        $totalExamenes = count($examenes);
        $totalRealizados = array_sum(array_map(function($examen) { return $examen->total_realizados ?? 0; }, $examenes));
        $totalCompletados = array_sum(array_map(function($examen) { return $examen->completados ?? 0; }, $examenes));
        $totalPacientes = $totales['pacientes'] ?? 0;
        $rows[] = [
            'Eficiencia General',
            $totalRealizados > 0 ? round(($totalCompletados / $totalRealizados) * 100, 2) . '%' : '0%',
            'Porcentaje de exámenes completados exitosamente'
        ];
        $rows[] = [
            'Productividad por Examen',
            $totalExamenes > 0 ? round($totalRealizados / $totalExamenes, 2) : 0,
            'Promedio de realizaciones por tipo de examen'
        ];
        $rows[] = [
            'Cobertura de Pacientes',
            $totalPacientes > 0 ? round($totalRealizados / $totalPacientes, 2) : 0,
            'Promedio de exámenes por paciente'
        ];
        $rows[] = [
            'Diversidad de Servicios',
            $totalExamenes,
            'Cantidad de tipos de exámenes diferentes'
        ];
        return $rows;
    }

    public function styles(Worksheet $sheet) {
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A3:C3');
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
        return [ 'A' => 30, 'B' => 20, 'C' => 40 ];
    }
}
