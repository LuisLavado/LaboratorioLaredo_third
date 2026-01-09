<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte Detallado de Categorías - Laboratorio Clínico Laredo' }}</title>
    <meta name="description" content="Reporte Detallado de Categorías del Laboratorio Clínico Laredo">
    <meta name="author" content="Laboratorio Clínico Laredo">
    <meta name="subject" content="Reporte de Categorías de Exámenes">
    <meta name="keywords" content="laboratorio, reporte, categorías, exámenes, análisis, clínico">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 15px;
            overflow: hidden; /* Evitar scrollbars que pueden causar páginas extra */
        }
        
        html {
            overflow: hidden; /* Evitar contenido que se desborde */
        }
        
        /* Encabezado principal */
        .main-header {
            background: #4472c4;
            color: white;
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .main-header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        
        .main-header .subtitle {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .period-info {
            background: #4472c4;
            color: white;
            text-align: center;
            padding: 8px;
            margin: 20px 0;
            font-weight: bold;
            border-radius: 4px;
        }
        
        /* Título del reporte destacado */
        .report-title-block {
            background:rgb(100, 141, 211);
            color: white;
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .report-title-block h2 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        /* Resumen ejecutivo */
        .executive-summary {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .summary-title {
            background: #28a745;
            color: white;
            text-align: center;
            padding: 10px;
            margin: -20px -20px 15px -20px;
            border-radius: 6px 6px 0 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .stat-label {
            font-weight: bold;
            color: #495057;
        }
        
        .stat-value {
            color: #2563eb;
            font-weight: bold;
        }
        
        /* Secciones */
        .section {
            margin: 25px 0;
            break-inside: avoid;
        }
        
        .section-header {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        
        /* Tablas */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        
        .data-table th {
            background: #e9ecef;
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 5px 8px;
            vertical-align: top;
        }
        
        .data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        /* Categoría Item */
        .category-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        
        .category-name {
            font-weight: bold;
            color: #2563eb;
            font-size: 12px;
        }
        
        .category-count {
            font-weight: bold;
            color: #28a745;
        }
        
        /* Información del reporte */
        .report-info {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            font-size: 10px;
        }
        
        .report-info-title {
            font-weight: bold;
            color: #004085;
            margin-bottom: 8px;
        }
        
        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #6c757d; }
        
        /* Evitar saltos de página */
        .no-break { 
            page-break-inside: avoid; 
            break-inside: avoid;
        }
        
        /* Evitar páginas vacías */
        .report-info {
            page-break-after: avoid;
            break-after: avoid;
        }
        
        /* Asegurar que el último elemento no genere página extra */
        body > :last-child {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        
        /* Gráfico de barras simple */
        .bar-chart {
            width: 100%;
            margin: 20px 0;
        }
        
        .bar-container {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .bar-label {
            width: 30%;
            font-size: 10px;
            padding-right: 10px;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .bar-wrapper {
            width: 60%;
            background: #e9ecef;
            height: 20px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .bar {
            height: 100%;
            background: #4472c4;
        }
        
        .bar-value {
            width: 10%;
            font-size: 10px;
            padding-left: 10px;
            font-weight: bold;
        }
        
        @media print {
            body { 
                font-size: 10px; 
                padding: 10px;
                overflow: hidden;
            }
            
            html {
                overflow: hidden;
            }
            
            .main-header { 
                margin-bottom: 15px; 
            }
            
            .period-info { 
                margin: 15px 0; 
            }
            
            /* Evitar páginas vacías al final */
            @page {
                margin: 10mm;
                size: A4;
            }
            
            /* Ocultar elementos que puedan causar páginas extra */
            script, style {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Encabezado Principal -->
    <div class="main-header">
        <h1>LABORATORIO CLÍNICO LAREDO</h1>
        <div class="subtitle">Sistema de Reportes</div>
    </div>
    
    <!-- Título del Reporte Destacado -->
    <div class="report-title-block">
        <h2>{{ $title ?? 'REPORTE DETALLADO DE CATEGORÍAS' }}</h2>
    </div>
    
    <!-- Información del Período -->
    <div class="period-info">
        Período: {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}
        <br>
        Generado el: {{ now()->format('d/m/Y H:i:s') }}
        @if(isset($generatedBy))
        <br>
        Generado por: {{ $generatedBy }}
        @endif
    </div>
    
    <!-- Resumen Ejecutivo -->
    <div class="executive-summary no-break">
        <div class="summary-title">RESUMEN EJECUTIVO</div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total de Solicitudes:</span>
                <span class="stat-value">{{ number_format($totalRequests ?? 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de Pacientes:</span>
                <span class="stat-value">{{ number_format($totalPatients ?? 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de Exámenes:</span>
                <span class="stat-value">{{ number_format($totalExams ?? 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de Categorías:</span>
                <span class="stat-value">
                    {{ isset($categoryStats) ? number_format(count($categoryStats)) : '0' }}
                </span>
            </div>
        </div>
    </div>
    
    <!-- Distribución de Categorías -->
    @if(isset($categoryStats) && count($categoryStats) > 0)
    <div class="section no-break">
        <div class="section-header">DISTRIBUCIÓN POR CATEGORÍAS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 45%;">Categoría</th>
                    <th style="width: 15%;">Cantidad</th>
                    <th style="width: 15%;">Porcentaje</th>
                    <th style="width: 15%;">Exámenes</th>
                </tr>
            </thead>
            <tbody>
                @php $totalCategories = 0; @endphp
                @foreach($categoryStats as $index => $category)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $category->name ?? $category->nombre ?? 'Sin nombre' }}</td>
                    <td class="text-right font-bold">{{ number_format($category->count ?? 0) }}</td>
                    <td class="text-center">{{ number_format($category->percentage ?? 0, 1) }}%</td>
                    <td class="text-center">
                        @if(isset($topExamsByCategory[$category->id ?? 0]))
                            {{ count($topExamsByCategory[$category->id ?? 0]) }}
                        @else
                            0
                        @endif
                    </td>
                </tr>
                @php $totalCategories += $category->count ?? 0; @endphp
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right font-bold">Total:</td>
                    <td class="text-right font-bold">{{ number_format($totalCategories) }}</td>
                    <td class="text-center">100%</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Visualización gráfica de categorías (Top 10) -->
    <div class="section no-break">
        <div class="section-header">DISTRIBUCIÓN GRÁFICA DE CATEGORÍAS (TOP 10)</div>
        
        <div class="bar-chart">
            @php
                $topCategories = collect($categoryStats)->sortByDesc('count')->take(10);
                $maxCount = $topCategories->max('count') ?? 1;
            @endphp
            
            @foreach($topCategories as $category)
                <div class="bar-container">
                    <div class="bar-label">{{ $category->name ?? $category->nombre ?? 'Sin nombre' }}</div>
                    <div class="bar-wrapper">
                        <div class="bar" style="width: {{ ($category->count / $maxCount) * 100 }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($category->count ?? 0) }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Exámenes por categoría -->
    @if(isset($topExamsByCategory) && count($topExamsByCategory) > 0)
    <div class="section">
        <div class="section-header">EXÁMENES POR CATEGORÍA</div>
        
        @foreach($topExamsByCategory as $categoryId => $exams)
            @if(count($exams) > 0)
            <div class="category-item no-break">
                <div class="category-header">
                    <span class="category-name">{{ $exams[0]->category ?? $exams[0]->categoria ?? 'Categoría ' . $categoryId }}</span>
                    <span class="category-count">{{ count($exams) }} exámenes</span>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">#</th>
                            <th style="width: 60%;">Examen</th>
                            <th style="width: 15%;">Cantidad</th>
                            <th style="width: 15%;">% de Categoría</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php 
                            $totalExamsInCategory = collect($exams)->sum('count');
                        @endphp
                        
                        @foreach($exams as $index => $exam)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $exam->name ?? $exam->nombre ?? 'Sin nombre' }}</td>
                            <td class="text-right font-bold">{{ number_format($exam->count ?? 0) }}</td>
                            <td class="text-center">
                                @if($totalExamsInCategory > 0)
                                    {{ number_format((($exam->count ?? 0) / $totalExamsInCategory) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        @endforeach
    </div>
    @endif
    
    <!-- Información del reporte -->
    <div class="report-info">
        <div class="report-info-title">Información del Reporte</div>
        <p>Este reporte muestra un análisis detallado de las categorías de exámenes realizados en el período seleccionado.</p>
        <p>Incluye la distribución de exámenes por categoría, así como un desglose de los exámenes más solicitados en cada categoría.</p>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
