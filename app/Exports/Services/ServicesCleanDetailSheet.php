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
 * Hoja de Lista Detallada - Servicios
 */
class ServicesCleanDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        return 'Lista Detallada';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre del Servicio',
            'Descripción',
            'Estado',
            'Total Solicitudes',
            'Pacientes Únicos',
            'Ingresos Estimados',
            'Promedio por Paciente',
            'Fecha Primera Solicitud',
            'Fecha Última Solicitud',
            'Exámenes Incluidos',
            'Popularidad',
            'Observaciones'
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
                    'N/A',
                    'No hay servicios disponibles',
                    'No se encontraron servicios en el período especificado',
                    'N/A',
                    0,
                    0,
                    0,
                    0,
                    'N/A',
                    'N/A',
                    0,
                    'Sin datos',
                    'Verificar configuración de servicios'
                ]
            ]);
        }

        $totalSolicitudes = $servicios->sum('total_solicitudes') ?: 1; // Evitar división por cero

        return $servicios->map(function ($servicio) use ($totalSolicitudes) {
            // Calcular métricas
            $solicitudes = $servicio->total_solicitudes ?? 0;
            $pacientesUnicos = $servicio->total_pacientes ?? 0;
            $precio = $servicio->precio ?? 0;
            $ingresos = $solicitudes * $precio;
            $promedioPorPaciente = $pacientesUnicos > 0 ? round($solicitudes / $pacientesUnicos, 2) : 0;
            $popularidad = $totalSolicitudes > 0 ? round(($solicitudes / $totalSolicitudes) * 100, 2) . '%' : '0%';

            // Determinar estado del servicio
            $estado = 'Activo';
            if ($solicitudes == 0) {
                $estado = 'Sin Actividad';
            } elseif ($solicitudes < 5) {
                $estado = 'Baja Demanda';
            } elseif ($solicitudes > 50) {
                $estado = 'Alta Demanda';
            }

            return [
                $servicio->id ?? 'N/A',
                $servicio->nombre ?? 'Sin nombre',
                $servicio->descripcion ?? 'Sin descripción',
                $estado,
                $solicitudes,
                $pacientesUnicos,
                '$' . number_format($ingresos, 2),
                $promedioPorPaciente,
                $servicio->primera_solicitud ?? 'N/A',
                $servicio->ultima_solicitud ?? 'N/A',
                $servicio->total_examenes ?? 0,
                $popularidad,
                $this->getObservaciones($servicio)
            ];
        });
    }

    private function getObservaciones($servicio)
    {
        $observaciones = [];
        
        $solicitudes = $servicio->total_solicitudes ?? 0;
        
        if ($solicitudes == 0) {
            $observaciones[] = 'Servicio sin solicitudes en el período';
        } elseif ($solicitudes == 1) {
            $observaciones[] = 'Servicio con muy poca actividad';
        } elseif ($solicitudes > 100) {
            $observaciones[] = 'Servicio de alta demanda';
        }

        if (($servicio->total_pacientes ?? 0) > 0 && $solicitudes > 0) {
            $ratio = $solicitudes / $servicio->total_pacientes;
            if ($ratio > 2) {
                $observaciones[] = 'Pacientes recurrentes';
            }
        }

        if (($servicio->precio ?? 0) > 0) {
            if ($servicio->precio > 100) {
                $observaciones[] = 'Servicio premium';
            } elseif ($servicio->precio < 20) {
                $observaciones[] = 'Servicio básico';
            }
        }

        return empty($observaciones) ? 'Servicio estándar' : implode('; ', $observaciones);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1976D2']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'A:M' => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ],
            'E:H' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]
        ];
    }
}
