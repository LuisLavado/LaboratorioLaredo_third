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

class SinglePatientSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return "Paciente_{$identifier}";
    }

    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE DEL PACIENTE'],
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
                'Sexo:', ucfirst($this->patient->sexo ?? 'N/A')
            ],
            [
                'Fecha Nacimiento:', $this->patient->fecha_nacimiento ? date('d/m/Y', strtotime($this->patient->fecha_nacimiento)) : 'N/A',
                'Celular:', $this->patient->celular ?? 'N/A'
            ],
            [],
            ['RESUMEN DE ATENCIONES'],
            [
                'Total Solicitudes:', $this->patient->total_solicitudes ?? 0,
                'Total Exámenes:', $this->patient->total_examenes ?? 0
            ],
            [],            ['DETALLE DE SOLICITUDES Y RESULTADOS']
        ];
    }    public function array(): array
    {
        $rows = [];
        
        if (isset($this->patient->solicitudes_detalle)) {
            $currentSolicitudId = null;
            
            foreach ($this->patient->solicitudes_detalle as $solicitud) {
                // Si cambiamos a una nueva solicitud, agregar un separador y encabezado
                if ($currentSolicitudId !== $solicitud->id) {
                    if ($currentSolicitudId !== null) {
                        $rows[] = ['', '', '', '', '', '', '', '', '', '', ''];  // Línea en blanco
                    }
                    
                    // Agregar encabezado de la solicitud
                    $rows[] = [
                        "SOLICITUD #{$solicitud->numero_recibo}",
                        "Fecha: " . date('d/m/Y', strtotime($solicitud->fecha)),
                        "Hora: " . $solicitud->hora,
                        "Servicio: " . ($solicitud->servicio ?? 'No asignado'),
                        '',
                        '',
                        "Estado: " . ucfirst($solicitud->estado ?? 'N/A'),
                        '', '', '', ''
                    ];
                    
                    $currentSolicitudId = $solicitud->id;
                    
                    // Agregar encabezado para los resultados
                    if (isset($solicitud->resultados) && count($solicitud->resultados) > 0) {
                        $rows[] = [
                            'EXAMEN',
                            'CÓDIGO',
                            'CAMPO',
                            'RESULTADO',
                            'UNIDAD',
                            'VALORES DE REFERENCIA',
                            'FUERA DE RANGO',
                            '', '', '', ''
                        ];
                    }
                }
                
                // Agregar los resultados si existen
                if (isset($solicitud->resultados) && count($solicitud->resultados) > 0) {
                    foreach ($solicitud->resultados as $resultado) {
                        $rows[] = [
                            $solicitud->examen ?? 'N/A',
                            $solicitud->codigo_examen ?? 'N/A',
                            $resultado->campo ?? 'N/A',
                            $resultado->valor ?? 'N/A',
                            $resultado->unidad ?? '',
                            $resultado->valor_referencia ?? '',
                            $resultado->fuera_rango ? 'SÍ' : 'NO',
                            '', '', '', ''
                        ];
                    }
                } else {
                    // Si no hay resultados, mostrar mensaje
                    $rows[] = [
                        $solicitud->examen ?? 'N/A',
                        $solicitud->codigo_examen ?? 'N/A',
                        'Sin resultados registrados',
                        'Pendiente',
                        '', '', '',
                        '', '', '', ''
                    ];
                }
            }
        }
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el título principal
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A1:K1');

        // Estilo para el rango de fechas
        $sheet->getStyle('A2:K2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A2:K2');

        // Estilo para las secciones de información
        $sheet->getStyle('A4')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDEBF7']
            ]
        ]);
        $sheet->mergeCells('A4:K4');

        // Estilo para las etiquetas de información del paciente
        foreach (['A5:B5', 'C5:D5', 'A6:B6', 'C6:D6', 'A7:B7', 'C7:D7', 'A8:B8', 'C8:D8'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2']
                ]
            ]);
        }

        // Estilo para el resumen de atenciones
        $sheet->getStyle('A10')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDEBF7']
            ]
        ]);
        $sheet->mergeCells('A10:K10');

        // Estilo para los totales
        $sheet->getStyle('A11:D11')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F2F2']
            ]
        ]);

        // Estilo para la cabecera de detalles
        $sheet->getStyle('A13')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDEBF7']
            ]
        ]);
        $sheet->mergeCells('A13:K13');

        // Estilo para los encabezados de la tabla
        $sheet->getStyle('A14:K14')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Estilo para los datos
        $lastRow = count($this->array()) + 14;
        if ($lastRow > 14) {
            $sheet->getStyle('A15:K' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);

            // Destacar valores fuera de rango
            for ($i = 15; $i <= $lastRow; $i++) {
                if ($sheet->getCell('K' . $i)->getValue() === 'SÍ') {
                    $sheet->getStyle('H' . $i)->applyFromArray([
                        'font' => ['color' => ['rgb' => 'FF0000']]
                    ]);
                    $sheet->getStyle('K' . $i)->applyFromArray([
                        'font' => ['color' => ['rgb' => 'FF0000']]
                    ]);
                }
            }
        }

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // Fecha
            'B' => 8,  // Hora
            'C' => 12, // N° Recibo
            'D' => 15, // Servicio
            'E' => 25, // Examen
            'F' => 10, // Código
            'G' => 12, // Estado
            'H' => 15, // Resultado
            'I' => 10, // Unidad
            'J' => 20, // Valores Ref.
            'K' => 12, // Fuera Rango
        ];
    }
}
