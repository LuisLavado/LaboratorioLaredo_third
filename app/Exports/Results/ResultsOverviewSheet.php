<?php

namespace App\Exports\Results;

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
 * Hoja de Vista General de Resultados
 */
class ResultsOverviewSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
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
        return 'Resumen de Resultados';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['VISTA GENERAL DE RESULTADOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'ID Resultado',
                'Paciente',
                'Examen',
                'Valor',
                'Unidad',
                'Valor Referencia',
                'Estado',
                'Fuera de Rango',
                'Observaciones',
                'Fecha Resultado',
                'Médico',
                'Verificado'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];

        if (empty($this->results)) {
            $rows[] = [
                'No hay resultados disponibles para el período seleccionado.',
                '', '', '', '', '', '', '', '', '', '', ''
            ];
            return $rows;
        }

        foreach ($this->results as $index => $result) {

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

            // Extraer nombre del médico
            $medicoNombre = 'Sin asignar';
            if (isset($result->medico)) {
                $nombres = (string) ($result->medico->nombres ?? '');
                $apellidos = (string) ($result->medico->apellidos ?? '');
                $medicoNombre = trim($nombres . ' ' . $apellidos);
                if (empty($medicoNombre)) {
                    $medicoNombre = 'Sin asignar';
                }
            }

            // Extraer nombre del examen
            $examenNombre = 'Sin examen';
            if (isset($result->examen->nombre)) {
                $examenNombre = (string) $result->examen->nombre;
            }
      

            // Variables directas del objeto principal
            $estado = isset($result->estado) ? (string) $result->estado : 'Sin estado';
            $valor = isset($result->valor) ? (string) $result->valor : 'Pendiente';
            $unidad = isset($result->unidad) ? (string) $result->unidad : '';
            $valorReferencia = isset($result->valor_referencia) ? (string) $result->valor_referencia : 'Sin referencia';
            $observaciones = isset($result->observaciones) ? (string) $result->observaciones : '';
            $id = isset($result->id) ? (string) $result->id : (string) ($index + 1);

            // Formatear estado
            $estadoFormateado = '';
            switch (strtolower($estado)) {
                case 'completado':
                    $estadoFormateado = 'COMPLETADO';
                    break;
                case 'pendiente':
                    $estadoFormateado = 'PENDIENTE';
                    break;
                case 'en_proceso':
                    $estadoFormateado = 'EN PROCESO';
                    break;
                default:
                    $estadoFormateado = strtoupper($estado);
            }

            // Fecha formateada
            $fechaFormateada = '';
            if (isset($result->fecha_resultado) && !empty($result->fecha_resultado)) {
                try {
                    $fechaFormateada = Carbon::parse($result->fecha_resultado)->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    $fechaFormateada = (string) $result->fecha_resultado;
                }
            }

            // Fuera de rango seguro
            $fueraRangoSeguro = 'No';
            if (isset($result->fuera_rango)) {
                $fueraRangoSeguro = $result->fuera_rango ? 'Sí' : 'No';
            }

            // Verificado
            $verificado = 'No';
            if (isset($result->verificado)) {
                $verificado = $result->verificado ? 'Sí' : 'No';
            }

            $rows[] = [
                $id,
                trim($pacienteNombre),
                $examenNombre,
                $valor,
                $unidad,
                $valorReferencia,
                $estadoFormateado,
                $fueraRangoSeguro,
                $observaciones,
                $fechaFormateada,
                trim($medicoNombre),
                $verificado
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
        $sheet->mergeCells('A1:L1'); // Título principal
        $sheet->mergeCells('A2:L2'); // Subtítulo
        $sheet->mergeCells('A3:L3'); // Período
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
            // Encabezados de columnas
            5 => [
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
            'A' => 8,  // ID Resultado
            'B' => 50,  // Paciente
            'C' => 50,  // Examen
            'D' => 15,  // Valor
            'E' => 10,  // Unidad
            'F' => 20,  // Valor Referencia
            'G' => 12,  // Estado
            'H' => 15,  // Observaciones
            'I' => 20,  // Fecha Resultado
            'J' => 30,  // Médico
            'K' => 35,   // Verificado
            'L' => 8   // Verificado
        ];
    }
}
