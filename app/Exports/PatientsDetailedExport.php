<?php

namespace App\Exports;

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

    protected $patients;
    protected $startDate;
    protected $endDate;

    public function __construct($patients, $startDate, $endDate)
    {
        $this->patients = $patients;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Pacientes Detallado';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['REPORTE DETALLADO DE PACIENTES'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            ['Generado el: ' . now()->format('d/m/Y H:i:s')],
            [],
            [
                'ID',
                'DNI',
                'Nombres',
                'Apellidos',
                'Fecha Nacimiento',
                'Edad',
                'Género',
                'Teléfono',
                'Email',
                'Dirección',
                'Distrito',
                'Total Solicitudes',
                'Última Visita',
                'Estado',
                'Observaciones',
                'Fecha Registro'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];

        if (empty($this->patients)) {
            $rows[] = [
                'No hay pacientes disponibles para el período seleccionado.',
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
            ];
            return $rows;
        }

        foreach ($this->patients as $patient) {
            $fechaNacimiento = '';
            $edad = '';
            
            if (isset($patient->fecha_nacimiento)) {
                $fechaNacimiento = Carbon::parse($patient->fecha_nacimiento)->format('d/m/Y');
                $edad = Carbon::parse($patient->fecha_nacimiento)->age;
            }

            $ultimaVisita = '';
            if (isset($patient->ultima_visita)) {
                $ultimaVisita = Carbon::parse($patient->ultima_visita)->format('d/m/Y H:i');
            }

            $fechaRegistro = '';
            if (isset($patient->created_at)) {
                $fechaRegistro = Carbon::parse($patient->created_at)->format('d/m/Y');
            }

            $rows[] = [
                $patient->id ?? '',
                $patient->dni ?? '',
                $patient->nombres ?? '',
                $patient->apellidos ?? '',
                $fechaNacimiento,
                $edad,
                $patient->genero ?? '',
                $patient->telefono ?? '',
                $patient->email ?? '',
                $patient->direccion ?? '',
                $patient->distrito ?? '',
                $patient->total_solicitudes ?? 0,
                $ultimaVisita,
                $patient->estado ?? 'Activo',
                $patient->observaciones ?? '',
                $fechaRegistro
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 16]
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 14]
            ],
            // Período
            3 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472c4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            // Fecha generación
            4 => [
                'font' => ['italic' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Encabezados de columnas
            6 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'dae3f3']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '4472c4']
                    ]
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
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
            'I' => 25,  // Email
            'J' => 30,  // Dirección
            'K' => 15,  // Distrito
            'L' => 12,  // Total Solicitudes
            'M' => 18,  // Última Visita
            'N' => 12,  // Estado
            'O' => 30,  // Observaciones
            'P' => 15   // Fecha Registro
        ];
    }
}
