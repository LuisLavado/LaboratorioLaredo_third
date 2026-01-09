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

class CategoryExamsSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $examsByCategory;
    protected $startDate;
    protected $endDate;
    protected $filteredExamsByCategory;
    protected $totalExamsFiltered;
    protected $totalCategoriesFiltered;

    /**
     * Constructor
     */
    public function __construct($examsByCategory, $startDate, $endDate)
    {
        $this->examsByCategory = $examsByCategory;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        // Filtrar categorías que tienen exámenes con solicitudes
        $this->filteredExamsByCategory = $this->filterCategoriesWithExams($examsByCategory);

        // Calcular estadísticas
        $this->totalExamsFiltered = $this->calculateTotalExams();
        $this->totalCategoriesFiltered = count($this->filteredExamsByCategory);
    }

    /**
     * Filtrar categorías que tienen exámenes con solicitudes
     */
    private function filterCategoriesWithExams($examsByCategory)
    {
        $filtered = [];

        foreach ($examsByCategory as $categoryId => $exams) {
            // Solo incluir categorías que tienen exámenes con count > 0
            $examsWithRequests = collect($exams)->filter(function($exam) {
                return isset($exam->count) && $exam->count > 0;
            });

            // Si hay exámenes con solicitudes, incluir la categoría
            if ($examsWithRequests->isNotEmpty()) {
                $filtered[$categoryId] = $examsWithRequests->toArray();
            }
        }

        \Log::info('CategoryExamsSheet - Filtrado de categorías', [
            'categorias_originales' => count($examsByCategory),
            'categorias_filtradas' => count($filtered),
            'categorias_con_examenes' => array_keys($filtered)
        ]);

        return $filtered;
    }

    /**
     * Calcular el total de exámenes con solicitudes
     */
    private function calculateTotalExams()
    {
        $total = 0;
        foreach ($this->filteredExamsByCategory as $exams) {
            foreach ($exams as $exam) {
                $total += $exam->count ?? 0;
            }
        }
        return $total;
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Exámenes por Categoría';
    }

    /**
     * Ancho de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 40,
            'C' => 15,
            'D' => 20,
            'E' => 15,
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

        // Título principal con mejor formato
        $collection->push(['📊 EXÁMENES POR CATEGORÍA', '', '', '', '']);
        $collection->push(['(Solo categorías con solicitudes)', '', '', '', '']);
        $collection->push(['', '', '', '', '']);
        $collection->push(['📅 Período:', $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y'), '', '', '']);
        $collection->push(['📈 Categorías activas:', $this->totalCategoriesFiltered . ' de ' . count($this->examsByCategory), '', '', '']);
        $collection->push(['🔬 Total exámenes:', $this->totalExamsFiltered, '', '', '']);
        $collection->push(['', '', '', '', '']);
        $collection->push(['', '', '', '', '']);

        // Si no hay datos filtrados, mostrar mensaje
        if (empty($this->filteredExamsByCategory)) {
            $collection->push(['', '📋 No hay exámenes con solicitudes en el período seleccionado', '', '', '']);
            $collection->push(['', 'Todas las categorías están vacías (sin solicitudes)', '', '', '']);
            $collection->push(['', 'Intenta ampliar el rango de fechas o verificar los filtros aplicados', '', '', '']);
            return $collection;
        }

        $currentRow = 9; // Ajustar por las nuevas filas de título

        // Iterar por cada categoría filtrada (solo las que tienen exámenes con solicitudes)
        foreach ($this->filteredExamsByCategory as $categoryId => $exams) {
            // Los exams ya están filtrados para tener count > 0

            // Obtener nombre de la categoría
            $categoryName = 'Categoría ' . $categoryId;
            if (isset($exams[0]->category)) {
                $categoryName = $exams[0]->category;
            } elseif (isset($exams[0]->categoria)) {
                $categoryName = $exams[0]->categoria;
            }

            // Separador antes de cada categoría
            $collection->push(['', '', '', '', '']);
            $currentRow++;

            // Título de la categoría con mejor formato
            $collection->push(['🏷️ ' . strtoupper($categoryName), '', '', '', '']);
            $currentRow++;

            // Línea separadora
            $collection->push(['', '', '', '', '']);
            $currentRow++;

            // Cabeceras de tabla
            $collection->push(['#', 'Examen', 'Código', 'Cantidad', '% Categoría']);
            $currentRow++;

            // Calcular el total de exámenes en la categoría
            $totalExamsInCategory = collect($exams)->sum('count');

            // Datos de exámenes
            $index = 1;
            foreach ($exams as $exam) {
                $percentage = $totalExamsInCategory > 0 ? ($exam->count / $totalExamsInCategory) * 100 : 0;
                
                $collection->push([
                    $index,
                    $exam->name ?? $exam->nombre ?? 'Sin nombre',
                    $exam->code ?? $exam->codigo ?? '',
                    $exam->count ?? 0,
                    number_format($percentage, 1) . '%'
                ]);
                
                $index++;
                $currentRow++;
            }

            // Separador entre categorías
            $collection->push(['', '', '', '', '']);
            $currentRow++;
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
        $sheet->getStyle('A1:E'.$lastRow)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
            ],
        ]);
        
        // Título principal
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 18,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f4e79'],
            ],
        ]);

        // Subtítulo
        $sheet->getStyle('A2:E2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => '666666'],
                'italic' => true,
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Información del período (fila 4)
        $sheet->getStyle('A4:E6')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '333333'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        
        // Aplicar estilos por cada categoría filtrada
        if (!empty($this->filteredExamsByCategory)) {
            $currentRow = 9; // Ajustar por las nuevas filas de título

            foreach ($this->filteredExamsByCategory as $categoryId => $exams) {
                // Los exams ya están filtrados

                // Saltar fila separadora
                $currentRow++;

                // Título de la categoría
                $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->applyFromArray([
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
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $currentRow++;

                // Saltar línea separadora
                $currentRow++;
                
                // Cabeceras de tabla
                $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $currentRow++;
                
                // Datos de exámenes
                $dataLastRow = $currentRow + count($exams) - 1;
                if ($dataLastRow >= $currentRow) {
                    // Aplicar bordes a todas las filas de datos
                    $sheet->getStyle('A'.$currentRow.':E'.$dataLastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                    
                    // Aplicar colores alternados a las filas
                    for ($i = $currentRow; $i <= $dataLastRow; $i++) {
                        if (($i - $currentRow) % 2 == 0) {
                            $sheet->getStyle('A'.$i.':E'.$i)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8F9FA']
                                ]
                            ]);
                        }
                    }
                    
                    // Resaltar columna de frecuencia
                    for ($i = $currentRow; $i <= $dataLastRow; $i++) {
                        $frecuencia = (int) $sheet->getCell('D'.$i)->getValue();
                        
                        if ($frecuencia > 0) {
                            // Color basado en la frecuencia
                            $color = '92D050'; // Verde (frecuencia alta)
                            if ($frecuencia < 5) {
                                $color = 'FF9999'; // Rojo (frecuencia baja)
                            } elseif ($frecuencia < 10) {
                                $color = 'FFCC99'; // Naranja (frecuencia media-baja)
                            } elseif ($frecuencia < 20) {
                                $color = 'FFFF99'; // Amarillo (frecuencia media)
                            }
                            
                            $sheet->getStyle('D'.$i)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => $color]
                                ]
                            ]);
                        }
                    }
                }
                
                $currentRow = $dataLastRow + 1;
                
                // Saltar la fila de separación
                $currentRow++;
            }
        }
        
        // Mergear celdas para títulos principales
        $sheet->mergeCells('A1:E1');  // Título principal
        $sheet->mergeCells('A2:E2');  // Subtítulo
        
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
                
                // Establecer altura de filas de títulos principales
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(25);
                
                // Aplicar estilos por cada categoría filtrada
                if (!empty($this->filteredExamsByCategory)) {
                    $currentRow = 9; // Ajustar por las nuevas filas de título

                    foreach ($this->filteredExamsByCategory as $categoryId => $exams) {
                        // Los exams ya están filtrados

                        // Saltar fila separadora
                        $currentRow++;

                        // Establecer altura de filas de títulos de categoría
                        $sheet->getRowDimension($currentRow)->setRowHeight(30);

                        // Centrar título de categoría
                        $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->mergeCells('A'.$currentRow.':E'.$currentRow);
                        $currentRow++;

                        // Saltar línea separadora
                        $currentRow++;
                        
                        // Centrar cabeceras
                        $sheet->getRowDimension($currentRow)->setRowHeight(22);
                        $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $currentRow++;
                        
                        // Centrar columna de índices y alinear columnas numéricas a la derecha
                        $dataLastRow = $currentRow + count($exams) - 1;
                        $sheet->getStyle('A'.$currentRow.':A'.$dataLastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('C'.$currentRow.':E'.$dataLastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        
                        // Añadir filtros a la tabla de esta categoría
                        $sheet->setAutoFilter('A'.($currentRow-1).':E'.$dataLastRow);
                        
                        $currentRow = $dataLastRow + 2; // +2 para saltar la fila vacía
                    }
                }
                
                // Ajustar ancho de columnas automáticamente
                $sheet->calculateColumnWidths();

                // Hoja sin protección para permitir edición
                $sheet->getProtection()->setSheet(false);
            },
        ];
    }
}
