<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ResultsDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        // Registrar para depuración
        \Log::info('ResultsDetailSheet inicializado', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'daily_stats_count' => isset($data['dailyStats']) ? count($data['dailyStats']) : 0,
            'status_filter' => $data['statusFilter'] ?? 'ninguno'
        ]);
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Detalle de Resultados';
    }

    /**
     * Encabezados de la tabla
     */
    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        // Si hay un filtro de estado, incluirlo en el título
        $statusFilterText = '';
        if (!empty($this->data['filteredByStatus']) && !empty($this->data['statusFilter'])) {
            $statusMap = [
                'pendiente' => 'PENDIENTES',
                'en_proceso' => 'EN PROCESO',
                'completado' => 'COMPLETADOS'
            ];
            $statusName = $statusMap[$this->data['statusFilter']] ?? strtoupper($this->data['statusFilter']);
            $statusFilterText = ' - FILTRADO POR ESTADO: ' . $statusName;
        }
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - REPORTE DE RESULTADOS' . $statusFilterText],
            ['Periodo: ' . $dateRange],
            [],
            ['Fecha', 'Total Exámenes', 'Completados', '%', 'En Proceso', '%', 'Pendientes', '%', 'Tiempo Promedio (horas)']
        ];
    }

    /**
     * Datos para la hoja
     */
    public function array(): array
    {
        $rows = [];
        
        // Verificar si hay datos diarios
        if (!isset($this->data['dailyStats']) || empty($this->data['dailyStats'])) {
            $rows[] = ['No hay datos de resultados para el período seleccionado.', '', '', '', '', '', '', '', ''];
            return $rows;
        }
        
        // Variables para totales
        $totalExamenes = 0;
        $totalCompletados = 0;
        $totalEnProceso = 0;
        $totalPendientes = 0;
        $totalTiempoPromedio = 0;
        $countDaysWithTime = 0;
        
        // Ordenar las estadísticas diarias por fecha
        $dailyStats = collect($this->data['dailyStats'])->sortBy('date')->values()->all();
        
        // Generar una fila para cada día
        foreach ($dailyStats as $stat) {
            // Convertir a array si es un objeto
            if (is_object($stat)) {
                $stat = (array) $stat;
            }
            
            // Calcular porcentajes
            $total = ($stat['total'] ?? 0) > 0 ? ($stat['total'] ?? 0) : 1; // Evitar división por cero
            $porcentajeCompletados = round((($stat['completados'] ?? $stat['completado'] ?? 0) / $total) * 100, 1);
            $porcentajeEnProceso = round((($stat['en_proceso'] ?? 0) / $total) * 100, 1);
            $porcentajePendientes = round((($stat['pendientes'] ?? $stat['pendiente'] ?? 0) / $total) * 100, 1);
            
            // Buscar el tiempo promedio de este día si está disponible
            $tiempoPromedio = ''; // Por defecto, no hay tiempo promedio
            
            // Sumar a los totales
            $totalExamenes += $stat['total'] ?? 0;
            $totalCompletados += $stat['completados'] ?? $stat['completado'] ?? 0;
            $totalEnProceso += $stat['en_proceso'] ?? 0;
            $totalPendientes += $stat['pendientes'] ?? $stat['pendiente'] ?? 0;
            
            // Formatear la fecha
            $fecha = Carbon::parse($stat['fecha'] ?? $stat['date'])->format('d/m/Y');
            
            $rows[] = [
                $fecha,
                $stat['total'] ?? 0,
                $stat['completados'] ?? $stat['completado'] ?? 0,
                $porcentajeCompletados . '%',
                $stat['en_proceso'] ?? 0,
                $porcentajeEnProceso . '%',
                $stat['pendientes'] ?? $stat['pendiente'] ?? 0,
                $porcentajePendientes . '%',
                $tiempoPromedio
            ];
        }
        
        // Agregar una fila vacía antes de los totales
        $rows[] = ['', '', '', '', '', '', '', '', ''];
        
        // Calcular porcentajes totales
        $porcentajeCompletadosTotal = $totalExamenes > 0 ? round(($totalCompletados / $totalExamenes) * 100, 1) : 0;
        $porcentajeEnProcesoTotal = $totalExamenes > 0 ? round(($totalEnProceso / $totalExamenes) * 100, 1) : 0;
        $porcentajePendientesTotal = $totalExamenes > 0 ? round(($totalPendientes / $totalExamenes) * 100, 1) : 0;
        
        // Calcular tiempo promedio total
        $tiempoPromedioTotal = $countDaysWithTime > 0 ? round($totalTiempoPromedio / $countDaysWithTime, 2) : '';
        
        // Agregar la fila de totales
        $rows[] = [
            'TOTALES',
            $totalExamenes,
            $totalCompletados,
            $porcentajeCompletadosTotal . '%',
            $totalEnProceso,
            $porcentajeEnProcesoTotal . '%',
            $totalPendientes,
            $porcentajePendientesTotal . '%',
            $tiempoPromedioTotal
        ];
        
        // Agregar información adicional
        $rows[] = ['', '', '', '', '', '', '', '', ''];
        $rows[] = ['Nota: Los porcentajes representan la proporción sobre el total diario de exámenes.', '', '', '', '', '', '', '', ''];
        
        // Agregar información de generación
        $rows[] = ['', '', '', '', '', '', '', '', ''];
        $rows[] = ['Generado por:', $this->data['generatedBy'] ?? 'Sistema', '', '', '', '', '', '', ''];
        $rows[] = ['Fecha de generación:', now()->format('d/m/Y H:i:s'), '', '', '', '', '', '', ''];
        
        return $rows;
    }

    /**
     * Estilos para la hoja
     */
    public function styles(Worksheet $sheet)
    {
        // Encabezados principales
        $sheet->getStyle('A1:I2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Encabezados de columnas
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Filas de datos - buscar la posición de la fila de totales
        $lastRow = count($this->array());
        $totalRow = 0;
        foreach ($this->array() as $index => $row) {
            if (isset($row[0]) && $row[0] === 'TOTALES') {
                $totalRow = $index + 5; // +5 porque los índices comienzan en 0 y hay encabezados
                break;
            }
        }
        
        // Si encontramos la fila de totales, aplicar estilos especiales
        if ($totalRow > 0) {
            $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2']
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);
        }
        
        // Aplicar bordes a todas las celdas con datos
        $dataEndRow = $totalRow - 2; // -2 porque hay una fila vacía antes de TOTALES
        if ($dataEndRow > 4) { // Asegurarse de que hay datos
            $sheet->getStyle('A5:I' . $dataEndRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);
        }
        
        // Centrar columnas de valores y porcentajes
        $sheet->getStyle('B5:I' . $lastRow)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Formato de porcentajes
        $sheet->getStyle('D5:D' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        $sheet->getStyle('F5:F' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        $sheet->getStyle('H5:H' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        
        return $sheet;
    }

    /**
     * Anchos de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // Fecha
            'B' => 15, // Total Exámenes
            'C' => 15, // Completados
            'D' => 10, // % Completados
            'E' => 15, // En Proceso
            'F' => 10, // % En Proceso
            'G' => 15, // Pendientes
            'H' => 10, // % Pendientes
            'I' => 25, // Tiempo Promedio
        ];
    }
}
