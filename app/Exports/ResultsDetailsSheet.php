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

class ResultsDetailsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $detailedResults;
    protected $startDate;
    protected $endDate;

    public function __construct($detailedResults, $startDate, $endDate)
    {
        $this->detailedResults = $detailedResults;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        \Log::info('ResultsDetailsSheet inicializado', [
            'total_resultados' => is_array($detailedResults) ? count($detailedResults) : 'No es array',
            'primer_resultado' => is_array($detailedResults) && !empty($detailedResults) ? 
                json_encode(array_keys((array)$detailedResults[0])) : 'No hay resultados'
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
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE COMPLETO DE RESULTADOS'],
            ['Periodo: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'Fecha', 
                'Hora', 
                'Nº Solicitud', 
                'Paciente', 
                'DNI', 
                'Examen', 
                'Código', 
                'Sección/Campo', 
                'Valor', 
                'Unidad', 
                'Valores Ref.', 
                'Estado', 
                'Fuera de Rango'
            ]
        ];
    }

    /**
     * Datos para la hoja
     */
    public function array(): array
    {
        $rows = [];
        
        if (empty($this->detailedResults)) {
            $rows[] = ['No hay resultados detallados disponibles para el período seleccionado.', '', '', '', '', '', '', '', '', '', '', '', ''];
            return $rows;
        }
        
        foreach ($this->detailedResults as $result) {
            // Formatear la fecha y hora
            $fecha = $result->fecha ?? 'N/A';
            if ($fecha !== 'N/A') {
                $fecha = Carbon::parse($fecha)->format('d/m/Y');
            }
            
            $hora = $result->hora ?? 'N/A';
            
            // Formato condicional para 'fuera de rango'
            $fueraRango = $result->fuera_rango ? 'SÍ' : 'NO';
            
            $rows[] = [
                $fecha,
                $hora,
                $result->solicitud_id ?? 'N/A',
                $result->nombre_paciente ?? 'N/A',
                $result->dni_paciente ?? 'N/A',
                $result->nombre_examen ?? 'N/A',
                $result->codigo_examen ?? 'N/A',
                $result->seccion_campo ?? 'N/A',
                $result->valor ?? 'N/A',
                $result->unidad ?? '',
                $result->valor_referencia ?? '',
                $result->estado ?? 'pendiente',
                $fueraRango
            ];
        }
        
        return $rows;
    }

    /**
     * Estilos para la hoja
     */
    public function styles(Worksheet $sheet)
    {
        // Encabezados principales
        $sheet->getStyle('A1:M2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        
        // Encabezados de columnas
        $sheet->getStyle('A4:M4')->applyFromArray([
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
        
        // Filas de datos
        $lastRow = count($this->array()) + 4; // +4 por los encabezados
        if ($lastRow > 5) { // Si hay datos (más que solo la fila que dice "No hay resultados")
            $sheet->getStyle('A5:M' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);
            
            // Centrar ciertas columnas
            $sheet->getStyle('A5:B' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('E5:G' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('J5:J' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('L5:M' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            // Destacar valores fuera de rango
            for ($i = 5; $i <= $lastRow; $i++) {
                if ($sheet->getCell('M' . $i)->getValue() === 'SÍ') {
                    $sheet->getStyle('I' . $i)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
                    ]);
                    $sheet->getStyle('M' . $i)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
                    ]);
                }
            }
            
            // Color según estado
            for ($i = 5; $i <= $lastRow; $i++) {
                $estado = $sheet->getCell('L' . $i)->getValue();
                $color = 'FFFFFF'; // Blanco por defecto
                
                if ($estado === 'completado') {
                    $color = 'DDFFDD'; // Verde claro
                } elseif ($estado === 'en_proceso') {
                    $color = 'FFFFDD'; // Amarillo claro
                } elseif ($estado === 'pendiente') {
                    $color = 'FFDDDD'; // Rojo claro
                }
                
                $sheet->getStyle('L' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $color]
                    ]
                ]);
            }
        }
        
        return $sheet;
    }

    /**
     * Anchos de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12, // Fecha
            'B' => 10, // Hora
            'C' => 12, // Nº Solicitud
            'D' => 25, // Paciente
            'E' => 12, // DNI
            'F' => 25, // Examen
            'G' => 12, // Código
            'H' => 20, // Sección/Campo
            'I' => 15, // Valor
            'J' => 10, // Unidad
            'K' => 20, // Valores Ref.
            'L' => 15, // Estado
            'M' => 15, // Fuera de Rango
        ];
    }
}
