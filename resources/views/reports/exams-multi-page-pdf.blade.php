<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Exámenes - Laboratorio Laredo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            line-height: 1.4;
            color: #1a1a1a;
            background: #ffffff;
            font-size: 11px;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15px;
            background: white;
        }

        .page-break {
            page-break-before: always;
        }

        /* Estilos para texto oscuro y legible */
        .text-dark { color: #1a1a1a; }
        .text-header { color: #2c3e50; }
        .text-muted { color: #4a4a4a; }
        
        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e0e0e0;
            color: #1a1a1a;
            font-size: 10px;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .section-header {
            color: #1a1a1a;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 8px;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            padding: 15px;
            background: #f0f0f0;
            border-left: 4px solid #2c3e50;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
        }
        
        .stat-label {
            font-size: 12px;
            color: #4a4a4a;
            margin-top: 4px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            color: white;
        }

        .badge-high { background: #e74c3c; }
        .badge-medium { background: #f39c12; }
        .badge-low { background: #27ae60; }
        .badge-critical { background: #8e44ad; }

        .progress-bar {
            background: #ecf0f1;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #2c3e50;
            transition: width 0.3s ease;
        }

        .highlight-box {
            background: #f8f9fa;
            border-left: 4px solid #2c3e50;
            padding: 10px;
            margin: 10px 0;
        }

        .summary-box {
            background: #34495e;
            color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
        }

        .chart-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">

@php
    // Manejo seguro de fechas
    $startDateFormatted = '';
    $endDateFormatted = '';
    
    try {
        if ($startDate instanceof \Carbon\Carbon) {
            $startDateFormatted = $startDate->format('d/m/Y');
        } elseif (is_string($startDate) && !empty($startDate)) {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $startDate)) {
                $startDateFormatted = $startDate;
            } else {
                $startDateFormatted = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate)->format('d/m/Y');
            }
        }
        
        if ($endDate instanceof \Carbon\Carbon) {
            $endDateFormatted = $endDate->format('d/m/Y');
        } elseif (is_string($endDate) && !empty($endDate)) {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $endDate)) {
                $endDateFormatted = $endDate;
            } else {
                $endDateFormatted = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate)->format('d/m/Y');
            }
        }
    } catch (\Exception $e) {
        $startDateFormatted = 'N/A';
        $endDateFormatted = 'N/A';
    }

    // Datos seguros
    $examStats = $examStats ?? [];
    $totalRequests = $totalRequests ?? 0;
    $totalPatients = $totalPatients ?? 0;
    $totalExams = $totalExams ?? 0;
    $tiposExamenes = count($examStats);
@endphp

<!-- PÁGINA 1: PORTADA Y ESTADÍSTICAS PRINCIPALES -->
<div style="text-align: center; margin-bottom: 25px; padding: 20px; background: #2c3e50; color: white;">
    <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: white;">LABORATORIO CLINICO LAREDO</h1>
    <h2 style="margin: 12px 0 0 0; font-size: 18px; color: white;">REPORTE DETALLADO DE EXÁMENES</h2>
    <p style="margin: 12px 0 0 0; font-size: 14px; color: white;">
        Periodo: {{ $startDateFormatted }} - {{ $endDateFormatted }}
    </p>
    <p style="margin: 8px 0 0 0; font-size: 12px; color: white;">
        Generado el: {{ now()->format('d/m/Y H:i:s') }}
    </p>
</div>

<!-- Estadísticas Principales -->
<div style="margin-bottom: 30px;">
    <h2 class="section-header">ESTADÍSTICAS PRINCIPALES</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $totalRequests }}</div>
            <div class="stat-label">Total Solicitudes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalPatients }}</div>
            <div class="stat-label">Total Pacientes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalExams }}</div>
            <div class="stat-label">Total Exámenes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $tiposExamenes }}</div>
            <div class="stat-label">Tipos de Exámenes</div>
        </div>
    </div>
</div>

<!-- Resumen Ejecutivo -->
<div style="margin-bottom: 30px;">
    <h2 class="section-header">RESUMEN EJECUTIVO</h2>
    
    <div class="summary-box">
        <h3 style="color: white; margin-bottom: 10px;">📊 ANÁLISIS DEL PERÍODO</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <p style="margin-bottom: 8px;"><strong>Total de Exámenes Realizados:</strong> {{ $totalExams }}</p>
                <p style="margin-bottom: 8px;"><strong>Tipos de Exámenes Diferentes:</strong> {{ $tiposExamenes }}</p>
                <p style="margin-bottom: 8px;"><strong>Promedio por Solicitud:</strong> 
                    {{ $totalRequests > 0 ? round($totalExams / $totalRequests, 1) : 0 }}
                </p>
            </div>
            <div>
                <p style="margin-bottom: 8px;"><strong>Pacientes Únicos:</strong> {{ $totalPatients }}</p>
                <p style="margin-bottom: 8px;"><strong>Promedio Exámenes/Paciente:</strong> 
                    {{ $totalPatients > 0 ? round($totalExams / $totalPatients, 1) : 0 }}
                </p>
                <p style="margin-bottom: 8px;"><strong>Diversidad de Exámenes:</strong> 
                    {{ $totalExams > 0 ? round(($tiposExamenes / $totalExams) * 100, 1) : 0 }}%
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Top 10 Exámenes más Solicitados -->
@if(!empty($examStats) && count($examStats) > 0)
<div style="margin-bottom: 30px;">
    <h2 class="section-header">TOP 10 EXÁMENES MÁS SOLICITADOS</h2>
    
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Pos.</th>
                <th style="width: 15%;">Código</th>
                <th style="width: 35%;">Examen</th>
                <th style="width: 20%;">Categoría</th>
                <th style="width: 12%;">Cantidad</th>
                <th style="width: 10%;">% Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $examStatsCollection = collect($examStats);
                $totalCount = $examStatsCollection->sum('count');
            @endphp
            @foreach($examStatsCollection->take(10) as $index => $exam)
            <tr>
                <td style="text-align: center; font-weight: bold;">
                    @if($index == 0)
                        🥇
                    @elseif($index == 1)
                        🥈
                    @elseif($index == 2)
                        🥉
                    @else
                        {{ $index + 1 }}
                    @endif
                </td>
                <td style="font-weight: bold;">{{ $exam->codigo ?? 'N/A' }}</td>
                <td>{{ $exam->nombre ?? 'N/A' }}</td>
                <td>
                    <span class="badge badge-medium">{{ $exam->categoria ?? 'General' }}</span>
                </td>
                <td style="text-align: center; font-weight: bold;">{{ $exam->count ?? 0 }}</td>
                <td style="text-align: center;">
                    @php
                        $percentage = $totalCount > 0 ? round(($exam->count / $totalCount) * 100, 1) : 0;
                    @endphp
                    {{ $percentage }}%
                    <div class="progress-bar" style="margin-top: 3px;">
                        <div class="progress-fill" style="width: {{ min($percentage, 100) }}%;"></div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- PÁGINA 2: ANÁLISIS POR CATEGORÍAS -->
<div class="page-break"></div>

<div style="text-align: center; margin-bottom: 25px; padding: 15px; background: #34495e; color: white;">
    <h2 style="margin: 0; font-size: 20px; font-weight: bold; color: white;">ANÁLISIS POR CATEGORÍAS</h2>
    <p style="margin: 8px 0 0 0; font-size: 12px; color: white;">
        Distribución y rendimiento por área médica
    </p>
</div>

@php
    // Agrupar exámenes por categoría
    $categorias = [];
    foreach ($examStats as $exam) {
        $categoria = $exam->categoria ?? 'General';
        if (!isset($categorias[$categoria])) {
            $categorias[$categoria] = [
                'nombre' => $categoria,
                'examenes' => [],
                'total_count' => 0
            ];
        }
        $categorias[$categoria]['examenes'][] = $exam;
        $categorias[$categoria]['total_count'] += $exam->count ?? 0;
    }
    
    // Ordenar categorías por total
    uasort($categorias, function($a, $b) {
        return $b['total_count'] <=> $a['total_count'];
    });
@endphp

<div style="margin-bottom: 30px;">
    <h2 class="section-header">RESUMEN POR CATEGORÍAS</h2>
    
    <table>
        <thead>
            <tr>
                <th style="width: 35%;">Categoría</th>
                <th style="width: 15%;">Tipos Examen</th>
                <th style="width: 15%;">Total Realizados</th>
                <th style="width: 15%;">% del Total</th>
                <th style="width: 20%;">Promedio por Tipo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categorias as $categoria)
            <tr>
                <td style="font-weight: bold;">
                    <span class="badge badge-medium">{{ $categoria['nombre'] }}</span>
                </td>
                <td style="text-align: center;">{{ count($categoria['examenes']) }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $categoria['total_count'] }}</td>
                <td style="text-align: center;">
                    @php
                        $catPercentage = $totalExams > 0 ? round(($categoria['total_count'] / $totalExams) * 100, 1) : 0;
                    @endphp
                    {{ $catPercentage }}%
                </td>
                <td style="text-align: center;">
                    {{ count($categoria['examenes']) > 0 ? round($categoria['total_count'] / count($categoria['examenes']), 1) : 0 }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Desglose Detallado por Categoría -->
@foreach(collect($categorias)->take(5) as $categoria)
<div style="margin-bottom: 25px;">
    <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #bdc3c7; padding-bottom: 5px;">
        📋 {{ strtoupper($categoria['nombre']) }}
    </h3>
    
    <div class="highlight-box">
        <p><strong>Exámenes en esta categoría:</strong> {{ count($categoria['examenes']) }}</p>
        <p><strong>Total realizados:</strong> {{ $categoria['total_count'] }}</p>
        <p><strong>Promedio por examen:</strong> {{ count($categoria['examenes']) > 0 ? round($categoria['total_count'] / count($categoria['examenes']), 1) : 0 }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre del Examen</th>
                <th>Cantidad</th>
                <th>% Categoría</th>
                <th>Ranking</th>
            </tr>
        </thead>
        <tbody>
            @foreach(collect($categoria['examenes'])->take(10) as $index => $exam)
            <tr>
                <td>{{ $exam->codigo ?? 'N/A' }}</td>
                <td>{{ $exam->nombre ?? 'N/A' }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $exam->count ?? 0 }}</td>
                <td style="text-align: center;">
                    @php
                        $examPercentage = $categoria['total_count'] > 0 ? round(($exam->count / $categoria['total_count']) * 100, 1) : 0;
                    @endphp
                    {{ $examPercentage }}%
                </td>
                <td style="text-align: center;">
                    @if($exam->count >= 10)
                        <span class="badge badge-high">Alto</span>
                    @elseif($exam->count >= 5)
                        <span class="badge badge-medium">Medio</span>
                    @else
                        <span class="badge badge-low">Bajo</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach

<!-- PÁGINA 3: ANÁLISIS DE RENDIMIENTO -->
<div class="page-break"></div>

<div style="text-align: center; margin-bottom: 25px; padding: 15px; background: #34495e; color: white;">
    <h2 style="margin: 0; font-size: 20px; font-weight: bold; color: white;">ANÁLISIS DE RENDIMIENTO</h2>
    <p style="margin: 8px 0 0 0; font-size: 12px; color: white;">
        Indicadores clave y métricas de eficiencia
    </p>
</div>

@php
    // Calcular KPIs
    $promedioExamenesPorSolicitud = $totalRequests > 0 ? round($totalExams / $totalRequests, 2) : 0;
    $promedioExamenesPorPaciente = $totalPatients > 0 ? round($totalExams / $totalPatients, 2) : 0;
    $diversidadExamenes = $totalExams > 0 ? round(($tiposExamenes / $totalExams) * 100, 2) : 0;
    
    // Clasificar exámenes por rendimiento
    $examenesAltoRendimiento = collect($examStats)->filter(function($exam) {
        return ($exam->count ?? 0) >= 10;
    });
    
    $examenesMedioRendimiento = collect($examStats)->filter(function($exam) {
        return ($exam->count ?? 0) >= 5 && ($exam->count ?? 0) < 10;
    });
    
    $examenesBajoRendimiento = collect($examStats)->filter(function($exam) {
        return ($exam->count ?? 0) < 5;
    });
@endphp

<div style="margin-bottom: 30px;">
    <h2 class="section-header">INDICADORES CLAVE DE RENDIMIENTO (KPIs)</h2>
    
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-number">{{ $promedioExamenesPorSolicitud }}</div>
            <div class="stat-label">Promedio Exámenes/Solicitud</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $promedioExamenesPorPaciente }}</div>
            <div class="stat-label">Promedio Exámenes/Paciente</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $diversidadExamenes }}%</div>
            <div class="stat-label">Diversidad de Exámenes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ count($categorias) }}</div>
            <div class="stat-label">Categorías Activas</div>
        </div>
    </div>
</div>

<!-- Distribución por Nivel de Rendimiento -->
<div style="margin-bottom: 30px;">
    <h2 class="section-header">DISTRIBUCIÓN POR NIVEL DE RENDIMIENTO</h2>
    
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Nivel</th>
                <th style="width: 20%;">Criterio</th>
                <th style="width: 15%;">Cantidad Tipos</th>
                <th style="width: 15%;">% del Total</th>
                <th style="width: 25%;">Interpretación</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="badge badge-high">Alto Rendimiento</span></td>
                <td>≥ 10 exámenes</td>
                <td style="text-align: center; font-weight: bold;">{{ $examenesAltoRendimiento->count() }}</td>
                <td style="text-align: center;">{{ $tiposExamenes > 0 ? round(($examenesAltoRendimiento->count() / $tiposExamenes) * 100, 1) : 0 }}%</td>
                <td>Exámenes con alta demanda</td>
            </tr>
            <tr>
                <td><span class="badge badge-medium">Rendimiento Medio</span></td>
                <td>5-9 exámenes</td>
                <td style="text-align: center; font-weight: bold;">{{ $examenesMedioRendimiento->count() }}</td>
                <td style="text-align: center;">{{ $tiposExamenes > 0 ? round(($examenesMedioRendimiento->count() / $tiposExamenes) * 100, 1) : 0 }}%</td>
                <td>Demanda moderada estable</td>
            </tr>
            <tr>
                <td><span class="badge badge-low">Bajo Rendimiento</span></td>
                <td>< 5 exámenes</td>
                <td style="text-align: center; font-weight: bold;">{{ $examenesBajoRendimiento->count() }}</td>
                <td style="text-align: center;">{{ $tiposExamenes > 0 ? round(($examenesBajoRendimiento->count() / $tiposExamenes) * 100, 1) : 0 }}%</td>
                <td>Exámenes especializados</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Recomendaciones Estratégicas -->
<div style="margin-bottom: 30px;">
    <h2 class="section-header">RECOMENDACIONES ESTRATÉGICAS</h2>
    
    <div class="summary-box">
        <h3 style="color: white; margin-bottom: 15px;">💡 ANÁLISIS Y SUGERENCIAS</h3>
        
        @if($examenesAltoRendimiento->count() > 0)
        <div style="margin-bottom: 15px;">
            <h4 style="color: #ecf0f1; margin-bottom: 8px;">🔥 Exámenes de Alta Demanda:</h4>
            <p>• Se identificaron {{ $examenesAltoRendimiento->count() }} exámenes con alta demanda (≥10 solicitudes)</p>
            <p>• Considerar optimizar recursos y tiempos para estos exámenes prioritarios</p>
            <p>• Evaluar capacidad de procesamiento para mantener eficiencia</p>
        </div>
        @endif
        
        @if($examenesBajoRendimiento->count() > 0)
        <div style="margin-bottom: 15px;">
            <h4 style="color: #ecf0f1; margin-bottom: 8px;">📊 Exámenes Especializados:</h4>
            <p>• {{ $examenesBajoRendimiento->count() }} exámenes con demanda baja (<5 solicitudes)</p>
            <p>• Estos pueden ser exámenes especializados de alto valor diagnóstico</p>
            <p>• Mantener disponibilidad para casos específicos</p>
        </div>
        @endif
        
        <div style="margin-bottom: 15px;">
            <h4 style="color: #ecf0f1; margin-bottom: 8px;">📈 Eficiencia Operativa:</h4>
            <p>• Promedio de {{ $promedioExamenesPorSolicitud }} exámenes por solicitud</p>
            <p>• Diversidad del {{ $diversidadExamenes }}% indica buena variedad de servicios</p>
            @if($promedioExamenesPorSolicitud > 2)
            <p>• Alto nivel de exámenes complementarios por paciente</p>
            @endif
        </div>
    </div>
</div>

<!-- PÁGINA 4: LISTADO COMPLETO DE EXÁMENES -->
<div class="page-break"></div>

<div style="text-align: center; margin-bottom: 25px; padding: 15px; background: #34495e; color: white;">
    <h2 style="margin: 0; font-size: 20px; font-weight: bold; color: white;">LISTADO COMPLETO DE EXÁMENES</h2>
    <p style="margin: 8px 0 0 0; font-size: 12px; color: white;">
        Detalle completo ordenado por volumen
    </p>
</div>

<div style="margin-bottom: 30px;">
    <h2 class="section-header">TODOS LOS EXÁMENES DEL PERÍODO</h2>
    
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Pos.</th>
                <th style="width: 12%;">Código</th>
                <th style="width: 35%;">Nombre del Examen</th>
                <th style="width: 18%;">Categoría</th>
                <th style="width: 12%;">Cantidad</th>
                <th style="width: 10%;">% Total</th>
                <th style="width: 5%;">Nivel</th>
            </tr>
        </thead>
        <tbody>
            @foreach($examStats as $index => $exam)
            <tr>
                <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                <td>{{ $exam->codigo ?? 'N/A' }}</td>
                <td>{{ $exam->nombre ?? 'N/A' }}</td>
                <td>
                    <span class="badge badge-medium">{{ $exam->categoria ?? 'General' }}</span>
                </td>
                <td style="text-align: center; font-weight: bold;">{{ $exam->count ?? 0 }}</td>
                <td style="text-align: center;">
                    @php
                        $percentage = $totalExams > 0 ? round(($exam->count / $totalExams) * 100, 1) : 0;
                    @endphp
                    {{ $percentage }}%
                </td>
                <td style="text-align: center;">
                    @if($exam->count >= 10)
                        <span class="badge badge-high">A</span>
                    @elseif($exam->count >= 5)
                        <span class="badge badge-medium">M</span>
                    @else
                        <span class="badge badge-low">B</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Resumen Final -->
<div style="margin-top: 30px; padding: 20px; background: #2c3e50; color: white; text-align: center;">
    <h3 style="margin: 0 0 10px 0; color: white;">RESUMEN FINAL DEL REPORTE</h3>
    <p style="margin: 5px 0;">
        <strong>{{ $totalExams }}</strong> exámenes realizados | 
        <strong>{{ $tiposExamenes }}</strong> tipos diferentes | 
        <strong>{{ count($categorias) }}</strong> categorías activas
    </p>
    <p style="margin: 5px 0; font-size: 10px;">
        Generado el {{ now()->format('d/m/Y H:i:s') }} • Laboratorio Clínico Laredo
    </p>
</div>

    </div>
</body>
</html>
