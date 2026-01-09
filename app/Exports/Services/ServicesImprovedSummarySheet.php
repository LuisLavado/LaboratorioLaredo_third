<?php

namespace App\Exports\Services;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServicesImprovedSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
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

    public function collection()
    {
        $servicios = $this->data['servicios'] ?? collect();
        $totales = $this->data['totales'] ?? [];

        // Convertir a Collection si es necesario
        if (!($servicios instanceof \Illuminate\Support\Collection)) {
            $servicios = collect($servicios);
        }

        if ($servicios->isEmpty()) {
            return collect([
                ['No hay servicios disponibles en el período especificado'],
                [''],
                ['Período consultado:', $this->data['periodo']['inicio'] ?? 'N/A', 'al', $this->data['periodo']['fin'] ?? 'N/A'],
            ]);
        }

        // Calcular estadísticas básicas
        $totalServicios = $servicios->count();
        $totalSolicitudes = $servicios->sum('total_solicitudes');
        $promedioSolicitudes = $totalServicios > 0 ? round($totalSolicitudes / $totalServicios, 2) : 0;

        // Servicios más y menos solicitados
        $servicioTop = $servicios->sortByDesc('total_solicitudes')->first();
        $servicioMenor = $servicios->sortBy('total_solicitudes')->where('total_solicitudes', '>', 0)->first();

        // Convertir a array si es necesario
        if ($servicioTop && is_object($servicioTop)) {
            $servicioTop = (array) $servicioTop;
        }
        if ($servicioMenor && is_object($servicioMenor)) {
            $servicioMenor = (array) $servicioMenor;
        }

        // Construcción de datos de manera eficiente
        $data = collect([
            // Encabezado del período
            ['RESUMEN EJECUTIVO DE SERVICIOS'],
            ['Período:', $this->data['periodo']['inicio'] ?? 'N/A', 'al', $this->data['periodo']['fin'] ?? 'N/A'],
            ['Generado:', $this->data['generado'] ?? date('d/m/Y H:i:s')],
            [''],

            // Estadísticas generales
            ['ESTADÍSTICAS GENERALES'],
            ['Total de Servicios Activos', $totalServicios],
            ['Total de Solicitudes', $totalSolicitudes],
            ['Promedio por Servicio', $promedioSolicitudes],
            [''],

            // Servicio destacado
            ['SERVICIO MÁS SOLICITADO'],
            ['Nombre', isset($servicioTop['nombre']) ? $servicioTop['nombre'] : 'N/A'],
            ['Solicitudes', isset($servicioTop['total_solicitudes']) ? $servicioTop['total_solicitudes'] : 0],
            ['Porcentaje del Total', $totalSolicitudes > 0 && isset($servicioTop['total_solicitudes']) ? round(($servicioTop['total_solicitudes'] / $totalSolicitudes) * 100, 2) . '%' : '0%'],
            [''],

            // Distribución de servicios por actividad
            ['DISTRIBUCIÓN POR ACTIVIDAD'],
        ]);

        // Distribución por rangos de forma eficiente
        $alta = $servicios->where('total_solicitudes', '>', 20)->count();
        $media = $servicios->whereBetween('total_solicitudes', [6, 20])->count();
        $baja = $servicios->whereBetween('total_solicitudes', [1, 5])->count();
        $inactivos = $servicios->where('total_solicitudes', 0)->count();

        $data->push(['Alta actividad (>20)', $alta, $totalServicios > 0 ? round(($alta / $totalServicios) * 100, 1) . '%' : '0%']);
        $data->push(['Media actividad (6-20)', $media, $totalServicios > 0 ? round(($media / $totalServicios) * 100, 1) . '%' : '0%']);
        $data->push(['Baja actividad (1-5)', $baja, $totalServicios > 0 ? round(($baja / $totalServicios) * 100, 1) . '%' : '0%']);
        $data->push(['Sin actividad (0)', $inactivos, $totalServicios > 0 ? round(($inactivos / $totalServicios) * 100, 1) . '%' : '0%']);
        $data->push(['']);

        // Top 5 servicios para evitar problemas de memoria
        $data->push(['TOP 5 SERVICIOS MÁS SOLICITADOS']);
        $data->push(['Posición', 'Servicio', 'Solicitudes', '% del Total']);

        $topServicios = $servicios->sortByDesc('total_solicitudes')->take(5);
        $posicion = 1;
        foreach ($topServicios as $servicio) {
            // Convertir a array si es necesario
            if (is_object($servicio)) {
                $servicio = (array) $servicio;
            }
            
            $solicitudesServicio = isset($servicio['total_solicitudes']) ? $servicio['total_solicitudes'] : 0;
            $porcentaje = $totalSolicitudes > 0 ? round(($solicitudesServicio / $totalSolicitudes) * 100, 2) . '%' : '0%';
            $data->push([
                $posicion++,
                isset($servicio['nombre']) ? $servicio['nombre'] : (isset($servicio['name']) ? $servicio['name'] : 'Sin nombre'),
                $solicitudesServicio,
                $porcentaje
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Concepto', 'Valor', 'Adicional', 'Porcentaje'];
    }

    public function title(): string
    {
        return 'Resumen Ejecutivo';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            5 => ['font' => ['bold' => true, 'size' => 12]],
            10 => ['font' => ['bold' => true, 'size' => 12]],
            15 => ['font' => ['bold' => true, 'size' => 12]],
            20 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
