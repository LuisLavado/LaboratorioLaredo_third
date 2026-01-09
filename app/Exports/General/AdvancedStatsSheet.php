<?php

namespace App\Exports\General;

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
 * Hoja de Estadísticas Avanzadas
 */
class AdvancedStatsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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

    public function title(): string
    {
        return 'Estadísticas Avanzadas';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['ESTADÍSTICAS AVANZADAS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            ['Generado el: ' . now()->format('d/m/Y H:i:s')],
            [],
            ['SECCIÓN', 'MÉTRICA', 'VALOR', 'PORCENTAJE']
        ];
    }

    public function array(): array
    {
        $rows = [];

        // Estadísticas de Pacientes
        $rows[] = ['PACIENTES', 'Total de pacientes', $this->getPatientsCount(), '100%'  ];
        $rows[] = ['', 'Pacientes nuevos', $this->getNewPatientsCount(), $this->getNewPatientsPercentage()  ];
        $rows[] = ['', 'Promedio edad', $this->getAverageAge() . ' años', ''  ];
        $rows[] = ['', 'Distribución por género (M)', $this->getMaleCount(), $this->getMalePercentage()  ];
        $rows[] = ['', 'Distribución por género (F)', $this->getFemaleCount(), $this->getFemalePercentage()  ];
        
        $rows[] = ['', '', '', '', ''];  // Separador

        // Estadísticas de Solicitudes
        $rows[] = ['SOLICITUDES', 'Total de solicitudes', $this->getRequestsCount(), '100%'  ];
        $rows[] = ['', 'Solicitudes completadas', $this->getCompletedRequestsCount(), $this->getCompletedRequestsPercentage()  ];
        $rows[] = ['', 'Solicitudes pendientes', $this->getPendingRequestsCount(), $this->getPendingRequestsPercentage()  ];
        $rows[] = ['', 'Promedio exámenes por solicitud', $this->getAverageExamsPerRequest(), ''  ];
        $rows[] = ['', '', '', '', ''];  // Separador

        // Estadísticas de Exámenes
        $rows[] = ['EXÁMENES', 'Total de exámenes realizados', $this->getExamsCount(), '100%'  ];
        $rows[] = ['', 'Exámenes más solicitados', $this->getMostRequestedExam(), ''  ];
        $rows[] = ['', 'Categoría más popular', $this->getMostPopularCategory(), ''  ];
        $rows[] = ['', 'Resultados normales', $this->getNormalResultsCount(), $this->getNormalResultsPercentage()  ];
        $rows[] = ['', 'Resultados anormales', $this->getAbnormalResultsCount(), $this->getAbnormalResultsPercentage()  ];

        return $rows;
    }

    // Métodos auxiliares para calcular estadísticas
    private function getPatientsCount()
    {
        return $this->data['total_patients'] ?? 0;
    }

    private function getNewPatientsCount()
    {
        return $this->data['new_patients'] ?? 0;
    }

    private function getNewPatientsPercentage()
    {
        $total = $this->getPatientsCount();
        $new = $this->getNewPatientsCount();
        return $total > 0 ? number_format(($new / $total) * 100, 1) . '%' : '0%';
    }

    private function getRecurrentPatientsCount()
    {
        return $this->getPatientsCount() - $this->getNewPatientsCount();
    }

    private function getRecurrentPatientsPercentage()
    {
        $total = $this->getPatientsCount();
        $recurrent = $this->getRecurrentPatientsCount();
        return $total > 0 ? number_format(($recurrent / $total) * 100, 1) . '%' : '0%';
    }

    private function getAverageAge()
    {
        return number_format($this->data['average_age'] ?? 0, 1);
    }

    private function getMaleCount()
    {
        return $this->data['male_patients'] ?? 0;
    }

    private function getMalePercentage()
    {
        $total = $this->getPatientsCount();
        $male = $this->getMaleCount();
        return $total > 0 ? number_format(($male / $total) * 100, 1) . '%' : '0%';
    }

    private function getFemaleCount()
    {
        return $this->data['female_patients'] ?? 0;
    }

    private function getFemalePercentage()
    {
        $total = $this->getPatientsCount();
        $female = $this->getFemaleCount();
        return $total > 0 ? number_format(($female / $total) * 100, 1) . '%' : '0%';
    }

    private function getRequestsCount()
    {
        return $this->data['total_requests'] ?? 0;
    }

    private function getCompletedRequestsCount()
    {
        return $this->data['completed_requests'] ?? 0;
    }

    private function getCompletedRequestsPercentage()
    {
        $total = $this->getRequestsCount();
        $completed = $this->getCompletedRequestsCount();
        return $total > 0 ? number_format(($completed / $total) * 100, 1) . '%' : '0%';
    }

    private function getPendingRequestsCount()
    {
        return $this->getRequestsCount() - $this->getCompletedRequestsCount();
    }

    private function getPendingRequestsPercentage()
    {
        $total = $this->getRequestsCount();
        $pending = $this->getPendingRequestsCount();
        return $total > 0 ? number_format(($pending / $total) * 100, 1) . '%' : '0%';
    }

    private function getAverageExamsPerRequest()
    {
        return number_format($this->data['average_exams_per_request'] ?? 0, 1);
    }

    private function getAverageProcessingTime()
    {
        return $this->data['average_processing_time'] ?? 'N/A';
    }

    private function getExamsCount()
    {
        return $this->data['total_exams'] ?? 0;
    }

    private function getMostRequestedExam()
    {
        return $this->data['most_requested_exam'] ?? 'N/A';
    }

    private function getMostPopularCategory()
    {
        return $this->data['most_popular_category'] ?? 'N/A';
    }

    private function getNormalResultsCount()
    {
        return $this->data['normal_results'] ?? 0;
    }

    private function getNormalResultsPercentage()
    {
        $total = $this->getExamsCount();
        $normal = $this->getNormalResultsCount();
        return $total > 0 ? number_format(($normal / $total) * 100, 1) . '%' : '0%';
    }

    private function getAbnormalResultsCount()
    {
        return $this->getExamsCount() - $this->getNormalResultsCount();
    }

    private function getAbnormalResultsPercentage()
    {
        $total = $this->getExamsCount();
        $abnormal = $this->getAbnormalResultsCount();
        return $total > 0 ? number_format(($abnormal / $total) * 100, 1) . '%' : '0%';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:D1'); // Título principal
        $sheet->mergeCells('A2:D2'); // Subtítulo
        $sheet->mergeCells('A3:D3'); // Período
        $sheet->mergeCells('A4:D4'); // Fecha generación
        
        // Combinar celdas para las secciones
        $rowIndex = 7; // Empezamos en la fila 7 (después de los encabezados)
        
        // Sección PACIENTES (5 filas)
        $sheet->mergeCells("A{$rowIndex}:A" . ($rowIndex + 4));
        $rowIndex += 6; // Avanzamos 5 filas de datos + 1 fila separadora
        
        // Sección SOLICITUDES (5 filas)
        $sheet->mergeCells("A{$rowIndex}:A" . ($rowIndex + 4));
        $rowIndex += 6; // Avanzamos 5 filas de datos + 1 fila separadora
        
        // Sección EXÁMENES (5 filas)
        $sheet->mergeCells("A{$rowIndex}:A" . ($rowIndex + 4));
        
        return [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 16]
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 14]
            ],
            // Período y fecha generación
            '3:4' => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Encabezados de columnas
            6 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ]
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Estilo para las secciones principales
            'A7:A' . ($rowIndex + 4) => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ],
            // Estilo para todas las celdas de datos
            'A7:D' . ($rowIndex + 4) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ]
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // SECCIÓN
            'B' => 35,  // MÉTRICA
            'C' => 20,  // VALOR
            'D' => 15,  // PORCENTAJE
            'E' => 12   // TENDENCIA
        ];
    }
}
