<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte de Doctores Detallado - Laboratorio Clínico Laredo' }}</title>
    <meta name="description" content="Reporte Detallado de Doctores del Laboratorio Clínico Laredo">
    <meta name="author" content="Laboratorio Clínico Laredo">
    <meta name="subject" content="Reporte de Actividad por Doctores">
    <meta name="keywords" content="laboratorio, doctores, médicos, análisis, clínico, solicitudes">
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
        
        /* Perfiles de doctor destacados */
        .doctor-profiles {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .doctor-profile {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            position: relative;
        }
        
        .doctor-profile-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .doctor-name {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
        }
        
        .doctor-specialty {
            color: #6c757d;
            font-size: 10px;
            margin: 3px 0;
        }
        
        .doctor-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .doctor-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 5px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .doctor-stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .doctor-stat-label {
            font-size: 9px;
            color: #6c757d;
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
        
        /* Gráficos simulados */
        .chart-bar {
            background: #e9ecef;
            height: 15px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 2px;
        }
        
        .chart-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%);
        }
        
        /* Indicadores de actividad */
        .activity-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .activity-high {
            background: #10b981;
        }
        
        .activity-medium {
            background: #f59e0b;
        }
        
        .activity-low {
            background: #ef4444;
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
                margin: 15mm;
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
        <h2>{{ $title ?? 'REPORTE DE DOCTORES DETALLADO' }}</h2>
    </div>
    
    <!-- Información del Período -->
    <div class="period-info">
        Período: {{ $startDate->format('d/m/Y') ?? 'N/A' }} al {{ $endDate->format('d/m/Y') ?? 'N/A' }}
        <br>
        Generado el: {{ now()->format('d/m/Y H:i:s') }}
    </div>
    
    <!-- Resumen Ejecutivo -->
    <div class="executive-summary no-break">
        <div class="summary-title">RESUMEN EJECUTIVO</div>

        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total de Doctores:</span>
                <span class="stat-value">{{ $totalDoctors ?? 0 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de Solicitudes:</span>
                <span class="stat-value">{{ $totalRequests ?? 0 }}</span>
            </div>

            <div class="stat-item">
                <span class="stat-label">Total de Exámenes:</span>
                <span class="stat-value">{{ $totalExams ?? 0 }}</span>
            </div>
        </div>
    </div>
    
    <!-- Lista de Doctores -->
    @if(isset($doctorStats) && count($doctorStats) > 0)
    <div class="section no-break">
        <div class="section-header">LISTA DE DOCTORES REGISTRADOS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="30%">Nombre Completo</th>
                    <th width="20%">Especialidad</th>
                    <th width="15%">Solicitudes</th>
                    <th width="15%">Pacientes</th>
                    <th width="15%">Exámenes</th>
        
                </tr>
            </thead>
            <tbody>
                @foreach($doctorStats as $doctor)
                <tr>
                    <td>{{ ($doctor->nombres ?? '') . ' ' . ($doctor->apellidos ?? '') }}</td>
                    <td>{{ $doctor->especialidad ?? 'No especificada' }}</td>
                    <td class="text-center">{{ $doctor->total_solicitudes ?? 0 }}</td>
                    <td class="text-center">{{ $doctor->total_pacientes ?? 0 }}</td>
                    <td class="text-center">{{ $doctor->total_examenes ?? 0 }}</td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Top 5 Doctores Más Activos -->
    @if(isset($doctorStats) && count($doctorStats) > 0)
    <div class="section no-break">
        <div class="section-header">TOP 5 DOCTORES MÁS ACTIVOS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="10%">Ranking</th>
                    <th width="35%">Doctor</th>
                    <th width="20%">Especialidad</th>
                    <th width="15%">Solicitudes</th>
                    <th width="20%">Porcentaje del Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $topDoctors = collect($doctorStats)
                        ->sortByDesc('total_solicitudes')
                        ->take(5);
                @endphp

                @foreach($topDoctors as $index => $doctor)
                <tr>
                    <td class="text-center font-bold">{{ $index + 1 }}</td>
                    <td>{{ ($doctor->nombres ?? '') . ' ' . ($doctor->apellidos ?? '') }}</td>
                    <td>{{ $doctor->especialidad ?? 'No especificada' }}</td>
                    <td class="text-center font-bold">{{ $doctor->total_solicitudes ?? 0 }}</td>
                    <td class="text-center">
                        @if(isset($totalRequests) && $totalRequests > 0)
                            {{ number_format(($doctor->total_solicitudes / $totalRequests) * 100, 1) }}%
                        @else
                            0.0%
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Resumen por Especialidad -->
    @if(isset($doctorStats) && count($doctorStats) > 0)
    <div class="section no-break">
        <div class="section-header">RESUMEN POR ESPECIALIDAD</div>

        @php
            $especialidades = collect($doctorStats)
                ->groupBy('especialidad')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'solicitudes' => $group->sum('total_solicitudes'),
                        'pacientes' => $group->sum('total_pacientes'),
                        'examenes' => $group->sum('total_examenes')
                    ];
                })
                ->sortByDesc('solicitudes');
        @endphp

        <table class="data-table">
            <thead>
                <tr>
                    <th width="30%">Especialidad</th>
                    <th width="15%">Cantidad Doctores</th>
                    <th width="15%">Total Solicitudes</th>
                    <th width="15%">Total Pacientes</th>
                    <th width="15%">Total Exámenes</th>
                    <th width="10%">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($especialidades as $especialidad => $stats)
                <tr>
                    <td>{{ $especialidad ?: 'No especificada' }}</td>
                    <td class="text-center">{{ $stats['count'] }}</td>
                    <td class="text-center">{{ $stats['solicitudes'] }}</td>
                    <td class="text-center">{{ $stats['pacientes'] }}</td>
                    <td class="text-center">{{ $stats['examenes'] }}</td>
                    <td class="text-center">
                        @if(isset($totalRequests) && $totalRequests > 0)
                            {{ number_format(($stats['solicitudes'] / $totalRequests) * 100, 1) }}%
                        @else
                            0.0%
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Estadísticas por Estado de Actividad -->
    @if(isset($doctorStats) && count($doctorStats) > 0)
    <div class="section no-break">
        <div class="section-header">ESTADÍSTICAS POR ESTADO DE ACTIVIDAD</div>

        @php
            $doctoresActivos = collect($doctorStats)->filter(function($doctor) {
                return ($doctor->total_solicitudes ?? 0) > 0;
            });
            $doctoresInactivos = collect($doctorStats)->filter(function($doctor) {
                return ($doctor->total_solicitudes ?? 0) == 0;
            });

            $totalDoctoresCount = count($doctorStats);
            $activosCount = $doctoresActivos->count();
            $inactivosCount = $doctoresInactivos->count();

            $solicitudesActivos = $doctoresActivos->sum('total_solicitudes');
            $pacientesActivos = $doctoresActivos->sum('total_pacientes');
            $examenesActivos = $doctoresActivos->sum('total_examenes');
        @endphp

        <table class="data-table">
            <thead>
                <tr>
                    <th width="25%">Estado</th>
                    <th width="15%">Cantidad Doctores</th>
                    <th width="15%">Total Solicitudes</th>
                    <th width="15%">Total Pacientes</th>
                    <th width="15%">Total Exámenes</th>
                    <th width="15%">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Doctores Activos</strong></td>
                    <td class="text-center">{{ $activosCount }}</td>
                    <td class="text-center">{{ $solicitudesActivos }}</td>
                    <td class="text-center">{{ $pacientesActivos }}</td>
                    <td class="text-center">{{ $examenesActivos }}</td>
                    <td class="text-center">{{ $totalDoctoresCount > 0 ? round(($activosCount / $totalDoctoresCount) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td><strong>Doctores Inactivos</strong></td>
                    <td class="text-center">{{ $inactivosCount }}</td>
                    <td class="text-center">0</td>
                    <td class="text-center">0</td>
                    <td class="text-center">0</td>
                    <td class="text-center">{{ $totalDoctoresCount > 0 ? round(($inactivosCount / $totalDoctoresCount) * 100, 1) : 0 }}%</td>
                </tr>
                <tr style="background-color: #e9ecef; font-weight: bold;">
                    <td><strong>TOTAL</strong></td>
                    <td class="text-center">{{ $totalDoctoresCount }}</td>
                    <td class="text-center">{{ $solicitudesActivos }}</td>
                    <td class="text-center">{{ $pacientesActivos }}</td>
                    <td class="text-center">{{ $examenesActivos }}</td>
                    <td class="text-center">100%</td>
                </tr>
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
        <div><strong>Tipo de reporte:</strong> {{'Doctores Detallado' }}</div>
        <div><strong>Total de doctores analizados:</strong> {{ isset($doctorStats) ? count($doctorStats) : 0 }}</div>
    </div>
</body>
</html>
