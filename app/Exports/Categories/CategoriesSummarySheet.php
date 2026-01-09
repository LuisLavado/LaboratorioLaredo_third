<?php

namespace App\Exports\Categories;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CategoriesSummarySheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $data;
    protected $startDate;
    protected $endDate;
    protected $currentRow = 1;

    /**
     * Constructor
     */
    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Resumen Ejecutivo';
    }

    /**
     * Ancho de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 20,
        ];
    }

    /**
     * Cabeceras
     */
    public function headings(): array
    {
        return [];  // Las cabeceras serán parte del contenido para permitir filas de título
    }

    /**
     * Datos de la hoja
     */
    public function collection()
    {
        $collection = new Collection();

        // Título principal
        $collection->push(['REPORTE DE CATEGORÍAS DE EXÁMENES', '', '']);
        $collection->push(['Laboratorio Clínico Laredo', '', '']);
        $collection->push(['', '', '']);

        // Período del reporte
        $collection->push(['Período:', $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y'), '']);
        $collection->push(['Generado:', Carbon::now()->format('d/m/Y H:i:s'), '']);
        $collection->push(['', '', '']);

        // Sección de resumen
        $collection->push(['RESUMEN EJECUTIVO', '', '']);
        $collection->push(['', '', '']);

        // Estadísticas principales
        $collection->push(['Total de Categorías:', $this->data['categoryStats'] ? count($this->data['categoryStats']) : 0, '']);
        $collection->push(['Total de Solicitudes:', $this->data['totalRequests'] ?? 0, '']);
        $collection->push(['Total de Pacientes:', $this->data['totalPatients'] ?? 0, '']);
        $collection->push(['Total de Exámenes:', $this->data['totalExams'] ?? 0, '']);
        $collection->push(['', '', '']);

        // Principales categorías (Top 5)
        $collection->push(['PRINCIPALES CATEGORÍAS', '', '']);
        $collection->push(['Categoría', 'Cantidad', 'Porcentaje']);

        // Verificar si hay datos de categorías y obtener las top 5
        if (!empty($this->data['categoryStats'])) {
            $topCategories = collect($this->data['categoryStats'])
                ->sortByDesc('count')
                ->take(5);

            foreach ($topCategories as $category) {
                $collection->push([
                    $category->name ?? 'Sin nombre', 
                    $category->count ?? 0,
                    number_format(($category->percentage ?? 0), 1) . '%'
                ]);
            }
        } else {
            $collection->push(['No hay datos disponibles', '', '']);
        }

        return $collection;
    }

    /**
     * Estilos de la hoja
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Estilo por defecto para toda la hoja
        $sheet->getStyle('A1:C'.$lastRow)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
            ],
        ]);
        
        // Título principal
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 16, 
                'color' => ['rgb' => 'FFFFFF']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f4e79']
            ]
        ]);
        
        // Nombre del laboratorio
        $sheet->getStyle('A2:C2')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 12, 
                'color' => ['rgb' => 'FFFFFF']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472c4']
            ]
        ]);
        
        // Período y generación
        $sheet->getStyle('A4:C5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F4FD']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        $sheet->getStyle('A4:A5')->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
        
        // Título de resumen ejecutivo
        $sheet->getStyle('A7:C7')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        
        // Estadísticas principales
        $sheet->getStyle('A9:C12')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        $sheet->getStyle('A9:A12')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
        
        // Título de principales categorías
        $sheet->getStyle('A14:C14')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        
        // Cabeceras de la tabla
        $sheet->getStyle('A15:C15')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        
        // Datos de la tabla
        if (!empty($this->data['categoryStats'])) {
            $topCategoriesCount = min(5, count($this->data['categoryStats']));
            if ($topCategoriesCount > 0) {
                // Filas con datos
                $sheet->getStyle('A16:C'.(15+$topCategoriesCount))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // Aplicar colores alternados a las filas
                for ($i = 16; $i <= (15+$topCategoriesCount); $i++) {
                    if (($i - 16) % 2 == 0) {
                        $sheet->getStyle('A'.$i.':C'.$i)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8F9FA']
                            ]
                        ]);
                    }
                }
            }
        }
        
        // Unir celdas para títulos
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A7:C7');
        $sheet->mergeCells('A14:C14');
        
        return $sheet;
    }

    /**
     * Eventos de la hoja
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Alinear valores numéricos a la derecha
                $sheet->getStyle('B9:B12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('B16:C50')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Ajustar ancho de columnas automáticamente
                $sheet->calculateColumnWidths();
                
                // Establecer altura de filas de títulos
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(25);
                $sheet->getRowDimension(7)->setRowHeight(25);
                $sheet->getRowDimension(14)->setRowHeight(25);
                
                // Proteger la hoja (solo lectura)
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setSort(true);
                $sheet->getProtection()->setInsertRows(true);
                $sheet->getProtection()->setFormatCells(true);
            },
        ];
    }
}
