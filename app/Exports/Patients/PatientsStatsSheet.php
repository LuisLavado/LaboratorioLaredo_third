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
 * Hoja de Estadísticas Detalladas de Pacientes
 */
class PatientsStatsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $reportData;
    protected $startDate;
    protected $endDate;

    public function __construct($reportData, $startDate, $endDate)
    {
        $this->reportData = $reportData;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Estadísticas Detalladas';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - ESTADÍSTICAS DETALLADAS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            ['ANÁLISIS ESTADÍSTICO'],
            []
        ];
    }

    public function array(): array
    {
        $data = [];
        $patients = $this->reportData['patients'] ?? [];

        // Estadísticas generales
        $data[] = ['ESTADÍSTICAS GENERALES'];
        $data[] = ['Métrica', 'Valor', 'Porcentaje', 'Observaciones'];
        $data[] = ['Total Pacientes', count($patients), '100%', 'Base de cálculo'];
        $data[] = ['Pacientes con Solicitudes', $this->reportData['totalPatients'] ?? 0, '', 'En el período'];
        $data[] = ['Promedio Solicitudes/Paciente', $this->calculateAverage(), '', 'Actividad promedio'];
        $data[] = [];

        // Estados de exámenes detallado
        if (isset($this->reportData['examStatusStats']) && !empty($this->reportData['examStatusStats'])) {
            $data[] = ['ESTADOS DE EXÁMENES'];
            $data[] = ['Estado', 'Cantidad', 'Porcentaje', 'Descripción'];
            
            $totalExams = $this->reportData['totalExams'] ?? 1;
            foreach ($this->reportData['examStatusStats'] as $status) {
                $count = $status['count'] ?? 0;
                $percentage = $totalExams > 0 ? number_format(($count / $totalExams) * 100, 1) . '%' : '0%';
                $data[] = [
                    $status['name'] ?? 'No definido',
                    $count,
                    $percentage,
                    $this->getStatusDescription($status['name'] ?? '')
                ];
            }
            $data[] = [];
        }

        // Top 10 pacientes más activos
        if (isset($this->reportData['topPatients']) && !empty($this->reportData['topPatients'])) {
            $data[] = ['TOP 10 PACIENTES MÁS ACTIVOS'];
            $data[] = ['Ranking', 'Paciente', 'Solicitudes', 'Porcentaje', 'Clasificación'];
            
            $totalRequests = $this->reportData['totalRequests'] ?? 1;
            $topPatientsArray = is_array($this->reportData['topPatients']) 
                ? $this->reportData['topPatients'] 
                : $this->reportData['topPatients']->toArray();
            $topPatients = array_slice($topPatientsArray, 0, 10);
            
            foreach ($topPatients as $index => $patient) {
                $count = $patient['count'] ?? 0;
                $percentage = $totalRequests > 0 ? number_format(($count / $totalRequests) * 100, 1) . '%' : '0%';
                $classification = $this->getActivityClassification($count);
                
                $data[] = [
                    $index + 1,
                    $patient['name'] ?? 'Sin nombre',
                    $count,
                    $percentage,
                    $classification
                ];
            }
            $data[] = [];
        }

        // Análisis de actividad por rangos
        $data[] = ['ANÁLISIS DE ACTIVIDAD'];
        $data[] = ['Rango de Actividad', 'Pacientes', 'Porcentaje', 'Descripción'];
        $activityRanges = $this->calculateActivityRanges($patients);
        foreach ($activityRanges as $range) {
            $data[] = [
                $range['name'],
                $range['count'],
                $range['percentage'],
                $range['description']
            ];
        }

        return $data;
    }

    private function calculateAverage(): string
    {
        $totalPatients = $this->reportData['totalPatients'] ?? 0;
        $totalRequests = $this->reportData['totalRequests'] ?? 0;
        
        if ($totalPatients > 0) {
            return number_format($totalRequests / $totalPatients, 2);
        }
        
        return '0';
    }

    private function getStatusDescription($status): string
    {
        $descriptions = [
            'Completados' => 'Exámenes finalizados y con resultados',
            'Pendientes' => 'Exámenes en espera de procesamiento',
            'En Proceso' => 'Exámenes siendo procesados',
            'Fuera de Rango' => 'Resultados que requieren atención'
        ];

        return $descriptions[$status] ?? 'Estado no definido';
    }

    private function getActivityClassification($count): string
    {
        if ($count >= 10) return 'Muy Alta';
        if ($count >= 5) return 'Alta';
        if ($count >= 3) return 'Media';
        if ($count >= 1) return 'Baja';
        return 'Sin actividad';
    }

    private function calculateActivityRanges($patients): array
    {
        $ranges = [
            'Sin actividad' => 0,
            'Baja (1-2)' => 0,
            'Media (3-4)' => 0,
            'Alta (5-9)' => 0,
            'Muy Alta (10+)' => 0
        ];

        $totalPatients = count($patients);

        foreach ($patients as $patient) {
            $solicitudes = $patient->total_solicitudes ?? 0;
            
            if ($solicitudes == 0) {
                $ranges['Sin actividad']++;
            } elseif ($solicitudes <= 2) {
                $ranges['Baja (1-2)']++;
            } elseif ($solicitudes <= 4) {
                $ranges['Media (3-4)']++;
            } elseif ($solicitudes <= 9) {
                $ranges['Alta (5-9)']++;
            } else {
                $ranges['Muy Alta (10+)']++;
            }
        }

        $result = [];
        foreach ($ranges as $name => $count) {
            $percentage = $totalPatients > 0 ? number_format(($count / $totalPatients) * 100, 1) . '%' : '0%';
            $result[] = [
                'name' => $name,
                'count' => $count,
                'percentage' => $percentage,
                'description' => $this->getRangeDescription($name)
            ];
        }

        return $result;
    }

    private function getRangeDescription($range): string
    {
        $descriptions = [
            'Sin actividad' => 'Pacientes sin solicitudes en el período',
            'Baja (1-2)' => 'Pacientes con actividad mínima',
            'Media (3-4)' => 'Pacientes con actividad regular',
            'Alta (5-9)' => 'Pacientes con alta actividad',
            'Muy Alta (10+)' => 'Pacientes con actividad muy frecuente'
        ];

        return $descriptions[$range] ?? '';
    }

    public function styles(Worksheet $sheet)
    {
        // Título principal
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f4e79']
            ]
        ]);
        
        // Período
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472c4']
            ]
        ]);
        
        // Título principal de análisis
        $sheet->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Estilos para títulos de secciones
        $arrayData = $this->array();
        $rowOffset = 6; // Fila 6 es donde empiezan los datos
        
        for ($i = 0; $i < count($arrayData); $i++) {
            $currentRow = $rowOffset + $i;
            $cellValue = isset($arrayData[$i][0]) ? $arrayData[$i][0] : '';
            
            // Identificar títulos de secciones principales
            if (in_array($cellValue, ['ESTADÍSTICAS GENERALES', 'ESTADOS DE EXÁMENES', 'TOP 10 PACIENTES MÁS ACTIVOS', 'DISTRIBUCIÓN POR ACTIVIDAD'])) {
                $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28A745']
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
                $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
            }
            // Identificar encabezados de tablas
            elseif (in_array($cellValue, ['Métrica', 'Estado', 'Ranking', 'Rango de Actividad'])) {
                $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'dae3f3']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
            }
        }
        
        // Mergear celdas de títulos principales
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->mergeCells('A4:D4');
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 15,
            'C' => 15,
            'D' => 35
        ];
    }
}
