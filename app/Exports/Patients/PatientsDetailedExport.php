<?php

namespace App\Exports\Patients;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

/**
 * Exportación Detallada de Pacientes
 * Reporte específico y detallado de información de pacientes
 */
class PatientsDetailedExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    use Exportable;

    protected $reportData;
    protected $patients;
    protected $startDate;
    protected $endDate;

    public function __construct($reportData, $startDate, $endDate)
    {
        $this->reportData = $reportData;
        // Extraer los pacientes del array de datos del reporte
        $this->patients = $reportData['patients'] ?? [];
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Pacientes Detallado';
    }

    public function headings(): array
    {
        $stats = [];
        
        // Agregar estadísticas si están disponibles
        if (isset($this->reportData['totalPatients'])) {
            $stats[] = ['ESTADÍSTICAS DEL REPORTE'];
            $stats[] = ['Total de Pacientes: ' . ($this->reportData['totalPatients'] ?? 0)];
            $stats[] = ['Total de Solicitudes: ' . ($this->reportData['totalRequests'] ?? 0)];
            $stats[] = ['Total de Exámenes: ' . ($this->reportData['totalExams'] ?? 0)];
            $stats[] = ['Exámenes Pendientes: ' . ($this->reportData['pendingCount'] ?? 0)];
            $stats[] = ['Exámenes Completados: ' . ($this->reportData['completedCount'] ?? 0)];
            $stats[] = [];
        }

        return array_merge([
            ['LABORATORIO CLÍNICO LAREDO - PACIENTES DETALLADO'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' al ' . $this->endDate->format('d/m/Y')],
            []
        ], $stats, [
            [
                'ID',
                'DNI',
                'Nombres',
                'Apellidos',
                'Fecha Nacimiento',
                'Edad',
                'Género',
                'Teléfono',
                'Total Solicitudes',
                'Última Visita',
                'Fecha Registro'
            ]
        ]);
    }

    public function array(): array
    {
        $rows = [];

        if (empty($this->patients)) {
            $rows[] = [
                'No hay pacientes disponibles para el período seleccionado.',
                '', '', '', '', '', '', '', '', '', ''
            ];
            return $rows;
        }

        foreach ($this->patients as $patient) {
            // Filtrar pacientes sin información básica esencial
            $nombres = $patient->nombres ?? '';
            $apellidos = $patient->apellidos ?? '';
            $documento = $patient->documento ?? $patient->dni ?? '';
            
            // Saltar pacientes que no tienen nombres, apellidos Y documento
            if (empty($nombres) && empty($apellidos) && empty($documento)) {
                continue;
            }
            
            $fechaNacimiento = '';
            $edad = '';
            
            // Manejar fecha de nacimiento y edad (viene desde la query como 'edad' calculada)
            if (isset($patient->fecha_nacimiento)) {
                $fechaNacimiento = Carbon::parse($patient->fecha_nacimiento)->format('d/m/Y');
            }
            
            // Usar la edad calculada en la query o calcular si es necesario
            if (isset($patient->edad)) {
                $edad = $patient->edad;
            } elseif (isset($patient->fecha_nacimiento)) {
                $edad = Carbon::parse($patient->fecha_nacimiento)->age;
            }

            $ultimaVisita = '';
            if (isset($patient->ultima_visita)) {
                $ultimaVisita = Carbon::parse($patient->ultima_visita)->format('d/m/Y H:i');
            }

            $fechaRegistro = $patient->primera_visita;
        

            $rows[] = [
                $patient->id ?? '',
                $documento,
                $nombres,
                $apellidos,
                $fechaNacimiento,
                $edad,
                ucfirst($patient->sexo ?? $patient->genero ?? ''),  // sexo viene de la query
                $patient->celular ?? $patient->telefono ?? '',  // celular viene de la query
                $patient->total_solicitudes ?? 0,  // total_solicitudes viene de la query
                $ultimaVisita,
                $fechaRegistro
            ];
        }

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

        // Subtítulo
        $sheet->getStyle('A2:K2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2f5f8f']
            ]
        ]);

        // Período
        $sheet->getStyle('A3:K3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472c4']
            ]
        ]);

        // Fecha de generación
        $sheet->getStyle('A4:K4')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Encabezados de columnas (usando el mismo estilo verde que el overview)
        $sheet->getStyle('A11:K11')->applyFromArray([
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

        // Mergear celdas de títulos
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');
        $sheet->mergeCells('A4:K4');
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 12,  // DNI
            'C' => 20,  // Nombres
            'D' => 20,  // Apellidos
            'E' => 15,  // Fecha Nacimiento
            'F' => 8,   // Edad
            'G' => 10,  // Género
            'H' => 15,  // Teléfono
            'I' => 12,  // Total Solicitudes
            'J' => 18,  // Última Visita
            'K' => 15   // Fecha Registro
        ];
    }
}
