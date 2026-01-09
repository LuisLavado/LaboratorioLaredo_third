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
 * Hoja de Análisis de Rendimiento - Servicios
 */
class ServicesCleanPerformanceSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        return 'Análisis de Rendimiento';
    }

    public function headings(): array
    {
        return [
            'Servicio',
            'Solicitudes Totales',
            'Ingresos Estimados',
            'Precio Unitario',
            'ROI Estimado',
            'Pacientes Únicos',
            'Recurrencia Promedio',
            'Tendencia',
            'Calificación Performance',
            'Participación Mercado',
            'Velocidad Crecimiento',
            'Recomendación'
        ];
    }

    public function collection()
    {
        // Asegurar que servicios sea una Collection
        $servicios = $this->data['servicios'] ?? [];
        if (is_array($servicios)) {
            $servicios = collect($servicios);
        }
        $totales = $this->data['totales'] ?? [];

        if ($servicios->isEmpty()) {
            return collect([
                [
                    'No hay servicios disponibles',
                    0,
                    '$0.00',
                    '$0.00',
                    '0%',
                    0,
                    0,
                    'Sin datos',
                    'N/A',
                    '0%',
                    'N/A',
                    'Verificar configuración'
                ]
            ]);
        }

        $totalSolicitudesGeneral = $servicios->sum('total_solicitudes') ?: 1;
        $totalIngresosGeneral = $servicios->sum(function($s) { return ($s->total_solicitudes ?? 0) * ($s->precio ?? 0); });

        return $servicios->map(function ($servicio) use ($totalSolicitudesGeneral, $totalIngresosGeneral) {
            $solicitudes = $servicio->total_solicitudes ?? 0;
            $precio = $servicio->precio ?? 0;
            $pacientes = $servicio->total_pacientes ?? 0;
            $ingresos = $solicitudes * $precio;

            // Calcular métricas de rendimiento
            $roi = $this->calcularROI($ingresos, $precio);
            $recurrencia = $pacientes > 0 ? round($solicitudes / $pacientes, 2) : 0;
            $participacionMercado = $totalSolicitudesGeneral > 0 ? round(($solicitudes / $totalSolicitudesGeneral) * 100, 2) . '%' : '0%';
            
            // Análisis de tendencia (simulado - en producción vendría de datos históricos)
            $tendencia = $this->analizarTendencia($solicitudes);
            $velocidadCrecimiento = $this->calcularVelocidadCrecimiento($servicio);
            $calificacionPerformance = $this->calcularCalificacionPerformance($solicitudes, $ingresos, $recurrencia);
            $recomendacion = $this->generarRecomendacion($calificacionPerformance, $participacionMercado, $tendencia);

            return [
                $servicio->nombre ?? 'Sin nombre',
                $solicitudes,
                '$' . number_format($ingresos, 2),
                '$' . number_format($precio, 2),
                $roi . '%',
                $pacientes,
                $recurrencia,
                $tendencia,
                $calificacionPerformance,
                $participacionMercado,
                $velocidadCrecimiento,
                $recomendacion
            ];
        })->sortByDesc(function($row) {
            // Ordenar por solicitudes totales
            return $row[1];
        })->values();
    }

    private function calcularROI($ingresos, $precio)
    {
        if ($precio == 0) return 0;
        
        // ROI simplificado basado en el volumen vs precio base
        $costoEstimado = $precio * 0.3; // Asumiendo 30% de costos
        $beneficio = $precio - $costoEstimado;
        
        return $costoEstimado > 0 ? round(($beneficio / $costoEstimado) * 100, 1) : 0;
    }

    private function analizarTendencia($solicitudes)
    {
        // En un sistema real, esto compararía con períodos anteriores
        if ($solicitudes == 0) return 'Sin actividad';
        if ($solicitudes < 5) return 'Estable bajo';
        if ($solicitudes < 20) return 'Estable medio';
        if ($solicitudes < 50) return 'Creciente';
        
        return 'Alto crecimiento';
    }

    private function calcularVelocidadCrecimiento($servicio)
    {
        // Simulación basada en datos disponibles
        $solicitudes = $servicio->total_solicitudes ?? 0;
        
        if ($solicitudes == 0) return 'Sin movimiento';
        if ($solicitudes < 10) return 'Lento';
        if ($solicitudes < 30) return 'Moderado';
        if ($solicitudes < 60) return 'Rápido';
        
        return 'Muy rápido';
    }

    private function calcularCalificacionPerformance($solicitudes, $ingresos, $recurrencia)
    {
        $puntuacion = 0;
        
        // Puntuación por solicitudes
        if ($solicitudes > 50) $puntuacion += 40;
        elseif ($solicitudes > 20) $puntuacion += 30;
        elseif ($solicitudes > 5) $puntuacion += 20;
        elseif ($solicitudes > 0) $puntuacion += 10;
        
        // Puntuación por ingresos
        if ($ingresos > 5000) $puntuacion += 30;
        elseif ($ingresos > 1000) $puntuacion += 20;
        elseif ($ingresos > 200) $puntuacion += 10;
        
        // Puntuación por recurrencia
        if ($recurrencia > 3) $puntuacion += 30;
        elseif ($recurrencia > 2) $puntuacion += 20;
        elseif ($recurrencia > 1.5) $puntuacion += 10;
        
        // Convertir a calificación
        if ($puntuacion >= 80) return 'Excelente';
        if ($puntuacion >= 60) return 'Muy bueno';
        if ($puntuacion >= 40) return 'Bueno';
        if ($puntuacion >= 20) return 'Regular';
        
        return 'Bajo';
    }

    private function generarRecomendacion($calificacion, $participacion, $tendencia)
    {
        $participacionNum = (float) str_replace('%', '', $participacion);
        
        if ($calificacion === 'Excelente') {
            return 'Mantener estrategia actual';
        }
        
        if ($calificacion === 'Muy bueno' && $participacionNum > 10) {
            return 'Potencial para expansion';
        }
        
        if ($tendencia === 'Alto crecimiento') {
            return 'Invertir en promoción';
        }
        
        if ($participacionNum < 2) {
            return 'Revisar estrategia comercial';
        }
        
        if ($calificacion === 'Bajo') {
            return 'Evaluar viabilidad del servicio';
        }
        
        return 'Optimizar procesos';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FF6F00']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'A:L' => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ],
            'B:F' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ],
            'J:J' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]
        ];
    }
}
