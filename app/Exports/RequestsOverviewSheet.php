<?php

namespace App\Exports;

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
                'Fecha',
                'Hora',
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
            $rows[] = ['No hay solicitudes registradas en el período seleccionado', '', '', '', '', '', '', '', '', '', '', ''];
            return $rows;
        }
        
        foreach ($this->requests as $request) {
            // Usar 'fecha' en lugar de 'fecha_solicitud'
            $fecha = isset($request->fecha) ? 
                Carbon::parse($request->fecha) : null;
            
            $totalExamenes = $this->getTotalExamenes($request);
            $completados = $this->getExamenesCompletados($request);
            $pendientes = $totalExamenes - $completados;
            $progreso = $totalExamenes > 0 ? round(($completados / $totalExamenes) * 100) : 0;
            
            $rows[] = [
                $request->id ?? 'N/A',
                $fecha ? $fecha->format('d/m/Y') : 'N/A',
                $fecha ? $fecha->format('H:i') : 'N/A',
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
        $rows[] = ['ESTADÍSTICAS DEL PERÍODO:', '', '', '', '', '', '', '', '', '', '', ''];
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
            $this->getSolicitudesPendientes(),
            ''
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
            '',
            ''
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezados principales
        $sheet->getStyle('A1:L2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Encabezados de columnas
        $sheet->getStyle('A4:L4')->applyFromArray([
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
            $sheet->getStyle('A' . $dataStartRow . ':L' . $dataEndRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);
            
            // Aplicar colores según estado y progreso
            for ($i = $dataStartRow; $i <= $dataEndRow; $i++) {
                // Color alternado para filas
                if (($i - $dataStartRow) % 2 == 0) {
                    $sheet->getStyle('A' . $i . ':L' . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA']
                        ]
                    ]);
                }
                
                // Color según estado
                $estado = $sheet->getCell('K' . $i)->getValue();
                $color = $this->getEstadoColor($estado);
                if ($color) {
                    $sheet->getStyle('K' . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color]
                        ]
                    ]);
                }
                
                // Color de progreso
                $progreso = (int) str_replace('%', '', $sheet->getCell('L' . $i)->getValue());
                $progressColor = $this->getProgressColor($progreso);
                if ($progressColor) {
                    $sheet->getStyle('L' . $i)->applyFromArray([
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
        $sheet->getStyle('A' . $statsStartRow . ':L' . ($statsStartRow + 2))->applyFromArray([
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
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // Nº Solicitud
            'B' => 12, // Fecha
            'C' => 8,  // Hora
            'D' => 25, // Paciente
            'E' => 12, // DNI
            'F' => 20, // Médico
            'G' => 20, // Servicio
            'H' => 10, // Total Exámenes
            'I' => 12, // Completados
            'J' => 12, // Pendientes
            'K' => 15, // Estado
            'L' => 10  // Progreso
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
        if (isset($request->paciente_dni)) {
            return $request->paciente_dni;
        }
        
        if (isset($request->paciente) && is_object($request->paciente)) {
            return $request->paciente->numero_documento ?? $request->paciente->dni ?? 'N/A';
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
        if (isset($request->servicio_nombre)) {
            return $request->servicio_nombre;
        }
        
        if (isset($request->servicio) && is_object($request->servicio)) {
            return $request->servicio->nombre ?? 'N/A';
        }
        
        if (isset($request->servicio)) {
            return $request->servicio;
        }
        
        return 'N/A';
    }
}
