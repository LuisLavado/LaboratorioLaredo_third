<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte General Detallado - Laboratorio Clínico Laredo' }}</title>
    <meta name="description" content="Reporte General Detallado del Laboratorio Clínico Laredo">
    <meta name="author" content="Laboratorio Clínico Laredo">
    <meta name="subject" content="Reporte General de Actividades">
    <meta name="keywords" content="laboratorio, reporte, análisis, clínico">
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
        
        /* Distribución por género */
        .gender-distribution {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        
        .gender-item {
            text-align: center;
            padding: 10px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .gender-count {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .gender-percentage {
            font-size: 11px;
            color: #6c757d;
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
        <h2>{{ $title ?? 'REPORTE GENERAL DETALLADO' }}</h2>
    </div>
    
    <!-- Información del Período -->
    <div class="period-info">
        Período: {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}
        <br>
        Generado el: {{ now()->format('d/m/Y H:i:s') }}
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
            
        </div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Completados:</span>
                <span class="stat-value">{{ number_format($completedCount ?? 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">En Proceso:</span>
                <span class="stat-value">{{ number_format($inProcessCount ?? 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Pendientes:</span>
                <span class="stat-value">{{ number_format($pendingCount ?? 0) }}</span>
            </div>
           
        </div>
    </div>
    
    <!-- Servicios Más Activos -->
    @if(isset($serviceStats) && count($serviceStats) > 0)
    <div class="section no-break">
        <div class="section-header">SOLICITUDES POR SERVICIO</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 60%;">Servicio</th>
                    <th style="width: 30%;">Solicitudes</th>
                </tr>
            </thead>
            <tbody>
                @foreach(collect($serviceStats)->take(10) as $index => $service)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $service->name ?? 'Sin nombre' }}</td>
                    <td class="text-right font-bold">{{ number_format($service->count ?? 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Exámenes Más Solicitados -->
    @if(isset($examStats) && count($examStats) > 0)
    <div class="section no-break">
        <div class="section-header">EXÁMENES MÁS SOLICITADOS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 50%;">Examen</th>
                    <th style="width: 5%;">Cantidad de solicitudes</th>
                    <th style="width: 20%;">Categoría</th>
                </tr>
            </thead>
            <tbody>
                @foreach(collect($examStats)->take(15) as $index => $exam)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $exam->name ?? $exam->nombre ?? 'Sin nombre' }}</td>
                    <td class="text-right font-bold">{{ number_format($exam->count ?? 0) }}</td>
                    <td class="text-center">{{ $exam->categoria ?? 'General' }}</td>
                </tr>
                @endforeach
              
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Distribución por Género -->
    @if(isset($patients) && count($patients) > 0)
    <div class="section no-break">
        <div class="section-header">DISTRIBUCIÓN POR GÉNERO</div>
        
        @php
            $masculino = 0;
            $femenino = 0;
            $otros = 0;
            
            foreach($patients as $patient) {
                if (!$patient) continue; // Saltar si el paciente es null
                
                $sexo = strtolower($patient->sexo ?? '');
                if (in_array($sexo, ['m', 'masculino', 'hombre'])) {
                    $masculino++;
                } elseif (in_array($sexo, ['f', 'femenino', 'mujer'])) {
                    $femenino++;
                } else {
                    $otros++;
                }
            }
            
            $totalPacientes = count($patients);
        @endphp
        
        <div class="gender-distribution">
            @if($masculino > 0)
            <div class="gender-item">
                <div class="gender-count">{{ number_format($masculino) }}</div>
                <div>Masculino</div>
                <div class="gender-percentage">
                    ({{ $totalPacientes > 0 ? number_format(($masculino / $totalPacientes) * 100, 1) : 0 }}%)
                </div>
            </div>
            @endif
            
            @if($femenino > 0)
            <div class="gender-item">
                <div class="gender-count">{{ number_format($femenino) }}</div>
                <div>Femenino</div>
                <div class="gender-percentage">
                    ({{ $totalPacientes > 0 ? number_format(($femenino / $totalPacientes) * 100, 1) : 0 }}%)
                </div>
            </div>
            @endif
            
            @if($otros > 0)
            <div class="gender-item">
                <div class="gender-count">{{ number_format($otros) }}</div>
                <div>No especificado</div>
                <div class="gender-percentage">
                    ({{ $totalPacientes > 0 ? number_format(($otros / $totalPacientes) * 100, 1) : 0 }}%)
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
    
    <!-- Últimas Solicitudes Detalladas -->
    @if(isset($solicitudes) && count($solicitudes) > 0)
    <div class="section">
        <div class="section-header">SOLICITUDES (Total {{ count($solicitudes) }})</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Nº</th>
                    <th style="width: 12%;">Fecha</th>
                    <th style="width: 25%;">Paciente</th>
                    <th style="width: 12%;">DNI</th>
                    <th style="width: 20%;">Servicio</th>
                    <th style="width: 15%;">Médico</th>
                    <th style="width: 8%;">Exámenes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitudes as $solicitud)

                <tr>
                    <td class="text-center">{{ $solicitud->id ?? 'N/A' }}</td>
                    <td class="text-center">
                        @if(isset($solicitud->fecha))
                            {{ \Carbon\Carbon::parse($solicitud->fecha)->format('d/m/Y') }}
                            @if(isset($solicitud->hora))
                                <br><small>{{ $solicitud->hora }}</small>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(isset($solicitud->paciente))
                            {{ $solicitud->paciente->nombres ?? '' }} {{ $solicitud->paciente->apellidos ?? '' }}
                        @else
                            No disponible
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($solicitud->paciente))
                            {{ $solicitud->paciente->dni ?? 'Sin documento' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $solicitud->servicio->nombre ?? 'No especificado' }}</td>
                    <td>
                        @if(isset($solicitud->user))
                            {{ $solicitud->user->nombre ?? '' }} {{ $solicitud->user->apellido ?? '' }}
                        @else
                            No asignado
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($solicitud->detalles))
                            {{ count($solicitud->detalles) }}
                        @else
                            0
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <!-- Resumen de Pacientes -->
    @if(isset($patients) && count($patients) > 0)
    <div class="section no-break">
        <div class="section-header">RESUMEN DE PACIENTES ({{ count($patients) }} únicos)</div>
        
        @php
            $masculinoResumen = 0;
            $femeninoResumen = 0;
            $otrosResumen = 0;
            $totalEdades = 0;
            $pacientesConEdad = 0;
            
            foreach($patients as $patient) {
                if (!$patient) continue; // Saltar si el paciente es null
                
                $sexo = strtolower($patient->sexo ?? '');
                if (in_array($sexo, ['m', 'masculino', 'hombre'])) {
                    $masculinoResumen++;
                } elseif (in_array($sexo, ['f', 'femenino', 'mujer'])) {
                    $femeninoResumen++;
                } else {
                    $otrosResumen++;
                }
                
                if (isset($patient->edad) && is_numeric($patient->edad)) {
                    $totalEdades += $patient->edad;
                    $pacientesConEdad++;
                }
            }
            
            $totalPacientesResumen = count($patients);
            $edadPromedio = $pacientesConEdad > 0 ? round($totalEdades / $pacientesConEdad, 1) : 0;
        @endphp
        
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Pacientes Masculinos:</span>
                <span class="stat-value">{{ number_format($masculinoResumen) }} ({{ $totalPacientesResumen > 0 ? number_format(($masculinoResumen / $totalPacientesResumen) * 100, 1) : 0 }}%)</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Pacientes Femeninos:</span>
                <span class="stat-value">{{ number_format($femeninoResumen) }} ({{ $totalPacientesResumen > 0 ? number_format(($femeninoResumen / $totalPacientesResumen) * 100, 1) : 0 }}%)</span>
            </div>
           
            <div class="stat-item">
                <span class="stat-label">Edad Promedio:</span>
                <span class="stat-value">{{ $edadPromedio }} años</span>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Estadísticas Avanzadas y Tendencias -->
    <div class="section no-break">
        <div class="section-header">ESTADÍSTICAS AVANZADAS</div>
        
       
           
            
            </div>
            <div class="stat-item">
                <span class="stat-label">Utilización de Servicios:</span>
                <span class="stat-value">
                    @php
                        $serviciosActivos = isset($serviceStats) ? count($serviceStats) : 0;
                    @endphp
                    {{ $serviciosActivos }} servicios activos
                </span>
            </div>
        </div>
        
        @if(isset($dailyStats) && count($dailyStats) > 0)
        <div style="margin-top: 15px;">
            <strong>Tendencia Diaria:</strong>
            <table class="data-table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th style="width: 25%;">Fecha</th>
                        <th style="width: 25%;">Solicitudes</th>
                        <th style="width: 25%;">Pacientes</th>
                        <th style="width: 25%;">Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(collect($dailyStats)->take(10) as $stat)
                    <tr>
                        <td class="text-center">{{ \Carbon\Carbon::parse($stat->date)->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $stat->count ?? 0 }}</td>
                        <td class="text-center">{{ $stat->patientCount ?? 0 }}</td>
                        <td class="text-center">
                            {{ ($stat->patientCount ?? 0) > 0 ? number_format(($stat->count ?? 0) / ($stat->patientCount ?? 0), 1) : '0' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <!-- Información del Reporte -->
    <div class="report-info no-break">
        <div class="report-info-title">INFORMACIÓN DEL REPORTE</div>
        <div><strong>Período:</strong> {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}</div>
        <div><strong>Generado el:</strong> {{ now()->format('d/m/Y H:i:s') }}</div>
        <div><strong>Generado por:</strong> {{ $generatedBy ?? 'Sistema' }}</div>
        <div><strong>Tipo de reporte:</strong> {{ $reportType ?? 'General' }}</div>
    </div>
</body>
</html>
