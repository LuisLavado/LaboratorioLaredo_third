<?php

namespace App\Exports;

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
        return 'Resultados General';
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

        foreach ($this->results as $result) {
            $pacienteNombre = '';
            if (isset($result->paciente)) {
                $pacienteNombre = ($result->paciente->nombres ?? '') . ' ' . ($result->paciente->apellidos ?? '');
            }

            $medicoNombre = '';
            if (isset($result->medico)) {
                $medicoNombre = ($result->medico->nombres ?? '') . ' ' . ($result->medico->apellidos ?? '');
            }

            $examenNombre = $result->examen->nombre ?? '';

            $estado = $result->estado ?? 'Sin estado';

            $rows[] = [
                $result->id ?? '',
                trim($pacienteNombre),
                $examenNombre,
                $result->valor ?? '',
                $result->unidad ?? '',
                $result->valor_referencia ?? '',
                $estado,
                isset($result->fuera_rango) && $result->fuera_rango ? 'Sí' : 'No',
                $result->observaciones ?? '',
                isset($result->fecha_resultado) ? Carbon::parse($result->fecha_resultado)->format('d/m/Y H:i') : '',
                trim($medicoNombre),
                isset($result->verificado) && $result->verificado ? 'Sí' : 'No'
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
            'A' => 12,  // ID Resultado
            'B' => 25,  // Paciente
            'C' => 30,  // Examen
            'D' => 15,  // Valor
            'E' => 10,  // Unidad
            'F' => 20,  // Valor Referencia
            'G' => 12,  // Estado
            'H' => 30,  // Observaciones
            'I' => 18,  // Fecha Resultado
            'J' => 25,  // Médico
            'K' => 12   // Verificado
        ];
    }
}
