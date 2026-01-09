<?php

namespace App\Exports\Patients;

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
 * Hoja de Top Pacientes Más Activos
 */
class PatientsTopSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $topPatients;
    protected $startDate;
    protected $endDate;

    public function __construct($topPatients, $startDate, $endDate)
    {
        $this->topPatients = $topPatients;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Top Pacientes Activos';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - TOP PACIENTES ACTIVOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            ['RANKING DE PACIENTES POR ACTIVIDAD'],
            [],
            [
                'Ranking',
                'Paciente',
                'Total Solicitudes',
                'Promedio Mensual',
                'Clasificación',
                'Porcentaje del Total',
                'Observaciones'
            ]
        ];
    }

    public function array(): array
    {
        $data = [];
        
        if (empty($this->topPatients)) {
            $data[] = [
                'No hay datos de pacientes disponibles para el período seleccionado.',
                '', '', '', '', '', ''
            ];
            return $data;
        }

        // Convertir Collection a array si es necesario
        $topPatientsArray = is_array($this->topPatients) 
            ? $this->topPatients 
            : $this->topPatients->toArray();

        // Calcular el total de solicitudes para porcentajes
        $totalSolicitudes = array_sum(array_column($topPatientsArray, 'count'));
        
        // Calcular meses en el período para promedio mensual
        $mesesPeriodo = $this->calculateMonthsInPeriod();

        foreach ($topPatientsArray as $index => $patient) {
            $solicitudes = $patient['count'] ?? 0;
            $percentage = $totalSolicitudes > 0 ? number_format(($solicitudes / $totalSolicitudes) * 100, 1) : 0;
            $promedioMensual = $mesesPeriodo > 0 ? number_format($solicitudes / $mesesPeriodo, 1) : 0;
            
            $data[] = [
                $index + 1,
                $patient['name'] ?? 'Sin nombre',
                $solicitudes,
                $promedioMensual,
                $this->getActivityClassification($solicitudes),
                $percentage . '%',
                $this->getObservations($solicitudes, $promedioMensual)
            ];
        }

        // Agregar estadísticas adicionales al final
        $data[] = [];
        $data[] = ['ESTADÍSTICAS DEL RANKING'];
        $data[] = ['Total de pacientes en ranking:', count($topPatientsArray)];
        $data[] = ['Total de solicitudes:', $totalSolicitudes];
        $data[] = ['Promedio de solicitudes por paciente:', $totalSolicitudes > 0 ? number_format($totalSolicitudes / count($topPatientsArray), 1) : 0];
        $data[] = ['Período analizado (meses):', $mesesPeriodo];

        return $data;
    }

    private function calculateMonthsInPeriod(): float
    {
        $diffInDays = $this->startDate->diffInDays($this->endDate);
        return round($diffInDays / 30.44, 1); // Promedio de días por mes
    }

    private function getActivityClassification($solicitudes): string
    {
        if ($solicitudes >= 20) return 'Muy Alta';
        if ($solicitudes >= 10) return 'Alta';
        if ($solicitudes >= 5) return 'Media';
        if ($solicitudes >= 2) return 'Baja';
        return 'Mínima';
    }

    private function getObservations($solicitudes, $promedioMensual): string
    {
        if ($solicitudes >= 20) {
            return 'Paciente de alta frecuencia - Revisar historial';
        }
        if ($solicitudes >= 10) {
            return 'Paciente frecuente - Monitoreo regular';
        }
        if ($solicitudes >= 5) {
            return 'Actividad regular';
        }
        if ($solicitudes >= 2) {
            return 'Actividad ocasional';
        }
        return 'Actividad mínima';
    }

    public function styles(Worksheet $sheet)
    {
        // Título principal
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f4e79']
            ]
        ]);
        
        // Período
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472c4']
            ]
        ]);
        
        // Títulos de secciones
        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Encabezados de tabla
        $sheet->getStyle('A6:G6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Mergear celdas de títulos
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A4:G4');
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Ranking
            'B' => 25,  // Paciente
            'C' => 15,  // Total Solicitudes
            'D' => 18,  // Promedio Mensual
            'E' => 15,  // Clasificación
            'F' => 18,  // Porcentaje del Total
            'G' => 30   // Observaciones
        ];
    }
}
