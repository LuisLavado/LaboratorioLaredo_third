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
 * Hoja de Resumen Ejecutivo - Servicios
 */
class ServicesCleanSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        return 'Resumen Ejecutivo';
    }

    public function headings(): array
    {
        return [
            'REPORTE DE SERVICIOS - RESUMEN EJECUTIVO',
            '',
            'Período: ' . $this->startDate . ' al ' . $this->endDate,
            'Generado: ' . now()->format('d/m/Y H:i:s'),
            '',
            '=== ESTADÍSTICAS GENERALES ===',
            '',
            'Métrica',
            'Valor',
            'Porcentaje',
            'Observaciones'
        ];
    }

    public function collection()
    {
        // Manejar servicios como Collection o array
        $servicios = $this->data['servicios'] ?? collect();
        if (is_array($servicios)) {
            $servicios = collect($servicios);
        }
        // Si ya es una Collection, la dejamos como está
        $totales = $this->data['totales'] ?? [];

        // Debug adicional
        \Log::info('ServicesCleanSummarySheet - Debug:', [
            'servicios_count' => $servicios->count(),
            'servicios_type' => get_class($servicios),
            'primer_servicio_existe' => $servicios->first() ? 'Si' : 'No',
            'primer_servicio_props' => $servicios->first() ? array_keys(get_object_vars($servicios->first())) : 'N/A'
        ]);

        // Calcular estadísticas
        $totalServicios = $servicios->count();
        $totalSolicitudes = $servicios->sum('total_solicitudes') ?: $totales['solicitudes'] ?? 0;
        $servicioMasSolicitado = $servicios->sortByDesc('total_solicitudes')->first();
        $servicioMenosSolicitado = $servicios->sortBy('total_solicitudes')->where('total_solicitudes', '>', 0)->first();

        $data = collect([
            // Estadísticas básicas
            ['Total de Servicios Activos', $totalServicios, '100%', 'Servicios disponibles en el período'],
            ['Total de Solicitudes', $totalSolicitudes, '100%', 'Solicitudes generadas por servicios'],
            ['Promedio por Servicio', $totalServicios > 0 ? round($totalSolicitudes / $totalServicios, 2) : 0, '-', 'Solicitudes promedio por servicio'],
            [''],
            
            // Top servicios
            ['=== SERVICIO MÁS SOLICITADO ==='],
            ['Nombre', $servicioMasSolicitado->nombre ?? 'N/A', '-', 'Servicio líder en demanda'],
            ['Solicitudes', $servicioMasSolicitado->total_solicitudes ?? 0, $totalSolicitudes > 0 ? round((($servicioMasSolicitado->total_solicitudes ?? 0) / $totalSolicitudes) * 100, 2) . '%' : '0%', 'Participación en el total'],
            [''],
            
            ['=== SERVICIO MENOS SOLICITADO ==='],
            ['Nombre', $servicioMenosSolicitado->nombre ?? 'N/A', '-', 'Servicio con menor demanda'],
            ['Solicitudes', $servicioMenosSolicitado->total_solicitudes ?? 0, $totalSolicitudes > 0 ? round((($servicioMenosSolicitado->total_solicitudes ?? 0) / $totalSolicitudes) * 100, 2) . '%' : '0%', 'Participación en el total'],
            [''],
            
            // Análisis de distribución
            ['=== ANÁLISIS DE DISTRIBUCIÓN ==='],
        ]);

        // Agregar distribución por rangos
        $rangos = [
            'Alta demanda (>50 solicitudes)' => $servicios->where('total_solicitudes', '>', 50)->count(),
            'Demanda media (11-50 solicitudes)' => $servicios->whereBetween('total_solicitudes', [11, 50])->count(),
            'Demanda baja (1-10 solicitudes)' => $servicios->whereBetween('total_solicitudes', [1, 10])->count(),
            'Sin demanda (0 solicitudes)' => $servicios->where('total_solicitudes', 0)->count(),
        ];

        foreach ($rangos as $rango => $cantidad) {
            $porcentaje = $totalServicios > 0 ? round(($cantidad / $totalServicios) * 100, 2) . '%' : '0%';
            $data->push([$rango, $cantidad, $porcentaje, 'Servicios en este rango']);
        }

        $data->push(['']);
        $data->push(['=== TOP 10 SERVICIOS MÁS SOLICITADOS ===']);
        $data->push(['Posición', 'Servicio', 'Solicitudes', 'Porcentaje del Total']);

        // Top 10 servicios
        $topServicios = $servicios->sortByDesc('total_solicitudes')->take(10);
        $posicion = 1;
        foreach ($topServicios as $servicio) {
            $porcentaje = $totalSolicitudes > 0 ? round(($servicio->total_solicitudes / $totalSolicitudes) * 100, 2) . '%' : '0%';
            $data->push([
                $posicion++,
                $servicio->nombre ?? 'Sin nombre',
                $servicio->total_solicitudes ?? 0,
                $porcentaje
            ]);
        }

        // Si hay menos de 10 servicios, llenar el resto
        while ($posicion <= 10) {
            $data->push([$posicion++, 'N/A', 0, '0%']);
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar estilos
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E7D32']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            3 => ['font' => ['bold' => true, 'color' => ['rgb' => '1565C0']]],
            4 => ['font' => ['italic' => true]],
            6 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1976D2']]
            ],
            'A:D' => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]
        ];
    }
}
