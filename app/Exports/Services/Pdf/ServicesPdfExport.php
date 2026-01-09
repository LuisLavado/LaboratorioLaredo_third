<?php

namespace App\Exports\Services\Pdf;

use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Exportador PDF para Reportes de Servicios
 * Versión organizada y sin referencias económicas
 */
class ServicesPdfExport
{
    protected $data;
    protected $startDate;
    protected $endDate;
    protected $type;
    protected $generatedBy;

    public function __construct($data, $startDate, $endDate, $type = 'complete', $generatedBy = 'Sistema')
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->generatedBy = $generatedBy;
    }

    /**
     * Generar el PDF del reporte de servicios
     */
    public function generate()
    {
        // Preparar datos para la vista
        $viewData = $this->prepareViewData();
        
        // Seleccionar plantilla según el tipo
        $templateName = $this->getTemplateName();
        
        // Generar PDF
        $pdf = Pdf::loadView($templateName, $viewData);
        
        // Configurar opciones del PDF
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Preparar datos para la vista del PDF
     */
    protected function prepareViewData()
    {
        // Los datos vienen del controlador con la estructura específica de servicios
        $servicios = $this->data['serviceStats'] ?? [];
        
        // Si servicios es una Collection, convertir a array
        if (is_object($servicios) && method_exists($servicios, 'toArray')) {
            $servicios = $servicios->toArray();
        }
        if (!is_array($servicios)) {
            $servicios = [];
        }

        // Convertir objetos stdClass a arrays para facilitar el manejo
        $servicios = array_map(function($item) {
            if (is_object($item)) {
                return (array) $item;
            }
            return $item;
        }, $servicios);

        // Usar datos ya calculados del controlador si están disponibles
        $totalRequests = $this->data['totalRequests'] ?? 0;
        $totalPatients = $this->data['totalPatients'] ?? 0;
        $totalExams = $this->data['totalExams'] ?? 0;

        // Calcular estadísticas principales
        $stats = [
            'totalSolicitudes' => $totalRequests,
            'totalPacientes' => $totalPatients,
            'totalExamenes' => $totalExams,
            'serviciosActivos' => count(array_filter($servicios, function($s) {
                return ($s['count'] ?? 0) > 0;
            })),
            'totalServicios' => count($servicios),
            'promedioPorServicio' => count($servicios) > 0 ? round($totalRequests / count($servicios), 2) : 0
        ];
        
        // Preparar datos por secciones
        $topServices = $this->getTopServices($servicios);
        $servicesByExams = $this->groupServicesByExams($servicios);
        $performanceAnalysis = $this->getPerformanceAnalysis($servicios);
        $statusAnalysis = $this->getStatusAnalysis($servicios);

        return [
            'title' => 'Reporte por Servicios',
            'subtitle' => 'Análisis Detallado de Servicios de Laboratorio',
            'reportType' => 'Servicios',
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'period' => [
                'start' => $this->startDate,
                'end' => $this->endDate,
                'formatted' => $this->formatPeriod()
            ],
            'stats' => $stats,
            'topServices' => $topServices,
            'servicesByExams' => $servicesByExams,
            'performanceAnalysis' => $performanceAnalysis,
            'statusAnalysis' => $statusAnalysis,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $this->generatedBy,
            'totalServices' => count($servicios),
            'totalRequests' => $totalRequests,
            'totalExams' => $totalExams,
            'totalPatients' => $totalPatients,

        ];
    }

    /**
     * Obtener nombre de la plantilla según el tipo
     */
    protected function getTemplateName()
    {
        switch ($this->type) {

            default:
                return 'reports.services.services-detailed-pdf';
        }
    }

    // Función calculateStats ya no se necesita - se usa la lógica en prepareViewData

    /**
     * Obtener top servicios más solicitados
     */
    protected function getTopServices($servicios, $limit = 10)
    {
        if (empty($servicios)) {
            return [];
        }

        // Convertir objetos a arrays si es necesario
        $servicios = array_map(function($item) {
            if (is_object($item)) {
                return (array) $item;
            }
            return $item;
        }, $servicios);

        // Los datos ya vienen ordenados por count DESC del controlador
        $topServices = array_slice($servicios, 0, $limit);
        
        $totalSolicitudes = array_sum(array_map(function($s) {
            return (int)($s['count'] ?? 0);
        }, $servicios));

        return array_map(function($servicio, $index) use ($totalSolicitudes) {
            $solicitudes = (int)($servicio['count'] ?? 0);
            $porcentaje = isset($servicio['percentage']) ? $servicio['percentage'] : 
                ($totalSolicitudes > 0 ? round(($solicitudes / $totalSolicitudes) * 100, 2) : 0);
            
            return [
                'position' => $index + 1,
                'name' => $servicio['name'] ?? 'Sin nombre',
                'solicitudes' => $solicitudes,
                'percentage' => $porcentaje,
                'level' => $this->getServiceLevel($solicitudes),
                'exams' => (int)($servicio['unique_exams'] ?? 1)
            ];
        }, $topServices, array_keys($topServices));
    }

    /**
     * Agrupar servicios por número de exámenes
     */
    protected function groupServicesByExams($servicios)
    {
        // Convertir objetos a arrays si es necesario
        $servicios = array_map(function($item) {
            if (is_object($item)) {
                return (array) $item;
            }
            return $item;
        }, $servicios);

        $rangos = [
            '1 examen' => ['min' => 1, 'max' => 1],
            '2-3 exámenes' => ['min' => 2, 'max' => 3],
            '4-5 exámenes' => ['min' => 4, 'max' => 5],
            '6-10 exámenes' => ['min' => 6, 'max' => 10],
            '11+ exámenes' => ['min' => 11, 'max' => 999]
        ];

        $grupos = [];
        foreach ($rangos as $nombre => $rango) {
            $grupos[$nombre] = [
                'count' => 0,
                'solicitudes' => 0,
                'servicios' => []
            ];
        }

        foreach ($servicios as $servicio) {
            $examenes = (int)($servicio['unique_exams'] ?? 1);
            $solicitudes = (int)($servicio['count'] ?? 0);
            $nombre = $servicio['name'] ?? 'Sin nombre';
            
            foreach ($rangos as $nombreRango => $rango) {
                if ($examenes >= $rango['min'] && $examenes <= $rango['max']) {
                    $grupos[$nombreRango]['count']++;
                    $grupos[$nombreRango]['solicitudes'] += $solicitudes;
                    if (count($grupos[$nombreRango]['servicios']) < 3) {
                        $grupos[$nombreRango]['servicios'][] = $nombre;
                    }
                    break;
                }
            }
        }

        return $grupos;
    }

    /**
     * Análisis de rendimiento
     */
    protected function getPerformanceAnalysis($servicios)
    {
        // Convertir objetos a arrays si es necesario
        $servicios = array_map(function($item) {
            if (is_object($item)) {
                return (array) $item;
            }
            return $item;
        }, $servicios);

        $analysis = [];
        
        foreach ($servicios as $servicio) {
            $solicitudes = (int)($servicio['count'] ?? 0);
            $examenes = (int)($servicio['unique_exams'] ?? 1);
            
            if ($solicitudes > 0) {
                // Para el análisis de rendimiento, estimamos valores cuando no están disponibles
                $pacientes = (int)($solicitudes * 0.7); // Estimación: 70% de pacientes únicos
                $recurrencia = $pacientes > 0 ? round($solicitudes / $pacientes, 2) : 1;
                $eficiencia = round($solicitudes / $examenes, 2);
                $score = $this->calculatePerformanceScore($solicitudes, $recurrencia, $eficiencia);
                
                $analysis[] = [
                    'name' => $servicio['name'] ?? 'Sin nombre',
                    'solicitudes' => $solicitudes,
                    'recurrencia' => $recurrencia,
                    'eficiencia' => $eficiencia,
                    'score' => $score,
                    'level' => $this->getPerformanceLevel($score)
                ];
            }
        }

        // Ordenar por score descendente
        usort($analysis, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        return array_slice($analysis, 0, 15);
    }

    /**
     * Análisis por estado
     */
    protected function getStatusAnalysis($servicios)
    {
        // Convertir objetos a arrays si es necesario
        $servicios = array_map(function($item) {
            if (is_object($item)) {
                return (array) $item;
            }
            return $item;
        }, $servicios);

        $estados = [
            'Alta Demanda' => ['min' => 30, 'count' => 0, 'solicitudes' => 0],
            'Demanda Media' => ['min' => 10, 'max' => 29, 'count' => 0, 'solicitudes' => 0],
            'Baja Demanda' => ['min' => 1, 'max' => 9, 'count' => 0, 'solicitudes' => 0],
            'Sin Actividad' => ['max' => 0, 'count' => 0, 'solicitudes' => 0],
            'Especializado' => ['min_exams' => 6, 'count' => 0, 'solicitudes' => 0]
        ];

        foreach ($servicios as $servicio) {
            $solicitudes = (int)($servicio['count'] ?? 0);
            $examenes = (int)($servicio['unique_exams'] ?? 1);
            
            $clasificado = false;
            
            if ($examenes >= 6 && $solicitudes > 0 && !$clasificado) {
                $estados['Especializado']['count']++;
                $estados['Especializado']['solicitudes'] += $solicitudes;
                $clasificado = true;
            }
            
            if (!$clasificado) {
                if ($solicitudes >= 30) {
                    $estados['Alta Demanda']['count']++;
                    $estados['Alta Demanda']['solicitudes'] += $solicitudes;
                } elseif ($solicitudes >= 10) {
                    $estados['Demanda Media']['count']++;
                    $estados['Demanda Media']['solicitudes'] += $solicitudes;
                } elseif ($solicitudes >= 1) {
                    $estados['Baja Demanda']['count']++;
                    $estados['Baja Demanda']['solicitudes'] += $solicitudes;
                } else {
                    $estados['Sin Actividad']['count']++;
                }
            }
        }

        return $estados;
    }

    /**
     * Determinar nivel del servicio
     */
    protected function getServiceLevel($solicitudes)
    {
        if ($solicitudes >= 50) return 'Muy Alto';
        if ($solicitudes >= 30) return 'Alto';
        if ($solicitudes >= 10) return 'Medio';
        if ($solicitudes >= 1) return 'Bajo';
        return 'Sin Actividad';
    }

    /**
     * Calcular score de rendimiento
     */
    protected function calculatePerformanceScore($solicitudes, $recurrencia, $eficiencia)
    {
        $score = 0;
        
        // Puntuación por solicitudes
        if ($solicitudes >= 50) $score += 40;
        elseif ($solicitudes >= 30) $score += 30;
        elseif ($solicitudes >= 10) $score += 20;
        elseif ($solicitudes >= 1) $score += 10;
        
        // Puntuación por recurrencia
        if ($recurrencia >= 3) $score += 30;
        elseif ($recurrencia >= 2) $score += 20;
        elseif ($recurrencia >= 1.5) $score += 15;
        elseif ($recurrencia >= 1) $score += 10;
        
        // Puntuación por eficiencia
        if ($eficiencia >= 10) $score += 30;
        elseif ($eficiencia >= 5) $score += 20;
        elseif ($eficiencia >= 3) $score += 15;
        elseif ($eficiencia >= 1) $score += 10;
        
        return min($score, 100);
    }

    /**
     * Obtener nivel de rendimiento
     */
    protected function getPerformanceLevel($score)
    {
        if ($score >= 80) return 'Excelente';
        if ($score >= 60) return 'Bueno';
        if ($score >= 40) return 'Regular';
        if ($score >= 20) return 'Bajo';
        return 'Crítico';
    }

    /**
     * Formatear período
     */
    protected function formatPeriod()
    {
        return 'Del ' . date('d/m/Y', strtotime($this->startDate)) . ' al ' . date('d/m/Y', strtotime($this->endDate));
    }
}
