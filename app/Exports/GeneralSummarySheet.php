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

/**
 * Hoja de Resumen Ejecutivo - Información general del laboratorio
 */
class GeneralSummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            ['LABORATORIO CLÍNICO LAREDO'],
            ['RESUMEN EJECUTIVO DEL PERÍODO'],
            ['Desde: ' . $this->startDate->format('d/m/Y') . ' hasta: ' . $this->endDate->format('d/m/Y')],
            ['Generado el: ' . now()->format('d/m/Y H:i:s')],
            [],
            ['Concepto', 'Valor']
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        // Obtener totales básicos de forma segura
        $totalSolicitudes = $this->data['totales']['solicitudes'] ?? 0;
        $totalPacientes = $this->data['totales']['pacientes'] ?? 0;
        $totalExamenes = $this->data['totales']['examenes_realizados'] ?? 0;
        
        // ESTADÍSTICAS GENERALES
        $rows[] = ['Total de Solicitudes', $totalSolicitudes];
        $rows[] = ['Total de Pacientes', $totalPacientes];
        $rows[] = ['Total de Exámenes', $totalExamenes];
        
        $rows[] = ['', ''];
        
        // PROMEDIOS
        if ($totalPacientes > 0) {
            $promedioExamenes = round($totalExamenes / $totalPacientes, 1);
            $rows[] = ['Promedio Exámenes por Paciente', $promedioExamenes];
        }
        
        if ($totalSolicitudes > 0) {
            $promedioExamenesPorSolicitud = round($totalExamenes / $totalSolicitudes, 1);
            $rows[] = ['Promedio Exámenes por Solicitud', $promedioExamenesPorSolicitud];
        }
        
        $rows[] = ['', ''];
        
        // SERVICIOS MÁS ACTIVOS
        if (isset($this->data['servicios']) && !empty($this->data['servicios'])) {
            $rows[] = ['SERVICIOS MÁS ACTIVOS', ''];
            $rows[] = ['', ''];
            
            $serviciosOrdenados = collect($this->data['servicios'])->sortByDesc('total_solicitudes')->take(5);
            foreach ($serviciosOrdenados as $index => $servicio) {
                $nombre = $servicio->nombre ?? 'Sin nombre';
                $solicitudes = $servicio->total_solicitudes ?? 0;
                $rows[] = [($index + 1) . '. ' . $nombre, $solicitudes . ' solicitudes'];
            }
            
            $rows[] = ['', ''];
        }
        
        // DISTRIBUCIÓN POR GÉNERO (si hay datos de pacientes)
        if (isset($this->data['patients']) && !empty($this->data['patients'])) {
            $masculino = 0;
            $femenino = 0;
            $otros = 0;
            
            foreach ($this->data['patients'] as $patient) {
                $sexo = strtolower($patient->sexo ?? '');
                if (in_array($sexo, ['m', 'masculino', 'hombre'])) {
                    $masculino++;
                } elseif (in_array($sexo, ['f', 'femenino', 'mujer'])) {
                    $femenino++;
                } else {
                    $otros++;
                }
            }
            
            $total = count($this->data['patients']);
            if ($total > 0) {
                $rows[] = ['DISTRIBUCIÓN POR GÉNERO', ''];
                $rows[] = ['', ''];
                if ($masculino > 0) {
                    $porcentaje = round(($masculino / $total) * 100, 1);
                    $rows[] = ['Masculino', $masculino . ' (' . $porcentaje . '%)'];
                }
                if ($femenino > 0) {
                    $porcentaje = round(($femenino / $total) * 100, 1);
                    $rows[] = ['Femenino', $femenino . ' (' . $porcentaje . '%)'];
                }
                if ($otros > 0) {
                    $porcentaje = round(($otros / $total) * 100, 1);
                    $rows[] = ['No especificado', $otros . ' (' . $porcentaje . '%)'];
                }
                $rows[] = ['', ''];
            }
        }
        
        // INFORMACIÓN DEL REPORTE
        $rows[] = ['INFORMACIÓN DEL REPORTE', ''];
        $rows[] = ['', ''];
        $fechaInicio = $this->data['periodo']['inicio'] ?? 'No disponible';
        $fechaFin = $this->data['periodo']['fin'] ?? 'No disponible';
        $rows[] = ['Período', $fechaInicio . ' al ' . $fechaFin];
        $rows[] = ['Generado el', $this->data['generado'] ?? now()->format('d/m/Y H:i:s')];
        $rows[] = ['Generado por', $this->data['generatedBy'] ?? 'Sistema'];
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado principal
        $sheet->getStyle('A1:B4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Encabezados de columnas
        $sheet->getStyle('A6:B6')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']]
        ]);
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 25
        ];
    }
}
