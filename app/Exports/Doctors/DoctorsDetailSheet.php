<?php

namespace App\Exports\Doctors;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Hoja de detalles de doctores para exportación Excel
 * Contiene información detallada sobre la actividad de cada doctor
 */
class DoctorsDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $data;
    protected $startDate;
    protected $endDate;

    /**
     * Constructor
     *
     * @param array $data Datos del reporte
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     */
    public function __construct(array $data, string $startDate, string $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Detalles por Doctor';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['REPORTE DETALLADO DE SOLICITUDES POR DOCTOR'],
            ['Período: ' . $this->startDate . ' al ' . $this->endDate],
            [''],
            ['Doctor', 'Especialidad', 'Fecha Solicitud', 'Paciente', 'DNI', 'Exámenes Solicitados', 'Estado', 'Servicio'],
        ];
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $doctorStats = $this->data['doctorStats'] ?? [];
        
        if (empty($doctorStats)) {
            return [['No hay datos disponibles para mostrar', '', '', '', '', '', '', '']];
        }
        
        // Convertir a array si es una colección
        if ($doctorStats instanceof \Illuminate\Support\Collection) {
            $doctorStats = $doctorStats->toArray();
        }
        
        $rows = [];
        
        // Para cada doctor, obtener sus solicitudes detalladas
        foreach ($doctorStats as $doctor) {
            $doctorId = $doctor->id ?? null;
            
            if (!$doctorId) continue;
            
            // Obtener las solicitudes de este doctor en el período
            $solicitudes = $this->getDoctorSolicitudes($doctorId);
            
            if (empty($solicitudes)) {
                // Si no hay solicitudes, mostrar al menos el doctor
                $rows[] = [
                    ($doctor->nombres ?? '') . ' ' . ($doctor->apellidos ?? ''),
                    $doctor->especialidad ?? 'No especificada',
                    'Sin solicitudes',
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A'
                ];
            } else {
                // Mostrar cada solicitud del doctor
                foreach ($solicitudes as $solicitud) {
                    $rows[] = [
                        ($doctor->nombres ?? '') . ' ' . ($doctor->apellidos ?? ''),
                        $doctor->especialidad ?? 'No especificada',
                        $solicitud->fecha ?? 'N/A',
                        $solicitud->paciente_nombre ?? 'N/A',
                        $solicitud->paciente_dni ?? 'N/A',
                        $solicitud->examenes_lista ?? 'N/A',
                        $solicitud->estado ?? 'N/A',
                        $solicitud->servicio ?? 'N/A'
                    ];
                }
            }
            
            // Agregar una fila en blanco para separar doctores
            $rows[] = ['', '', '', '', '', '', '', ''];
        }
        
        return $rows;
    }
    
    /**
     * Obtener solicitudes detalladas de un doctor
     *
     * @param int $doctorId
     * @return array
     */
    private function getDoctorSolicitudes($doctorId)
    {
        try {
            $startDate = \Carbon\Carbon::parse($this->startDate)->format('Y-m-d');
            $endDate = \Carbon\Carbon::parse($this->endDate)->format('Y-m-d');
            
            $solicitudes = \DB::table('solicitudes')
                ->join('pacientes', 'solicitudes.paciente_id', '=', 'pacientes.id')
                ->leftJoin('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
                ->where('solicitudes.user_id', $doctorId)
                ->whereBetween('solicitudes.fecha', [$startDate, $endDate])
                ->select(
                    'solicitudes.id',
                    'solicitudes.fecha',
                    'solicitudes.estado',
                    \DB::raw("CONCAT(pacientes.nombres, ' ', pacientes.apellidos) as paciente_nombre"),
                    'pacientes.dni as paciente_dni',
                    'servicios.nombre as servicio'
                )
                ->orderBy('solicitudes.fecha', 'desc')
                ->get();
            
            // Para cada solicitud, obtener los exámenes
            foreach ($solicitudes as $solicitud) {
                $examenes = \DB::table('detallesolicitud')
                    ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                    ->where('detallesolicitud.solicitud_id', $solicitud->id)
                    ->select('examenes.nombre', 'detallesolicitud.estado')
                    ->get();
                
                $examenesLista = [];
                foreach ($examenes as $examen) {
                    $examenesLista[] = $examen->nombre;
                }
                
                $solicitud->examenes_lista = implode('; ', $examenesLista);
            }
            
            return $solicitudes->toArray();
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener solicitudes del doctor: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Merge cells for headers
        $sheet->mergeCells('A1:H1'); // Título principal
        $sheet->mergeCells('A2:H2'); // Subtítulo
        $sheet->mergeCells('A3:H3'); // Período
        
        // Apply styles similar to other sheets
        $styles = [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ]
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ]
            ],
            // Período
            3 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472c4']
                ]
            ],
            // Encabezados de columnas
            5 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'dae3f3']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '4472c4']
                    ]
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ]
        ];
        
        // Apply styles to data rows
        for ($row = 6; $row <= $lastRow; $row++) {
            $styles[$row] = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '4472c4']
                    ]
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => false
                ]
            ];
            
            // Specific alignment for each column
            $styles["A{$row}"] = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]];   // Doctor
            $styles["B{$row}"] = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]];   // Especialidad
            $styles["C{$row}"] = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]; // Fecha
            $styles["D{$row}"] = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]];   // Paciente
            $styles["E{$row}"] = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]; // DNI
            $styles["F{$row}"] = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'wrapText' => true]]; // Exámenes
            $styles["G{$row}"] = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]; // Estado
            $styles["H{$row}"] = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]];   // Servicio
        }
        
        // Ajustar altura de filas para que el texto se vea bien
        for ($row = 6; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(25);
        }
        
        // Colorear filas alternas para mejor legibilidad
        for ($row = 6; $row <= $lastRow; $row += 2) {
            $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FA');
        }
        
        return $styles;
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 35, // Doctor
            'B' => 20, // Especialidad
            'C' => 15, // Fecha Solicitud
            'D' => 35, // Paciente
            'E' => 12, // DNI
            'F' => 40, // Exámenes Solicitados
            'G' => 15, // Estado
            'H' => 20, // Servicio
        ];
    }
}
