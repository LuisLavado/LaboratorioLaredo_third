<?php

namespace App\Exports\Results;

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
                json_encode(array_keys((array)$detailedResults[0])) : 'No hay resultados',
            'estructura_detallada' => is_array($detailedResults) && !empty($detailedResults) ? 
                json_encode(array_slice($detailedResults, 0, 1)) : 'No hay resultados',
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
            ['LABORATORIO CLÍNICO LAREDO'],
            ['DETALLE COMPLETO DE RESULTADOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
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
            
            // Obtener la fecha y hora como strings
            $fecha = 'N/A';
            $hora = 'N/A';

            // Intentar obtener fecha/hora de los datos originales
            if (isset($result->fecha_resultado)) {
                try {
                    $fecha = Carbon::parse($result->fecha_resultado)->format('d/m/Y');
                    // Extraer hora de fecha_resultado si contiene espacio
                    if (strpos($result->fecha_resultado, ' ') !== false) {
                        $parts = explode(' ', $result->fecha_resultado, 2);
                        if (count($parts) > 1) {
                            $hora = (string) $parts[1];
                        }
                    }
                } catch (\Exception $e) {
                    $fecha = (string) $result->fecha_resultado;
                }
            }

            // ID de solicitud como string
            $solicitudId = isset($result->solicitud_id) ? (string) $result->solicitud_id : 'N/A';
            
            // Formato condicional para 'fuera de rango'
            $fueraRango = 'NO';
            if (isset($result->fuera_rango) && $result->fuera_rango) {
                $fueraRango = 'SÍ';
            }

            // Valor y unidad como strings desde el objeto
            $valor = isset($result->valor) ? (string) $result->valor : 'Pendiente';
            $unidad = isset($result->unidad) ? (string) $result->unidad : '';
            $valorReferencia = isset($result->valor_referencia) ? (string) $result->valor_referencia : 'Sin referencia';
            $estado = isset($result->estado) ? (string) $result->estado : 'pendiente';

            // Extraer datos de la estructura real (objetos anidados)

            // Extraer nombre del paciente
            $pacienteNombre = 'Sin nombre';
            if (isset($result->paciente)) {
                $nombres = (string) ($result->paciente->nombres ?? '');
                $apellidos = (string) ($result->paciente->apellidos ?? '');
                $pacienteNombre = trim($nombres . ' ' . $apellidos);
                if (empty($pacienteNombre)) {
                    $pacienteNombre = 'Sin nombre';
                }
            }

            // Extraer DNI del paciente
            $pacienteDNI = isset($result->paciente->dni) ? (string) $result->paciente->dni : 'Sin DNI';

            // Extraer nombre del examen
            $examenNombre = 'Sin examen';
            if (isset($result->examen->nombre)) {
                $examenNombre = (string) $result->examen->nombre;
            }

            // Extraer código del examen
            $examenCodigo = isset($result->examen->codigo) ? (string) $result->examen->codigo : '';

            // Campo nombre del resultado
            $campoNombre = isset($result->campo_nombre) ? (string) $result->campo_nombre : 'Resultado general';
            
            // Construir la fila de datos con todos los valores como strings
            $rows[] = [
                $fecha,
                $hora,
                $solicitudId,
                $pacienteNombre,
                $pacienteDNI,
                $examenNombre,
                $examenCodigo,
                $campoNombre,
                $valor,
                $unidad,
                $valorReferencia,
                strtoupper($estado),
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
        $sheet->mergeCells('A1:M1'); // Título principal
        $sheet->mergeCells('A2:M2'); // Subtítulo
        $sheet->mergeCells('A3:M3'); // Período
        
        // Estilos para encabezados
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f4e79']
            ]
        ]);
        
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2f5f8f']
            ]
        ]);
        
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472c4']
            ]
        ]);
        
        // Encabezados de columnas
        $sheet->getStyle('A5:M5')->applyFromArray([
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
        $lastRow = count($this->array()) + 5; // +4 por los encabezados
        if ($lastRow > 6) { // Si hay datos (más que solo la fila que dice "No hay resultados")
            $sheet->getStyle('A6:M' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);
            
            // Centrar ciertas columnas
            $sheet->getStyle('A6:B' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('E6:G' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('J6:J' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('L6:M' . $lastRow)->applyFromArray([
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
            for ($i = 6; $i <= $lastRow; $i++) {
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
