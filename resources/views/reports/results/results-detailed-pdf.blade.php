<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?: 'Reporte Detallado de Resultados - Laboratorio Clínico Laredo' }}</title>
    <meta name="description" content="Reporte Detallado de Resultados del Laboratorio Clínico Laredo">
    <meta name="author" content="Laboratorio Clínico Laredo">
    <meta name="subject" content="Reporte de Resultados de Exámenes">
    <meta name="keywords" content="laboratorio, resultados, análisis, clínico, exámenes">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 15px;
            overflow: hidden;
        }
        
        html {
            overflow: hidden;
        }
        
        /* Encabezado principal */
        .main-header {
            background: #dc3545;
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
            background: #dc3545;
            color: white;
            text-align: center;
            padding: 8px;
            margin: 20px 0;
            font-weight: bold;
            border-radius: 4px;
        }
        
        /* Título del reporte destacado */
        .report-title-block {
            background: #dc3545;
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
            background: #dc3545;
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
        
        .stats-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
            color: #dc3545;
            font-weight: bold;
        }
        
        /* Estados específicos */
        .pending-stat .stat-value {
            color: #ffc107;
        }
        
        .process-stat .stat-value {
            color: #007bff;
        }
        
        .completed-stat .stat-value {
            color: #28a745;
        }
        
        /* Secciones */
        .section {
            margin: 25px 0;
            break-inside: avoid;
        }
        
        .section-header {
            background: #dc3545;
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
        
        /* Estados en tablas */
        .status-pending {
            background-color: #fff3cd !important;
            color: #856404;
        }
        
        .status-process {
            background-color: #d1ecf1 !important;
            color: #0c5460;
        }
        
        .status-completed {
            background-color: #d4edda !important;
            color: #155724;
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
        
        /* Forzar saltos de página */
        .page-break {
            page-break-before: always;
            break-before: page;
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
            
            @page {
                margin: 10mm;
                size: A4;
            }
            
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
        <div class="subtitle">Sistema de Reportes - Análisis de Resultados</div>
    </div>
    
    <!-- Título del Reporte Destacado -->
    <div class="report-title-block">
        <h2>{{ $title ?: 'REPORTE DETALLADO DE RESULTADOS' }}</h2>
    </div>
    
    <!-- Información del Período -->
    <div class="period-info">
        Período: {{ $startDate ?: 'N/A' }} al {{ $endDate ?: 'N/A' }}
        <br>
        Generado el: {{ now()->format('d/m/Y H:i:s') }}
    </div>
    
    <!-- Resumen Ejecutivo -->
    <div class="executive-summary no-break">
        <div class="summary-title">RESUMEN EJECUTIVO DE RESULTADOS</div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total de Solicitudes:</span>
                <span class="stat-value">{{ number_format($totalRequests ?: 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de Pacientes:</span>
                <span class="stat-value">{{ number_format($totalPatients ?: 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de Exámenes:</span>
                <span class="stat-value">{{ number_format($totalExams ?: 0) }}</span>
            </div>

        </div>
        
        <!-- Estado de Resultados -->
        <div class="stats-grid-3">
            <div class="stat-item pending-stat">
                <span class="stat-label">Pendientes:</span>
                <span class="stat-value">{{ number_format($pendingCount ?: 0) }}</span>
            </div>
            <div class="stat-item process-stat">
                <span class="stat-label">En Proceso:</span>
                <span class="stat-value">{{ number_format($inProcessCount ?: 0) }}</span>
            </div>
            <div class="stat-item completed-stat">
                <span class="stat-label">Completados:</span>
                <span class="stat-value">{{ number_format($completedCount ?: 0) }}</span>
            </div>
        </div>
        
        @php
            $totalProcessed = ($pendingCount ?: 0) + ($inProcessCount ?: 0) + ($completedCount ?: 0);
            $completionRate = $totalProcessed > 0 ? round((($completedCount ?: 0) / $totalProcessed) * 100, 1) : 0;
            $pendingRate = $totalProcessed > 0 ? round((($pendingCount ?: 0) / $totalProcessed) * 100, 1) : 0;
        @endphp
        
     
    </div>
    
    <!-- Distribución de Estados -->
    @if(isset($statusCounts) && count($statusCounts) > 0)
    <div class="section no-break">
        <div class="section-header">DISTRIBUCIÓN DETALLADA DE ESTADOS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Estado</th>
                    <th style="width: 20%;">Cantidad</th>
                    <th style="width: 20%;">Porcentaje</th>
                    <th style="width: 20%;">Indicador</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statusCounts as $status => $count)
                @php
                    $total = array_sum($statusCounts);
                    $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                    $statusClass = '';
                    $statusName = '';
                    switch(strtolower($status)) {
                        case 'pendiente':
                            $statusClass = 'status-pending';
                            $statusName = 'Pendiente';
                            break;
                        case 'en_proceso':
                            $statusClass = 'status-process';
                            $statusName = 'En Proceso';
                            break;
                        case 'completado':
                            $statusClass = 'status-completed';
                            $statusName = 'Completado';
                            break;
                        default:
                            $statusName = ucfirst(str_replace('_', ' ', $status));
                    }
                @endphp
                <tr class="{{ $statusClass }}">
                    <td class="font-bold">{{ $statusName }}</td>
                    <td class="text-center font-bold">{{ number_format($count) }}</td>
                    <td class="text-center">{{ $percentage }}%</td>
                    <td class="text-center">
                        @if($percentage >= 70 && strtolower($status) === 'completado')
                            ✓ Excelente
                        @elseif($percentage >= 50 && strtolower($status) === 'completado')
                            ↗ Bueno
                        @elseif($percentage >= 30 && strtolower($status) === 'pendiente')
                            ⚠ Atención
                        @else
                            ⚪ Normal
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Tiempos de Procesamiento -->
    @if(isset($processingTimeStats) && count($processingTimeStats) > 0)
    <div class="section no-break">
        <div class="section-header">ANÁLISIS DE TIEMPOS DE PROCESAMIENTO</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Rango de Tiempo</th>
                    <th style="width: 20%;">Cantidad</th>
                    <th style="width: 20%;">Porcentaje</th>
                    <th style="width: 20%;">Eficiencia</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalProcessingStats = array_sum(array_column($processingTimeStats, 'count'));
                @endphp
                @foreach($processingTimeStats as $stat)
                @php
                    $percentage = $totalProcessingStats > 0 ? round(($stat['count'] / $totalProcessingStats) * 100, 1) : 0;
                    $efficiency = '';
                    if(strpos($stat['range'], '0-24 horas') !== false) {
                        $efficiency = '🚀 Óptimo';
                    } elseif(strpos($stat['range'], '1-3 días') !== false) {
                        $efficiency = '✓ Bueno';
                    } elseif(strpos($stat['range'], '3-7 días') !== false) {
                        $efficiency = '⚠ Regular';
                    } else {
                        $efficiency = '🔴 Lento';
                    }
                @endphp
                <tr>
                    <td class="font-bold">{{ $stat['range'] }}</td>
                    <td class="text-center">{{ number_format($stat['count']) }}</td>
                    <td class="text-center">{{ $percentage }}%</td>
                    <td class="text-center">{{ $efficiency }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Actividad Diaria de Resultados -->
    @if(isset($dailyStats) && count($dailyStats) > 0)
    <div class="section no-break">
        <div class="section-header">ACTIVIDAD DIARIA DE RESULTADOS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Fecha</th>
                    <th style="width: 15%;">Pendientes</th>
                    <th style="width: 15%;">En Proceso</th>
                    <th style="width: 15%;">Completados</th>
                    <th style="width: 15%;">Total</th>
                    <th style="width: 20%;">Eficiencia Diaria</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyStats as $day)
                @php
                    $dayPending = $day->pending ?: 0;
                    $dayInProcess = $day->in_process ?: 0;
                    $dayCompleted = $day->completed ?: 0;
                    $dayTotal = $day->count ?: ($dayPending + $dayInProcess + $dayCompleted);
                    $dayEfficiency = $dayTotal > 0 ? round(($dayCompleted / $dayTotal) * 100, 1) : 0;
                    
                    $efficiencyLabel = '';
                    if($dayEfficiency >= 80) {
                        $efficiencyLabel = '🟢 Excelente';
                    } elseif($dayEfficiency >= 60) {
                        $efficiencyLabel = '🟡 Bueno';
                    } elseif($dayEfficiency >= 40) {
                        $efficiencyLabel = '🟠 Regular';
                    } else {
                        $efficiencyLabel = '🔴 Bajo';
                    }
                @endphp
                <tr>
                    <td class="font-bold">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
                    <td class="text-center status-pending">{{ number_format($dayPending) }}</td>
                    <td class="text-center status-process">{{ number_format($dayInProcess) }}</td>
                    <td class="text-center status-completed">{{ number_format($dayCompleted) }}</td>
                    <td class="text-center font-bold">{{ number_format($dayTotal) }}</td>
                    <td class="text-center">{{ $efficiencyLabel }} ({{ $dayEfficiency }}%)</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    

    <!-- ===================== NUEVA PÁGINA: ANÁLISIS POR EXÁMENES ===================== -->
    <div class="page-break"></div>
    
    <!-- Título de la segunda página -->
    <div class="report-title-block">
        <h2>ANÁLISIS DETALLADO POR EXÁMENES</h2>
    </div>
    
    <!-- Información del Período (repetida) -->
    <div class="period-info">
        Período: {{ $startDate ?: 'N/A' }} al {{ $endDate ?: 'N/A' }}
        <br>
        Análisis de Resultados por Examen
    </div>
    
    <!-- Exámenes Más Solicitados con Estados -->
    @if(isset($examStats) && count($examStats) > 0)
    <div class="section no-break">
        <div class="section-header">EXÁMENES MÁS SOLICITADOS - ANÁLISIS DE RESULTADOS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 25%;">Examen</th>
                    <th style="width: 15%;">Categoría</th>
                    <th style="width: 10%;">Total</th>
                    <th style="width: 12%;">Completados</th>
                    <th style="width: 10%;">Pendientes</th>
                    <th style="width: 10%;">En Proceso</th>
                    <th style="width: 10%;">% Eficiencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examStats as $index => $exam)
                @php
                    $efficiency = $exam->total_count > 0 ? round(($exam->completed_count / $exam->total_count) * 100, 1) : 0;
                    $efficiencyClass = '';
                    if($efficiency >= 80) {
                        $efficiencyClass = 'status-completed';
                    } elseif($efficiency >= 60) {
                        $efficiencyClass = 'status-process';
                    } else {
                        $efficiencyClass = 'status-pending';
                    }
                @endphp
                <tr>
                    <td class="text-center font-bold">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $exam->name ?: 'Sin nombre' }}</td>
                    <td class="text-center">{{ $exam->categoria ?: 'General' }}</td>
                    <td class="text-center font-bold">{{ number_format($exam->total_count) }}</td>
                    <td class="text-center status-completed">{{ number_format($exam->completed_count) }}</td>
                    <td class="text-center status-pending">{{ number_format($exam->pending_count) }}</td>
                    <td class="text-center status-process">{{ number_format($exam->in_process_count) }}</td>
                    <td class="text-center {{ $efficiencyClass }}">{{ $efficiency }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Análisis por Categorías de Exámenes -->
    @if(isset($categoryStats) && count($categoryStats) > 0)
    <div class="section no-break">
        <div class="section-header">ANÁLISIS POR CATEGORÍAS DE EXÁMENES</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Categoría</th>
                    <th style="width: 15%;">Total Exámenes</th>
                    <th style="width: 15%;">Completados</th>
                    <th style="width: 15%;">Pendientes</th>
                    <th style="width: 15%;">En Proceso</th>
                    <th style="width: 15%;">% Eficiencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryStats as $category)
                @php
                    $catEfficiency = $category->total_count > 0 ? round(($category->completed_count / $category->total_count) * 100, 1) : 0;
                    $catEfficiencyClass = '';
                    if($catEfficiency >= 80) {
                        $catEfficiencyClass = 'status-completed';
                    } elseif($catEfficiency >= 60) {
                        $catEfficiencyClass = 'status-process';
                    } else {
                        $catEfficiencyClass = 'status-pending';
                    }
                @endphp
                <tr>
                    <td class="font-bold">{{ $category->categoria ?: 'Sin categoría' }}</td>
                    <td class="text-center font-bold">{{ number_format($category->total_count) }}</td>
                    <td class="text-center status-completed">{{ number_format($category->completed_count) }}</td>
                    <td class="text-center status-pending">{{ number_format($category->pending_count) }}</td>
                    <td class="text-center status-process">{{ number_format($category->in_process_count) }}</td>
                    <td class="text-center {{ $catEfficiencyClass }}">{{ $catEfficiency }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Distribución de Exámenes por Categoría -->
    @if(isset($examsByCategory) && count($examsByCategory) > 0)
    <div class="section no-break">
        <div class="section-header">DISTRIBUCIÓN DETALLADA POR CATEGORÍA</div>
        
        @foreach($examsByCategory as $categoryName => $categoryExams)
        <div style="margin-bottom: 20px; break-inside: avoid;">
            <h4 style="background: #f8f9fa; padding: 8px; margin: 10px 0 5px 0; border-left: 4px solid #dc3545; color: #333;">
                📂 {{ $categoryName }} ({{ count($categoryExams) }} exámenes)
            </h4>
            
            <table class="data-table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th style="width: 40%;">Examen</th>
                        <th style="width: 12%;">Total</th>
                        <th style="width: 12%;">Completados</th>
                        <th style="width: 12%;">Pendientes</th>
                        <th style="width: 12%;">En Proceso</th>
                        <th style="width: 12%;">% Eficiencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(collect($categoryExams)->take(10) as $exam)
                    @php
                        $examEfficiency = $exam->total_count > 0 ? round(($exam->completed_count / $exam->total_count) * 100, 1) : 0;
                        $examEfficiencyClass = '';
                        if($examEfficiency >= 80) {
                            $examEfficiencyClass = 'status-completed';
                        } elseif($examEfficiency >= 60) {
                            $examEfficiencyClass = 'status-process';
                        } else {
                            $examEfficiencyClass = 'status-pending';
                        }
                    @endphp
                    <tr>
                        <td>{{ $exam->name ?: 'Sin nombre' }}</td>
                        <td class="text-center font-bold">{{ number_format($exam->total_count) }}</td>
                        <td class="text-center status-completed">{{ number_format($exam->completed_count) }}</td>
                        <td class="text-center status-pending">{{ number_format($exam->pending_count) }}</td>
                        <td class="text-center status-process">{{ number_format($exam->in_process_count) }}</td>
                        <td class="text-center {{ $examEfficiencyClass }}">{{ $examEfficiency }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Resultados Detallados Individuales -->
    @if(isset($detailedResults) && count($detailedResults) > 0)
    <div class="section no-break" style="page-break-before: always;">
        <div class="section-header">RESULTADOS DETALLADOS INDIVIDUALES</div>
        <div style="font-size: 10px; color: #666; margin-bottom: 15px; text-align: center;">
            Total de resultados: {{ count($detailedResults) }} | Mostrando todos los registros del período
        </div>

        <table class="data-table" style="font-size: 8px;">
            <thead>
                <tr style="background: #dc3545; color: white;">
                    <th style="width: 8%;">Fecha</th>
                    <th style="width: 6%;">Sol.</th>
                    <th style="width: 18%;">Paciente</th>
                    <th style="width: 8%;">DNI</th>
                    <th style="width: 20%;">Examen</th>
                    <th style="width: 12%;">Campo</th>
                    <th style="width: 10%;">Resultado</th>
                    <th style="width: 10%;">Referencia</th>
                    <th style="width: 8%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detailedResults as $index => $resultado)
                @php
                    // Procesar datos con validación para evitar caracteres especiales
                    $fecha = 'Sin fecha';
                    if (!empty($resultado->fecha_resultado)) {
                        try {
                            $fecha = \Carbon\Carbon::parse($resultado->fecha_resultado)->format('d/m/Y');
                        } catch (\Exception $e) {
                            $fecha = 'Fecha inválida';
                        }
                    }

                    // Extraer datos de la estructura real (objetos anidados)
                    $pacienteNombre = 'Sin nombre';
                    if (isset($resultado->paciente)) {
                        $nombres = (string) ($resultado->paciente->nombres ?? '');
                        $apellidos = (string) ($resultado->paciente->apellidos ?? '');
                        $pacienteNombre = trim($nombres . ' ' . $apellidos);
                        if (empty($pacienteNombre)) {
                            $pacienteNombre = 'Sin nombre';
                        }
                    }

                    $pacienteDNI = isset($resultado->paciente->dni) ? (string) $resultado->paciente->dni : 'Sin DNI';

                    $examenNombre = 'Sin examen';
                    if (isset($resultado->examen->nombre)) {
                        $examenNombre = (string) $resultado->examen->nombre;
                    }

                    $campoNombre = isset($resultado->campo_nombre) ? (string) $resultado->campo_nombre : 'Resultado general';
                    $valor = isset($resultado->valor) ? (string) $resultado->valor : 'Pendiente';
                    $unidad = isset($resultado->unidad) ? (string) $resultado->unidad : '';
                    $valorReferencia = isset($resultado->valor_referencia) ? (string) $resultado->valor_referencia : 'Sin referencia';
                    $estado = isset($resultado->estado) ? (string) $resultado->estado : 'Sin estado';
                    $solicitudId = isset($resultado->solicitud_id) ? (string) $resultado->solicitud_id : 'N/A';

                    // Formatear estado con colores
                    $estadoClass = '';
                    $estadoFormateado = '';
                    switch (strtolower(trim($estado))) {
                        case 'completado':
                        case 'normal':
                            $estadoFormateado = 'COMPLETADO';
                            $estadoClass = 'color: #28a745; font-weight: bold;';
                            break;
                        case 'pendiente':
                            $estadoFormateado = 'PENDIENTE';
                            $estadoClass = 'color: #ffc107; font-weight: bold;';
                            break;
                        case 'en_proceso':
                        case 'en proceso':
                            $estadoFormateado = 'EN PROCESO';
                            $estadoClass = 'color: #007bff; font-weight: bold;';
                            break;
                        default:
                            $estadoFormateado = strtoupper($estado);
                            $estadoClass = 'color: #6c757d;';
                    }

                    // Truncar textos largos para que quepan en la tabla
                    $pacienteNombre = strlen($pacienteNombre) > 25 ? substr($pacienteNombre, 0, 22) . '...' : $pacienteNombre;
                    $examenNombre = strlen($examenNombre) > 30 ? substr($examenNombre, 0, 27) . '...' : $examenNombre;
                    $campoNombre = strlen($campoNombre) > 20 ? substr($campoNombre, 0, 17) . '...' : $campoNombre;
                    $valorReferencia = strlen($valorReferencia) > 15 ? substr($valorReferencia, 0, 12) . '...' : $valorReferencia;
                @endphp
                <tr style="{{ $index % 2 == 0 ? 'background-color: #f8f9fa;' : '' }}">
                    <td style="text-align: center; font-size: 7px;">{{ $fecha }}</td>
                    <td style="text-align: center; font-size: 7px;">{{ $solicitudId }}</td>
                    <td style="font-size: 7px;">{{ $pacienteNombre }}</td>
                    <td style="text-align: center; font-size: 7px;">{{ $pacienteDNI }}</td>
                    <td style="font-size: 7px;">{{ $examenNombre }}</td>
                    <td style="font-size: 7px;">{{ $campoNombre }}</td>
                    <td style="text-align: center; font-size: 7px; font-weight: bold;">{{ $valor }}{{ !empty($unidad) ? ' ' . $unidad : '' }}</td>
                    <td style="text-align: center; font-size: 7px;">{{ $valorReferencia }}</td>
                    <td style="text-align: center; font-size: 7px; {{ $estadoClass }}">{{ $estadoFormateado }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if(count($detailedResults) > 50)
        <div style="margin-top: 10px; font-size: 9px; color: #666; text-align: center; font-style: italic;">
            📊 Mostrando {{ count($detailedResults) }} resultados totales del período {{ $startDate }} - {{ $endDate }}
        </div>
        @endif
    </div>
    @else
    <div class="section no-break">
        <div class="section-header">RESULTADOS DETALLADOS INDIVIDUALES</div>
        <div style="text-align: center; padding: 30px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; margin: 10px 0;">
            <p style="color: #6c757d; font-style: italic; margin: 0; font-size: 12px;">
                📋 No se encontraron resultados detallados para el período especificado.
            </p>
            <p style="color: #6c757d; font-size: 10px; margin: 5px 0 0 0;">
                Período: {{ $startDate }} - {{ $endDate }}
            </p>
        </div>
    </div>
    @endif

    <!-- Recomendaciones Operativas -->
    <div class="section no-break">
        <div class="section-header">RECOMENDACIONES OPERATIVAS</div>
        
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 15px; margin: 10px 0;">
            <div style="font-weight: bold; color: #856404; margin-bottom: 10px;">💡 Sugerencias para Optimización:</div>
            <ul style="margin: 0; padding-left: 20px; color: #856404;">
                @if(($pendingCount ?: 0) > ($completedCount ?: 0))
                <li>📋 <strong>Atención:</strong> Hay más resultados pendientes que completados. Revisar flujo de trabajo.</li>
                @endif
                
                @if($completionRate < 70)
                <li>⚡ <strong>Eficiencia:</strong> La tasa de finalización es del {{ $completionRate }}%. Meta recomendada: 85%+</li>
                @endif
                
                @if(isset($processingTimeStats) && count($processingTimeStats) > 0)
                    @php
                        $fastProcessing = collect($processingTimeStats)->where('range', '0-24 horas')->first();
                        $slowProcessing = collect($processingTimeStats)->where('range', 'Más de 7 días')->first();
                    @endphp
                    @if($slowProcessing && $slowProcessing['count'] > 0)
                    <li>🕐 <strong>Tiempo:</strong> {{ $slowProcessing['count'] }} exámenes tardan más de 7 días. Revisar procesos.</li>
                    @endif
                @endif
                
                @if($completionRate >= 85)
                <li>🎯 <strong>Excelente:</strong> Tasa de finalización superior al 85%. ¡Mantener el buen rendimiento!</li>
                @endif
                
                @if(isset($examStats) && count($examStats) > 0)
                    @php
                        $lowEfficiencyExams = collect($examStats)->filter(function($exam) {
                            return $exam->total_count > 0 && (($exam->completed_count / $exam->total_count) * 100) < 60;
                        });
                        $highVolumeExams = collect($examStats)->take(3);
                    @endphp
                    
                    @if($lowEfficiencyExams->count() > 0)
                    <li>🔍 <strong>Exámenes:</strong> {{ $lowEfficiencyExams->count() }} exámenes tienen eficiencia menor al 60%. Revisar procesos específicos.</li>
                    @endif
                    
                    @if($highVolumeExams->count() > 0)
                    <li>📊 <strong>Volumen:</strong> Los 3 exámenes más solicitados representan {{ number_format($highVolumeExams->sum('total_count')) }} resultados. Priorizar estos procesos.</li>
                    @endif
                @endif
                
                @if(isset($categoryStats) && count($categoryStats) > 0)
                    @php
                        $topCategory = collect($categoryStats)->first();
                        $categoryEfficiency = $topCategory && $topCategory->total_count > 0 ? 
                            round(($topCategory->completed_count / $topCategory->total_count) * 100, 1) : 0;
                    @endphp
                    
                    @if($topCategory && $categoryEfficiency < 70)
                    <li>📂 <strong>Categoría principal:</strong> "{{ $topCategory->categoria }}" tiene {{ $categoryEfficiency }}% de eficiencia. Requiere atención especial.</li>
                    @endif
                @endif
            </ul>
        </div>
    </div>

    <!-- Información del Reporte -->
    <div class="report-info no-break">
        <div class="report-info-title">INFORMACIÓN DEL REPORTE</div>
        <div><strong>Período:</strong> {{ $startDate ?: 'N/A' }} al {{ $endDate ?: 'N/A' }}</div>
        <div><strong>Generado el:</strong> {{ now()->format('d/m/Y H:i:s') }}</div>
        <div><strong>Generado por:</strong> {{ $generatedBy ?: 'Sistema' }}</div>
        <div><strong>Tipo de reporte:</strong> Resultados Detallado</div>
        <div><strong>Total de registros procesados:</strong> {{ number_format(($totalRequests ?: 0) + ($totalPatients ?: 0) + ($totalExams ?: 0)) }}</div>
    </div>
</body>
</html>
