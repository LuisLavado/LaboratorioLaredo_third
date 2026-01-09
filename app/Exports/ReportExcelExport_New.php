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

class ReportExcelExport implements WithMultipleSheets
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
     * Convertir los datos al formato necesario para la exportación
     * 
     * Este método preprocesa los datos para asegurarnos de que todas las propiedades necesarias existen
     */
    private function preprocessData()
    {
        // Asegurarnos de que existen todos los índices necesarios para evitar errores
        if (!isset($this->data['generatedBy'])) {
            $this->data['generatedBy'] = 'Sistema';
        }
        
        if (!isset($this->data['totales'])) {
            $this->data['totales'] = [
                'solicitudes' => 0,
                'pacientes' => 0,
                'examenes_realizados' => 0
            ];
        }
        
        // Procesar los datos según el tipo de reporte
        if ($this->type === 'patients' && isset($this->data['patients'])) {
            // Asegurar que los pacientes tienen todos los campos necesarios
            foreach ($this->data['patients'] as &$patient) {
                if (!isset($patient->total_examenes)) {
                    $patient->total_examenes = 0;
                }
                if (!isset($patient->total_solicitudes)) {
                    $patient->total_solicitudes = 0;
                }
            }
        }
    }
    
    public function sheets(): array
    {
        // Preprocesar los datos para asegurar que tienen el formato correcto
        $this->preprocessData();
        $sheets = [];
        
        // Siempre incluir una hoja de resumen
        $sheets[] = new ReportSummarySheet($this->data, $this->type, $this->startDate, $this->endDate);
        
        // Añadir hojas específicas según el tipo de reporte
        switch ($this->type) {
            case 'patients':
                if (isset($this->data['patients']) && !empty($this->data['patients'])) {
                    // Primero agregamos la hoja de resumen
                    $sheets[] = new PatientsSummarySheet($this->data['patients'], $this->startDate, $this->endDate);
                    
                    // Luego una hoja individual para cada paciente
                    foreach ($this->data['patients'] as $patient) {
                        $sheets[] = new SinglePatientSheet($patient, $this->startDate, $this->endDate);
                    }
                }
                break;
                  
            case 'categories':
                if (isset($this->data['categoryStats']) && !empty($this->data['categoryStats'])) {
                    \Log::info('Generando hojas para reporte de categorías', [
                        'categoryStats_count' => count($this->data['categoryStats']),
                        'topExams_count' => isset($this->data['topExamsByCategory']) ? count($this->data['topExamsByCategory']) : 0
                    ]);
                    $sheets[] = new CategoriesDetailSheet($this->data, $this->startDate, $this->endDate);
                } else {
                    \Log::warning('No hay datos de categorías disponibles');
                }
                break;
                
            case 'exams':
                if (isset($this->data['examenes']) && !empty($this->data['examenes'])) {
                    // Primero agregamos la hoja de resumen general
                    $sheets[] = new ExamsDetailSheet($this->data['examenes'], $this->startDate, $this->endDate);
                    
                    // Luego una hoja individual para cada examen
                    foreach ($this->data['examenes'] as $exam) {
                        $sheets[] = new SingleExamSheet($exam, $this->startDate, $this->endDate);
                    }
                }
                break;
                
            case 'doctors':
                if (isset($this->data['doctores']) && !empty($this->data['doctores'])) {
                    $sheets[] = new DoctorsDetailSheet($this->data['doctores'], $this->startDate, $this->endDate);
                }
                break;
            
            case 'results':
                // Implementamos hoja detallada para resultados
                \Log::info('Generando hojas para reporte de resultados', [
                    'dailyStats_count' => isset($this->data['dailyStats']) ? count($this->data['dailyStats']) : 0,
                    'statusCounts' => $this->data['statusCounts'] ?? [],
                    'patients_with_results_count' => isset($this->data['patients_with_results']) ? count($this->data['patients_with_results']) : 0
                ]);
                
                // Primero agregamos una hoja con los resultados diarios si existen
                if (isset($this->data['dailyStats']) && !empty($this->data['dailyStats'])) {
                    $sheets[] = new ResultsDetailSheet($this->data, $this->startDate, $this->endDate);
                }
                
                // Luego una hoja individual para cada paciente con resultados
                if (isset($this->data['patients_with_results']) && !empty($this->data['patients_with_results'])) {
                    foreach ($this->data['patients_with_results'] as $patient) {
                        $sheets[] = new SinglePatientResultsSheet($patient, $this->startDate, $this->endDate);
                    }
                }
                break;
            
            case 'services':
                if (isset($this->data['servicios'])) {
                    // Verificamos si $this->data['servicios'] es un objeto o un array
                    $servicios = $this->data['servicios'];
                    $totalServicios = is_array($servicios) ? count($servicios) : (is_object($servicios) && method_exists($servicios, 'count') ? $servicios->count() : 0);
                    
                    \Log::info('Generando hojas para servicios', [
                        'total_servicios' => $totalServicios,
                        'tipo_dato' => gettype($servicios),
                        'es_objeto' => is_object($servicios) ? 'sí' : 'no',
                        'es_array' => is_array($servicios) ? 'sí' : 'no',
                        'es_collection' => (is_object($servicios) && method_exists($servicios, 'count')) ? 'sí' : 'no',
                        'primer_servicio' => $totalServicios > 0 ? json_encode(is_array($servicios) ? reset($servicios) : $servicios->first()) : 'No hay servicios'
                    ]);
                    
                    if ($totalServicios > 0) {
                        // Primero agregamos una hoja con el resumen de todos los servicios
                        $sheets[] = new ServicesDetailSheet($servicios, $this->startDate, $this->endDate);
                    }
                }
                break;
        }
        
        return $sheets;
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
            ['LABORATORIO CLÍNICO LAREDO - REPORTE DE ' . strtoupper($this->getReportTypeName())],
            ['Periodo: ' . $dateRange],
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
        return [
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
            ],
        ];
    }
    
    /**
     * Ancho de las columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 15,
            'C' => 15,
            'D' => 30,
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

// CONTINÚA EN EL SIGUIENTE ARCHIVO...
