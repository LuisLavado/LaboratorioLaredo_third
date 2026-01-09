<?php

namespace App\Exports\General;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

/**
 * Hoja de Vista General de Solicitudes
 */
class RequestsOverviewSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $requests;
    protected $startDate;
    protected $endDate;

    public function __construct($requests, $startDate, $endDate)
    {
        $this->requests = $requests;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Solicitudes';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO - REGISTRO DE SOLICITUDES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            [],
            [
                'Nº Solicitud',
                'Fecha y hora',
                'Paciente',
                'DNI',
                'Médico Solicitante',
                'Servicio',
                'Total Exámenes',
                'Completados',
                'Pendientes',
                'Estado',
                'Progreso'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        if (empty($this->requests)) {
            $rows[] = ['No hay solicitudes registradas en el período seleccionado', '', '', '', '', '', '', '', '', '', ''];
            return $rows;
        }
        
        foreach ($this->requests as $request) {
            // Intentar múltiples campos de fecha
            $fecha = null;
            if (isset($request->fecha_solicitud)) {
                $fecha = Carbon::parse($request->fecha_solicitud);
            } elseif (isset($request->fecha)) {
                $fecha = Carbon::parse($request->fecha);
            }
            
            $totalExamenes = $this->getTotalExamenes($request);
            $completados = $this->getExamenesCompletados($request);
            $pendientes = $totalExamenes - $completados;
            $progreso = $totalExamenes > 0 ? round(($completados / $totalExamenes) * 100) : 0;
            
            // Formatear fecha y hora
            $fechaHora = 'N/A';
            if ($fecha) {
                if (isset($request->hora) && $request->hora) {
                    $fechaHora = $fecha->format('d/m/Y') . ' ' . $request->hora;
                } else {
                    $fechaHora = $fecha->format('d/m/Y');
                }
            }
            
            $rows[] = [
                $request->id ?? 'N/A',
                $fechaHora,
                $this->getPacienteName($request),
                $this->getPacienteDNI($request),
                $this->getMedicoName($request),
                $this->getServicioName($request),
                $totalExamenes,
                $completados,
                $pendientes,
                $this->getEstadoSolicitud($request, $progreso),
                $progreso . '%'
            ];
        }

        // Agregar estadísticas de resumen
        $rows[] = [];
        $rows[] = ['ESTADÍSTICAS DEL PERÍODO:', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = [
            'Total Solicitudes:',
            count($this->requests),
            '',
            'Completadas:',
            $this->getSolicitudesCompletadas(),
            '',
            'En Proceso:',
            $this->getSolicitudesEnProceso(),
            '',
            'Pendientes:',
            $this->getSolicitudesPendientes()
        ];
        
        $rows[] = [
            'Total Exámenes:',
            $this->getTotalExamenesGeneral(),
            '',
            'Promedio por Solicitud:',
            $this->getPromedioExamenesPorSolicitud(),
            '',
            'Tasa Completación:',
            $this->getTasaCompletacion() . '%',
            '',
            '',
            ''
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Título principal
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f4e79']
            ]
        ]);
        
        // Período
        $sheet->getStyle('A2:K2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472c4']
            ]
        ]);
        
        // Encabezados de columnas
        $sheet->getStyle('A4:K4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Filas de datos
        $dataStartRow = 5;
        $dataEndRow = $dataStartRow + count($this->requests) - 1;
        
        if ($dataEndRow >= $dataStartRow) {
            $sheet->getStyle('A' . $dataStartRow . ':K' . $dataEndRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);
            
            // Aplicar colores según estado y progreso
            for ($i = $dataStartRow; $i <= $dataEndRow; $i++) {
                // Color alternado para filas
                if (($i - $dataStartRow) % 2 == 0) {
                    $sheet->getStyle('A' . $i . ':K' . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA']
                        ]
                    ]);
                }
                
                // Color según estado
                $estado = $sheet->getCell('J' . $i)->getValue();
                $color = $this->getEstadoColor($estado);
                if ($color) {
                    $sheet->getStyle('J' . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color]
                        ]
                    ]);
                }
                
                // Color de progreso
                $progreso = (int) str_replace('%', '', $sheet->getCell('K' . $i)->getValue());
                $progressColor = $this->getProgressColor($progreso);
                if ($progressColor) {
                    $sheet->getStyle('K' . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $progressColor]
                        ]
                    ]);
                }
            }
        }
        
        // Sección de estadísticas
        $statsStartRow = $dataEndRow + 2;
        $sheet->getStyle('A' . $statsStartRow . ':K' . ($statsStartRow + 2))->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F4FD']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        
        // Mergear celdas de título
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Nº Solicitud
            'B' => 20, // Fecha y hora
            'C' => 40, // Paciente
            'D' => 30, // DNI
            'E' => 30, // Médico Solicitante
            'F' => 20, // Servicio
            'G' => 30, // Total Exámenes
            'H' => 12, // Completados
            'I' => 12, // Pendientes
            'J' => 15, // Estado
            'K' => 12, // Progreso
        ];

    }

    // Métodos auxiliares
    private function getTotalExamenes($request)
    {
        if (isset($request->total_examenes)) {
            return $request->total_examenes;
        }
        
        if (isset($request->examenes)) {
            return is_array($request->examenes) ? count($request->examenes) : 0;
        }
        
        return 0;
    }

    private function getExamenesCompletados($request)
    {
        if (isset($request->examenes_completados)) {
            return $request->examenes_completados;
        }
        
        $completados = 0;
        if (isset($request->examenes) && is_array($request->examenes)) {
            foreach ($request->examenes as $examen) {
                if (isset($examen->estado) && strtolower($examen->estado) === 'completado') {
                    $completados++;
                }
            }
        }
        
        return $completados;
    }

    private function getPacienteName($request)
    {
        if (isset($request->paciente_nombre)) {
            return $request->paciente_nombre;
        }
        
        if (isset($request->paciente)) {
            if (is_object($request->paciente)) {
                return ($request->paciente->nombres ?? '') . ' ' . ($request->paciente->apellidos ?? '');
            }
            return $request->paciente;
        }
        
        return 'N/A';
    }

    private function getPacienteDNI($request)
    {
        // Prioridad 1: Campo directo desde la consulta
        if (isset($request->documento_paciente)) {
            return $request->documento_paciente;
        }
        
        // Prioridad 2: Otros campos alternativos
        if (isset($request->paciente_dni)) {
            return $request->paciente_dni;
        }
        
        // Prioridad 3: Si viene como objeto paciente
        if (isset($request->paciente) && is_object($request->paciente)) {
            return $request->paciente->dni ?? $request->paciente->documento_paciente ?? 'N/A';
        }
        
        // Prioridad 4: Campo dni directo
        if (isset($request->dni)) {
            return $request->dni;
        }
        
        return 'N/A';
    }

    private function getEstadoSolicitud($request, $progreso)
    {
        if (isset($request->estado)) {
            return $this->formatEstado($request->estado);
        }
        
        // Determinar estado basado en progreso
        if ($progreso >= 100) {
            return '🟢 Completada';
        } elseif ($progreso > 0) {
            return '🟡 En Proceso';
        } else {
            return '🔴 Pendiente';
        }
    }

    private function formatEstado($estado)
    {
        switch (strtolower($estado)) {
            case 'completado':
            case 'completada':
            case 'finalizado':
                return '🟢 Completada';
            case 'en_proceso':
            case 'procesando':
                return '🟡 En Proceso';
            case 'pendiente':
                return '🔴 Pendiente';
            case 'cancelado':
            case 'cancelada':
                return '⚫ Cancelada';
            default:
                return '❓ ' . ucfirst($estado);
        }
    }

    private function getSolicitudesCompletadas()
    {
        $count = 0;
        foreach ($this->requests as $request) {
            $totalExamenes = $this->getTotalExamenes($request);
            $completados = $this->getExamenesCompletados($request);
            if ($totalExamenes > 0 && $completados >= $totalExamenes) {
                $count++;
            }
        }
        return $count;
    }

    private function getSolicitudesEnProceso()
    {
        $count = 0;
        foreach ($this->requests as $request) {
            $totalExamenes = $this->getTotalExamenes($request);
            $completados = $this->getExamenesCompletados($request);
            if ($completados > 0 && $completados < $totalExamenes) {
                $count++;
            }
        }
        return $count;
    }

    private function getSolicitudesPendientes()
    {
        $count = 0;
        foreach ($this->requests as $request) {
            $completados = $this->getExamenesCompletados($request);
            if ($completados == 0) {
                $count++;
            }
        }
        return $count;
    }

    private function getTotalExamenesGeneral()
    {
        $total = 0;
        foreach ($this->requests as $request) {
            $total += $this->getTotalExamenes($request);
        }
        return $total;
    }

    private function getPromedioExamenesPorSolicitud()
    {
        $totalSolicitudes = count($this->requests);
        if ($totalSolicitudes == 0) return 0;
        
        return round($this->getTotalExamenesGeneral() / $totalSolicitudes, 2);
    }

    private function getTasaCompletacion()
    {
        $totalExamenes = $this->getTotalExamenesGeneral();
        if ($totalExamenes == 0) return 0;
        
        $totalCompletados = 0;
        foreach ($this->requests as $request) {
            $totalCompletados += $this->getExamenesCompletados($request);
        }
        
        return round(($totalCompletados / $totalExamenes) * 100, 2);
    }

    private function getEstadoColor($estado)
    {
        if (strpos($estado, '🟢') !== false) return 'D5E8D4';
        if (strpos($estado, '🟡') !== false) return 'FFF2CC';
        if (strpos($estado, '🔴') !== false) return 'F8D7DA';
        if (strpos($estado, '⚫') !== false) return 'E5E5E5';
        return null;
    }

    private function getProgressColor($progreso)
    {
        if ($progreso >= 100) return 'D5E8D4';  // Verde
        if ($progreso >= 75) return 'D4EDDA';   // Verde claro
        if ($progreso >= 50) return 'FFF3CD';   // Amarillo
        if ($progreso >= 25) return 'FCE4D6';   // Naranja
        if ($progreso > 0) return 'F8D7DA';     // Rojo claro
        return 'F8F9FA';                        // Gris
    }

    private function getMedicoName($request)
    {
        if (isset($request->medico_solicitante)) {
            return $request->medico_solicitante;
        }
        
        if (isset($request->user) && is_object($request->user)) {
            return trim(($request->user->nombre ?? '') . ' ' . ($request->user->apellido ?? ''));
        }
        
        if (isset($request->doctor)) {
            return $request->doctor;
        }
        
        return 'N/A';
    }

    private function getServicioName($request)
    {
        // Prioridad 1: Campo directo desde la consulta
        if (isset($request->servicio)) {
            return $request->servicio;
        }
        
        // Prioridad 2: Campo alternativo
        if (isset($request->servicio_nombre)) {
            return $request->servicio_nombre;
        }
        
        // Prioridad 3: Si viene como objeto servicio
        if (isset($request->servicio_obj) && is_object($request->servicio_obj)) {
            return $request->servicio_obj->nombre ?? 'N/A';
        }
        
        return 'N/A';
    }
}
