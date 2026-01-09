<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportExcelExport implements WithMultipleSheets
{
    use Exportable;

    protected $data;
    protected $type;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $type, $startDate, $endDate)
    {
        $this->data = $data;
        $this->type = $type;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    private function preprocessData()
    {
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
        
        if ($this->type === 'patients' && isset($this->data['patients'])) {
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
        $this->preprocessData();
        $sheets = [];
        
        // Siempre incluir una hoja de resumen
        $sheets[] = new ReportSummarySheet($this->data, $this->type, $this->startDate, $this->endDate);
        
        switch ($this->type) {
            case 'patients':
                if (isset($this->data['patients']) && !empty($this->data['patients'])) {
                    $sheets[] = new PatientsSummarySheet($this->data['patients'], $this->startDate, $this->endDate);
                    foreach ($this->data['patients'] as $patient) {
                        $sheets[] = new SinglePatientSheet($patient, $this->startDate, $this->endDate);
                    }
                }
                break;
                
            case 'categories':
                if (isset($this->data['categoryStats']) && !empty($this->data['categoryStats'])) {
                    Log::info('Generando hojas para reporte de categorías', [
                        'categoryStats_count' => count($this->data['categoryStats']),
                        'topExams_count' => isset($this->data['topExamsByCategory']) ? count($this->data['topExamsByCategory']) : 0
                    ]);
                    $sheets[] = new CategoriesDetailSheet($this->data, $this->startDate, $this->endDate);
                } else {
                    Log::warning('No hay datos de categorías disponibles');
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
                
            case 'results':
                Log::info('Generando hojas para reporte de resultados', [
                    'dailyStats_count' => isset($this->data['dailyStats']) ? count($this->data['dailyStats']) : 0,
                    'statusCounts' => $this->data['statusCounts'] ?? []
                ]);
                
                if (isset($this->data['dailyStats']) && !empty($this->data['dailyStats'])) {
                    $sheets[] = new ResultsDetailSheet($this->data, $this->startDate, $this->endDate);
                }
                break;
            
            case 'services':
                if (isset($this->data['servicios'])) {
                    $servicios = $this->data['servicios'];
                    $totalServicios = is_array($servicios) ? count($servicios) : 
                        (is_object($servicios) && method_exists($servicios, 'count') ? $servicios->count() : 0);
                    
                    Log::info('Generando hojas para servicios', [
                        'total_servicios' => $totalServicios,
                        'tipo_dato' => gettype($servicios),
                        'es_objeto' => is_object($servicios) ? 'sí' : 'no',
                        'es_array' => is_array($servicios) ? 'sí' : 'no',
                        'es_collection' => (is_object($servicios) && method_exists($servicios, 'count')) ? 'sí' : 'no'
                    ]);
                    
                    if ($totalServicios > 0) {
                        $sheets[] = new ServicesDetailSheet($servicios, $this->startDate, $this->endDate);
                    }
                }
                break;
        }
        
        return $sheets;
    }
}
