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
 * Hoja de Distribuciones de Pacientes (Género, Edad, etc.)
 */
class PatientsDistributionSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return 'Distribuciones';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - DISTRIBUCIONES DE PACIENTES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            ['ANÁLISIS DE DISTRIBUCIONES'],
            []
        ];
    }

    public function array(): array
    {
        $data = [];

        // Distribución por género
        if (isset($this->reportData['genderStats']) && !empty($this->reportData['genderStats'])) {
            $data[] = ['DISTRIBUCIÓN POR GÉNERO'];
            $data[] = ['Género', 'Cantidad', 'Porcentaje', 'Observaciones'];
            
            $genderStatsArray = is_array($this->reportData['genderStats']) 
                ? $this->reportData['genderStats'] 
                : $this->reportData['genderStats']->toArray();
            $totalGender = array_sum(array_column($genderStatsArray, 'count'));
            
            foreach ($this->reportData['genderStats'] as $gender) {
                $count = $gender['count'] ?? 0;
                $percentage = $totalGender > 0 ? number_format(($count / $totalGender) * 100, 1) : 0;
                
                $data[] = [
                    $gender['name'] ?? 'No especificado',
                    $count,
                    $percentage . '%',
                    $this->getGenderObservation($gender['name'] ?? '', $percentage)
                ];
            }
            $data[] = ['Total', $totalGender, '100%', 'Total de pacientes clasificados'];
            $data[] = [];
        }

        // Distribución por edad
        if (isset($this->reportData['ageStats']) && !empty($this->reportData['ageStats'])) {
            $data[] = ['DISTRIBUCIÓN POR GRUPOS DE EDAD'];
            $data[] = ['Grupo de Edad', 'Cantidad', 'Porcentaje', 'Descripción'];
            
            $ageStatsArray = is_array($this->reportData['ageStats']) 
                ? $this->reportData['ageStats'] 
                : $this->reportData['ageStats']->toArray();
            $totalAge = array_sum(array_column($ageStatsArray, 'count'));
            
            foreach ($this->reportData['ageStats'] as $age) {
                $count = $age['count'] ?? 0;
                $percentage = $totalAge > 0 ? number_format(($count / $totalAge) * 100, 1) : 0;
                
                $data[] = [
                    $age['name'] ?? 'No especificado',
                    $count,
                    $percentage . '%',
                    $this->getAgeGroupDescription($age['name'] ?? '')
                ];
            }
            $data[] = ['Total', $totalAge, '100%', 'Total de pacientes con edad conocida'];
            $data[] = [];
        }

        // Distribución por actividad
        $data[] = ['DISTRIBUCIÓN POR NIVEL DE ACTIVIDAD'];
        $data[] = ['Nivel de Actividad', 'Cantidad', 'Porcentaje', 'Rango de Solicitudes'];
        
        $activityDistribution = $this->calculateActivityDistribution();
        foreach ($activityDistribution as $activity) {
            $data[] = [
                $activity['name'],
                $activity['count'],
                $activity['percentage'],
                $activity['range']
            ];
        }
        $data[] = [];

        // Análisis temporal si hay datos
        $data[] = ['ANÁLISIS TEMPORAL'];
        $data[] = ['Métrica', 'Valor', 'Descripción'];
        $data[] = ['Período de análisis', $this->calculatePeriodDays() . ' días', 'Duración del período reportado'];
        $data[] = ['Promedio pacientes/día', $this->calculateDailyAverage(), 'Basado en solicitudes del período'];
        $data[] = ['Pico de actividad estimado', $this->estimatePeakActivity(), 'Nivel máximo de actividad'];

        return $data;
    }

    private function getGenderObservation($gender, $percentage): string
    {
        if ($percentage > 60) {
            return 'Mayoría significativa';
        } elseif ($percentage > 40) {
            return 'Distribución equilibrada';
        } else {
            return 'Minoría';
        }
    }

    private function getAgeGroupDescription($ageGroup): string
    {
        $descriptions = [
            '0-17 años' => 'Pacientes pediátricos',
            '18-29 años' => 'Adultos jóvenes',
            '30-49 años' => 'Adultos de mediana edad',
            '50-69 años' => 'Adultos mayores',
            '70+ años' => 'Adultos de tercera edad'
        ];

        return $descriptions[$ageGroup] ?? 'Grupo no clasificado';
    }

    private function calculateActivityDistribution(): array
    {
        $patients = $this->reportData['patients'] ?? [];
        $total = count($patients);
        
        $distribution = [
            'Sin actividad' => ['count' => 0, 'range' => '0 solicitudes'],
            'Baja' => ['count' => 0, 'range' => '1-2 solicitudes'],
            'Media' => ['count' => 0, 'range' => '3-5 solicitudes'],
            'Alta' => ['count' => 0, 'range' => '6-10 solicitudes'],
            'Muy Alta' => ['count' => 0, 'range' => '11+ solicitudes']
        ];

        foreach ($patients as $patient) {
            $solicitudes = $patient->total_solicitudes ?? 0;
            
            if ($solicitudes == 0) {
                $distribution['Sin actividad']['count']++;
            } elseif ($solicitudes <= 2) {
                $distribution['Baja']['count']++;
            } elseif ($solicitudes <= 5) {
                $distribution['Media']['count']++;
            } elseif ($solicitudes <= 10) {
                $distribution['Alta']['count']++;
            } else {
                $distribution['Muy Alta']['count']++;
            }
        }

        $result = [];
        foreach ($distribution as $name => $data) {
            $percentage = $total > 0 ? number_format(($data['count'] / $total) * 100, 1) . '%' : '0%';
            $result[] = [
                'name' => $name,
                'count' => $data['count'],
                'percentage' => $percentage,
                'range' => $data['range']
            ];
        }

        return $result;
    }

    private function calculatePeriodDays(): int
    {
        return $this->startDate->diffInDays($this->endDate) + 1;
    }

    private function calculateDailyAverage(): string
    {
        $totalRequests = $this->reportData['totalRequests'] ?? 0;
        $days = $this->calculatePeriodDays();
        
        if ($days > 0) {
            return number_format($totalRequests / $days, 1);
        }
        
        return '0';
    }

    private function estimatePeakActivity(): string
    {
        $dailyAverage = floatval($this->calculateDailyAverage());
        
        if ($dailyAverage >= 50) {
            return 'Muy alto (50+ solicitudes/día)';
        } elseif ($dailyAverage >= 20) {
            return 'Alto (20-49 solicitudes/día)';
        } elseif ($dailyAverage >= 10) {
            return 'Medio (10-19 solicitudes/día)';
        } elseif ($dailyAverage >= 5) {
            return 'Bajo (5-9 solicitudes/día)';
        } else {
            return 'Muy bajo (<5 solicitudes/día)';
        }
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
        
        // Título principal de distribuciones
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
            if (in_array($cellValue, ['DISTRIBUCIÓN POR GÉNERO', 'DISTRIBUCIÓN POR EDAD', 'DISTRIBUCIÓN POR ACTIVIDAD', 'ANÁLISIS TEMPORAL'])) {
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
            elseif (in_array($cellValue, ['Género', 'Grupo de Edad', 'Nivel de Actividad', 'Métrica'])) {
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
            'B' => 12,
            'C' => 12,
            'D' => 35
        ];
    }
}
