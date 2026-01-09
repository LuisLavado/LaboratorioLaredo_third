<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte de Exámenes Detallado - Laboratorio Clínico Laredo' }}</title>
    <meta name="description" content="Reporte Detallado de Exámenes del Laboratorio Clínico Laredo">
    <meta name="author" content="Laboratorio Clínico Laredo">
    <meta name="subject" content="Reporte de Exámenes por Categorías">
    <meta name="keywords" content="laboratorio, exámenes, análisis, categorías, clínico">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        
        html {
            overflow: hidden;
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
            background: rgb(100, 141, 211);
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
        
        /* Secciones por categoría */
        .category-section {
            margin: 20px 0;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .category-header {
            background: linear-gradient(135deg, #6f42c1 0%, #8b5a9e 100%);
            color: white;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 13px;
        }
        
        .category-stats {
            background: #f8f9fa;
            padding: 15px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .category-stat {
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        
        .category-stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #6f42c1;
        }
        
        .category-stat-label {
            font-size: 10px;
            color: #6c757d;
            margin-top: 2px;
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
        
        /* Indicadores de popularidad */
        .popularity-indicator {
            display: inline-block;
            width: 50px;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        
        .popularity-bar {
            height: 100%;
            background-color: #28a745;
            border-radius: 4px;
            position: absolute;
            top: 0;
            left: 0;
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
        
        /* Asegurar que el último elemento no genere página extra */
        body > :last-child {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        
        @media print {
            body { 
                font-size: 10px; 
                padding: 10px;
            }
            
            .main-header { 
                margin-bottom: 15px; 
            }
            
            .period-info { 
                margin: 15px 0; 
            }
            
            @page {
                margin: 10mm;
                size: A4;
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
        <h2>{{ $title ?? 'REPORTE DE EXÁMENES DETALLADO' }}</h2>
    </div>
    
    <!-- Información del Período -->
    <div class="period-info">
        Período: {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}
        <br>
        Generado el: {{ now()->format('d/m/Y H:i:s') }}
    </div>
    
    <!-- Resumen Ejecutivo -->
    <div class="executive-summary no-break">
        <div class="summary-title">RESUMEN</div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total de Exámenes:</span>
                <span class="stat-value">{{ number_format($totalExams ?? 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Categorías con solicitudes:</span>
                <span class="stat-value">{{ number_format($totalCategories ?? 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Exámenes con solicitudes:</span>
                <span class="stat-value">{{ number_format($uniqueExams ?? 0) }}</span>
            </div>
          
        </div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Examen más Solicitado:</span>
                <span class="stat-value">{{ $mostRequestedExam ?? 'N/A' }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Categoría Líder:</span>
                <span class="stat-value">{{ $topCategory ?? 'N/A' }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Solicitudes Promedio:</span>
                <span class="stat-value">
                    {{ $uniqueExams > 0 ? number_format(($totalExams ?? 0) / $uniqueExams, 1) : '0' }}
                </span>
            </div>
            <div class="stat-item">
             
            </div>
        </div>
    </div>
    
    <!-- Exámenes Más Solicitados -->
    @if(isset($examStats) && count($examStats) > 0)
    <div class="section no-break" style="page-break-before: always;">
        <div class="section-header">EXÁMENES MÁS SOLICITADOS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 45%;">Examen</th>
                    <th style="width: 15%;">Cantidad</th>
                    <th style="width: 15%;">Categoría</th>
                    <th style="width: 17%;">Popularidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach(collect($examStats) as $index => $exam)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $exam->name ?? $exam->nombre ?? 'Sin nombre' }}</td>
                    <td class="text-right font-bold">{{ number_format($exam->count ?? 0) }}</td>
                    <td class="text-center">{{ $exam->categoria ?? 'General' }}</td>
                    <td class="text-center">
                        @php
                            $maxCount = collect($examStats)->max('count') ?? 1;
                            $percentage = (($exam->count ?? 0) / $maxCount) * 100;
                            $width = $percentage;
                        @endphp
                        <div class="popularity-indicator">
                            <div class="popularity-bar" style="width: {{ $width }}px; max-width: 50px;"></div>
                        </div>
                        <small>{{ number_format($percentage, 1) }}%</small>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
  
    <!-- Estadísticas de Distribución -->
    @if(isset($categoryStats) && count($categoryStats) > 0)
    <div class="section no-break" style="page-break-before: always;">
        <div class="section-header">DISTRIBUCIÓN POR CATEGORÍAS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 35%;">Categoría</th>
                    <th style="width: 20%;">Total Solicitudes</th>
                    <th style="width: 15%;">Tipos Únicos</th>
                    <th style="width: 12%;">% del Total</th>
                    <th style="width: 10%;">Promedio</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandTotal = collect($categoryStats)->sum('total_count');
                @endphp
                @foreach(collect($categoryStats)->sortByDesc('total_count') as $index => $category)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $category->categoria ?? 'Sin categoría' }}</td>
                    <td class="text-right font-bold">{{ number_format($category->total_count ?? 0) }}</td>
                    <td class="text-center">{{ number_format($category->unique_count ?? 0) }}</td>
                    <td class="text-center">
                        {{ $grandTotal > 0 ? number_format((($category->total_count ?? 0) / $grandTotal) * 100, 1) : 0 }}%
                    </td>
                    <td class="text-center">
                        {{ ($category->unique_count ?? 0) > 0 ? number_format(($category->total_count ?? 0) / ($category->unique_count ?? 0), 1) : '0' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Información del Reporte -->
    <div class="report-info no-break">
        <div class="report-info-title">INFORMACIÓN DEL REPORTE</div>
        <div><strong>Período:</strong> {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}</div>
        <div><strong>Generado el:</strong> {{ now()->format('d/m/Y H:i:s') }}</div>
        <div><strong>Generado por:</strong> {{ $generatedBy ?? 'Sistema' }}</div>
        <div><strong>Tipo de reporte:</strong> {{'Exámenes Detallado'}}</div>
        <div><strong>Total de registros procesados:</strong> {{ number_format($totalExams ?? 0) }}</div>
    </div>
</body>
</html>
