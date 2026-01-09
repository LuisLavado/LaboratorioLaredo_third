<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte General - Laboratorio Laredo</title>
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
@endphp

<!-- PÁGINA 1: PORTADA Y ESTADÍSTICAS PRINCIPALES -->
<div style="text-align: center; margin-bottom: 25px; padding: 20px; background: #2c3e50; color: white;">
    <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: white;">LABORATORIO CLINICO LAREDO</h1>
    <h2 style="margin: 12px 0 0 0; font-size: 18px; color: white;">REPORTE GENERAL EJECUTIVO</h2>
    <p style="margin: 12px 0 0 0; font-size: 14px; color: white;">
        Periodo: {{ $startDateFormatted }} - {{ $endDateFormatted }}
    </p>
    <p style="margin: 8px 0 0 0; font-size: 12px; color: white;">
        Generado el: {{ now()->format('d/m/Y H:i:s') }}
    </p>
</div>

<!-- Estadísticas Principales -->
<div style="margin-bottom: 30px;">
    <h2 class="section-header">ESTADISTICAS PRINCIPALES</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $totalRequests ?? 0 }}</div>
            <div class="stat-label">Total Solicitudes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $pendingCount ?? 0 }}</div>
            <div class="stat-label">Pendientes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $inProcessCount ?? 0 }}</div>
            <div class="stat-label">En Proceso</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $completedCount ?? 0 }}</div>
            <div class="stat-label">Completadas</div>
        </div>
    </div>
</div>

<!-- Información General del Período -->
<div style="margin-bottom: 30px;">
    <h2 class="section-header">INFORMACION GENERAL DEL PERIODO</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <h3 style="color: #1a1a1a; font-size: 14px; margin-bottom: 10px; font-weight: bold;">ESTADISTICAS BASICAS</h3>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Concepto</th>
                        <th style="text-align: right;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: bold;">Total de Solicitudes:</td>
                        <td style="text-align: right; font-weight: bold;">{{ $totalRequests ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Total de Pacientes:</td>
                        <td style="text-align: right; font-weight: bold;">{{ $totalPatients ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Total de Examenes:</td>
                        <td style="text-align: right; font-weight: bold;">{{ $totalExams ?? 0 }}</td>
                    </tr>
                    <tr style="background: #e8f4fd;">
                        <td style="font-weight: bold;">Promedio Examenes/Solicitud:</td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ ($totalRequests ?? 0) > 0 ? round(($totalExams ?? 0) / ($totalRequests ?? 1), 1) : 0 }}
                        </td>
                    </tr>
                    <tr style="background: #e8f5e8;">
                        <td style="font-weight: bold;">Promedio Examenes/Paciente:</td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ ($totalPatients ?? 0) > 0 ? round(($totalExams ?? 0) / ($totalPatients ?? 1), 1) : 0 }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div>
            <h3 style="color: #1a1a1a; font-size: 14px; margin-bottom: 10px; font-weight: bold;">DISTRIBUCION POR ESTADO</h3>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Estado</th>
                        <th style="text-align: right;">Cantidad (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="color: #27ae60; font-weight: bold;">Completadas:</td>
                        <td style="text-align: right; color: #27ae60; font-weight: bold;">
                            {{ $completedCount ?? 0 }} 
                            ({{ ($totalRequests ?? 0) > 0 ? round((($completedCount ?? 0) / ($totalRequests ?? 1)) * 100, 1) : 0 }}%)
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #f39c12; font-weight: bold;">En Proceso:</td>
                        <td style="text-align: right; color: #f39c12; font-weight: bold;">
                            {{ $inProcessCount ?? 0 }}
                            ({{ ($totalRequests ?? 0) > 0 ? round((($inProcessCount ?? 0) / ($totalRequests ?? 1)) * 100, 1) : 0 }}%)
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #e74c3c; font-weight: bold;">Pendientes:</td>
                        <td style="text-align: right; color: #e74c3c; font-weight: bold;">
                            {{ $pendingCount ?? 0 }}
                            ({{ ($totalRequests ?? 0) > 0 ? round((($pendingCount ?? 0) / ($totalRequests ?? 1)) * 100, 1) : 0 }}%)
                        </td>
                    </tr>
                    <tr style="background: #f0f9ff;">
                        <td style="color: #2c3e50; font-weight: bold;">Eficiencia Global:</td>
                        <td style="text-align: right; color: #2c3e50; font-weight: bold;">
                            {{ ($totalRequests ?? 0) > 0 ? round((($completedCount ?? 0) / ($totalRequests ?? 1)) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SALTO DE PÁGINA -->
<div class="page-break"></div>

<!-- PÁGINA 2: DETALLE DE SOLICITUDES -->
@if((isset($solicitudes) && count($solicitudes) > 0) || (isset($dailyStats) && count($dailyStats) > 0))
<div style="margin-bottom: 30px;">
    <h2 class="section-header">DETALLE DE SOLICITUDES</h2>
    
    @if(isset($solicitudes) && count($solicitudes) > 0)
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">ID</th>
                    <th style="text-align: center;">Fecha</th>
                    <th style="text-align: center;">Paciente</th>
                    <th style="text-align: center;">Doctor</th>
                    <th style="text-align: center;">Examenes</th>
                    <th style="text-align: center;">Estado</th>
                    <th style="text-align: center;">Progreso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitudes as $solicitud)
                <tr>
                    <td style="font-weight: bold; text-align: center;">#{{ $solicitud->id }}</td>
                    <td style="text-align: center;">
                        @php
                            try {
                                $fechaSolicitud = \Carbon\Carbon::parse($solicitud->fecha)->format('d/m/Y');
                            } catch (\Exception $e) {
                                $fechaSolicitud = 'N/A';
                            }
                        @endphp
                        {{ $fechaSolicitud }}
                    </td>
                    <td>
                        <div style="font-weight: bold;">{{ $solicitud->paciente->nombres ?? 'N/A' }} {{ $solicitud->paciente->apellidos ?? '' }}</div>
                        <div style="font-size: 9px; color: #666;">DNI: {{ $solicitud->paciente->dni ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div style="font-weight: bold;">{{ $solicitud->user->nombre ?? 'N/A' }} {{ $solicitud->user->apellido ?? '' }}</div>
                        <div style="font-size: 9px; color: #666;">{{ $solicitud->user->email ?? 'N/A' }}</div>
                    </td>
                    <td>
                        @if(isset($solicitud->detalles) && count($solicitud->detalles) > 0)
                            <div style="max-width: 140px;">
                                @foreach($solicitud->detalles->take(2) as $detalle)
                                    <div style="font-size: 9px; margin-bottom: 1px;">
                                        • {{ $detalle->examen->nombre ?? 'N/A' }}
                                    </div>
                                @endforeach
                                @if(count($solicitud->detalles) > 2)
                                    <div style="font-size: 9px; color: #666; font-style: italic;">
                                        +{{ count($solicitud->detalles) - 2 }} mas...
                                    </div>
                                @endif
                            </div>
                        @else
                            <span style="color: #666;">Sin examenes</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @php
                            $estado = $solicitud->estado_calculado ?? $solicitud->estado ?? 'pendiente';
                            $estadoTexto = match($estado) {
                                'completado' => 'Completado',
                                'en_proceso' => 'En Proceso',
                                default => 'Pendiente'
                            };
                            $estadoColor = match($estado) {
                                'completado' => '#27ae60',
                                'en_proceso' => '#f39c12',
                                default => '#e74c3c'
                            };
                        @endphp
                        <span style="color: {{ $estadoColor }}; font-weight: bold; font-size: 9px;">{{ $estadoTexto }}</span>
                    </td>
                    <td style="text-align: center;">
                        @php
                            $totalExamenes = count($solicitud->detalles ?? []);
                            $completados = collect($solicitud->detalles ?? [])->where('estado', 'completado')->count();
                            $porcentaje = $totalExamenes > 0 ? round(($completados / $totalExamenes) * 100) : 0;
                        @endphp
                        <div style="font-weight: bold; color: #1a1a1a; font-size: 9px;">{{ $porcentaje }}%</div>
                        <div style="font-size: 8px; color: #666;">
                            {{ $completados }}/{{ $totalExamenes }} examenes
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">Fecha</th>
                    <th style="text-align: center;">Solicitudes</th>
                    <th style="text-align: center;">Pacientes</th>
                    <th style="text-align: center;">Examenes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyStats as $stat)
                <tr>
                    <td style="font-weight: bold; text-align: center;">
                        @php
                            try {
                                $fechaStats = \Carbon\Carbon::parse($stat->date ?? $stat['date'] ?? '')->format('d/m/Y');
                            } catch (\Exception $e) {
                                $fechaStats = $stat->date ?? $stat['date'] ?? 'N/A';
                            }
                        @endphp
                        {{ $fechaStats }}
                    </td>
                    <td style="text-align: center;">{{ $stat->count ?? $stat['count'] ?? 0 }}</td>
                    <td style="text-align: center;">{{ $stat->patientCount ?? $stat['patientCount'] ?? 0 }}</td>
                    <td style="text-align: center;">{{ $stat->examCount ?? $stat['examCount'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endif

<!-- SALTO DE PÁGINA -->
<div class="page-break"></div>

<!-- PÁGINA 3: EXAMENES MAS SOLICITADOS -->
@if((isset($topExamenes) && count($topExamenes) > 0) || (isset($examStats) && count($examStats) > 0))
<div style="margin-bottom: 30px;">
    <h2 class="section-header">EXAMENES MAS SOLICITADOS</h2>
    
    <table>
        <thead>
            <tr>
                <th style="text-align: center;">Posicion</th>
                <th style="text-align: center;">Codigo</th>
                <th style="text-align: center;">Examen</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: center;">Porcentaje</th>
                <th style="text-align: center;">Categoria</th>
            </tr>
        </thead>
        <tbody>
            @php
                $examenes = $topExamenes ?? $examStats ?? [];
            @endphp
            @foreach($examenes as $index => $examen)
            <tr>
                <td style="font-weight: bold; text-align: center;">
                    @if($index < 3)
                        <span style="font-size: 12px; font-weight: bold;">
                            @if($index == 0) 1º
                            @elseif($index == 1) 2º
                            @else 3º
                            @endif
                        </span>
                    @else
                        <span style="font-weight: bold; color: #666;">#{{ $index + 1 }}</span>
                    @endif
                </td>
                <td style="color: #3498db; font-weight: bold; text-align: center;">
                    @if(is_array($examen))
                        {{ $examen['codigo'] ?? $examen['id'] ?? 'N/A' }}
                    @else
                        {{ $examen->codigo ?? $examen->id ?? 'N/A' }}
                    @endif
                </td>
                <td>
                    @if(is_array($examen))
                        {{ $examen['nombre'] ?? 'N/A' }}
                    @else
                        {{ $examen->name ?? $examen->nombre ?? 'N/A' }}
                    @endif
                </td>
                <td style="font-weight: bold; text-align: center;">
                    @if(is_array($examen))
                        {{ $examen['cantidad'] ?? 0 }}
                    @else
                        {{ $examen->count ?? $examen->cantidad ?? 0 }}
                    @endif
                </td>
                <td style="text-align: center;">
                    @php
                        $porcentaje = 0;
                        if (is_array($examen)) {
                            $porcentaje = $examen['porcentaje'] ?? 0;
                        } else {
                            $porcentaje = $examen->percentage ?? $examen->porcentaje ?? 0;
                        }
                    @endphp
                    <span style="font-weight: bold;">{{ $porcentaje }}%</span>
                </td>
                <td style="text-align: center;">
                    @if($porcentaje > 15)
                        <span style="color: #27ae60; font-weight: bold;">Alto</span>
                    @elseif($porcentaje > 5)
                        <span style="color: #f39c12; font-weight: bold;">Medio</span>
                    @else
                        <span style="color: #666;">Bajo</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- SALTO DE PÁGINA -->
<div class="page-break"></div>

<!-- PÁGINA 4: DOCTORES MAS ACTIVOS -->
@if((isset($topDoctores) && count($topDoctores) > 0) || (isset($doctorStats) && count($doctorStats) > 0))
<div style="margin-bottom: 30px;">
    <h2 class="section-header">DOCTORES MAS ACTIVOS</h2>
    
    <table>
        <thead>
            <tr>
                <th style="text-align: center;">Posicion</th>
                <th style="text-align: center;">Doctor</th>
                <th style="text-align: center;">Solicitudes</th>
                <th style="text-align: center;">Porcentaje</th>
                <th style="text-align: center;">Nivel de Actividad</th>
            </tr>
        </thead>
        <tbody>
            @php
                $doctores = $topDoctores ?? $doctorStats ?? [];
            @endphp
            @foreach($doctores as $index => $doctor)
            <tr>
                <td style="font-weight: bold; text-align: center;">
                    @if($index < 3)
                        <span style="font-size: 12px; font-weight: bold;">#{{ $index + 1 }}</span>
                    @else
                        <span style="font-weight: bold; color: #666;">#{{ $index + 1 }}</span>
                    @endif
                </td>
                <td>
                    @if(is_array($doctor))
                        <div style="font-weight: bold;">Dr. {{ $doctor['nombre'] ?? 'N/A' }} {{ $doctor['apellido'] ?? '' }}</div>
                        <div style="font-size: 9px; color: #666;">{{ $doctor['email'] ?? 'N/A' }}</div>
                    @else
                        <div style="font-weight: bold;">Dr. {{ $doctor->name ?? $doctor->nombre ?? 'N/A' }} {{ $doctor->apellido ?? '' }}</div>
                        <div style="font-size: 9px; color: #666;">{{ $doctor->email ?? 'N/A' }}</div>
                    @endif
                </td>
                <td style="font-weight: bold; text-align: center;">
                    @if(is_array($doctor))
                        {{ $doctor['cantidad'] ?? 0 }}
                    @else
                        {{ $doctor->count ?? $doctor->cantidad ?? 0 }}
                    @endif
                </td>
                <td style="text-align: center;">
                    @php
                        $porcentaje = 0;
                        if (is_array($doctor)) {
                            $porcentaje = $doctor['porcentaje'] ?? 0;
                        } else {
                            $porcentaje = $doctor->percentage ?? $doctor->porcentaje ?? 0;
                        }
                    @endphp
                    <span style="font-weight: bold;">{{ $porcentaje }}%</span>
                </td>
                <td style="text-align: center;">
                    @if($porcentaje > 30)
                        <span style="color: #27ae60; font-weight: bold;">Muy Alta</span>
                    @elseif($porcentaje > 15)
                        <span style="color: #f39c12; font-weight: bold;">Alta</span>
                    @else
                        <span style="color: #666;">Normal</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- SALTO DE PÁGINA -->
<div class="page-break"></div>

<!-- PÁGINA 5: RESUMEN EJECUTIVO FINAL -->
<div style="margin-bottom: 30px; background: #f0f0f0; padding: 20px;">
    <h2 style="color: #1a1a1a; font-size: 20px; margin-bottom: 20px; text-align: center; border-bottom: 2px solid #2c3e50; padding-bottom: 12px; font-weight: bold;">
        RESUMEN EJECUTIVO CONSOLIDADO
    </h2>
    
    <table style="margin-bottom: 20px;">
        <thead>
            <tr>
                <th style="text-align: left; font-size: 12px;">Indicador Clave</th>
                <th style="text-align: center; font-size: 12px;">Valor</th>
                <th style="text-align: center; font-size: 12px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: bold;">Total de Solicitudes Procesadas</td>
                <td style="text-align: center; font-weight: bold;">{{ $totalRequests ?? 0 }}</td>
                <td style="text-align: center; color: #27ae60; font-weight: bold;">Activo</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Tasa de Completacion</td>
                <td style="text-align: center; font-weight: bold;">{{ ($totalRequests ?? 0) > 0 ? round((($completedCount ?? 0) / ($totalRequests ?? 1)) * 100, 1) : 0 }}%</td>
                <td style="text-align: center; font-weight: bold;">
                    @if((($totalRequests ?? 0) > 0 ? round((($completedCount ?? 0) / ($totalRequests ?? 1)) * 100, 1) : 0) > 80)
                        <span style="color: #27ae60;">Excelente</span>
                    @elseif((($totalRequests ?? 0) > 0 ? round((($completedCount ?? 0) / ($totalRequests ?? 1)) * 100, 1) : 0) > 60)
                        <span style="color: #f39c12;">Bueno</span>
                    @else
                        <span style="color: #e74c3c;">Mejorable</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Total de Pacientes Atendidos</td>
                <td style="text-align: center; font-weight: bold;">{{ $totalPatients ?? 0 }}</td>
                <td style="text-align: center; color: #27ae60; font-weight: bold;">Activo</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Examenes Realizados</td>
                <td style="text-align: center; font-weight: bold;">{{ $totalExams ?? 0 }}</td>
                <td style="text-align: center; color: #27ae60; font-weight: bold;">Activo</td>
            </tr>
            <tr style="background: #e8f4fd;">
                <td style="font-weight: bold;">Promedio Examenes por Solicitud</td>
                <td style="text-align: center; font-weight: bold;">{{ ($totalRequests ?? 0) > 0 ? round(($totalExams ?? 0) / ($totalRequests ?? 1), 1) : 0 }}</td>
                <td style="text-align: center; color: #3498db; font-weight: bold;">Optimo</td>
            </tr>
        </tbody>
    </table>
    
    <div style="margin-top: 25px; padding: 15px; background: white; border-left: 4px solid #2c3e50;">
        <h3 style="color: #1a1a1a; font-size: 14px; margin-bottom: 10px; font-weight: bold;">OBSERVACIONES GENERALES</h3>
        <ul style="color: #1a1a1a; font-size: 11px; line-height: 1.5;">
            <li>El laboratorio mantiene un nivel de actividad constante durante el periodo analizado.</li>
            <li>La distribucion de examenes por solicitud se encuentra dentro de parametros normales.</li>
            <li>Se recomienda continuar con el seguimiento de indicadores de calidad.</li>
            <li>Los tiempos de procesamiento muestran una tendencia positiva.</li>
        </ul>
    </div>
    
    <div style="margin-top: 20px; text-align: center; font-size: 10px; color: #666;">
        <p>--- Fin del Reporte ---</p>
        <p>Laboratorio Clinico Laredo - Sistema de Gestion de Resultados</p>
        <p>Generado automaticamente el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</div>

    </div>
</body>
</html>
