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

class SinglePatientResultsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $patient;
    protected $startDate;
    protected $endDate;

    public function __construct($patient, $startDate, $endDate)
    {
        $this->patient = $patient;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        // Usar DNI o ID como identificador único en el título
        $identifier = $this->patient->documento ?? $this->patient->id;
        return "Resultados_{$identifier}";
    }

    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - RESULTADOS DEL PACIENTE'],
            ['Periodo: ' . $dateRange],
            [],
            ['INFORMACIÓN DEL PACIENTE'],
            [
                'DNI/Documento:', $this->patient->documento ?? 'N/A',
                'Historia Clínica:', $this->patient->historia_clinica ?? 'N/A'
            ],
            [
                'Nombres:', $this->patient->nombres ?? 'N/A',
                'Apellidos:', $this->patient->apellidos ?? 'N/A'
            ],
            [
                'Edad:', $this->patient->edad ?? 'N/A',
                'Sexo:', ucfirst($this->patient->sexo ?? 'N/A'),
                'Celular:', $this->patient->celular ?? 'N/A'
            ],
            [],
            ['RESUMEN DE RESULTADOS'],
            [
                'Total Solicitudes:', $this->patient->total_solicitudes ?? 0,
                'Total Exámenes:', $this->patient->total_examenes ?? 0,
                'Resultados Completados:', $this->patient->resultados_completados ?? 0
            ],
            [
                'Resultados Pendientes:', $this->patient->resultados_pendientes ?? 0,
                'Resultados Fuera de Rango:', $this->patient->resultados_fuera_rango ?? 0,
                '% Completados:', $this->calculateCompletionPercentage()
            ],
            [],
            ['DETALLE DE RESULTADOS POR SOLICITUD'],
            ['Fecha', 'N° Recibo', 'Examen', 'Campo', 'Resultado', 'Unidad', 'Valores Ref.', 'Estado', 'Fuera Rango', 'Observaciones']
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        if (isset($this->patient->resultados_detalle) && !empty($this->patient->resultados_detalle)) {
            $currentSolicitudId = null;
            
            foreach ($this->patient->resultados_detalle as $resultado) {
                // Si cambiamos a una nueva solicitud, agregar separador y encabezado
                if ($currentSolicitudId !== $resultado->solicitud_id) {
                    if ($currentSolicitudId !== null) {
                        $rows[] = ['', '', '', '', '', '', '', '', '', ''];  // Línea en blanco
                    }
                    
                    // Agregar encabezado de la solicitud
                    $rows[] = [
                        "SOLICITUD #{$resultado->numero_recibo}",
                        "Fecha: " . date('d/m/Y', strtotime($resultado->fecha_solicitud)),
                        "Servicio: " . ($resultado->servicio ?? 'No asignado'),
                        "Estado General: " . ucfirst($resultado->estado_solicitud ?? 'N/A'),
                        '', '', '', '', '', ''
                    ];
                    
                    $currentSolicitudId = $resultado->solicitud_id;
                }
                
                // Agregar el resultado individual
                $rows[] = [
                    date('d/m/Y', strtotime($resultado->fecha_resultado ?? $resultado->fecha_solicitud)),
                    $resultado->numero_recibo ?? 'N/A',
                    $resultado->examen_nombre ?? 'N/A',
                    $resultado->campo_nombre ?? 'N/A',
                    $resultado->valor ?? 'Pendiente',
                    $resultado->unidad ?? '',
                    $resultado->valor_referencia ?? '',
                    ucfirst($resultado->estado ?? 'pendiente'),
                    $resultado->fuera_rango ? 'SÍ' : 'NO',
                    $resultado->observaciones ?? ''
                ];
            }
        } else {
            $rows[] = ['No hay resultados registrados para este paciente en el período seleccionado', '', '', '', '', '', '', '', '', ''];
        }
        
        // Separador y resumen estadístico
        $rows[] = ['', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['ANÁLISIS ESTADÍSTICO DE RESULTADOS', '', '', '', '', '', '', '', '', ''];
        
        // Estadísticas por examen
        if (isset($this->patient->estadisticas_por_examen) && !empty($this->patient->estadisticas_por_examen)) {
            $rows[] = ['Examen', 'Total Resultados', 'Completados', 'Pendientes', 'Fuera de Rango', '% Fuera Rango', '', '', '', ''];
            
            foreach ($this->patient->estadisticas_por_examen as $stat) {
                $porcentajeFueraRango = $stat->total > 0 ? round(($stat->fuera_rango / $stat->total) * 100, 2) : 0;
                $rows[] = [
                    $stat->examen_nombre ?? 'N/A',
                    $stat->total ?? 0,
                    $stat->completados ?? 0,
                    $stat->pendientes ?? 0,
                    $stat->fuera_rango ?? 0,
                    $porcentajeFueraRango . '%',
                    '', '', '', ''
                ];
            }
        }
        
        return $rows;
    }

    private function calculateCompletionPercentage(): string
    {
        $total = $this->patient->total_examenes ?? 0;
        $completados = $this->patient->resultados_completados ?? 0;
        
        if ($total == 0) return '0%';
        
        $percentage = round(($completados / $total) * 100, 2);
        return $percentage . '%';
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el título principal
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A1:J1');

        // Estilo para el rango de fechas
        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A2:J2');

        // Estilo para las secciones
        foreach (['A4', 'A9', 'A13'] as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DDEBF7']
                ]
            ]);
            $sheet->mergeCells($cell . ':J' . substr($cell, 1));
        }

        // Estilo para las etiquetas de información
        foreach (['A5:B5', 'C5:D5', 'A6:B6', 'C6:D6', 'A7:B7', 'C7:D7', 'E7:F7'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2']
                ]
            ]);
        }

        // Estilo para el resumen de resultados
        foreach (['A10:B10', 'C10:D10', 'E10:F10', 'A11:B11', 'C11:D11', 'E11:F11'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2']
                ]
            ]);
        }

        // Estilo para los encabezados de la tabla de resultados
        $sheet->getStyle('A14:J14')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Aplicar estilos dinámicos a los datos
        $data = $this->array();
        $rowIndex = 15; // Comenzamos después de los encabezados
        
        foreach ($data as $row) {
            // Detectar encabezados de solicitud
            if (isset($row[0]) && strpos($row[0], 'SOLICITUD #') === 0) {
                $sheet->getStyle("A{$rowIndex}:J{$rowIndex}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E7E6E6']
                    ]
                ]);
            }
            // Detectar análisis estadístico
            elseif (isset($row[0]) && $row[0] === 'ANÁLISIS ESTADÍSTICO DE RESULTADOS') {
                $sheet->getStyle("A{$rowIndex}:J{$rowIndex}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DDEBF7']
                    ]
                ]);
                $sheet->mergeCells("A{$rowIndex}:J{$rowIndex}");
            }
            // Destacar valores fuera de rango
            elseif (isset($row[8]) && $row[8] === 'SÍ') {
                $sheet->getStyle("E{$rowIndex}:I{$rowIndex}")->applyFromArray([
                    'font' => ['color' => ['rgb' => 'FF0000'], 'bold' => true]
                ]);
            }
            // Aplicar bordes a filas de datos
            elseif (!empty($row[0]) && $row[0] !== '' && strpos($row[0], 'SOLICITUD #') !== 0 && $row[0] !== 'ANÁLISIS ESTADÍSTICO DE RESULTADOS') {
                $sheet->getStyle("A{$rowIndex}:J{$rowIndex}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);
            }
            
            $rowIndex++;
        }

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Fecha
            'B' => 15,  // N° Recibo
            'C' => 25,  // Examen
            'D' => 20,  // Campo
            'E' => 15,  // Resultado
            'F' => 10,  // Unidad
            'G' => 20,  // Valores Ref.
            'H' => 12,  // Estado
            'I' => 12,  // Fuera Rango
            'J' => 25,  // Observaciones
        ];
    }
}
