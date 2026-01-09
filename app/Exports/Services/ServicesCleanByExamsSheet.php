<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

/**
 * Hoja de Análisis por Exámenes Incluidos - Servicios
 */
class ServicesCleanByExamsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Servicios por Exámenes';
    }

    public function headings(): array
    {
        return [
            'Servicio',
            'Total Exámenes Incluidos',
            'Examen Más Solicitado',
            'Solicitudes del Top Examen',
            'Porcentaje del Top',
            'Segundo Examen',
            'Solicitudes 2do Examen',
            'Porcentaje 2do',
            'Diversidad de Exámenes',
            'Complejidad del Servicio',
            'Especialización',
            'Eficiencia por Examen'
        ];
    }

    public function collection()
    {
        // Asegurar que servicios sea una Collection
        $servicios = $this->data['servicios'] ?? [];
        if (is_array($servicios)) {
            $servicios = collect($servicios);
        }

        if ($servicios->isEmpty()) {
            return collect([
                [
                    'No hay servicios disponibles',
                    0,
                    'N/A',
                    0,
                    '0%',
                    'N/A',
                    0,
                    '0%',
                    'Sin datos',
                    'N/A',
                    'N/A',
                    0
                ]
            ]);
        }

        return $servicios->map(function ($servicio) {
            // Obtener exámenes asociados al servicio
            $examenes = $servicio->examenes ?? collect();
            $totalExamenes = $examenes->count();
            $totalSolicitudesServicio = $servicio->total_solicitudes ?? 0;

            // Análisis de exámenes
            $topExamen = $examenes->sortByDesc('total_solicitudes')->first();
            $segundoExamen = $examenes->sortByDesc('total_solicitudes')->skip(1)->first();

            $topSolicitudes = $topExamen->total_solicitudes ?? 0;
            $segundoSolicitudes = $segundoExamen->total_solicitudes ?? 0;

            $porcentajeTop = $totalSolicitudesServicio > 0 ? round(($topSolicitudes / $totalSolicitudesServicio) * 100, 2) . '%' : '0%';
            $porcentajeSegundo = $totalSolicitudesServicio > 0 ? round(($segundoSolicitudes / $totalSolicitudesServicio) * 100, 2) . '%' : '0%';

            // Calcular métricas de diversidad y complejidad
            $diversidad = $this->calcularDiversidad($examenes);
            $complejidad = $this->calcularComplejidad($totalExamenes, $totalSolicitudesServicio);
            $especializacion = $this->calcularEspecializacion($examenes);
            $eficiencia = $totalExamenes > 0 ? round($totalSolicitudesServicio / $totalExamenes, 2) : 0;

            return [
                $servicio->nombre ?? 'Sin nombre',
                $totalExamenes,
                $topExamen->nombre ?? 'N/A',
                $topSolicitudes,
                $porcentajeTop,
                $segundoExamen->nombre ?? 'N/A',
                $segundoSolicitudes,
                $porcentajeSegundo,
                $diversidad,
                $complejidad,
                $especializacion,
                $eficiencia
            ];
        });
    }

    private function calcularDiversidad($examenes)
    {
        $count = $examenes->count();
        
        if ($count == 0) return 'Sin exámenes';
        if ($count == 1) return 'Muy baja';
        if ($count <= 3) return 'Baja';
        if ($count <= 8) return 'Media';
        if ($count <= 15) return 'Alta';
        
        return 'Muy alta';
    }

    private function calcularComplejidad($totalExamenes, $totalSolicitudes)
    {
        if ($totalExamenes == 0) return 'Sin datos';
        
        $ratio = $totalSolicitudes / $totalExamenes;
        
        if ($ratio < 1) return 'Muy baja';
        if ($ratio < 5) return 'Baja';
        if ($ratio < 15) return 'Media';
        if ($ratio < 30) return 'Alta';
        
        return 'Muy alta';
    }

    private function calcularEspecializacion($examenes)
    {
        if ($examenes->isEmpty()) return 'Sin datos';
        
        // Calcular distribución de solicitudes entre exámenes
        $solicitudes = $examenes->pluck('total_solicitudes');
        $total = $solicitudes->sum();
        
        if ($total == 0) return 'Sin actividad';
        
        // Calcular el coeficiente de Gini simplificado
        $max = $solicitudes->max();
        $concentracion = $total > 0 ? ($max / $total) : 0;
        
        if ($concentracion > 0.7) return 'Muy especializado';
        if ($concentracion > 0.5) return 'Especializado';
        if ($concentracion > 0.3) return 'Moderadamente especializado';
        
        return 'Diversificado';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '388E3C']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'A:L' => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ],
            'B:D' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ],
            'F:H' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ],
            'L:L' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]
        ];
    }
}
