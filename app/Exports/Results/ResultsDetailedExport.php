<?php

namespace App\Exports\Results;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

/**
 * Exportación Detallada de Resultados
 * Reporte específico y detallado de resultados de laboratorio
 */
class ResultsDetailedExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    use Exportable;

    protected $results;
    protected $startDate;
    protected $endDate;

    public function __construct($results, $startDate, $endDate)
    {
        $this->results = $results;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Resultados Detallado';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['REPORTE DETALLADO DE RESULTADOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            ['Generado el: ' . now()->format('d/m/Y H:i:s')],
            [],
            [
                'ID Resultado',
                'Paciente DNI',
                'Paciente Nombre',
                'Solicitud N°',
                'Examen',
                'Categoría',
                'Valor',
                'Unidad',
                'Valor Referencia',
                'Estado',
                'Observaciones',
                'Fecha Resultado',
                'Médico',
                'Técnico',
                'Verificado',
                'Fecha Verificación'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];

        if (empty($this->results)) {
            $rows[] = [
                'No hay resultados disponibles para el período seleccionado.',
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
            ];
            return $rows;
        }

        foreach ($this->results as $result) {
            // Información del paciente
            $pacienteDni = '';
            $pacienteNombre = '';
            if (isset($result->paciente)) {
                $pacienteDni = $result->paciente->dni ?? '';
                $pacienteNombre = trim(($result->paciente->nombres ?? '') . ' ' . ($result->paciente->apellidos ?? ''));
            }

            // Información del médico
            $medicoNombre = '';
            if (isset($result->medico)) {
                $medicoNombre = trim(($result->medico->nombres ?? '') . ' ' . ($result->medico->apellidos ?? ''));
            }

            // Información del técnico
            $tecnicoNombre = '';
            if (isset($result->tecnico)) {
                $tecnicoNombre = trim(($result->tecnico->nombres ?? '') . ' ' . ($result->tecnico->apellidos ?? ''));
            }

            // Información del examen
            $examenNombre = $result->examen->nombre ?? '';
            $categoria = $result->examen->categoria ?? '';

            // Fechas
            $fechaResultado = '';
            if (isset($result->fecha_resultado)) {
                $fechaResultado = Carbon::parse($result->fecha_resultado)->format('d/m/Y H:i');
            }

            $fechaVerificacion = '';
            if (isset($result->fecha_verificacion)) {
                $fechaVerificacion = Carbon::parse($result->fecha_verificacion)->format('d/m/Y H:i');
            }

            // Estado del resultado
            $estado = $this->determineResultStatus($result);

            $rows[] = [
                $result->id ?? '',
                $pacienteDni,
                $pacienteNombre,
                $result->solicitud_id ?? '',
                $examenNombre,
                $categoria,
                $result->valor ?? '',
                $result->unidad ?? '',
                $result->valor_referencia ?? '',
                $estado,
                $result->observaciones ?? '',
                $fechaResultado,
                $medicoNombre,
                $tecnicoNombre,
                $result->verificado ? 'Sí' : 'No',
                $fechaVerificacion
            ];
        }

        return $rows;
    }

    /**
     * Determina el estado del resultado basado en los valores
     */
    private function determineResultStatus($result)
    {
        if (!isset($result->valor) || !isset($result->valor_referencia)) {
            return 'Sin evaluar';
        }

        $valor = $result->valor;
        $referencia = $result->valor_referencia;

        // Si es numérico, comparar con rangos
        if (is_numeric($valor)) {
            if (strpos($referencia, '-') !== false) {
                $rango = explode('-', $referencia);
                if (count($rango) == 2) {
                    $min = floatval(trim($rango[0]));
                    $max = floatval(trim($rango[1]));
                    $valorNum = floatval($valor);
                    
                    if ($valorNum < $min) {
                        return 'Bajo';
                    } elseif ($valorNum > $max) {
                        return 'Alto';
                    } else {
                        return 'Normal';
                    }
                }
            }
        }

        // Para valores no numéricos o sin rango claro
        return 'Normal';
    }

    public function styles(Worksheet $sheet)
    {
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
            // Período
            3 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472c4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            // Fecha generación
            4 => [
                'font' => ['italic' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Encabezados de columnas
            6 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'dae3f3']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '4472c4']
                    ]
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // ID Resultado
            'B' => 12,  // Paciente DNI
            'C' => 25,  // Paciente Nombre
            'D' => 12,  // Solicitud N°
            'E' => 30,  // Examen
            'F' => 20,  // Categoría
            'G' => 15,  // Valor
            'H' => 10,  // Unidad
            'I' => 20,  // Valor Referencia
            'J' => 12,  // Estado
            'K' => 30,  // Observaciones
            'L' => 18,  // Fecha Resultado
            'M' => 25,  // Médico
            'N' => 25,  // Técnico
            'O' => 10,  // Verificado
            'P' => 18   // Fecha Verificación
        ];
    }
}
