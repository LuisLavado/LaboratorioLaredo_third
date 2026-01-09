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
 * Hoja de Estados Específicos - Pendientes y En Proceso
 */
class ResultsStatusSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Estados Pendientes y En Proceso';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['ANÁLISIS DE ESTADOS: PENDIENTES Y EN PROCESO'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['RESUMEN POR ESTADO'],
            [
                'Estado',
                'Cantidad',
                'Porcentaje',
                'Prioridad',
                'Acción Recomendada'
            ],
            [],
            [],
            ['DETALLE DE EXÁMENES PENDIENTES Y EN PROCESO'],
            [
                'Fecha',
                'Paciente',
                'DNI',
                'Examen',
                'Estado',
                'Días Transcurridos',
                'Prioridad',
                'Observaciones'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        // Obtener datos de estados
        $statusCounts = $this->data['statusCounts'] ?? [];
        $detailedResults = $this->data['detailed_results'] ?? [];
        
        // Calcular totales
        $totalExams = array_sum($statusCounts);
        $pendientes = $statusCounts['pendiente'] ?? 0;
        $enProceso = $statusCounts['en_proceso'] ?? 0;
        $completados = $statusCounts['completado'] ?? 0;
        
        // Sección 1: Resumen por Estado
        if ($totalExams > 0) {
            $pctPendientes = round(($pendientes / $totalExams) * 100, 1);
            $pctEnProceso = round(($enProceso / $totalExams) * 100, 1);
            $pctCompletados = round(($completados / $totalExams) * 100, 1);
            
            // Determinar prioridades
            $prioridadPendientes = $pctPendientes > 30 ? 'ALTA' : ($pctPendientes > 15 ? 'MEDIA' : 'BAJA');
            $prioridadEnProceso = $pctEnProceso > 20 ? 'ALTA' : ($pctEnProceso > 10 ? 'MEDIA' : 'BAJA');
            
            // Acciones recomendadas
            $accionPendientes = $pctPendientes > 30 ? 'Revisar capacidad de procesamiento' : 
                               ($pctPendientes > 15 ? 'Monitorear de cerca' : 'Mantener seguimiento');
            $accionEnProceso = $pctEnProceso > 20 ? 'Acelerar procesamiento' : 
                              ($pctEnProceso > 10 ? 'Revisar tiempos' : 'Proceso normal');
            
            $rows[] = ['PENDIENTE', $pendientes, $pctPendientes . '%', $prioridadPendientes, $accionPendientes];
            $rows[] = ['EN PROCESO', $enProceso, $pctEnProceso . '%', $prioridadEnProceso, $accionEnProceso];
            $rows[] = ['COMPLETADO', $completados, $pctCompletados . '%', 'NORMAL', 'Mantener calidad'];
        } else {
            $rows[] = ['Sin datos disponibles', '', '', '', ''];
        }
        
        // Líneas vacías para separar secciones
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['', '', '', '', ''];
        
        // Sección 2: Detalle de exámenes pendientes y en proceso
        if (!empty($detailedResults)) {
            foreach ($detailedResults as $result) {
                // Convertir objeto a array si es necesario
                $resultArray = is_array($result) ? $result : (array) $result;

                $estado = $resultArray['estado'] ?? 'Sin estado';

                // Solo mostrar pendientes y en proceso
                if (in_array(strtolower($estado), ['pendiente', 'en_proceso'])) {
                    $fechaResultado = 'Sin fecha';
                    if (isset($resultArray['fecha_resultado'])) {
                        try {
                            $fechaResultado = Carbon::parse($resultArray['fecha_resultado'])->format('d/m/Y');
                        } catch (\Exception $e) {
                            $fechaResultado = (string) $resultArray['fecha_resultado'];
                        }
                    }

                    $pacienteNombre = (string) trim(($resultArray['nombre_paciente'] ?? '') . ' ' . ($resultArray['apellido_paciente'] ?? ''));
                    if (empty($pacienteNombre)) {
                        $pacienteNombre = 'Sin nombre';
                    }

                    $dni = (string) ($resultArray['dni_paciente'] ?? 'Sin DNI');
                    $examen = (string) ($resultArray['nombre_examen'] ?? 'Sin examen');
                    
                    // Calcular días transcurridos
                    $diasTranscurridos = 'N/D';
                    if (isset($resultArray['fecha_resultado'])) {
                        try {
                            $fechaExamen = Carbon::parse($resultArray['fecha_resultado']);
                            $diasTranscurridos = $fechaExamen->diffInDays(Carbon::now());
                        } catch (\Exception $e) {
                            $diasTranscurridos = 'N/D';
                        }
                    }

                    // Determinar prioridad basada en días transcurridos
                    $prioridad = 'NORMAL';
                    if (is_numeric($diasTranscurridos)) {
                        if ($diasTranscurridos > 7) {
                            $prioridad = 'CRÍTICA';
                        } elseif ($diasTranscurridos > 3) {
                            $prioridad = 'ALTA';
                        } elseif ($diasTranscurridos > 1) {
                            $prioridad = 'MEDIA';
                        }
                    }

                    $observaciones = $resultArray['observaciones'] ?? '';
                    if (empty($observaciones)) {
                        if ($estado === 'pendiente') {
                            $observaciones = 'Pendiente de procesamiento';
                        } else {
                            $observaciones = 'En proceso de análisis';
                        }
                    }
                    
                    $rows[] = [
                        $fechaResultado,
                        $pacienteNombre,
                        $dni,
                        $examen,
                        strtoupper($estado),
                        $diasTranscurridos,
                        $prioridad,
                        $observaciones
                    ];
                }
            }
        }
        
        if (empty($rows) || count($rows) <= 3) {
            $rows[] = ['No hay exámenes pendientes o en proceso en este período', '', '', '', '', '', '', ''];
        }
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'DC3545']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'DC3545']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Período
            3 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Headers de resumen
            5 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FA']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            6 => [
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E9ECEF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ],
            // Headers de detalle
            9 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FA']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            10 => [
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E9ECEF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // Fecha/Estado
            'B' => 25, // Paciente/Cantidad
            'C' => 12, // DNI/Porcentaje
            'D' => 25, // Examen/Prioridad
            'E' => 15, // Estado/Acción
            'F' => 15, // Días Transcurridos
            'G' => 12, // Prioridad
            'H' => 30, // Observaciones
        ];
    }
}
