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

class SingleExamSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $exam;
    protected $startDate;
    protected $endDate;

    public function __construct($exam, $startDate, $endDate)
    {
        $this->exam = $exam;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        // Usar código o nombre del examen como identificador único
        $identifier = $this->exam->codigo ?? str_replace(['/', '\\', '?', '*', '[', ']'], '-', $this->exam->nombre);
        return "Exam_{$identifier}";
    }

    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE DE EXAMEN'],
            ['Periodo: ' . $dateRange],
            [],
            ['INFORMACIÓN DEL EXAMEN'],
            [
                'Código:', $this->exam->codigo ?? 'N/A',
                'Nombre:', $this->exam->nombre ?? 'N/A'
            ],
            [
                'Categoría:', $this->exam->categoria ?? 'N/A',
                'Estado:', $this->exam->activo ? 'Activo' : 'Inactivo'
            ],
            [],
            ['ESTADÍSTICAS DEL EXAMEN'],
            [
                'Total Realizados:', $this->exam->total_realizados ?? 0,
                'Total Pacientes:', $this->exam->total_pacientes ?? 0
            ],
            [
                'Pendientes:', $this->exam->pendientes ?? 0,
                'En Proceso:', $this->exam->en_proceso ?? 0,
                'Completados:', $this->exam->completados ?? 0
            ],
            [],
            ['CAMPOS Y PARÁMETROS'],
            ['Campo', 'Tipo', 'Unidad', 'Valores de Referencia', 'Sección', 'Obligatorio', 'Orden']
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        // Campos y parámetros del examen
        if (isset($this->exam->campos) && !empty($this->exam->campos)) {
            foreach ($this->exam->campos as $campo) {
                $rows[] = [
                    $campo->nombre ?? 'N/A',
                    $campo->tipo ?? 'N/A',
                    $campo->unidad ?? '-',
                    $campo->valor_referencia ?? '-',
                    $campo->seccion ?? 'General',
                    $campo->obligatorio ? 'Sí' : 'No',
                    $campo->orden ?? 0
                ];
            }
        } else {
            $rows[] = ['No hay campos definidos para este examen', '', '', '', '', '', ''];
        }
        
        // Separador
        $rows[] = ['', '', '', '', '', '', ''];
        
        // Servicios que más solicitan este examen
        if (isset($this->exam->servicios_principales) && !empty($this->exam->servicios_principales)) {
            $rows[] = ['SERVICIOS QUE MÁS SOLICITAN ESTE EXAMEN', '', '', '', '', '', ''];
            $rows[] = ['Servicio', 'Total Solicitudes', 'Porcentaje', 'Completados', 'En Proceso', 'Pendientes', ''];
            
            foreach ($this->exam->servicios_principales as $servicio) {
                $porcentaje = $this->exam->total_realizados > 0 ? 
                    round(($servicio->total / $this->exam->total_realizados) * 100, 2) . '%' : '0%';
                
                $rows[] = [
                    $servicio->nombre ?? 'N/A',
                    $servicio->total ?? 0,
                    $porcentaje,
                    $servicio->completados ?? 0,
                    $servicio->en_proceso ?? 0,
                    $servicio->pendientes ?? 0,
                    ''
                ];
            }
        }
        
        // Separador
        $rows[] = ['', '', '', '', '', '', ''];
        
        // Histórico de resultados si está disponible
        if (isset($this->exam->historico_resultados) && !empty($this->exam->historico_resultados)) {
            $rows[] = ['HISTÓRICO DE RESULTADOS RECIENTES', '', '', '', '', '', ''];
            $rows[] = ['Fecha', 'Paciente', 'Resultado', 'Unidad', 'Valor Ref.', 'Estado', 'Observaciones'];
            
            foreach ($this->exam->historico_resultados as $resultado) {
                $rows[] = [
                    $resultado->fecha ?? 'N/A',
                    $resultado->paciente ?? 'N/A',
                    $resultado->valor ?? 'N/A',
                    $resultado->unidad ?? '-',
                    $resultado->valor_referencia ?? '-',
                    $resultado->estado ?? 'N/A',
                    $resultado->observaciones ?? ''
                ];
            }
        }
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el título principal
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A1:G1');

        // Estilo para el rango de fechas
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A2:G2');

        // Estilo para las secciones
        foreach (['A4', 'A8', 'A12'] as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DDEBF7']
                ]
            ]);
            $sheet->mergeCells($cell . ':G' . substr($cell, 1));
        }

        // Estilo para las etiquetas de información
        foreach (['A5:B5', 'C5:D5', 'A6:B6', 'C6:D6', 'A9:B9', 'C9:D9', 'A10:B10', 'C10:D10', 'E10:F10'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2']
                ]
            ]);
        }

        // Estilo para los encabezados de tabla
        $sheet->getStyle('A13:G13')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Campo/Nombre
            'B' => 15,  // Tipo
            'C' => 12,  // Unidad
            'D' => 25,  // Valores de Referencia
            'E' => 15,  // Sección
            'F' => 12,  // Obligatorio
            'G' => 10,  // Orden
        ];
    }
}
