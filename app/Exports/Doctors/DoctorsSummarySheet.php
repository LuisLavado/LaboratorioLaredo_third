<?php

namespace App\Exports\Doctors;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de resumen del reporte de doctores
 */
class DoctorsSummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $data;
    protected $startDate;
    protected $endDate;

    /**
     * Constructor
     *
     * @param array $data Datos del reporte
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     */
    public function __construct(array $data, string $startDate, string $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Resumen';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['REPORTE DE DOCTORES - RESUMEN GENERAL'],
            ['Período: ' . $this->startDate . ' al ' . $this->endDate],
            [''],
            ['ESTADÍSTICAS PRINCIPALES'],
            ['Métrica', 'Valor'],
        ];
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $totalDoctors = isset($this->data['doctorStats']) ? count($this->data['doctorStats']) : 0;
        $totalRequests = $this->data['totalRequests'] ?? 0;
        $totalPatients = $this->data['totalPatients'] ?? 0;
        $totalExams = $this->data['totalExams'] ?? 0;
        
        // Debug: Log the data being received
        \Log::info('DoctorsSummarySheet - Data received:', [
            'totalDoctors' => $totalDoctors,
            'totalRequests' => $totalRequests,
            'totalPatients' => $totalPatients,
            'totalExams' => $totalExams,
            'doctorStats_sample' => isset($this->data['doctorStats']) && !empty($this->data['doctorStats']) 
                ? array_slice($this->data['doctorStats'], 0, 2) 
                : 'No data'
        ]);
        
        // Calcular promedios
        $avgRequestsPerDoctor = $totalDoctors > 0 ? round($totalRequests / $totalDoctors, 2) : 0;
        $avgPatientsPerDoctor = $totalDoctors > 0 ? round($totalPatients / $totalDoctors, 2) : 0;
        $avgExamsPerDoctor = $totalDoctors > 0 ? round($totalExams / $totalDoctors, 2) : 0;
        
        // Top doctores (si existen)
        $topDoctors = [];
        if (isset($this->data['doctorStats'])) {
            $doctorStats = $this->data['doctorStats'];
            
            // Convertir a array si es una colección
            if ($doctorStats instanceof \Illuminate\Support\Collection) {
                $doctorStatsArray = $doctorStats->toArray();
            } else {
                $doctorStatsArray = $doctorStats;
            }
            
            \Log::info('DoctorsSummarySheet - Doctor stats before sorting:', [
                'count' => count($doctorStatsArray),
                'sample' => array_slice($doctorStatsArray, 0, 2)
            ]);
            
            // Ordenar por solicitudes en orden descendente
            usort($doctorStatsArray, function($a, $b) {
                $aCount = $a->total_solicitudes ?? $a->count ?? 0;
                $bCount = $b->total_solicitudes ?? $b->count ?? 0;
                return $bCount <=> $aCount;
            });
            
            // Tomar los primeros 5 doctores
            $topDoctors = array_slice($doctorStatsArray, 0, 5);
            
            \Log::info('DoctorsSummarySheet - Top doctors selected:', [
                'count' => count($topDoctors),
                'doctors' => array_map(function($doctor) {
                    return [
                        'nombres' => $doctor->nombres ?? 'N/A',
                        'apellidos' => $doctor->apellidos ?? 'N/A',
                        'total_solicitudes' => $doctor->total_solicitudes ?? 0,
                        'total_pacientes' => $doctor->total_pacientes ?? 0,
                        'total_examenes' => $doctor->total_examenes ?? 0
                    ];
                }, $topDoctors)
            ]);
        }
        
        $rows = [
            ['Total de Doctores Activos', $totalDoctors],
            ['Total de Solicitudes', $totalRequests],
            ['Total de Pacientes', $totalPatients],
            ['Total de Exámenes', $totalExams],
            ['Promedio de Solicitudes por Doctor', $avgRequestsPerDoctor],
            ['Promedio de Pacientes por Doctor', $avgPatientsPerDoctor],
            ['Promedio de Exámenes por Doctor', $avgExamsPerDoctor],
            ['TOP 5 DOCTORES POR ACTIVIDAD', '', '', ''],
            ['Nombre', 'Solicitudes', 'Pacientes', 'Exámenes']
        ];
        
        // Agregar los top doctores a la tabla
        foreach ($topDoctors as $index => $doctor) {
            $rows[] = [
                ($doctor->nombres ?? '') . ' ' . ($doctor->apellidos ?? ''),
                $doctor->total_solicitudes ?? $doctor->count ?? 0,
                $doctor->total_pacientes ?? 0,
                $doctor->total_examenes ?? 0
            ];
        }
        
        // Si no hay doctores, agregar una fila informativa
        if (empty($topDoctors)) {
            $rows[] = ['No hay datos disponibles para mostrar', '', '', ''];
        }
        
        return $rows;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Merge cells for headers
        $sheet->mergeCells('A1:B1'); // Título principal
        $sheet->mergeCells('A2:B2'); // Subtítulo
        $sheet->mergeCells('A3:B3'); // Período
        $sheet->mergeCells('A5:B5'); // Estadísticas principales
        $sheet->mergeCells('A8:D8'); // Top doctores
        
        // Apply styles similar to DoctorsOverviewSheet
        $styles = [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ]
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ]
            ],
            // Período
            3 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472c4']
                ]
            ],
            // Encabezado estadísticas principales
            5 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47']
                ]
            ],
            // Encabezados de columnas estadísticas
            6 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '4472c4']
                    ]
                ]
            ]
        ];
        
        // Apply borders to statistics table (rows 6-13 approximately)
        $sheet->getStyle('A6:B13')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Top doctores section (if exists)
        if ($lastRow > 15) {
            // Encabezado top doctores (row 14)
            $sheet->mergeCells('A14:D14');
            $sheet->getStyle('A14:D14')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A14:D14')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A14:D14')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('70AD47');
            $sheet->getStyle('A14:D14')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            
            // Encabezados de columnas top doctores (row 15)
            $sheet->getStyle('A15:D15')->getFont()->setBold(true);
            $sheet->getStyle('A15:D15')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2EFDA');
            
            // Borders for top doctors table
            $sheet->getStyle('A15:D' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        
        return $styles;
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 20,
            'C' => 20,
            'D' => 20
        ];
    }
}
