<?php

namespace App\Exports\Doctors;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Hoja de resultados procesados por doctores
 * Muestra estadísticas de resultados completados vs pendientes por doctor
 */
class DoctorsResultsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $resultStats;
    protected $startDate;
    protected $endDate;

    /**
     * Constructor
     *
     * @param array $resultStats Datos de resultados por doctor
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     */
    public function __construct(array $resultStats, string $startDate, string $endDate)
    {
        $this->resultStats = $resultStats;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Análisis de Resultados';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            ['ANÁLISIS DE RESULTADOS POR DOCTOR'],
            ['Período: ' . $this->startDate . ' al ' . $this->endDate],
            [''],
            ['#', 'Doctor', 'Resultados Completados', 'Resultados Pendientes', 'Total Resultados', '% Completados', '% Pendientes'],
        ];
    }

    /**
     * @return array
     */
    public function array(): array
    {
        if (empty($this->resultStats)) {
            return [['No hay datos disponibles para mostrar', '', '', '', '', '', '']];
        }
        
        // Ordenar por número total de resultados (descendente)
        $sortedStats = collect($this->resultStats)->sortByDesc(function ($result) {
            return ($result->completed ?? 0) + ($result->pending ?? 0);
        })->values()->all();
        
        $rows = [];
        
        foreach ($sortedStats as $index => $result) {
            $completados = $result->completed ?? 0;
            $pendientes = $result->pending ?? 0;
            $total = $completados + $pendientes;
            
            // Calcular porcentajes
            $porcentajeCompletados = $total > 0 ? round(($completados / $total) * 100, 2) : 0;
            $porcentajePendientes = $total > 0 ? round(($pendientes / $total) * 100, 2) : 0;
            
            $rows[] = [
                $index + 1,
                $result->doctor_name ?? 'Sin nombre',
                $completados,
                $pendientes,
                $total,
                $porcentajeCompletados,
                $porcentajePendientes
            ];
        }
        
        // Añadir fila de totales
        $totalCompletados = array_sum(array_column($rows, 2));
        $totalPendientes = array_sum(array_column($rows, 3));
        $granTotal = $totalCompletados + $totalPendientes;
        
        // Calcular porcentajes totales
        $porcentajeTotalCompletados = $granTotal > 0 ? round(($totalCompletados / $granTotal) * 100, 2) : 0;
        $porcentajeTotalPendientes = $granTotal > 0 ? round(($totalPendientes / $granTotal) * 100, 2) : 0;
        
        $rows[] = ['', 'TOTALES', $totalCompletados, $totalPendientes, $granTotal, $porcentajeTotalCompletados, $porcentajeTotalPendientes];
        
        return $rows;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Estilo para el título y subtítulo
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:G1')->getFont()->setColor(new Color(Color::COLOR_WHITE));
        
        $sheet->getStyle('A2:G2')->getFont()->setItalic(true)->setSize(11);
        $sheet->getStyle('A2:G2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Estilo para los encabezados de tabla
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:G4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2EFDA');
        
        // Bordes para toda la tabla
        $sheet->getStyle('A4:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Alineación central para ciertas columnas
        $sheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C5:G' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        // Formato de porcentaje para las columnas de porcentaje
        $sheet->getStyle('F5:G' . $lastRow)->getNumberFormat()->setFormatCode('0.00"%"');
        
        // Colorear filas alternas para mejor legibilidad
        for ($row = 5; $row <= $lastRow - 1; $row += 2) {
            $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FA');
        }
        
        // Estilo para la fila de totales
        $sheet->getStyle('A' . $lastRow . ':G' . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow . ':G' . $lastRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2EFDA');
        
        // Añadir formatos condicionales para las columnas de porcentaje
        $this->addCompletionRateConditionalFormatting($sheet, 'F5:F' . ($lastRow - 1));
        $this->addPendingRateConditionalFormatting($sheet, 'G5:G' . ($lastRow - 1));
        
        return [];
    }

    /**
     * Añadir formato condicional para la tasa de completados
     *
     * @param Worksheet $sheet
     * @param string $range
     */
    private function addCompletionRateConditionalFormatting(Worksheet $sheet, $range)
    {
        $conditionalStyles = $sheet->getStyle($range)->getConditionalStyles();
        
        // Alto (verde) - Más del 80%
        $highCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $highCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
        $highCondition->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_GREATERTHANOREQUAL);
        $highCondition->addCondition(80);
        $highCondition->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('C6E0B4');
        $conditionalStyles[] = $highCondition;
        
        // Medio (amarillo) - Entre 50% y 80%
        $mediumCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $mediumCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
        $mediumCondition->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_BETWEEN);
        $mediumCondition->addCondition(50);
        $mediumCondition->addCondition(80);
        $mediumCondition->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699');
        $conditionalStyles[] = $mediumCondition;
        
        // Bajo (rojo) - Menos del 50%
        $lowCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $lowCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
        $lowCondition->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN);
        $lowCondition->addCondition(50);
        $lowCondition->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8CBAD');
        $conditionalStyles[] = $lowCondition;
        
        $sheet->getStyle($range)->setConditionalStyles($conditionalStyles);
    }

    /**
     * Añadir formato condicional para la tasa de pendientes
     *
     * @param Worksheet $sheet
     * @param string $range
     */
    private function addPendingRateConditionalFormatting(Worksheet $sheet, $range)
    {
        $conditionalStyles = $sheet->getStyle($range)->getConditionalStyles();
        
        // Alto (rojo) - Más del 50%
        $highCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $highCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
        $highCondition->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_GREATERTHAN);
        $highCondition->addCondition(50);
        $highCondition->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8CBAD');
        $conditionalStyles[] = $highCondition;
        
        // Medio (amarillo) - Entre 20% y 50%
        $mediumCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $mediumCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
        $mediumCondition->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_BETWEEN);
        $mediumCondition->addCondition(20);
        $mediumCondition->addCondition(50);
        $mediumCondition->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699');
        $conditionalStyles[] = $mediumCondition;
        
        // Bajo (verde) - Menos del 20%
        $lowCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $lowCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
        $lowCondition->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN);
        $lowCondition->addCondition(20);
        $lowCondition->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('C6E0B4');
        $conditionalStyles[] = $lowCondition;
        
        $sheet->getStyle($range)->setConditionalStyles($conditionalStyles);
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,  // #
            'B' => 35, // Doctor
            'C' => 20, // Resultados Completados
            'D' => 20, // Resultados Pendientes
            'E' => 20, // Total Resultados
            'F' => 15, // % Completados
            'G' => 15, // % Pendientes
        ];
    }
}
