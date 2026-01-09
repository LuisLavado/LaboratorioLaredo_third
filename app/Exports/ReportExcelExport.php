<?php

namespace App\Exports;

use App\Exports\ResultsDetailSheet;
use App\Exports\ResultsDetailsSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportExcelExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    use Exportable;

    protected $data;
    protected $type;
    protected $startDate;
    protected $endDate;

    /**
     * Constructor
     * 
     * @param array $data Datos para el Excel
     * @param string $type Tipo de reporte
     * @param string|Carbon $startDate Fecha inicial
     * @param string|Carbon $endDate Fecha final
     */
    public function __construct($data, $type, $startDate, $endDate)
    {
        $this->data = $data;
        $this->type = $type;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Reporte Completo';
    }

    /**
     * Encabezados de la tabla
     */
    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - REPORTE COMPLETO DE RESULTADOS'],
            ['Periodo: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'Fecha Solicitud',
                'Hora',
                'Nº Solicitud',
                'Paciente',
                'DNI',
                'Edad',
                'Género',
                'Doctor',
                'Examen',
                'Código Examen',
                'Parámetro/Campo',
                'Resultado',
                'Unidad',
                'Valores Referencia',
                'Estado',
                'Observaciones',
                'Fuera de Rango'
            ]
        ];
    }

    /**
     * Datos para la exportación completa
     */
    public function array(): array
    {
        $rows = [];

        try {
            if (isset($this->data['patients_with_results']) && !empty($this->data['patients_with_results'])) {
                $rows = $this->processPatientResults($this->data['patients_with_results']);
            } elseif (isset($this->data['patients']) && !empty($this->data['patients'])) {
                $rows = $this->processPatientsData($this->data['patients']);
            } else {
                $rows[] = [
                    'No hay datos disponibles para el período seleccionado.',
                    '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Error en array() de ReportExcelExport', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $rows[] = [
                'Error al procesar los datos: ' . $e->getMessage(),
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
            ];
        }

        return $rows;
    }

    /**
     * Procesar resultados de pacientes con datos completos
     */
    private function processPatientResults($patientsData)
    {
        $rows = [];

        foreach ($patientsData as $patient) {
            if (!isset($patient->solicitudes) || empty($patient->solicitudes)) {
                continue;
            }

            foreach ($patient->solicitudes as $solicitud) {
                if (!isset($solicitud->examenes) || empty($solicitud->examenes)) {
                    // Si no hay exámenes, crear una fila básica
                    $rows[] = $this->createBasicRow($patient, $solicitud);
                    continue;
                }

                foreach ($solicitud->examenes as $examen) {
                    if (isset($examen->resultados) && !empty($examen->resultados)) {
                        $rows = array_merge($rows, $this->processExamResults($patient, $solicitud, $examen));
                    } else {
                        // Examen sin resultados
                        $rows[] = $this->createExamRow($patient, $solicitud, $examen);
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Procesar datos básicos de pacientes
     */
    private function processPatientsData($patientsData)
    {
        $rows = [];

        foreach ($patientsData as $patient) {
            $rows[] = [
                $patient->ultima_visita ?? 'N/A',
                '',
                '',
                $patient->nombre_completo ?? $patient->nombre ?? 'N/A',
                $patient->numero_documento ?? $patient->dni ?? 'N/A',
                $patient->edad ?? 'N/A',
                $patient->genero ?? 'N/A',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ];
        }

        return $rows;
    }

    /**
     * Procesar resultados de un examen específico
     */
    private function processExamResults($patient, $solicitud, $examen)
    {
        $rows = [];
        $resultados = $examen->resultados;

        // Si es un array simple de valores
        if (is_array($resultados)) {
            foreach ($resultados as $resultado) {
                $rows[] = $this->createResultRow($patient, $solicitud, $examen, $resultado);
            }
        } 
        // Si es un objeto con propiedades
        elseif (is_object($resultados)) {
            $resultadosArray = (array) $resultados;
            foreach ($resultadosArray as $campo => $valor) {
                $resultado = (object) [
                    'campo' => $campo,
                    'valor' => $valor,
                    'unidad' => '',
                    'referencia' => '',
                    'estado' => 'completado'
                ];
                $rows[] = $this->createResultRow($patient, $solicitud, $examen, $resultado);
            }
        }
        // Si no hay estructura reconocible
        else {
            $rows[] = $this->createExamRow($patient, $solicitud, $examen, 'Sin resultados estructurados');
        }

        return $rows;
    }

    /**
     * Crear fila básica de paciente/solicitud
     */
    private function createBasicRow($patient, $solicitud, $observacion = 'Sin exámenes')
    {
        return [
            isset($solicitud->fecha_solicitud) ? Carbon::parse($solicitud->fecha_solicitud)->format('d/m/Y') : 'N/A',
            isset($solicitud->fecha_solicitud) ? Carbon::parse($solicitud->fecha_solicitud)->format('H:i') : '',
            $solicitud->id ?? $solicitud->solicitud_id ?? 'N/A',
            $patient->nombre_completo ?? $patient->nombre ?? 'N/A',
            $patient->numero_documento ?? $patient->dni ?? 'N/A',
            $patient->edad ?? 'N/A',
            $patient->genero ?? $patient->sexo ?? 'N/A',
            $solicitud->doctor ?? $solicitud->medico ?? 'N/A',
            '',
            '',
            '',
            '',
            '',
            '',
            $solicitud->estado ?? 'pendiente',
            $observacion,
            ''
        ];
    }

    /**
     * Crear fila de examen sin resultados
     */
    private function createExamRow($patient, $solicitud, $examen, $observacion = 'Sin resultados')
    {
        return [
            isset($solicitud->fecha_solicitud) ? Carbon::parse($solicitud->fecha_solicitud)->format('d/m/Y') : 'N/A',
            isset($solicitud->fecha_solicitud) ? Carbon::parse($solicitud->fecha_solicitud)->format('H:i') : '',
            $solicitud->id ?? $solicitud->solicitud_id ?? 'N/A',
            $patient->nombre_completo ?? $patient->nombre ?? 'N/A',
            $patient->numero_documento ?? $patient->dni ?? 'N/A',
            $patient->edad ?? 'N/A',
            $patient->genero ?? $patient->sexo ?? 'N/A',
            $solicitud->doctor ?? $solicitud->medico ?? 'N/A',
            $examen->nombre ?? $examen->nombre_examen ?? 'N/A',
            $examen->codigo ?? $examen->codigo_examen ?? 'N/A',
            '',
            '',
            '',
            '',
            $examen->estado ?? $solicitud->estado ?? 'pendiente',
            $observacion,
            ''
        ];
    }

    /**
     * Crear fila de resultado específico
     */
    private function createResultRow($patient, $solicitud, $examen, $resultado)
    {
        // Determinar si está fuera de rango
        $fueraRango = '';
        if (isset($resultado->fuera_rango)) {
            $fueraRango = $resultado->fuera_rango ? 'SÍ' : 'NO';
        }

        return [
            isset($solicitud->fecha_solicitud) ? Carbon::parse($solicitud->fecha_solicitud)->format('d/m/Y') : 'N/A',
            isset($solicitud->fecha_solicitud) ? Carbon::parse($solicitud->fecha_solicitud)->format('H:i') : '',
            $solicitud->id ?? $solicitud->solicitud_id ?? 'N/A',
            $patient->nombre_completo ?? $patient->nombre ?? 'N/A',
            $patient->numero_documento ?? $patient->dni ?? 'N/A',
            $patient->edad ?? 'N/A',
            $patient->genero ?? $patient->sexo ?? 'N/A',
            $solicitud->doctor ?? $solicitud->medico ?? 'N/A',
            $examen->nombre ?? $examen->nombre_examen ?? 'N/A',
            $examen->codigo ?? $examen->codigo_examen ?? 'N/A',
            $resultado->campo ?? $resultado->seccion_campo ?? $resultado->parametro ?? 'Resultado',
            $resultado->valor ?? $resultado->resultado ?? 'N/A',
            $resultado->unidad ?? '',
            $resultado->referencia ?? $resultado->valor_referencia ?? '',
            $resultado->estado ?? $examen->estado ?? $solicitud->estado ?? 'completado',
            $resultado->observaciones ?? '',
            $fueraRango
        ];
    }

    /**
     * Anchos de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Fecha
            'B' => 8,   // Hora
            'C' => 10,  // Nº Solicitud
            'D' => 25,  // Paciente
            'E' => 12,  // DNI
            'F' => 6,   // Edad
            'G' => 8,   // Género
            'H' => 20,  // Doctor
            'I' => 25,  // Examen
            'J' => 12,  // Código
            'K' => 20,  // Parámetro
            'L' => 15,  // Resultado
            'M' => 10,  // Unidad
            'N' => 15,  // Ref
            'O' => 12,  // Estado
            'P' => 15,  // Observaciones
            'Q' => 8,   // Fuera Rango
        ];
    }

    /**
     * Estilos para la hoja
     */
    public function styles(Worksheet $sheet)
    {
        // Encabezados principales
        $sheet->getStyle('A1:Q2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A1:Q1');
        $sheet->mergeCells('A2:Q2');
        
        // Encabezados de columnas
        $sheet->getStyle('A4:Q4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        
        // Filas de datos
        $dataRows = $this->array();
        $lastRow = count($dataRows) + 4; // +4 por los encabezados
        
        if ($lastRow > 5) { // Si hay datos
            $sheet->getStyle('A5:Q' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            
            // Centrar ciertas columnas
            $sheet->getStyle('A5:C' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('E5:G' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('J5:J' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('M5:M' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('O5:Q' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            // Destacar valores fuera de rango
            for ($i = 5; $i <= $lastRow; $i++) {
                if ($sheet->getCell('Q' . $i)->getValue() === 'SÍ') {
                    $sheet->getStyle('L' . $i)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
                    ]);
                    $sheet->getStyle('Q' . $i)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
                    ]);
                }
            }
            
            // Color según estado
            for ($i = 5; $i <= $lastRow; $i++) {
                $estado = $sheet->getCell('O' . $i)->getValue();
                $color = 'FFFFFF'; // Blanco por defecto
                
                switch ($estado) {
                    case 'completado':
                    case 'finalizado':
                        $color = 'DDFFDD'; // Verde claro
                        break;
                    case 'en_proceso':
                    case 'procesando':
                        $color = 'FFFFDD'; // Amarillo claro
                        break;
                    case 'pendiente':
                        $color = 'FFDDDD'; // Rojo claro
                        break;
                }
                
                $sheet->getStyle('O' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $color]
                    ]
                ]);
            }
        }
        
        return $sheet;
    }
}

/**
 * Hoja de resumen
 */
class ReportSummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $data;
    protected $type;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $type, $startDate, $endDate)
    {
        $this->data = $data;
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Resumen';
    }

    /**
     * Encabezados de la tabla
     */
    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['🏥 LABORATORIO CLÍNICO LAREDO'],
            ['📊 REPORTE DE ' . strtoupper($this->getReportTypeName())],
            ['📅 Período: ' . $dateRange],
            ['⏰ Generado: ' . now()->format('d/m/Y H:i:s')],
            [],
            ['📋 RESUMEN EJECUTIVO'],
            [],
            ['Concepto', 'Cantidad', 'Porcentaje', 'Observaciones']
        ];
    }

    /**
     * Datos para la hoja
     */
    public function array(): array
    {
        $rows = [];
        
        switch ($this->type) {
            case 'general':
                $rows = $this->getGeneralReportRows();
                break;
            case 'patients':
                $rows = $this->getPatientsReportRows();
                break;
            case 'exams':
                $rows = $this->getExamsReportRows();
                break;
            case 'doctors':
                $rows = $this->getDoctorsReportRows();
                break;
            case 'services':
                $rows = $this->getServicesReportRows();
                break;
            case 'results':
                $rows = $this->getResultsReportRows();
                break;
            case 'categories':
                $rows = $this->getCategoriesReportRows();
                break;
        }
        
        // Añadir información de generación al final
        $rows[] = [];
        $rows[] = ['Generado por:', $this->data['generatedBy'] ?? 'Sistema', '', ''];
        $rows[] = ['Fecha de generación:', now()->format('d/m/Y H:i:s'), '', ''];
        
        return $rows;
    }
    
    /**
     * Estilos de la hoja
     */
    public function styles(Worksheet $sheet)
    {
        // Encabezado principal del laboratorio
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 18,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E7D32'] // Verde médico
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]
            ]
        ]);
        
        // Título del reporte
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1976D2'] // Azul profesional
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]
            ]
        ]);
        
        // Período y fecha de generación
        $sheet->getStyle('A3:D4')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 11,
                'color' => ['rgb' => '37474F']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD'] // Azul muy claro
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        
        // Título de sección
        $sheet->getStyle('A6:D6')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF6F00'] // Naranja ejecutivo
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]
            ]
        ]);
        
        // Encabezados de columnas
        $sheet->getStyle('A8:D8')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '424242'] // Gris oscuro
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]
            ]
        ]);
        
        // Ajustar altura de filas para mejor apariencia
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(18);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(6)->setRowHeight(20);
        $sheet->getRowDimension(8)->setRowHeight(20);
        
        return $sheet;
    }
    
    /**
     * Ancho de las columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Fecha
            'B' => 8,   // Hora
            'C' => 10,  // Nº Solicitud
            'D' => 25,  // Paciente
            'E' => 12,  // DNI
            'F' => 6,   // Edad
            'G' => 8,   // Género
            'H' => 20,  // Doctor
            'I' => 25,  // Examen
            'J' => 12,  // Código
            'K' => 20,  // Parámetro
            'L' => 15,  // Resultado
            'M' => 10,  // Unidad
            'N' => 15,  // Ref
            'O' => 12,  // Estado
            'P' => 15,  // Observaciones
            'Q' => 8,   // Fuera Rango
        ];
    }

    /**
     * Obtener el nombre del tipo de reporte
     */
    protected function getReportTypeName(): string
    {
        $types = [
            'general' => 'General',
            'patients' => 'Pacientes',
            'exams' => 'Exámenes',
            'doctors' => 'Doctores',
            'services' => 'Servicios',
            'results' => 'Resultados',
            'categories' => 'Categorías'
        ];
        
        return $types[$this->type] ?? $this->type;
    }
    
    /**
     * Obtener las filas para el reporte general
     */
    protected function getGeneralReportRows(): array
    {
        $rows = [];
        
        // Estadísticas generales
        $rows[] = ['ESTADÍSTICAS GENERALES', '', '', ''];
        
        if (isset($this->data['solicitudes_count'])) {
            $rows[] = ['Total solicitudes', $this->data['solicitudes_count'], '100%', ''];
        }
        
        if (isset($this->data['resultados_count'])) {
            $percentage = isset($this->data['solicitudes_count']) && $this->data['solicitudes_count'] > 0 
                ? round(($this->data['resultados_count'] / $this->data['solicitudes_count']) * 100, 2) . '%'
                : 'N/A';
            $rows[] = ['Total resultados', $this->data['resultados_count'], $percentage, ''];
        }
        
        if (isset($this->data['examenes_count'])) {
            $rows[] = ['Exámenes realizados', $this->data['examenes_count'], '', ''];
        }
        
        if (isset($this->data['pacientes_count'])) {
            $rows[] = ['Pacientes atendidos', $this->data['pacientes_count'], '', ''];
        }
        
        // Solicitudes por estado
        if (isset($this->data['solicitudesPorEstado']) && !empty($this->data['solicitudesPorEstado'])) {
            $rows[] = [];
            $rows[] = ['SOLICITUDES POR ESTADO', '', '', ''];
            
            foreach ($this->data['solicitudesPorEstado'] as $estado => $count) {
                $percentage = isset($this->data['solicitudes_count']) && $this->data['solicitudes_count'] > 0
                    ? round(($count / $this->data['solicitudes_count']) * 100, 2) . '%'
                    : 'N/A';
                $rows[] = [ucfirst($estado), $count, $percentage, ''];
            }
        }
        
        // Solicitudes por servicio
        if (isset($this->data['solicitudesPorServicio']) && !empty($this->data['solicitudesPorServicio'])) {
            $rows[] = [];
            $rows[] = ['SOLICITUDES POR SERVICIO', '', '', ''];
            
            foreach ($this->data['solicitudesPorServicio'] as $servicio) {
                $percentage = isset($this->data['solicitudes_count']) && $this->data['solicitudes_count'] > 0
                    ? round(($servicio->total / $this->data['solicitudes_count']) * 100, 2) . '%'
                    : 'N/A';
                $rows[] = [$servicio->nombre, $servicio->total, $percentage, ''];
            }
        }
        
        return $rows;
    }
    
    /**
     * Obtener las filas para el reporte de pacientes
     */
    protected function getPatientsReportRows(): array
    {
        $rows = [];
        
        // Estadísticas de pacientes
        $rows[] = ['ESTADÍSTICAS DE PACIENTES', '', '', ''];
        
        if (isset($this->data['pacientes_count'])) {
            $rows[] = ['Total pacientes', $this->data['pacientes_count'], '100%', ''];
        }
        
        // Pacientes por género
        if (isset($this->data['genderStats']) && !empty($this->data['genderStats'])) {
            $rows[] = [];
            $rows[] = ['PACIENTES POR GÉNERO', '', '', ''];
            
            foreach ($this->data['genderStats'] as $gender => $count) {
                $genderName = $gender == 'M' ? 'Masculino' : ($gender == 'F' ? 'Femenino' : 'No especificado');
                $percentage = isset($this->data['pacientes_count']) && $this->data['pacientes_count'] > 0
                    ? round(($count / $this->data['pacientes_count']) * 100, 2) . '%'
                    : 'N/A';
                $rows[] = [$genderName, $count, $percentage, ''];
            }
        }
        
        return $rows;
    }
    
    /**
     * Obtener las filas para el reporte de exámenes
     */
    protected function getExamsReportRows(): array
    {
        $rows = [];
        
        // Estadísticas de exámenes
        $rows[] = ['ESTADÍSTICAS DE EXÁMENES', '', '', ''];
        
        if (isset($this->data['examenes_count'])) {
            $rows[] = ['Total exámenes', $this->data['examenes_count'], '100%', ''];
        }
        
        // Exámenes por categoría
        if (isset($this->data['examenesPorCategoria']) && !empty($this->data['examenesPorCategoria'])) {
            $rows[] = [];
            $rows[] = ['EXÁMENES POR CATEGORÍA', '', '', ''];
            
            foreach ($this->data['examenesPorCategoria'] as $categoria) {
                $percentage = isset($this->data['examenes_count']) && $this->data['examenes_count'] > 0
                    ? round(($categoria->total / $this->data['examenes_count']) * 100, 2) . '%'
                    : 'N/A';
                $rows[] = [$categoria->nombre, $categoria->total, $percentage, ''];
            }
        }
        
        return $rows;
    }
    
    /**
     * Obtener las filas para el reporte de doctores
     */
    protected function getDoctorsReportRows(): array
    {
        $rows = [];
        
        // Estadísticas de doctores
        $rows[] = ['ESTADÍSTICAS DE DOCTORES', '', '', ''];
        
        // Solicitudes por doctor
        if (isset($this->data['solicitudesPorDoctor']) && !empty($this->data['solicitudesPorDoctor'])) {
            $rows[] = [];
            $rows[] = ['SOLICITUDES POR DOCTOR', '', '', ''];
            
            foreach ($this->data['solicitudesPorDoctor'] as $doctor) {
                $percentage = isset($this->data['solicitudes_count']) && $this->data['solicitudes_count'] > 0
                    ? round(($doctor->total / $this->data['solicitudes_count']) * 100, 2) . '%'
                    : 'N/A';
                $rows[] = [$doctor->nombre . ' ' . $doctor->apellido, $doctor->total, $percentage, ''];
            }
        }
        
        return $rows;
    }
    
    /**
     * Obtener las filas para el reporte de servicios
     */
    protected function getServicesReportRows(): array
    {
        $rows = [];
        
        // Estadísticas de servicios
        $rows[] = ['ESTADÍSTICAS DE SERVICIOS', '', '', ''];
        
        if (isset($this->data['servicios_count'])) {
            $rows[] = ['Total servicios', $this->data['servicios_count'], '100%', ''];
        }
        
        // Solicitudes por servicio
        if (isset($this->data['solicitudesPorServicio']) && !empty($this->data['solicitudesPorServicio'])) {
            $rows[] = [];
            $rows[] = ['SOLICITUDES POR SERVICIO', '', '', ''];
            
            foreach ($this->data['solicitudesPorServicio'] as $servicio) {
                $percentage = isset($this->data['solicitudes_count']) && $this->data['solicitudes_count'] > 0
                    ? round(($servicio->total / $this->data['solicitudes_count']) * 100, 2) . '%'
                    : 'N/A';
                $rows[] = [$servicio->nombre, $servicio->total, $percentage, ''];
            }
        }
        
        return $rows;
    }
    
    /**
     * Obtener las filas para el reporte de resultados
     */
    protected function getResultsReportRows(): array
    {
        $rows = [];
        
        // Estadísticas de resultados
        $rows[] = ['ESTADÍSTICAS DE RESULTADOS', '', '', ''];
        
        if (isset($this->data['total_resultados'])) {
            $rows[] = ['Total resultados', $this->data['total_resultados'], '100%', ''];
        }
        
        if (isset($this->data['pacientes_con_resultados'])) {
            $rows[] = ['Pacientes con resultados', $this->data['pacientes_con_resultados'], '', ''];
        }
        
        if (isset($this->data['examenes_con_resultados'])) {
            $rows[] = ['Exámenes con resultados', $this->data['examenes_con_resultados'], '', ''];
        }
        
        // Resultados por estado
        if (isset($this->data['statusCounts']) && !empty($this->data['statusCounts'])) {
            $rows[] = [];
            $rows[] = ['RESULTADOS POR ESTADO', '', '', ''];
            
            $totalEstados = array_sum($this->data['statusCounts']);
            foreach ($this->data['statusCounts'] as $estado => $count) {
                $percentage = $totalEstados > 0 ? round(($count / $totalEstados) * 100, 2) . '%' : 'N/A';
                $rows[] = [ucfirst($estado), $count, $percentage, ''];
            }
        }
        
        // Estadísticas diarias (top 5 días)
        if (isset($this->data['dailyStats']) && !empty($this->data['dailyStats'])) {
            $rows[] = [];
            $rows[] = ['TOP 5 DÍAS CON MÁS RESULTADOS', '', '', ''];
            
            $topDays = array_slice($this->data['dailyStats'], 0, 5);
            foreach ($topDays as $day) {
                $rows[] = [$day->fecha ?? 'N/A', $day->total_resultados ?? 0, '', 'Completados: ' . ($day->completados ?? 0)];
            }
        }
        
        return $rows;
    }
    
    /**
     * Obtener las filas para el reporte de categorías
     */
    protected function getCategoriesReportRows(): array
    {
        $rows = [];
        
        // Estadísticas de categorías
        $rows[] = ['ESTADÍSTICAS DE CATEGORÍAS', '', '', ''];
        
        if (isset($this->data['total_categorias'])) {
            $rows[] = ['Total categorías', $this->data['total_categorias'], '100%', ''];
        }
        
        // Categorías por popularidad
        if (isset($this->data['categoryStats']) && !empty($this->data['categoryStats'])) {
            $rows[] = [];
            $rows[] = ['CATEGORÍAS POR POPULARIDAD', '', '', ''];
            
            $totalExamenes = array_sum(array_column($this->data['categoryStats'], 'total_examenes'));
            foreach ($this->data['categoryStats'] as $categoria) {
                $percentage = $totalExamenes > 0 ? round(($categoria['total_examenes'] / $totalExamenes) * 100, 2) . '%' : 'N/A';
                $rows[] = [$categoria['nombre'] ?? 'Sin categoría', $categoria['total_examenes'] ?? 0, $percentage, 'Exámenes únicos: ' . ($categoria['examenes_unicos'] ?? 0)];
            }
        }
        
        // Top exámenes por categoría
        if (isset($this->data['topExamsByCategory']) && !empty($this->data['topExamsByCategory'])) {
            $rows[] = [];
            $rows[] = ['TOP EXÁMENES POR CATEGORÍA', '', '', ''];
            
            $topExams = array_slice($this->data['topExamsByCategory'], 0, 10);
            foreach ($topExams as $exam) {
                $rows[] = [$exam['examen_nombre'] ?? 'N/A', $exam['total'] ?? 0, '', 'Categoría: ' . ($exam['categoria'] ?? 'Sin categoría')];
            }
        }
        
        return $rows;
    }
}

/**
 * Hoja de detalle de pacientes
 */
class PatientsDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $patients;
    protected $startDate;
    protected $endDate;

    public function __construct($patients, $startDate, $endDate)
    {
        $this->patients = $patients;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Detalle de Pacientes';
    }

    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE DE PACIENTES CON RESULTADOS'],
            ['Periodo: ' . $dateRange],
            [],
            ['DNI', 'Nombres', 'Apellidos', 'Historia Clínica', 'Sexo', 'Edad', 'Celular', 'Fecha Nac.', 'Total Solicitudes', 'Total Exámenes']
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        foreach ($this->patients as $patient) {
            // Formateamos los campos según la estructura real de la base de datos con validación
            $sexo = isset($patient->sexo) 
                ? ($patient->sexo == 'masculino' ? 'Masculino' : ($patient->sexo == 'femenino' ? 'Femenino' : 'No especificado'))
                : 'No especificado';
            $fechaNac = isset($patient->fecha_nacimiento) && $patient->fecha_nacimiento 
                ? date('d/m/Y', strtotime($patient->fecha_nacimiento)) 
                : 'N/A';
            
            $rows[] = [
                $patient->documento ?? 'N/A',
                $patient->nombres ?? 'N/A',
                $patient->apellidos ?? 'N/A',
                $patient->historia_clinica ?? 'N/A',
                $sexo,
                $patient->edad ?? 'N/A',
                $patient->celular ?? 'N/A',
                $fechaNac,
                $patient->total_solicitudes ?? 0,
                $patient->total_examenes ?? 0
            ];
            
            // Añadir un separador después de cada paciente
            $rows[] = ['', '', '', '', '', '', '', '', '', ''];
            
            // Añadir el detalle de solicitudes de este paciente si están disponibles
            if (isset($patient->solicitudes_detalle) && count($patient->solicitudes_detalle) > 0) {
                $rows[] = ['DETALLE DE SOLICITUDES Y RESULTADOS DEL PACIENTE:', '', '', '', '', '', '', '', '', ''];
                $rows[] = ['Fecha', 'N° Recibo', 'Servicio', 'Examen', 'Código', 'Estado', 'Resultados', 'Valores Ref.', 'Observaciones', ''];
                
                foreach ($patient->solicitudes_detalle as $solicitud) {
                    // Cada solicitud tiene múltiples exámenes
                    if (isset($solicitud->examenes) && count($solicitud->examenes) > 0) {
                        foreach ($solicitud->examenes as $examen) {
                            // Formatear resultados y referencias usando la misma lógica de SinglePatientSheet
                            $resultados_texto = $this->formatearResultadosDetalle($examen);
                            $referencias_texto = $this->formatearReferenciasDetalle($examen);
                            
                            $rows[] = [
                                $solicitud->fecha ? date('d/m/Y', strtotime($solicitud->fecha)) : 'N/A',
                                $solicitud->numero_recibo ?? 'No registrado',
                                $solicitud->servicio ?? 'No asignado',
                                $examen->nombre ?? 'N/A',
                                $examen->codigo ?? 'N/A',
                                strtoupper($examen->estado ?? 'N/A'),
                                $resultados_texto,
                                $referencias_texto,
                                $examen->observaciones ?? '',
                                ''
                            ];
                        }
                    }
                }
                
                // Añadir un separador después del detalle
                $rows[] = ['', '', '', '', '', '', '', '', '', ''];
                $rows[] = ['', '', '', '', '', '', '', '', '', ''];
            }
        }
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            4 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ]
        ];
        
        // Detectar y aplicar estilos a filas especiales
        $data = $this->array();
        $rowIndex = 5; // Comenzamos desde la primera fila de datos (después de encabezados)
        
        foreach ($data as $row) {
            // Detectar fila de título "DETALLE DE SOLICITUDES Y RESULTADOS DEL PACIENTE"
            if (isset($row[0]) && $row[0] === 'DETALLE DE SOLICITUDES Y RESULTADOS DEL PACIENTE:') {
                $styles[$rowIndex] = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DDEBF7']
                    ]
                ];
            }
            
            // Detectar encabezados de detalle con resultados
            if (isset($row[0]) && $row[0] === 'Fecha' && isset($row[6]) && $row[6] === 'Resultados') {
                $styles[$rowIndex] = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BDD7EE']
                    ]
                ];
            }
            
            // Resaltar valores fuera de rango with emoji de advertencia en la columna de resultados
            if (isset($row[6]) && is_string($row[6]) && strpos($row[6], '⚠️') !== false) {
                $styles['G' . $rowIndex] = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'DC3545']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8D7DA'] // Rojo claro para valores críticos
                    ]
                ];
            }
            
            // Estados pendientes y en proceso
            if (isset($row[5]) && in_array($row[5], ['PENDIENTE', 'EN PROCESO'])) {
                $styles['E' . $rowIndex . ':F' . $rowIndex] = [
                    'font' => ['bold' => true, 'color' => ['rgb' => '856404']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3CD']
                    ]
                ];
            }
            
            // Estados completados
            if (isset($row[5]) && $row[5] === 'COMPLETADO') {
                $styles['F' . $rowIndex] = [
                    'font' => ['bold' => true, 'color' => ['rgb' => '155724']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D4EDDA'] // Verde claro para completados
                    ]
                ];
            }
            
            $rowIndex++;
        }
        
        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // DNI
            'B' => 20, // Nombres
            'C' => 20, // Apellidos
            'D' => 15, // Historia Clínica
            'E' => 12, // Sexo
            'F' => 8,  // Edad
            'G' => 15, // Celular
            'H' => 12, // Fecha Nacimiento
            'I' => 15, // Total Solicitudes
            'J' => 15, // Total Examenes
        ];
    }
    
    /**
     * Formatear resultados de un examen para el detalle de pacientes
     */
    private function formatearResultadosDetalle($examen)
    {
        // Si el examen no está completado, mostrar el estado
        $estado = strtolower($examen->estado ?? '');
        if ($estado !== 'completado') {
            switch ($estado) {
                case 'pendiente':
                    return 'PENDIENTE';
                case 'en_proceso':
                case 'en proceso':
                    return 'EN PROCESO';
                default:
                    return 'ESTADO: ' . strtoupper($estado);
            }
        }
        
        // Para exámenes completados, usar el valor directamente si está disponible
        if (isset($examen->valor) && !empty(trim($examen->valor))) {
            $resultado_texto = trim($examen->valor);
            
            // Añadir unidad si está disponible
            if (isset($examen->unidad) && !empty(trim($examen->unidad))) {
                $resultado_texto .= ' ' . trim($examen->unidad);
            }
            
            // Añadir indicador si está fuera de rango
            if (isset($examen->fuera_rango) && $examen->fuera_rango) {
                $resultado_texto .= ' ⚠️';
            }
            
            return $resultado_texto;
        }
        
        // Si no hay valor directo, buscar en estructuras anidadas
        $datos_resultados = $this->obtenerDatosResultadosDetalle($examen);
        
        if (empty($datos_resultados)) {
            return 'COMPLETADO - Consultar laboratorio';
        }
        
        $resultados_formateados = [];
        foreach ($datos_resultados as $resultado) {
            $texto_resultado = $this->formatearResultadoIndividualDetalle($resultado);
            if (!empty($texto_resultado)) {
                $resultados_formateados[] = $texto_resultado;
            }
        }
        
        if (empty($resultados_formateados)) {
            return 'COMPLETADO - Consultar laboratorio';
        }
        
        return implode(' | ', $resultados_formateados);
    }

    /**
     * Formatear valores de referencia para el detalle de pacientes
     */
    private function formatearReferenciasDetalle($examen)
    {
        // Si el examen no está completado, no mostrar referencias
        $estado = strtolower($examen->estado ?? '');
        if ($estado !== 'completado') {
            return 'N/A';
        }
        
        // Usar valor de referencia directamente si está disponible
        if (isset($examen->valor_referencia) && !empty(trim($examen->valor_referencia))) {
            return trim($examen->valor_referencia);
        }
        
        // Si no hay valor directo, buscar en estructuras anidadas
        $datos_resultados = $this->obtenerDatosResultadosDetalle($examen);
        
        if (empty($datos_resultados)) {
            return 'Consultar laboratorio';
        }
        
        $referencias_formateadas = [];
        foreach ($datos_resultados as $resultado) {
            $texto_referencia = $this->formatearReferenciaIndividualDetalle($resultado);
            if (!empty($texto_referencia)) {
                $referencias_formateadas[] = $texto_referencia;
            }
        }
        
        if (empty($referencias_formateadas)) {
            return 'Consultar laboratorio';
        }
        
        return implode(' | ', $referencias_formateadas);
    }
    
    /**
     * Obtener datos de resultados desde diferentes estructuras posibles
     */
    private function obtenerDatosResultadosDetalle($examen)
    {
        $estructuras_posibles = [
            'resultados', 'resultado', 'datos', 'valores', 'fields', 'parametros',
            'results', 'data', 'values', 'parameters', 'exam_results', 'test_results'
        ];
        
        foreach ($estructuras_posibles as $estructura) {
            if (isset($examen->$estructura)) {
                $datos = $examen->$estructura;
                
                if (empty($datos)) {
                    continue;
                }
                
                if (is_object($datos) && !is_array($datos)) {
                    $propiedades = get_object_vars($datos);
                    if (!empty($propiedades)) {
                        return [$datos];
                    }
                }
                
                if (is_array($datos)) {
                    $datos_filtrados = array_filter($datos, function($item) {
                        if (is_null($item)) return false;
                        if (is_object($item)) {
                            $propiedades = get_object_vars($item);
                            return !empty($propiedades);
                        }
                        if (is_array($item)) {
                            return !empty($item);
                        }
                        return !empty(trim($item));
                    });
                    
                    if (!empty($datos_filtrados)) {
                        return array_values($datos_filtrados);
                    }
                }
                
                if (is_string($datos) && !empty(trim($datos))) {
                    $json_decoded = json_decode($datos, true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($json_decoded)) {
                        return is_array($json_decoded) ? $json_decoded : [$json_decoded];
                    }
                    
                    return [(object)['valor' => trim($datos)]];
                }
            }
        }
        
        // Como último recurso, buscar propiedades directas
        $propiedades_directas = ['valor', 'value', 'resultado', 'result'];
        foreach ($propiedades_directas as $prop) {
            if (isset($examen->$prop) && !empty(trim($examen->$prop))) {
                return [(object)[
                    'campo' => $examen->nombre ?? 'Resultado',
                    'valor' => trim($examen->$prop),
                    'unidad' => $examen->unidad ?? '',
                    'valor_referencia' => $examen->valor_referencia ?? $examen->referencia ?? ''
                ]];
            }
        }
        
        return [];
    }
    
    /**
     * Formatear un resultado individual
     */
    private function formatearResultadoIndividualDetalle($resultado)
    {
        if (is_null($resultado)) {
            return '';
        }
        
        if (is_array($resultado)) {
            $resultado = (object)$resultado;
        }
        
        $campos_posibles = [
            'campo' => ['campo', 'name', 'parametro', 'parameter', 'test_name', 'exam_name', 'nombre'],
            'valor' => ['valor', 'value', 'resultado', 'result', 'measure', 'measurement'],
            'unidad' => ['unidad', 'unit', 'units', 'medida', 'measure_unit'],
            'fuera_rango' => ['fuera_rango', 'out_of_range', 'critical', 'abnormal', 'flag']
        ];
        
        $campo = '';
        $valor = '';
        $unidad = '';
        $fuera_rango = false;
        
        foreach ($campos_posibles['campo'] as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $campo = trim($resultado->$key);
                break;
            }
        }
        
        foreach ($campos_posibles['valor'] as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $valor = trim($resultado->$key);
                break;
            }
        }
        
        foreach ($campos_posibles['unidad'] as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $unidad = trim($resultado->$key);
                break;
            }
        }
        
        foreach ($campos_posibles['fuera_rango'] as $key) {
            if (isset($resultado->$key)) {
                $fuera_rango = in_array($resultado->$key, [1, '1', true, 'true', 'yes', 'sí']);
                break;
            }
        }
        
        if (empty($valor)) {
            return '';
        }
        
        $texto = '';
        if (!empty($campo)) {
            $texto .= $campo . ': ';
        }
        
        $texto .= $valor;
        
        if (!empty($unidad)) {
            $texto .= ' ' . $unidad;
        }
        
        if ($fuera_rango) {
            $texto .= ' ⚠️';
        }
        
        return $texto;
    }
    
    /**
     * Formatear una referencia individual
     */
    private function formatearReferenciaIndividualDetalle($resultado)
    {
        if (is_null($resultado)) {
            return '';
        }
        
        if (is_array($resultado)) {
            $resultado = (object)$resultado;
        }
        
        $campos_referencia = [
            'valor_referencia', 'reference_value', 'referencia', 'reference', 
            'normal_range', 'rango_normal', 'range', 'ref_value', 'ref_range'
        ];
        
        $campos_nombre = [
            'campo', 'name', 'parametro', 'parameter', 'test_name', 'exam_name', 'nombre'
        ];
        
        $campo = '';
        $referencia = '';
        
        foreach ($campos_nombre as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $campo = trim($resultado->$key);
                break;
            }
        }
        
        foreach ($campos_referencia as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $referencia = trim($resultado->$key);
                break;
            }
        }
        
        if (empty($referencia)) {
            return '';
        }
        
        $texto = '';
        if (!empty($campo)) {
            $texto .= $campo . ': ';
        }
        
        $texto .= $referencia;
        
        return $texto;
    }
}

/**
 * Hoja de detalle de exámenes
 * Implementa las interfaces básicas para plantilla
 */
class ExamsDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $exams;
    protected $startDate;
    protected $endDate;

    public function __construct($exams, $startDate, $endDate)
    {
        $this->exams = $exams;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Detalle de Exámenes';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE DE EXÁMENES'],
            ['Periodo: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            ['Código', 'Nombre', 'Categoría', 'Total Realizados', 'Total Pacientes', 'Pendientes', 'En Proceso', 'Completados']
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        foreach ($this->exams as $exam) {
            // Fila principal del examen
            $rows[] = [
                $exam->codigo ?? 'N/A',
                $exam->nombre ?? 'N/A',
                $exam->categoria ?? 'Sin categoría',
                $exam->total_realizados ?? 0,
                $exam->total_pacientes ?? 0,
                $exam->pendientes ?? 0,
                $exam->en_proceso ?? 0,
                $exam->completados ?? 0
            ];
            
            // Separador
            $rows[] = ['', '', '', '', '', '', '', ''];
            
            // Campos del examen si están disponibles
            if (isset($exam->campos) && count($exam->campos) > 0) {
                $rows[] = ['CAMPOS / PARÁMETROS DEL EXAMEN:', '', '', '', '', '', '', ''];
                $rows[] = ['Nombre', 'Tipo', 'Unidad', 'Valores de Referencia', 'Sección', 'Estado', '', ''];
                
                foreach ($exam->campos as $campo) {
                    $rows[] = [
                        $campo->nombre ?? 'N/A',
                        $campo->tipo ?? 'N/A',
                        $campo->unidad ?? '-',
                        $campo->valor_referencia ?? '-',
                        $campo->seccion ?? 'General',
                        $campo->activo ? 'Activo' : 'Inactivo',
                        '',
                        ''
                    ];
                }
                
                // Separador después de los campos
                $rows[] = ['', '', '', '', '', '', '', ''];
            }
            
            // Servicios principales donde se ha solicitado este examen
            if (isset($exam->servicios_principales) && count($exam->servicios_principales) > 0) {
                $rows[] = ['SERVICIOS QUE MÁS SOLICITAN ESTE EXAMEN:', '', '', '', '', '', '', ''];
                $rows[] = ['Servicio', 'Total Solicitudes', '', '', '', '', '', ''];
                
                foreach ($exam->servicios_principales as $servicio) {
                    $rows[] = [
                        $servicio->nombre ?? 'N/A',
                        $servicio->total ?? 0,
                        '',
                        '',
                        '',
                        '',
                        '',
                        ''
                    ];
                }
                
                // Separador después de los servicios
                $rows[] = ['', '', '', '', '', '', '', ''];
                $rows[] = ['', '', '', '', '', '', '', ''];
            }
        }
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            4 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ]
        ];

        // Detectar y aplicar estilos a filas especiales
        $data = $this->array();
        $rowIndex = 5; // Comenzamos desde la primera fila de datos (después de encabezados)
        
        foreach ($data as $row) {
            // Detectar filas de encabezados de sección
            if (isset($row[0])) {
                if ($row[0] === 'CAMPOS / PARÁMETROS DEL EXAMEN:' || $row[0] === 'SERVICIOS QUE MÁS SOLICITAN ESTE EXAMEN:') {
                    $styles[$rowIndex] = [
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'DDEBF7']
                        ]
                    ];
                }
                
                // Detectar encabezados de detalle
                if (($row[0] === 'Nombre' && $row[1] === 'Tipo') || ($row[0] === 'Servicio' && $row[1] === 'Total Solicitudes')) {
                    $styles[$rowIndex] = [
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'BDD7EE']
                        ]
                    ];
                }
            }
            
            $rowIndex++;
        }
        
        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Código
            'B' => 35, // Nombre
            'C' => 20, // Categoría
            'D' => 15, // Total Realizados
            'E' => 15, // Total Pacientes
            'F' => 15, // Pendientes
            'G' => 15, // En Proceso
            'H' => 15, // Completados
        ];
    }
}

/**
 * Hoja de detalle de doctores
 */
class DoctorsDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $doctors;
    protected $startDate;
    protected $endDate;

    public function __construct($doctors, $startDate, $endDate) 
    {
        $this->doctors = $doctors;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Detalle de Médicos';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE DE MÉDICOS'],
            ['Periodo: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            ['Nombre', 'Especialidad', 'Colegiatura', 'Rol', 'Total Solicitudes', 'Total Exámenes', 'Total Pacientes']
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        foreach ($this->doctors as $doctor) {
            // Fila principal del médico
            $rows[] = [
                $doctor->nombre ?? 'N/A',
                $doctor->especialidad ?? 'No especificado',
                $doctor->colegiatura ?? 'N/A',
                $this->formatRole($doctor->role ?? 'user'),
                $doctor->total_solicitudes ?? 0,
                $doctor->total_examenes ?? 0,
                $doctor->total_pacientes ?? 0
            ];
            
            // Separador
            $rows[] = ['', '', '', '', '', '', ''];
            
            // Exámenes más solicitados por este médico
            if (isset($doctor->examenes_principales) && count($doctor->examenes_principales) > 0) {
                $rows[] = ['EXÁMENES MÁS SOLICITADOS POR ESTE MÉDICO:', '', '', '', '', '', ''];
                $rows[] = ['Nombre', 'Código', 'Total', '', '', '', ''];
                
                foreach ($doctor->examenes_principales as $examen) {
                    $rows[] = [
                        $examen->nombre ?? 'N/A',
                        $examen->codigo ?? 'N/A',
                        $examen->total ?? 0,
                        '',
                        '',
                        '',
                        ''
                    ];
                }
                
                // Separador después de los exámenes
                $rows[] = ['', '', '', '', '', '', ''];
            }
            
            // Servicios más utilizados por este médico
            if (isset($doctor->servicios_principales) && count($doctor->servicios_principales) > 0) {
                $rows[] = ['SERVICIOS MÁS UTILIZADOS POR ESTE MÉDICO:', '', '', '', '', '', ''];
                $rows[] = ['Servicio', 'Total Solicitudes', '', '', '', '', ''];
                
                foreach ($doctor->servicios_principales as $servicio) {
                    $rows[] = [
                        $servicio->nombre ?? 'N/A',
                        $servicio->total ?? 0,
                        '',
                        '',
                        '',
                        '',
                        ''
                    ];
                }
                
                // Separador después de los servicios
                $rows[] = ['', '', '', '', '', '', ''];
            }
            
            // Estadísticas de resultados de este médico
            if (isset($doctor->estadisticas_resultados) && count($doctor->estadisticas_resultados) > 0) {
                $rows[] = ['ESTADÍSTICAS DE RESULTADOS:', '', '', '', '', '', ''];
                $rows[] = ['Estado', 'Cantidad', 'Porcentaje', '', '', '', ''];
                
                $totalResultados = 0;
                foreach ($doctor->estadisticas_resultados as $estadistica) {
                    $totalResultados += $estadistica->total;
                }
                
                foreach ($doctor->estadisticas_resultados as $estadistica) {
                    $porcentaje = $totalResultados > 0 ? round(($estadistica->total / $totalResultados) * 100, 2) . '%' : '0%';
                    $rows[] = [
                        ucfirst($estadistica->estado ?? 'N/A'),
                        $estadistica->total ?? 0,
                        $porcentaje,
                        '',
                        '',
                        '',
                        ''
                    ];
                }
                
                // Separador después de las estadísticas
                $rows[] = ['', '', '', '', '', '', ''];
                $rows[] = ['', '', '', '', '', '', ''];
            }
        }
        
        return $rows;
    }
    
    /**
     * Formatear el rol para una mejor visualización
     */
    private function formatRole($role)
    {
        $roles = [
            'admin' => 'Administrador',
            'doctor' => 'Médico',
            'laboratorio' => 'Laboratorio',
            'recepcion' => 'Recepción',
            'user' => 'Usuario'
        ];
        
        return $roles[$role] ?? ucfirst($role);
    }
    
    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            4 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ]
        ];

        // Detectar y aplicar estilos a filas especiales
        $data = $this->array();
        $rowIndex = 5; // Comenzamos desde la primera fila de datos (después de encabezados)
        
        foreach ($data as $row) {
            // Detectar filas de encabezados de sección
            if (isset($row[0])) {
                if (strpos($row[0], 'EXÁMENES MÁS SOLICITADOS') === 0 || 
                    strpos($row[0], 'SERVICIOS MÁS UTILIZADOS') === 0 ||
                    strpos($row[0], 'ESTADÍSTICAS DE RESULTADOS') === 0) {
                    $styles[$rowIndex] = [
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'DDEBF7']
                        ]
                    ];
                }
                
                // Detectar encabezados de detalle
                if (($row[0] === 'Nombre' && $row[1] === 'Tipo') || ($row[0] === 'Servicio' && $row[1] === 'Total Solicitudes')) {
                    $styles[$rowIndex] = [
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'BDD7EE']
                        ]
                    ];
                }
            }
            
            $rowIndex++;
        }
        
        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35, // Nombre
            'B' => 20, // Especialidad
            'C' => 15, // Colegiatura
            'D' => 15, // Rol
            'E' => 15, // Total Solicitudes
            'F' => 15, // Total Exámenes
            'G' => 15, // Total Pacientes
        ];
    }
}

/**
 * Hoja de detalle de servicios
 */
class ServicesDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $services;
    protected $startDate;
    protected $endDate;

    public function __construct($services, $startDate, $endDate)
    {
        // Asegurarse de que $services sea compatible tanto como colección como array
        $this->services = $services;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        // Registrar para depuración
        \Log::info('ServicesDetailSheet inicializado', [
            'tipo_services' => gettype($services),
            'es_coleccion' => is_object($services) && method_exists($services, 'count') ? 'sí' : 'no',
            'cantidad' => is_object($services) && method_exists($services, 'count') ? $services->count() : (is_array($services) ? count($services) : 'desconocido')
        ]);
    }
    
    public function title(): string
    {
        return 'Resumen de Servicios';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - RESUMEN DE SERVICIOS'],
            ['Periodo: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            ['(Cada servicio tiene su propia hoja detallada)'],
            [],
            ['Nombre', 'Total Solicitudes', 'Total Exámenes', 'Total Pacientes', 'Pendientes', 'En Proceso', 'Completados', '% Completados']
        ];
    }
      public function array(): array
    {
        $rows = [];
        
        // Añadir nota informativa
        $rows[] = ['NOTA: Cada servicio tiene su propia hoja detallada en este archivo Excel. Consulte las pestañas con el nombre de cada servicio.'];
        $rows[] = [''];
        
        // Verificar si hay servicios para mostrar
        if (!$this->services || (is_array($this->services) && empty($this->services)) || 
            (is_object($this->services) && method_exists($this->services, 'isEmpty') && $this->services->isEmpty())) {
            $rows[] = ['No hay servicios disponibles para el período seleccionado.'];
            return $rows;
        }
        
        foreach ($this->services as $service) {
            // Calcular porcentaje de completados
            $totalExamenes = $service->total_examenes ?? 0;
            $completados = $service->completados ?? 0;
            $porcentajeCompletados = $totalExamenes > 0 ? round(($completados / $totalExamenes) * 100, 2) : 0;
            
            // Fila principal del servicio
            $rows[] = [
                $service->nombre ?? 'N/A',
                $service->total_solicitudes ?? 0,
                $service->total_examenes ?? 0,
                $service->total_pacientes ?? 0,
                $service->pendientes ?? 0,
                $service->en_proceso ?? 0,
                $service->completados ?? 0,
                $porcentajeCompletados . '%'
            ];
        }
          // Agregar fila de totales
        // Verificar si $this->data['servicios'] es una colección o un array y calcular totales
        if (is_object($this->services) && method_exists($this->services, 'sum')) {
            // Si es una colección, usar los métodos de colección
            $totalSolicitudes = $this->services->sum('total_solicitudes');
            $totalExamenes = $this->services->sum('total_examenes');
            $totalPacientes = $this->services->sum('total_pacientes');
            $totalPendientes = $this->services->sum('pendientes');
            $totalEnProceso = $this->services->sum('en_proceso');
            $totalCompletados = $this->services->sum('completados');
        } else {
            // Si es un array, usar array_column y array_sum
            $totalSolicitudes = array_sum(array_column((array)$this->services, 'total_solicitudes'));
            $totalExamenes = array_sum(array_column((array)$this->services, 'total_examenes'));
            $totalPacientes = array_sum(array_column((array)$this->services, 'total_pacientes'));
            $totalPendientes = array_sum(array_column((array)$this->services, 'pendientes'));
            $totalEnProceso = array_sum(array_column((array)$this->services, 'en_proceso'));
            $totalCompletados = array_sum(array_column((array)$this->services, 'completados'));
        }
        $porcentajeTotal = $totalExamenes > 0 ? round(($totalCompletados / $totalExamenes) * 100, 2) : 0;
        
        $rows[] = [''];
        $rows[] = [
            'TOTALES',
            $totalSolicitudes,
            $totalExamenes,
            $totalPacientes,
            $totalPendientes,
            $totalEnProceso,
            $totalCompletados,
            $porcentajeTotal . '%'
        ];
        
        return $rows;
    }
      public function styles(Worksheet $sheet)
    {
        // Estilo para encabezados
        $sheet->getStyle('A1:H3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Estilo para la fila de columnas
        $sheet->getStyle('A5:H5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        
        // Estilo para la nota informativa
        $sheet->getStyle('A6:H6')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '0070C0']],
        ]);
        
        // Estilo para los datos de servicios
        $lastRow = $sheet->getHighestRow();
        $endRowForData = max(8, $lastRow - 2); // Asegurar que no sea menor que 8
        if ($endRowForData >= 8 && $lastRow > 8) {
            $sheet->getStyle('A8:H' . $endRowForData)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);
        }
        
        // Estilo para la fila de totales
        $sheet->getStyle('A' . $lastRow . ':H' . $lastRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35, // Nombre
            'B' => 20, // Total Solicitudes
            'C' => 20, // Total Exámenes
            'D' => 20, // Total Pacientes
            'E' => 15, // Pendientes
            'F' => 15, // En Proceso
            'G' => 15, // Completados
            'H' => 15  // % Completados
        ];
    }
}

/**
 * Hoja de detalle individual por servicio
 */
class SingleServiceDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $service;
    protected $startDate;
    protected $endDate;

    public function __construct($service, $startDate, $endDate)
    {
        $this->service = $service;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        // Limitar el título a 31 caracteres (límite de Excel)
        $title = 'Servicio: ' . $this->service->nombre;
        if (strlen($title) > 31) {
            $title = substr($title, 0, 28) . '...';
        }
        return $title;
    }

    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE DEL SERVICIO'],
            ['Servicio: ' . ($this->service->nombre ?? 'N/A')],
            ['Periodo: ' . $dateRange],
            [],
            ['ESTADÍSTICAS GENERALES DEL SERVICIO'],
            [],
            ['Concepto', 'Cantidad', 'Porcentaje'],
        ];
       }

    public function array(): array
    {
        $rows = [];

        // Estadísticas generales del servicio
        $totalExamenes = $this->service->total_examenes ?? 0;
        $totalCompletados = $this->service->completados ?? 0;
        $porcentajeCompletados = $totalExamenes > 0 ? round(($totalCompletados / $totalExamenes) * 100, 2) : 0;
        $totalPendientes = $this->service->pendientes ?? 0;
        $porcentajePendientes = $totalExamenes > 0 ? round(($totalPendientes / $totalExamenes) * 100, 2) : 0;
        $totalEnProceso = $this->service->en_proceso ?? 0;
        $porcentajeEnProceso = $totalExamenes > 0 ? round(($totalEnProceso / $totalExamenes) * 100, 2) : 0;

        $rows[] = ['Total de Solicitudes', $this->service->total_solicitudes ?? 0, '100%'];
        $rows[] = ['Total de Exámenes', $totalExamenes, '100%'];
        $rows[] = ['Total de Pacientes', $this->service->total_pacientes ?? 0, '100%'];
        $rows[] = ['Exámenes Completados', $totalCompletados, $porcentajeCompletados . '%'];
        $rows[] = ['Exámenes En Proceso', $totalEnProceso, $porcentajeEnProceso . '%'];
        $rows[] = ['Exámenes Pendientes', $totalPendientes, $porcentajePendientes . '%'];
        
        // Separador
        $rows[] = [];
        $rows[] = [];

        // Exámenes más solicitados en este servicio
        if (isset($this->service->examenes_principales) && count($this->service->examenes_principales) > 0) {
            $rows[] = ['EXÁMENES MÁS SOLICITADOS EN ESTE SERVICIO'];
            $rows[] = [];
            $rows[] = ['Nombre', 'Código', 'Categoría', 'Total', 'Porcentaje'];
            
            foreach ($this->service->examenes_principales as $examen) {
                $porcentaje = $totalExamenes > 0 ? round(($examen->total / $totalExamenes) * 100, 2) : 0;
                $rows[] = [
                    $examen->nombre ?? 'N/A',
                    $examen->codigo ?? 'N/A',
                    $examen->categoria ?? 'Sin categoría',
                    $examen->total ?? 0,
                    $porcentaje . '%'
                ];
            }
            
            // Separador después de los exámenes
            $rows[] = [];
            $rows[] = [];
        }
        
        // Doctores más activos en este servicio
        if (isset($this->service->doctores_principales) && count($this->service->doctores_principales) > 0) {
            $rows[] = ['MÉDICOS MÁS ACTIVOS EN ESTE SERVICIO'];
            $rows[] = [];
            $rows[] = ['Nombre', 'Especialidad', 'Total Solicitudes', 'Porcentaje'];
            
            $totalSolicitudes = $this->service->total_solicitudes ?? 0;
            foreach ($this->service->doctores_principales as $doctor) {
                $porcentaje = $totalSolicitudes > 0 ? round(($doctor->total_solicitudes / $totalSolicitudes) * 100, 2) : 0;
                $rows[] = [
                    $doctor->nombre ?? 'N/A',
                    $doctor->especialidad ?? 'No especificado',
                    $doctor->total_solicitudes ?? 0,
                    $porcentaje . '%'
                ];
            }
            
            // Separador después de los doctores
            $rows[] = [];
            $rows[] = [];
        }
        
        // Añadir gráfico de estados
        $rows[] = ['DISTRIBUCIÓN DE ESTADOS DE EXÁMENES'];
        $rows[] = [];
        $rows[] = ['Estado', 'Cantidad', 'Porcentaje'];
        $rows[] = ['Completados', $totalCompletados, $porcentajeCompletados . '%'];
        $rows[] = ['En Proceso', $totalEnProceso, $porcentajeEnProceso . '%'];
        $rows[] = ['Pendientes', $totalPendientes, $porcentajePendientes . '%'];
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para encabezados
        $sheet->getStyle('A1:G3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Estilo para el nombre del servicio
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0070C0']]
        ]);
        
        // Centrar y poner en negrita los encabezados de secciones
        $lastRow = $sheet->getHighestRow();
        for ($i = 1; $i <= $lastRow; $i++) {
            try {
                if (!$sheet->cellExists('A' . $i)) {
                    continue;
                }
                
                $cellValue = $sheet->getCell('A' . $i)->getValue();
                if (in_array($cellValue, [
                    'ESTADÍSTICAS GENERALES DEL SERVICIO',
                    'EXÁMENES MÁS SOLICITADOS EN ESTE SERVICIO',
                    'MÉDICOS MÁS ACTIVOS EN ESTE SERVICIO',
                    'DISTRIBUCIÓN DE ESTADOS DE EXÁMENES'
                ])) {
                    $sheet->mergeCells('A' . $i . ':E' . $i);
                    $sheet->getStyle('A' . $i . ':E' . $i)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4']
                    ],
                    'font' => ['color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
            }
        } catch (Exception $e) {
            // Continuar si hay problemas accediendo a la celda
            continue;
        }
        }
        
        // Estilo para encabezados de columnas
        for ($i = 1; $i <= $lastRow; $i++) {
            try {
                if (!$sheet->cellExists('A' . $i)) {
                    continue;
                }
                
                if (in_array($sheet->getCell('A' . $i)->getValue(), ['Concepto', 'Nombre', 'Estado'])) {
                    $sheet->getStyle('A' . $i . ':E' . $i)->applyFromArray([
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
            } catch (Exception $e) {
                // Continuar si hay problemas accediendo a la celda
                continue;
            }
        }
        
        // Estilo para filas de datos - Construir rangos seguros
        $dataRanges = [
            'A8:C13',  // Estadísticas generales
        ];
        
        // Solo añadir rangos si las celdas existen y tienen el contenido esperado
        if ($lastRow >= 17 && $sheet->cellExists('A17')) {
            try {
                $cellValue = $sheet->getCell('A17')->getValue();
                if ($cellValue == 'Nombre') {
                    $dataRanges[] = 'A17:E' . $lastRow;
                } else {
                    $dataRanges[] = 'A17:E25';
                }
            } catch (Exception $e) {
                $dataRanges[] = 'A17:E25';  // Rango por defecto seguro
            }
        }
        
        if ($lastRow >= 29 && $sheet->cellExists('A29')) {
            try {
                $cellValue = $sheet->getCell('A29')->getValue();
                if ($cellValue == 'Nombre') {
                    $dataRanges[] = 'A29:D' . $lastRow;
                } else {
                    $dataRanges[] = 'A29:D37';
                }
            } catch (Exception $e) {
                $dataRanges[] = 'A29:D37';  // Rango por defecto seguro
            }
        }
        
        if ($lastRow >= 44) {
            $dataRanges[] = 'A41:C44';  // Distribución de estados
        }
        
        foreach ($dataRanges as $range) {
            try {
                // Verificar que el rango sea válido antes de aplicar estilos
                $rangeParts = explode(':', $range);
                if (count($rangeParts) == 2 && 
                    $sheet->cellExists($rangeParts[0]) && 
                    $sheet->cellExists($rangeParts[1])) {
                    $sheet->getStyle($range)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ]
                    ]);
                }
            } catch (Exception $e) {
                // Ignorar rangos problemáticos sin interrumpir la generación
                continue;
            }
        }
        
        // Dar color a las filas de estados
        $estadosColores = [
            'Completados' => 'AAFFAA',  // Verde claro
            'En Proceso' => 'FFFFAA',   // Amarillo claro
            'Pendientes' => 'FFAAAA'    // Rojo claro
        ];
        
        for ($i = 1; $i <= $lastRow; $i++) {
            try {
                if (!$sheet->cellExists('A' . $i)) {
                    continue;
                }
                
                $cellValue = $sheet->getCell('A' . $i)->getValue();
                if (isset($estadosColores[$cellValue])) {
                    $sheet->getStyle('A' . $i . ':C' . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $estadosColores[$cellValue]]
                        ]
                    ]);
                }
            } catch (Exception $e) {
                // Continuar si hay problemas accediendo a la celda
                continue;
            }
        }
        
        return $sheet;
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 35, // Nombre
            'B' => 20, // Total Solicitudes
            'C' => 20, // Total Exámenes
            'D' => 20, // Total Pacientes
            'E' => 15, // Pendientes
            'F' => 15, // En Proceso
            'G' => 15, // Completados
            'H' => 15  // % Completados
        ];
    }
}

/**
 * Hoja individual por paciente con solicitudes separadas por fecha
 */
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
        // Limitar el título a 31 caracteres (límite de Excel)
        $nombre = ($this->patient->apellidos ?? '') . ' ' . ($this->patient->nombres ?? '');
        $title = trim($nombre);
        if (strlen($title) > 28) {
            $title = substr($title, 0, 25) . '...';
        }
        return $title ?: 'Paciente ' . ($this->patient->id ?? 'S/N');
    }

    public function headings(): array
    {
        $dateRange = $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y');
        
        return [
            ['LABORATORIO CLÍNICO LAREDO - DETALLE DEL PACIENTE'],
            ['Paciente: ' . ($this->patient->apellidos ?? '') . ' ' . ($this->patient->nombres ?? '')],
            ['DNI: ' . ($this->patient->documento ?? 'N/A') . ' | HCL: ' . ($this->patient->historia_clinica ?? 'N/A') . ' | Edad: ' . ($this->patient->edad ?? 'N/A')],
            ['Período: ' . $dateRange],
            [],
            ['SOLICITUDES Y EXÁMENES POR FECHA']
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        // Verificar si hay solicitudes
        if (!isset($this->patient->solicitudes_detalle) || empty($this->patient->solicitudes_detalle)) {
            $rows[] = ['No hay solicitudes registradas para este paciente en el período seleccionado.'];
            return $rows;
        }

        // Agrupar solicitudes por fecha y por número de recibo para mejor organización
        $solicitudesPorFecha = [];
        foreach ($this->patient->solicitudes_detalle as $solicitud) {
            $fecha = $solicitud->fecha ?? 'Sin fecha';
            $numeroRecibo = !empty($solicitud->numero_recibo) ? $solicitud->numero_recibo : 'No registrado';
            
            if (!isset($solicitudesPorFecha[$fecha])) {
                $solicitudesPorFecha[$fecha] = [];
            }
            
            // Usar una combinación de numero de recibo y ID de solicitud para evitar duplicados
            $claveUnica = $numeroRecibo . '_' . ($solicitud->solicitud_id ?? $solicitud->id ?? uniqid());
            $solicitudesPorFecha[$fecha][$claveUnica] = $solicitud;
        }

        // Ordenar las fechas
        ksort($solicitudesPorFecha);

        $totalExamenes = 0;
        $examenesCompletados = 0;
        $examenesPendientes = 0;
        $examenesEnProceso = 0;

        // Generar filas agrupadas por fecha de solicitud
        foreach ($solicitudesPorFecha as $fecha => $solicitudes) {
            // Separador y encabezado de fecha
            $rows[] = [];
            $fechaFormateada = ($fecha !== 'Sin fecha') ? date('d/m/Y', strtotime($fecha)) : 'Sin fecha';
            $rows[] = ['═══ SOLICITUDES DEL ' . $fechaFormateada . ' ═══', '', '', '', '', ''];
            $rows[] = [];
            
            foreach ($solicitudes as $claveUnica => $solicitud) {
                // Extraer el número de recibo de la clave única
                $numeroRecibo = explode('_', $claveUnica)[0];
                
                // Encabezado de la solicitud
                $servicioInfo = !empty($solicitud->servicio) ? ' - ' . $solicitud->servicio : '';
                $medicoInfo = !empty($solicitud->medico_solicitante) ? ' - Dr. ' . $solicitud->medico_solicitante : '';
                
                $rows[] = ['SOLICITUD #' . $numeroRecibo . $servicioInfo . $medicoInfo, '', '', '', '', ''];
                $rows[] = ['Examen', 'Código', 'Estado', 'Resultados', 'Valores de Referencia', 'Observaciones'];
                
                // Procesar exámenes de esta solicitud
                if (isset($solicitud->examenes) && !empty($solicitud->examenes)) {
                    foreach ($solicitud->examenes as $examen) {
                        $totalExamenes++;
                        
                        // Contar estados
                        $estado = strtolower($examen->estado ?? '');
                        switch ($estado) {
                            case 'completado':
                                $examenesCompletados++;
                                break;
                            case 'pendiente':
                                $examenesPendientes++;
                                break;
                            case 'en_proceso':
                                $examenesEnProceso++;
                                break;
                        }
                        
                        // Procesar resultados del examen
                        $resultados_texto = $this->formatearResultados($examen);
                        $referencias_texto = $this->formatearReferencias($examen);
                        
                        // Verificar que tenemos datos válidos antes de crear la fila
                        $nombreExamen = $examen->nombre ?? 'N/A';
                        $codigoExamen = $examen->codigo ?? 'N/A';
                        $estadoExamen = strtoupper($examen->estado ?? 'N/A');
                        $observacionesExamen = $examen->observaciones ?? '';
                        
                        $rows[] = [
                            $nombreExamen,
                            $codigoExamen,
                            $estadoExamen,
                            $resultados_texto,
                            $referencias_texto,
                            $observacionesExamen
                        ];
                    }
                } else {
                    $rows[] = ['Sin exámenes registrados', '', '', '', '', ''];
                }
                
                // Separador entre solicitudes de la misma fecha
                $rows[] = [];
            }
        }

        // Resumen estadístico del paciente
        $rows[] = [];
        $rows[] = ['═══ RESUMEN ESTADÍSTICO ═══', '', '', '', '', ''];
        $rows[] = ['Total de solicitudes:', count($this->patient->solicitudes_detalle ?? []), '', '', '', ''];
        $rows[] = ['Total de exámenes:', $totalExamenes, '', '', '', ''];
        $rows[] = ['Completados:', $examenesCompletados, 'Pendientes:', $examenesPendientes, 'En Proceso:', $examenesEnProceso];
        
        // Calcular porcentaje de completados
        if ($totalExamenes > 0) {
            $porcentajeCompletado = round(($examenesCompletados / $totalExamenes) * 100, 1);
            $rows[] = ['Progreso general:', $porcentajeCompletado . '%', '', '', '', ''];
        } else {
            $rows[] = ['Progreso general:', '0%', '', '', '', ''];
        }

        return $rows;
    }

    /**
     * Formatear resultados de un examen
     */
    private function formatearResultados($examen)
    {
        // Si el examen no está completado, mostrar el estado
        $estado = strtolower($examen->estado ?? '');
        if ($estado !== 'completado') {
            switch ($estado) {
                case 'pendiente':
                    return 'PENDIENTE';
                case 'en_proceso':
                case 'en proceso':
                    return 'EN PROCESO';
                default:
                    return 'ESTADO: ' . strtoupper($estado);
            }
        }
        
        // Primero, verificar si hay resultados en la estructura estándar del controlador
        if (isset($examen->resultados) && !empty($examen->resultados)) {
            $resultados_formateados = [];
            
            foreach ($examen->resultados as $resultado) {
                $texto_resultado = '';
                
                // Usar el campo o el nombre del examen si no hay campo específico
                $campo = $resultado->campo ?? $examen->nombre ?? 'Resultado';
                if (!empty(trim($campo))) {
                    $texto_resultado = $campo . ': ';
                }
                
                // Añadir el valor
                if (isset($resultado->valor) && !empty(trim($resultado->valor))) {
                    $texto_resultado .= trim($resultado->valor);
                    
                    // Añadir unidad si está disponible
                    if (isset($resultado->unidad) && !empty(trim($resultado->unidad))) {
                        $texto_resultado .= ' ' . trim($resultado->unidad);
                    }
                    
                    // Añadir indicador si está fuera de rango
                    if (isset($resultado->fuera_rango) && $resultado->fuera_rango) {
                        $texto_resultado .= ' ⚠️';
                    }
                    
                    $resultados_formateados[] = $texto_resultado;
                }
            }
            
            if (!empty($resultados_formateados)) {
                return implode(' | ', $resultados_formateados);
            }
        }
        
        // Segundo, verificar si hay resultado directo en el campo 'resultado' de detallesolicitud
        if (isset($examen->resultado) && !empty(trim($examen->resultado))) {
            $resultado_texto = trim($examen->resultado);
            
            // Añadir unidad si está disponible
            if (isset($examen->unidad) && !empty(trim($examen->unidad))) {
                $resultado_texto .= ' ' . trim($examen->unidad);
            }
            
            // Añadir indicador si está fuera de rango
            if (isset($examen->fuera_rango) && $examen->fuera_rango) {
                $resultado_texto .= ' ⚠️';
            }
            
            return $resultado_texto;
        }
        
        // Para compatibilidad, verificar si los datos están directamente en el examen
        if (isset($examen->valor) && !empty(trim($examen->valor))) {
            $resultado_texto = trim($examen->valor);
            
            // Añadir unidad si está disponible
            if (isset($examen->unidad) && !empty(trim($examen->unidad))) {
                $resultado_texto .= ' ' . trim($examen->unidad);
            }
            
            // Añadir indicador si está fuera de rango
            if (isset($examen->fuera_rango) && $examen->fuera_rango) {
                $resultado_texto .= ' ⚠️';
            }
            
            return $resultado_texto;
        }
        
        // Si no hay valor directo, buscar en estructuras anidadas
        $datos_resultados = $this->obtenerDatosResultadosRobustos($examen);
        
        if (empty($datos_resultados)) {
            // Verificar si tiene_resultados está disponible
            if (isset($examen->tiene_resultados) && $examen->tiene_resultados === false) {
                return 'COMPLETADO - Sin resultados registrados';
            }
            return 'COMPLETADO - Consultar laboratorio';
        }
        
        $resultados_formateados = [];
        foreach ($datos_resultados as $resultado) {
            $texto_resultado = $this->formatearResultadoIndividualRobustos($resultado);
            if (!empty($texto_resultado)) {
                $resultados_formateados[] = $texto_resultado;
            }
        }
        
        if (empty($resultados_formateados)) {
            return 'COMPLETADO - Consultar laboratorio';
        }
        
        return implode(' | ', $resultados_formateados);
    }

    /**
     * Formatear valores de referencia
     */
    private function formatearReferencias($examen)
    {
        // Si el examen no está completado, no mostrar referencias
        $estado = strtolower($examen->estado ?? '');
        if ($estado !== 'completado') {
            return 'N/A';
        }
        
        // Primero, verificar si hay resultados en la estructura estándar del controlador
        if (isset($examen->resultados) && !empty($examen->resultados)) {
            $referencias_formateadas = [];
            
            foreach ($examen->resultados as $resultado) {
                if (isset($resultado->valor_referencia) && !empty(trim($resultado->valor_referencia))) {
                    $referencias_formateadas[] = trim($resultado->valor_referencia);
                }
            }
            
            if (!empty($referencias_formateadas)) {
                return implode(' | ', $referencias_formateadas);
            }
        }
        
        // Para compatibilidad, usar valor de referencia directamente si está disponible
        if (isset($examen->valor_referencia) && !empty(trim($examen->valor_referencia))) {
            return trim($examen->valor_referencia);
        }
        
        // Si no hay valor directo, buscar en estructuras anidadas
        $datos_resultados = $this->obtenerDatosResultadosRobustos($examen);
        
        if (empty($datos_resultados)) {
            // Verificar si tiene_resultados está disponible
            if (isset($examen->tiene_resultados) && $examen->tiene_resultados === false) {
                return 'Sin resultados registrados';
            }
            return 'Consultar laboratorio';
        }
        
        $referencias_formateadas = [];
        foreach ($datos_resultados as $resultado) {
            $texto_referencia = $this->formatearReferenciaIndividualRobustos($resultado);
            if (!empty($texto_referencia)) {
                $referencias_formateadas[] = $texto_referencia;
            }
        }
        
        if (empty($referencias_formateadas)) {
            return 'Consultar laboratorio';
        }
        
        return implode(' | ', $referencias_formateadas);
    }
    
    /**
     * Obtener datos de resultados desde diferentes estructuras posibles (versión robusta)
     */
    private function obtenerDatosResultadosRobustos($examen)
    {
        // Múltiples estructuras posibles donde pueden estar los resultados
        $estructuras_posibles = [
            'resultados', 'resultado', 'datos', 'valores', 'fields', 'parametros',
            'results', 'data', 'values', 'parameters', 'exam_results', 'test_results'
        ];
        
        foreach ($estructuras_posibles as $estructura) {
            if (isset($examen->$estructura)) {
                $datos = $examen->$estructura;
                
                // Si es null o vacío, continuar
                if (empty($datos)) {
                    continue;
                }
                
                // Si es un objeto único, convertir a array
                if (is_object($datos) && !is_array($datos)) {
                    // Verificar que el objeto tenga propiedades útiles
                    $propiedades = get_object_vars($datos);
                    if (!empty($propiedades)) {
                        return [$datos];
                    }
                }
                
                // Si es array, verificar que no esté vacío
                if (is_array($datos)) {
                    // Filtrar elementos vacíos o nulos
                    $datos_filtrados = array_filter($datos, function($item) {
                        if (is_null($item)) return false;
                        if (is_object($item)) {
                            $propiedades = get_object_vars($item);
                            return !empty($propiedades);
                        }
                        if (is_array($item)) {
                            return !empty($item);
                        }
                        return !empty(trim($item));
                    });
                    
                    if (!empty($datos_filtrados)) {
                        return array_values($datos_filtrados);
                    }
                }
                
                // Si es string y no está vacío
                if (is_string($datos) && !empty(trim($datos))) {
                    // Intentar decodificar JSON
                    $json_decoded = json_decode($datos, true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($json_decoded)) {
                        return is_array($json_decoded) ? $json_decoded : [$json_decoded];
                    }
                    
                    // Si no es JSON, crear un objeto simple
                    return [(object)['valor' => trim($datos)]];
                }
            }
        }
        
        // Como último recurso, buscar propiedades que puedan contener valores directos
        $propiedades_directas = ['valor', 'value', 'resultado', 'result'];
        foreach ($propiedades_directas as $prop) {
            if (isset($examen->$prop) && !empty(trim($examen->$prop))) {
                return [(object)[
                    'campo' => $examen->nombre ?? 'Resultado',
                    'valor' => trim($examen->$prop),
                    'unidad' => $examen->unidad ?? '',
                    'valor_referencia' => $examen->valor_referencia ?? $examen->referencia ?? ''
                ]];
            }
        }
        
        return [];
    }
    
    /**
     * Formatear un resultado individual (versión robusta)
     */
    private function formatearResultadoIndividualRobustos($resultado)
    {
        // Si el resultado es null o vacío, no procesar
        if (is_null($resultado)) {
            return '';
        }
        
        // Convertir arrays a objetos para procesamiento uniforme
        if (is_array($resultado)) {
            $resultado = (object)$resultado;
        }
        
        // Múltiples campos posibles para cada tipo de dato
        $campos_posibles = [
            'campo' => ['campo', 'name', 'parametro', 'parameter', 'test_name', 'exam_name', 'nombre'],
            'valor' => ['valor', 'value', 'resultado', 'result', 'measure', 'measurement'],
            'unidad' => ['unidad', 'unit', 'units', 'medida', 'measure_unit'],
            'fuera_rango' => ['fuera_rango', 'out_of_range', 'critical', 'abnormal', 'flag']
        ];
        
        // Obtener valores usando múltiples campos posibles
        $campo = '';
        $valor = '';
        $unidad = '';
        $fuera_rango = false;
        
        foreach ($campos_posibles['campo'] as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $campo = trim($resultado->$key);
                break;
            }
        }
        
        foreach ($campos_posibles['valor'] as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $valor = trim($resultado->$key);
                break;
            }
        }
        
        foreach ($campos_posibles['unidad'] as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $unidad = trim($resultado->$key);
                break;
            }
        }
        
        foreach ($campos_posibles['fuera_rango'] as $key) {
            if (isset($resultado->$key)) {
                $fuera_rango = in_array($resultado->$key, [1, '1', true, 'true', 'yes', 'sí']);
                break;
            }
        }
        
        // Si no hay valor, no mostrar nada
        if (empty($valor)) {
            return '';
        }
        
        // Construir el texto del resultado
        $texto = '';
        if (!empty($campo)) {
            $texto .= $campo . ': ';
        }
        
        $texto .= $valor;
        
        if (!empty($unidad)) {
            $texto .= ' ' . $unidad;
        }
        
        if ($fuera_rango) {
            $texto .= ' ⚠️';
        }
        
        return $texto;
    }
    
    /**
     * Formatear una referencia individual (versión robusta)
     */
    private function formatearReferenciaIndividualRobustos($resultado)
    {
        // Si el resultado es null o vacío, no procesar
        if (is_null($resultado)) {
            return '';
        }
        
        // Convertir arrays a objetos para procesamiento uniforme
        if (is_array($resultado)) {
            $resultado = (object)$resultado;
        }
        
        // Múltiples campos posibles para referencias
        $campos_referencia = [
            'valor_referencia', 'reference_value', 'referencia', 'reference', 
            'normal_range', 'rango_normal', 'range', 'ref_value', 'ref_range'
        ];
        
        $campos_nombre = [
            'campo', 'name', 'parametro', 'parameter', 'test_name', 'exam_name', 'nombre'
        ];
        
        // Obtener valores
        $campo = '';
        $referencia = '';
        
        foreach ($campos_nombre as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $campo = trim($resultado->$key);
                break;
            }
        }
        
        foreach ($campos_referencia as $key) {
            if (isset($resultado->$key) && !empty(trim($resultado->$key))) {
                $referencia = trim($resultado->$key);
                break;
            }
        }
        
        // Si no hay referencia, no mostrar nada
        if (empty($referencia)) {
            return '';
        }
        
        // Construir el texto de la referencia
        $texto = '';
        if (!empty($campo)) {
            $texto .= $campo . ': ';
        }
        
        $texto .= $referencia;
        
        return $texto;
    }

    public function styles(Worksheet $sheet)
    {
        // Título principal
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']]
        ]);
        $sheet->mergeCells('A1:F1');

        // Información del paciente
        $sheet->getStyle('A2:F4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');
        $sheet->mergeCells('A4:F4');

        // Título de sección
        $sheet->getStyle('A6:F6')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD']
            ]
        ]);
        $sheet->mergeCells('A6:F6');

        // Aplicar estilos dinámicos según el contenido
        $data = $this->array();
        $rowIndex = 7; // Empezar después de los encabezados
        
        foreach ($data as $rowData) {
            // Verificar que la fila tenga datos válidos antes de aplicar estilos
            if (!is_array($rowData) || empty($rowData)) {
                $rowIndex++;
                continue;
            }
            
            // Verificar que existe el primer elemento de la fila
            if (!isset($rowData[0])) {
                $rowIndex++;
                continue;
            }
            
            $cellContent = $rowData[0];
            
            // Solo aplicar estilos si el contenido de la celda no está vacío
            if (empty($cellContent) || !is_string($cellContent)) {
                $rowIndex++;
                continue;
            }
            
            // Separadores de fecha con diseño destacado
            if (strpos($cellContent, '═══ SOLICITUDES DEL') === 0) {
                $sheet->getStyle('A' . $rowIndex . ':F' . $rowIndex)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '28A745'] // Verde para fechas
                        ]
                    ]);
                    $sheet->mergeCells('A' . $rowIndex . ':F' . $rowIndex);
                }
                
                // Encabezados de columnas de exámenes
                elseif ($cellContent === 'Examen') {
                    $sheet->getStyle('A' . $rowIndex . ':F' . $rowIndex)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA']
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                    ]);
                }
                
                // Información de solicitud
                elseif (strpos($cellContent, 'SOLICITUD #') === 0) {
                    $sheet->getStyle('A' . $rowIndex . ':F' . $rowIndex)->applyFromArray([
                        'font' => ['bold' => true, 'italic' => true, 'size' => 11],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFF3CD'] // Amarillo claro para solicitudes
                        ],
                        'borders' => [
                            'outline' => ['borderStyle' => Border::BORDER_MEDIUM]
                        ]
                    ]);
                    $sheet->mergeCells('A' . $rowIndex . ':F' . $rowIndex);
                }
                
                // Resumen estadístico
                elseif (strpos($cellContent, '═══ RESUMEN ESTADÍSTICO ═══') === 0) {
                    $sheet->getStyle('A' . $rowIndex . ':F' . $rowIndex)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '007BFF'] // Azul para resumen
                        ]
                    ]);
                    $sheet->mergeCells('A' . $rowIndex . ':F' . $rowIndex);
                }
                
                // Filas de resumen (estadísticas)
                elseif (in_array($cellContent, ['Total de solicitudes:', 'Total de exámenes:', 'Completados:', 'Progreso general:'])) {
                    $sheet->getStyle('A' . $rowIndex . ':F' . $rowIndex)->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E8F4FD']
                        ]
                    ]);
                }
                
                // Resaltar valores fuera de rango con emoji de advertencia
                if (isset($rowData[3]) && is_string($rowData[3]) && strpos($rowData[3], '⚠️') !== false) {
                    $sheet->getStyle('D' . $rowIndex)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'DC3545']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8D7DA'] // Rojo claro para valores críticos
                        ]
                    ]);
                }
                
                // Estados pendientes y en proceso
                elseif (isset($rowData[3]) && in_array($rowData[3], ['PENDIENTE', 'EN PROCESO'])) {
                    $sheet->getStyle('C' . $rowIndex . ':D' . $rowIndex)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '856404']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFF3CD']
                        ]
                    ]);
                }
                
                // Estados completados
                elseif (isset($rowData[2]) && $rowData[2] === 'COMPLETADO') {
                    $sheet->getStyle('C' . $rowIndex)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '155724']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D4EDDA'] // Verde claro para completados
                        ]
                    ]);
                }
            
            $rowIndex++;
        }

        // Ajustar altura de filas para mejor legibilidad
        $sheet->getDefaultRowDimension()->setRowHeight(18);
        
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Examen
            'B' => 12, // Código
            'C' => 12, // Estado
            'D' => 40, // Resultados
            'E' => 25, // Valores de Referencia
            'F' => 20, // Observaciones
        ];
    }
}
