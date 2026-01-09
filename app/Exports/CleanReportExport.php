<?php

namespace App\Exports;

class CleanReportExport
{
    private $data;
    private $title;
    private $startDate;
    private $endDate;
    private $reportType;
    private $generatedBy;

    public function __construct($data, $title, $startDate, $endDate, $reportType, $generatedBy)
    {
        $this->data = $data;
        $this->title = $title;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportType = $reportType;
        $this->generatedBy = $generatedBy;
    }

    public function export()
    {
        $csvData = [];
        
        // Header del reporte
        $csvData[] = ['LABORATORIO LAREDO - REPORTE DE ' . strtoupper($this->reportType)];
        $csvData[] = [''];
        $csvData[] = ['Información del Reporte'];
        $csvData[] = ['Período', $this->startDate . ' - ' . $this->endDate];
        $csvData[] = ['Tipo', $this->reportType];
        $csvData[] = ['Generado por', $this->generatedBy];
        $csvData[] = ['Fecha de generación', date('d/m/Y H:i:s')];
        $csvData[] = [''];

        // Estadísticas principales
        $csvData[] = ['RESUMEN ESTADÍSTICO'];
        $csvData[] = ['Métrica', 'Valor', 'Porcentaje'];
        
        switch ($this->reportType) {
            case 'general':
                $total = ($this->data['totalRequests'] ?? 0);
                $pending = ($this->data['pendingCount'] ?? 0);
                $inProcess = ($this->data['inProcessCount'] ?? 0);
                $completed = ($this->data['completedCount'] ?? 0);

                $pendingPerc = $total > 0 ? round(($pending / $total) * 100, 2) : 0;
                $inProcessPerc = $total > 0 ? round(($inProcess / $total) * 100, 2) : 0;
                $completedPerc = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

                $csvData[] = ['Total Solicitudes', $total, '100%'];
                $csvData[] = ['Pendientes', $pending, $pendingPerc . '%'];
                $csvData[] = ['En Proceso', $inProcess, $inProcessPerc . '%'];
                $csvData[] = ['Completadas', $completed, $completedPerc . '%'];
                $csvData[] = ['Total Pacientes', $this->data['totalPatients'] ?? 0, ''];
                $csvData[] = ['Total Exámenes', $this->data['totalExams'] ?? 0, ''];
                $csvData[] = [''];

                // Estadísticas diarias
                if (!empty($this->data['dailyStats'])) {
                    $csvData[] = ['ESTADÍSTICAS DIARIAS'];
                    $csvData[] = ['Fecha', 'Cantidad de Solicitudes'];

                    foreach ($this->data['dailyStats'] as $stat) {
                        $csvData[] = [
                            $stat->date ?? $stat['date'] ?? 'N/A',
                            $stat->count ?? $stat['count'] ?? 0
                        ];
                    }
                }
                break;

            case 'categories':
                $csvData[] = ['Total Solicitudes', $this->data['totalRequests'] ?? 0, '100%'];
                $csvData[] = ['Total Pacientes', $this->data['totalPatients'] ?? 0, ''];
                $csvData[] = ['Total Exámenes', $this->data['totalExams'] ?? 0, ''];
                $csvData[] = ['Categorías Activas', count($this->data['categoryStats'] ?? []), ''];
                $csvData[] = [''];
                
                // Datos por categorías
                $csvData[] = ['DETALLE POR CATEGORÍAS'];
                $csvData[] = ['Posición', 'Categoría', 'Cantidad', 'Porcentaje'];
                
                if (!empty($this->data['categoryStats'])) {
                    foreach ($this->data['categoryStats'] as $index => $category) {
                        $csvData[] = [
                            $index + 1,
                            $category->name ?? 'Sin categoría',
                            $category->count ?? 0,
                            ($category->percentage ?? 0) . '%'
                        ];
                    }
                }
                
                // Exámenes por categoría
                if (!empty($this->data['topExamsByCategory'])) {
                    $csvData[] = [''];
                    $csvData[] = ['EXÁMENES MÁS SOLICITADOS POR CATEGORÍA'];
                    
                    foreach ($this->data['categoryStats'] as $category) {
                        if (!empty($this->data['topExamsByCategory'][$category->id])) {
                            $csvData[] = [''];
                            $csvData[] = ['Categoría: ' . ($category->name ?? 'Sin categoría')];
                            $csvData[] = ['Posición', 'Examen', 'Cantidad', '% de la Categoría'];
                            
                            foreach ($this->data['topExamsByCategory'][$category->id] as $examIndex => $exam) {
                                $categoryTotal = $category->count ?? 1;
                                $examPercentage = $categoryTotal > 0 ? round(($exam->count / $categoryTotal) * 100, 1) : 0;
                                
                                $csvData[] = [
                                    $examIndex + 1,
                                    $exam->name ?? 'N/A',
                                    $exam->count ?? 0,
                                    $examPercentage . '%'
                                ];
                            }
                        }
                    }
                }
                break;
                
            case 'doctors':
                $csvData[] = ['Total Solicitudes', $this->data['totalRequests'] ?? 0, '100%'];
                $csvData[] = ['Total Pacientes', $this->data['totalPatients'] ?? 0, ''];
                $csvData[] = ['Total Exámenes', $this->data['totalExams'] ?? 0, ''];
                $csvData[] = ['Doctores Activos', count($this->data['doctorStats'] ?? []), ''];
                $csvData[] = [''];
                
                // Datos por doctores
                $csvData[] = ['RANKING DE DOCTORES POR ACTIVIDAD'];
                $csvData[] = ['Posición', 'Doctor', 'Especialidad', 'Rol', 'Solicitudes', 'Porcentaje'];
                
                if (!empty($this->data['doctorStats'])) {
                    foreach ($this->data['doctorStats'] as $index => $doctor) {
                        $csvData[] = [
                            $index + 1,
                            $doctor->name ?? 'N/A',
                            $doctor->especialidad ?? 'General',
                            $doctor->role == 'doctor' ? 'Médico' : 'Administrador de Laboratorio',
                            $doctor->count ?? 0,
                            ($doctor->percentage ?? 0) . '%'
                        ];
                    }
                }
                
                // Estado de exámenes por doctor
                if (!empty($this->data['resultStats'])) {
                    $csvData[] = [''];
                    $csvData[] = ['ESTADO DE EXÁMENES POR DOCTOR'];
                    $csvData[] = ['Doctor', 'Pendientes', 'En Proceso', 'Completados', 'Total', '% Completado'];
                    
                    foreach ($this->data['doctorStats'] as $doctor) {
                        $results = $this->data['resultStats'][$doctor->id] ?? ['completedCount' => 0, 'pendingCount' => 0, 'inProcessCount' => 0];
                        $total = $results['completedCount'] + $results['pendingCount'] + $results['inProcessCount'];
                        $completionRate = $total > 0 ? round(($results['completedCount'] / $total) * 100, 1) : 0;
                        
                        $csvData[] = [
                            $doctor->name ?? 'N/A',
                            $results['pendingCount'],
                            $results['inProcessCount'],
                            $results['completedCount'],
                            $total,
                            $completionRate . '%'
                        ];
                    }
                }
                break;
                
            case 'exams':
                $csvData[] = ['Total Solicitudes', $this->data['totalRequests'] ?? 0, '100%'];
                $csvData[] = ['Total Pacientes', $this->data['totalPatients'] ?? 0, ''];
                $csvData[] = ['Total Exámenes', $this->data['totalExams'] ?? 0, ''];
                $csvData[] = [''];
                
                // Datos por exámenes
                $csvData[] = ['EXÁMENES MÁS SOLICITADOS'];
                $csvData[] = ['Posición', 'Examen', 'Categoría', 'Cantidad', 'Porcentaje'];
                
                if (!empty($this->data['examStats'])) {
                    foreach ($this->data['examStats'] as $index => $exam) {
                        $csvData[] = [
                            $index + 1,
                            $exam->name ?? 'N/A',
                            $exam->category ?? 'Sin categoría',
                            $exam->count ?? 0,
                            ($exam->percentage ?? 0) . '%'
                        ];
                    }
                }
                break;
                
            case 'services':
                $csvData[] = ['Total Solicitudes', $this->data['totalRequests'] ?? 0, '100%'];
                $csvData[] = ['Total Pacientes', $this->data['totalPatients'] ?? 0, ''];
                $csvData[] = ['Total Exámenes', $this->data['totalExams'] ?? 0, ''];
                $csvData[] = [''];
                
                // Datos por servicios
                $csvData[] = ['SERVICIOS MÁS SOLICITADOS'];
                $csvData[] = ['Posición', 'Servicio', 'Cantidad', 'Porcentaje'];
                
                if (!empty($this->data['serviceStats'])) {
                    foreach ($this->data['serviceStats'] as $index => $service) {
                        $csvData[] = [
                            $index + 1,
                            $service->name ?? 'N/A',
                            $service->count ?? 0,
                            ($service->percentage ?? 0) . '%'
                        ];
                    }
                }
                break;
        }
        
        // Footer
        $csvData[] = [''];
        $csvData[] = ['LABORATORIO LAREDO - Excelencia en Diagnóstico Médico'];
        $csvData[] = ['Reporte generado automáticamente el ' . date('d/m/Y \a \l\a\s H:i:s')];
        
        return $this->arrayToCsv($csvData);
    }

    private function arrayToCsv($data)
    {
        $output = '';

        foreach ($data as $rowIndex => $row) {
            // Asegurar que $row sea un array
            if (!is_array($row)) {
                $row = [$row];
            }

            $csvRow = [];
            foreach ($row as $field) {
                // Convertir a string si no lo es
                $field = (string) $field;

                // Escapar comillas y envolver en comillas si es necesario
                if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false || strpos($field, ';') !== false) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
                $csvRow[] = $field;
            }

            $output .= implode(',', $csvRow) . "\n";
        }

        return $output;
    }
}
