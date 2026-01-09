<?php

namespace App\Exports\Services;

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
use App\Exports\Services\ServiceSheetHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ServicesByCategorySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    use ServiceSheetHelper;

    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    public function title(): string
    {
        return 'Top Exámenes y Servicios';
    }

    public function headings(): array
    {
        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['REPORTE DE EXÁMENES POR FRECUENCIA Y SERVICIOS'],
            ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
        ];
    }

public function array(): array
{
    $rows = [];

    $topExamsByService = $this->data['topExamsByService'] ?? [];
    $serviceStats = $this->data['serviceStats'] ?? [];

    $resumen = [];
    $totalExamenes = 0;

    foreach ($topExamsByService as $serviceId => $exams) {
        foreach ($exams as $exam) {
            $examData = $this->extractExamData($exam);
            $examName = $this->getProperty($examData, 'name', 'Sin nombre');
            $examCategory = $this->getProperty($examData, 'category', 'Sin categoría');
            $examCount = $this->getProperty($examData, 'count', 0);

            $key = $examName . '|' . $examCategory;

            if (!isset($resumen[$key])) {
                $resumen[$key] = 0;
            }

            $resumen[$key] += $examCount;
            $totalExamenes += $examCount;
        }
    }

    // Encabezados y título
   
    $rows[] = [];
    $rows[] = ['Examen', 'Categoría', 'Cantidad', '% del Examen', '% del Total'];

    foreach ($resumen as $key => $cantidad) {
        [$examName, $examCategory] = explode('|', $key);
        $porcentajeExamen = 100; // Ya está consolidado por examen
        $porcentajeTotal = $totalExamenes > 0 ? round(($cantidad / $totalExamenes) * 100, 1) : 0;

        $rows[] = [

            $examName,
            $examCategory,
            $cantidad,
            $porcentajeExamen . '%',
            $porcentajeTotal . '%',
        ];
    }

    if (count($rows) <= 5) {
        $rows[] = [];
        $rows[] = ['⚠️ No se encontraron exámenes asociados a servicios en el período.'];
    }

    return $rows;
}



    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1f4e79']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 16]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2f5f8f']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 14]
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472c4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            4 => [
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
            'A' => 35, // Servicio
            'B' => 35, // Examen/Descripción
            'C' => 25, // Categoría  
            'D' => 15  // Cantidad
        ];
    }

    /**
     * Función helper para acceder a propiedades de manera segura
     */
    private function getProperty($data, $property, $default = null)
    {
        // Si es un objeto
        if (is_object($data)) {
            return isset($data->$property) ? $data->$property : $default;
        }
        
        // Si es un array
        if (is_array($data)) {
            return isset($data[$property]) ? $data[$property] : $default;
        }
        
        return $default;
    }

    /**
     * Extraer datos del examen manejando la estructura stdClass
     */
    private function extractExamData($exam)
    {
        $examData = $exam;
        
        // Los datos vienen como array con clave "stdClass"
        if (is_array($exam)) {
            // Verificar si tiene la clave stdClass y extraer el objeto
            if (isset($exam['stdClass'])) {
                $examData = $exam['stdClass'];
            } else {
                // Si es un array normal, intentar usar el primer elemento
                $keys = array_keys($exam);
                if (!empty($keys)) {
                    $examData = $exam[$keys[0]];
                }
            }
        }
        
        return $examData;
    }

    /**
     * Buscar el nombre del servicio por ID
     */
    private function findServiceName($serviceId, $serviceStats)
    {
        $serviceName = 'Servicio ID: ' . $serviceId;
        
        // Log para debug
        \Log::info("Buscando nombre del servicio", [
            'serviceId' => $serviceId,
            'serviceStats_count' => count($serviceStats),
            'serviceStats_structure' => !empty($serviceStats) ? 
                (is_object($serviceStats[0]) ? get_object_vars($serviceStats[0]) : $serviceStats[0]) : 'empty'
        ]);
        
        foreach ($serviceStats as $service) {
            $serviceId_found = $this->getProperty($service, 'id', null);
            
            \Log::info("Comparando servicio", [
                'serviceId_found' => $serviceId_found,
                'serviceId_target' => $serviceId,
                'service_name' => $this->getProperty($service, 'name', 'no_name'),
                'match' => $serviceId_found == $serviceId
            ]);
            
            if ($serviceId_found == $serviceId) {
                $serviceName = $this->getProperty($service, 'name', $serviceName);
                break;
            }
        }
        
        return $serviceName;
    }
}
