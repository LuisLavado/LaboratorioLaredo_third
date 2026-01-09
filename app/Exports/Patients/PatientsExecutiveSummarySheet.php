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
 * Hoja de Resumen Ejecutivo de Pacientes
 */
class PatientsExecutiveSummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Resumen Ejecutivo';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - RESUMEN EJECUTIVO'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            ['MÉTRICAS PRINCIPALES'],
            []
        ];
    }

    public function array(): array
    {
        $data = [];

        // Métricas principales
        $data[] = ['Total de Pacientes', $this->reportData['totalPatients'] ?? 0];
        $data[] = ['Total de Solicitudes', $this->reportData['totalRequests'] ?? 0];
        $data[] = ['Total de Exámenes', $this->reportData['totalExams'] ?? 0];
        $data[] = ['Doctores Involucrados', $this->reportData['totalDoctors'] ?? 0];
        $data[] = [];

        // Estados de exámenes
        $data[] = ['ESTADOS DE EXÁMENES'];
        $data[] = ['Exámenes Pendientes', $this->reportData['pendingCount'] ?? 0];
        $data[] = ['Exámenes en Proceso', $this->reportData['inProcessCount'] ?? 0];
        $data[] = ['Exámenes Completados', $this->reportData['completedCount'] ?? 0];
        $data[] = [];

        // Distribución por género
        if (isset($this->reportData['genderStats']) && !empty($this->reportData['genderStats'])) {
            $data[] = ['DISTRIBUCIÓN POR GÉNERO'];
            foreach ($this->reportData['genderStats'] as $gender) {
                $data[] = [
                    $gender['name'] ?? 'No especificado',
                    $gender['count'] ?? 0
                ];
            }
            $data[] = [];
        }

        // Distribución por edad
        if (isset($this->reportData['ageStats']) && !empty($this->reportData['ageStats'])) {
            $data[] = ['DISTRIBUCIÓN POR EDAD'];
            foreach ($this->reportData['ageStats'] as $age) {
                $data[] = [
                    $age['name'] ?? 'No especificado',
                    $age['count'] ?? 0
                ];
            }
            $data[] = [];
        }

        // Top 5 pacientes más activos
        if (isset($this->reportData['topPatients']) && !empty($this->reportData['topPatients'])) {
            $data[] = ['TOP 5 PACIENTES MÁS ACTIVOS'];
            $data[] = ['Paciente', 'Solicitudes'];
            $topPatientsArray = is_array($this->reportData['topPatients']) 
                ? $this->reportData['topPatients'] 
                : $this->reportData['topPatients']->toArray();
            $topPatients = array_slice($topPatientsArray, 0, 5);
            foreach ($topPatients as $patient) {
                $data[] = [
                    $patient['name'] ?? 'Sin nombre',
                    $patient['count'] ?? 0
                ];
            }
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        // Título principal
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f4e79']
            ]
        ]);
        
        // Período
        $sheet->getStyle('A2:B2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472c4']
            ]
        ]);
        
        // Títulos de secciones principales
        $sheet->getStyle('A4:B4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Estilos para otros títulos de secciones (ESTADOS DE EXÁMENES, DISTRIBUCIÓN POR GÉNERO, etc.)
        $rowCount = 6; // Empezar después de las métricas principales
        $totalData = count($this->array());
        
        for ($i = 0; $i < $totalData; $i++) {
            $currentRow = $rowCount + $i;
            $cellValue = $this->array()[$i][0] ?? '';
            
            // Identificar títulos de secciones
            if (in_array($cellValue, ['ESTADOS DE EXÁMENES', 'DISTRIBUCIÓN POR GÉNERO', 'DISTRIBUCIÓN POR EDAD', 'TOP 5 PACIENTES MÁS ACTIVOS'])) {
                $sheet->getStyle("A{$currentRow}:B{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28A745']
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
                $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
            }
        }
        
        // Mergear celdas de títulos principales
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->mergeCells('A4:B4');
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30
        ];
    }
}
