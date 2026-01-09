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
    </style>
</head>
<body>
    <div class="container">
@php
    // Manejo seguro de fechas sin caracteres especiales
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
    
    // Funciones auxiliares para valores seguros
    function safeValue($value, $default = 0) {
        return isset($value) ? $value : $default;
    }
    
    function safeDivision($numerator, $denominator, $decimals = 1) {
        return $denominator > 0 ? round($numerator / $denominator, $decimals) : 0;
    }
    
    function safePercentage($value, $total) {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0;
    }
@endphp

<!-- PÁGINA 1: PORTADA Y ESTADÍSTICAS PRINCIPALES -->
<div style="text-align: center; margin-bottom: 25px; padding: 20px; background: #2c3e50; color: white;">
    <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: white;">LABORATORIO CLINICO LAREDO</h1>
    <h2 style="margin: 12px 0 0 0; font-size: 18px; color: white;">REPORTE GENERAL EJECUTIVO</h2>
    <p style="margin: 12px 0 0 0; font-size: 14px; color: white;">
        Período: {{ $startDateFormatted }} - {{ $endDateFormatted }}
    </p>
    <p style="margin: 8px 0 0 0; font-size: 12px; color: white;">
        Generado el: {{ now()->format('d/m/Y H:i:s') }}
    </p>
</div>

<!-- Estadísticas Principales -->
<div style="margin-bottom: 30px;">
    <h2 style="color: #1a1a1a; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">
        ESTADISTICAS PRINCIPALES
    </h2>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
        <div style="padding: 15px; background: #f0f0f0; border-left: 4px solid #2c3e50; text-align: center;">
            <div style="font-size: 28px; font-weight: bold; color: #1a1a1a;">{{ safeValue($stats['total_solicitudes'] ?? $totalRequests, 0) }}</div>
            <div style="font-size: 14px; color: #4a4a4a; margin-top: 4px;">Total Solicitudes</div>
        </div>
        <div style="padding: 15px; background: #f0f0f0; border-left: 4px solid #e74c3c; text-align: center;">
            <div style="font-size: 28px; font-weight: bold; color: #1a1a1a;">{{ safeValue($stats['pendientes'] ?? $pendingCount, 0) }}</div>
            <div style="font-size: 14px; color: #4a4a4a; margin-top: 4px;">Pendientes</div>
        </div>
        <div style="padding: 15px; background: #f0f0f0; border-left: 4px solid #f39c12; text-align: center;">
            <div style="font-size: 28px; font-weight: bold; color: #1a1a1a;">{{ safeValue($stats['en_proceso'] ?? $inProcessCount, 0) }}</div>
            <div style="font-size: 14px; color: #4a4a4a; margin-top: 4px;">En Proceso</div>
        </div>
        <div style="padding: 15px; background: #f0f0f0; border-left: 4px solid #27ae60; text-align: center;">
            <div style="font-size: 28px; font-weight: bold; color: #1a1a1a;">{{ safeValue($stats['completadas'] ?? $completedCount, 0) }}</div>
            <div style="font-size: 14px; color: #4a4a4a; margin-top: 4px;">Completadas</div>
        </div>
    </div>
</div>

<!-- Información General del Período -->
<div style="margin-bottom: 30px;">
    <h2 style="color: #1a1a1a; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">
        INFORMACION GENERAL DEL PERIODO
    </h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
        <div>
            <h3 style="color: #1a1a1a; font-size: 16px; margin-bottom: 10px;">ESTADISTICAS BASICAS</h3>
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
                        <td style="text-align: right; font-weight: bold;">{{ safeValue($stats['total_solicitudes'] ?? $totalRequests, 0) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Total de Pacientes:</td>
                        <td style="text-align: right; font-weight: bold;">{{ safeValue($totalPatients, 0) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Total de Examenes:</td>
                        <td style="text-align: right; font-weight: bold;">{{ safeValue($totalExams, 0) }}</td>
                    </tr>
                    <tr style="background: #e8f4fd;">
                        <td style="font-weight: bold;">Promedio Examenes/Solicitud:</td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ safeDivision(safeValue($totalExams, 0), safeValue($totalRequests, 1)) }}
                        </td>
                    </tr>
                    <tr style="background: #e8f5e8;">
                        <td style="font-weight: bold;">Promedio Examenes/Paciente:</td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ safeDivision(safeValue($totalExams, 0), safeValue($totalPatients, 1)) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div>
            <h3 style="color: #1a1a1a; font-size: 16px; margin-bottom: 10px;">DISTRIBUCION POR ESTADO</h3>
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
                            {{ safeValue($completedCount, 0) }} 
                            ({{ safePercentage(safeValue($completedCount, 0), safeValue($totalRequests, 1)) }}%)
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #f39c12; font-weight: bold;">En Proceso:</td>
                        <td style="text-align: right; color: #f39c12; font-weight: bold;">
                            {{ safeValue($inProcessCount, 0) }}
                            ({{ safePercentage(safeValue($inProcessCount, 0), safeValue($totalRequests, 1)) }}%)
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #e74c3c; font-weight: bold;">Pendientes:</td>
                        <td style="text-align: right; color: #e74c3c; font-weight: bold;">
                            {{ safeValue($pendingCount, 0) }}
                            ({{ safePercentage(safeValue($pendingCount, 0), safeValue($totalRequests, 1)) }}%)
                        </td>
                    </tr>
                    <tr style="background: #f0f9ff;">
                        <td style="color: #2c3e50; font-weight: bold;">Eficiencia Global:</td>
                        <td style="text-align: right; color: #2c3e50; font-weight: bold;">
                            {{ safePercentage(safeValue($completedCount, 0), safeValue($totalRequests, 1)) }}%
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
    <h2 style="color: #1a1a1a; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">
        DETALLE DE SOLICITUDES
    </h2>
    
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
                        <div style="font-weight: bold;">{{ safeValue($solicitud->paciente->nombres ?? null, 'N/A') }} {{ safeValue($solicitud->paciente->apellidos ?? null, '') }}</div>
                        <div style="font-size: 9px; color: #666;">DNI: {{ safeValue($solicitud->paciente->dni ?? null, 'N/A') }}</div>
                    </td>
                    <td>
                        <div style="font-weight: bold;">{{ safeValue($solicitud->user->nombre ?? null, 'N/A') }} {{ safeValue($solicitud->user->apellido ?? null, '') }}</div>
                        <div style="font-size: 9px; color: #666;">{{ safeValue($solicitud->user->email ?? null, 'N/A') }}</div>
                    </td>
                    <td>
                        @if(isset($solicitud->detalles) && count($solicitud->detalles) > 0)
                            <div style="max-width: 140px;">
                                @foreach($solicitud->detalles->take(3) as $detalle)
                                    <div style="font-size: 9px; margin-bottom: 1px;">
                                        • {{ safeValue($detalle->examen->nombre ?? null, 'N/A') }}
                                    </div>
                                @endforeach
                                @if(count($solicitud->detalles) > 3)
                                    <div style="font-size: 9px; color: #666; font-style: italic;">
                                        +{{ count($solicitud->detalles) - 3 }} mas...
                                    </div>
                                @endif
                            </div>
                        @else
                            <span style="color: #666;">Sin examenes</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @php
                            $estado = safeValue($solicitud->estado_calculado ?? $solicitud->estado ?? null, 'pendiente');
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
                                $fechaStats = safeValue($stat->date ?? $stat['date'] ?? null, 'N/A');
                            }
                        @endphp
                        {{ $fechaStats }}
                    </td>
                    <td style="text-align: center;">{{ safeValue($stat->count ?? $stat['count'] ?? null, 0) }}</td>
                    <td style="text-align: center;">{{ safeValue($stat->patientCount ?? $stat['patientCount'] ?? null, 0) }}</td>
                    <td style="text-align: center;">{{ safeValue($stat->examCount ?? $stat['examCount'] ?? null, 0) }}</td>
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
    <h2 style="color: #1a1a1a; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">
        EXAMENES MAS SOLICITADOS
    </h2>
    
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
                        <span style="font-size: 14px; font-weight: bold;">
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
                        {{ safeValue($examen['codigo'] ?? $examen['id'] ?? null, 'N/A') }}
                    @else
                        {{ safeValue($examen->codigo ?? $examen->id ?? null, 'N/A') }}
                    @endif
                </td>
                <td>
                    @if(is_array($examen))
                        {{ safeValue($examen['nombre'] ?? null, 'N/A') }}
                    @else
                        {{ safeValue($examen->name ?? $examen->nombre ?? null, 'N/A') }}
                    @endif
                </td>
                <td style="font-weight: bold; text-align: center;">
                    @if(is_array($examen))
                        {{ safeValue($examen['cantidad'] ?? null, 0) }}
                    @else
                        {{ safeValue($examen->count ?? $examen->cantidad ?? null, 0) }}
                    @endif
                </td>
                <td style="text-align: center;">
                    @php
                        $porcentaje = 0;
                        if (is_array($examen)) {
                            $porcentaje = safeValue($examen['porcentaje'] ?? null, 0);
                        } else {
                            $porcentaje = safeValue($examen->percentage ?? $examen->porcentaje ?? null, 0);
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
    <h2 style="color: #1a1a1a; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">
        DOCTORES MAS ACTIVOS
    </h2>
    
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
            <tr style="border-bottom: 1px solid #ecf0f1;">
                <td style="padding: 10px; color: #2c3e50; font-weight: bold; text-align: center;">
                    @if($index < 3)
                        <span style="font-size: 16px; font-weight: bold;">
                            @if($index == 0) 1°
                            @elseif($index == 1) 2°
                            @else 3°
                            @endif
                        </span>
                    @else
                        <span style="font-weight: bold; color: #7f8c8d;">#{{ $index + 1 }}</span>
                    @endif
                </td>
                <td style="padding: 10px; color: #2c3e50; font-weight: bold;">
                    Dr. 
                    @if(is_array($doctor))
                        {{ $doctor['nombre'] ?? 'N/A' }}
                    @else
                        {{ $doctor->name ?? $doctor->nombre ?? 'N/A' }}
                    @endif
                </td>
                <td style="padding: 10px; color: #2c3e50; font-weight: bold; text-align: center;">
                    @if(is_array($doctor))
                        {{ $doctor['cantidad'] ?? 0 }}
                    @else
                        {{ $doctor->count ?? $doctor->cantidad ?? 0 }}
                    @endif
                </td>
                <td style="padding: 10px; color: #2c3e50; text-align: center;">
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
                <td style="padding: 10px; text-align: center;">
                    @if($porcentaje > 30)
                        <span style="color: #27ae60; font-weight: bold;">Muy Alta</span>
                    @elseif($porcentaje > 15)
                        <span style="color: #f39c12; font-weight: bold;">Alta</span>
                    @else
                        <span style="color: #7f8c8d;">Normal</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- SALTO DE PÁGINA -->
<div style="page-break-before: always;"></div>

<!-- PÁGINA 5: RESUMEN EJECUTIVO FINAL -->
<div style="margin-bottom: 40px; background: #ecf0f1; padding: 25px; border-radius: 8px;">
    <h2 style="color: #2c3e50; font-size: 24px; margin-bottom: 25px; text-align: center; border-bottom: 3px solid #2c3e50; padding-bottom: 15px;">
        RESUMEN EJECUTIVO CONSOLIDADO
    </h2>
    
    <!-- Tabla Principal de Resumen -->
    <table style="width: 100%; border-collapse: collapse; background: white; margin-bottom: 25px;">
        <thead>
            <tr style="background: #2c3e50;">
                <th style="padding: 15px; color: white; text-align: left; font-size: 16px;">Indicador Clave</th>
                <th style="padding: 15px; color: white; text-align: center; font-size: 16px;">Valor</th>
                <th style="padding: 15px; color: white; text-align: center; font-size: 16px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #ecf0f1;">
                <td style="padding: 12px; color: #2c3e50; font-weight: bold;">Total de Solicitudes Procesadas</td>
                <td style="padding: 12px; text-align: center; color: #3498db; font-weight: bold; font-size: 18px;">{{ $stats['total_solicitudes'] ?? $totalRequests ?? 0 }}</td>
                <td style="padding: 12px; text-align: center;">
                    @php $total = $stats['total_solicitudes'] ?? $totalRequests ?? 0; @endphp
                    @if($total > 100)
                        <span style="color: #27ae60; font-weight: bold;">EXCELENTE</span>
                    @elseif($total > 50)
                        <span style="color: #f39c12; font-weight: bold;">BUENO</span>
                    @else
                        <span style="color: #e74c3c; font-weight: bold;">BAJO</span>
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ecf0f1;">
                <td style="padding: 12px; color: #2c3e50; font-weight: bold;">Tasa de Completitud</td>
                <td style="padding: 12px; text-align: center; color: #27ae60; font-weight: bold; font-size: 18px;">
                    {{ $totalRequests > 0 ? round((($completedCount ?? 0) / $totalRequests) * 100, 1) : 0 }}%
                </td>
                <td style="padding: 12px; text-align: center;">
                    @php $completitud = $totalRequests > 0 ? round((($completedCount ?? 0) / $totalRequests) * 100, 1) : 0; @endphp
                    @if($completitud >= 80)
                        <span style="color: #27ae60; font-weight: bold;">EXCELENTE</span>
                    @elseif($completitud >= 60)
                        <span style="color: #f39c12; font-weight: bold;">ACEPTABLE</span>
                    @else
                        <span style="color: #e74c3c; font-weight: bold;">NECESITA MEJORA</span>
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ecf0f1;">
                <td style="padding: 12px; color: #2c3e50; font-weight: bold;">Total de Exámenes Realizados</td>
                <td style="padding: 12px; text-align: center; color: #8e44ad; font-weight: bold; font-size: 18px;">{{ $totalExams ?? 0 }}</td>
                <td style="padding: 12px; text-align: center;">
                    @php $examenes = $totalExams ?? 0; @endphp
                    @if($examenes > 200)
                        <span style="color: #27ae60; font-weight: bold;">ALTO</span>
                    @elseif($examenes > 100)
                        <span style="color: #f39c12; font-weight: bold;">MEDIO</span>
                    @else
                        <span style="color: #e74c3c; font-weight: bold;">BAJO</span>
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ecf0f1;">
                <td style="padding: 12px; color: #2c3e50; font-weight: bold;">Pacientes Atendidos</td>
                <td style="padding: 12px; text-align: center; color: #e91e63; font-weight: bold; font-size: 18px;">{{ $totalPatients ?? 0 }}</td>
                <td style="padding: 12px; text-align: center;">
                    @php $pacientes = $totalPatients ?? 0; @endphp
                    @if($pacientes > 50)
                        <span style="color: #27ae60; font-weight: bold;">EXCELENTE</span>
                    @elseif($pacientes > 25)
                        <span style="color: #f39c12; font-weight: bold;">BUENO</span>
                    @else
                        <span style="color: #e74c3c; font-weight: bold;">BAJO</span>
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ecf0f1;">
                <td style="padding: 12px; color: #2c3e50; font-weight: bold;">Doctores Activos</td>
                <td style="padding: 12px; text-align: center; color: #16a085; font-weight: bold; font-size: 18px;">{{ count($topDoctores ?? $doctorStats ?? []) }}</td>
                <td style="padding: 12px; text-align: center;">
                    @php $doctores = count($topDoctores ?? $doctorStats ?? []); @endphp
                    @if($doctores > 10)
                        <span style="color: #27ae60; font-weight: bold;">DIVERSO</span>
                    @elseif($doctores > 5)
                        <span style="color: #f39c12; font-weight: bold;">ADECUADO</span>
                    @else
                        <span style="color: #e74c3c; font-weight: bold;">LIMITADO</span>
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ecf0f1;">
                <td style="padding: 12px; color: #2c3e50; font-weight: bold;">Tipos de Exámenes Ofrecidos</td>
                <td style="padding: 12px; text-align: center; color: #9b59b6; font-weight: bold; font-size: 18px;">{{ count($topExamenes ?? $examStats ?? []) }}</td>
                <td style="padding: 12px; text-align: center;">
                    @php $tiposExamenes = count($topExamenes ?? $examStats ?? []); @endphp
                    @if($tiposExamenes > 15)
                        <span style="color: #27ae60; font-weight: bold;">AMPLIO</span>
                    @elseif($tiposExamenes > 8)
                        <span style="color: #f39c12; font-weight: bold;">BUENO</span>
                    @else
                        <span style="color: #e74c3c; font-weight: bold;">LIMITADO</span>
                    @endif
                </td>
            </tr>
            <tr style="background: #34495e; border-top: 2px solid #2c3e50;">
                <td style="padding: 15px; color: white; font-weight: bold; font-size: 16px;">EFICIENCIA GLOBAL DEL SISTEMA</td>
                <td style="padding: 15px; text-align: center; color: white; font-weight: bold; font-size: 20px;">
                    {{ $totalRequests > 0 ? round((($completedCount ?? 0) / $totalRequests) * 100, 1) : 0 }}%
                </td>
                <td style="padding: 15px; text-align: center; color: white; font-weight: bold;">
                    @if($completitud >= 80)
                        ÓPTIMO
                    @elseif($completitud >= 60)
                        BUENO
                    @else
                        MEJORAR
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
    
    <!-- Conclusiones -->
    <div style="padding: 20px; background: white; border-left: 5px solid #3498db; border-radius: 4px;">
        <h3 style="color: #2c3e50; margin: 0 0 15px 0; font-size: 18px;">CONCLUSIONES DEL PERÍODO</h3>
        <ul style="margin: 0; padding-left: 25px; color: #2c3e50; line-height: 1.6;">
            <li style="margin-bottom: 8px;">Se procesaron <strong>{{ $totalRequests ?? 0 }} solicitudes</strong> con una tasa de completitud del <strong>{{ $totalRequests > 0 ? round((($completedCount ?? 0) / $totalRequests) * 100, 1) : 0 }}%</strong></li>
            <li style="margin-bottom: 8px;">Se atendieron <strong>{{ $totalPatients ?? 0 }} pacientes únicos</strong> realizando <strong>{{ $totalExams ?? 0 }} exámenes</strong></li>
            <li style="margin-bottom: 8px;">Promedio de <strong>{{ $totalPatients > 0 ? round(($totalExams ?? 0) / $totalPatients, 1) : 0 }} exámenes por paciente</strong> y <strong>{{ $totalRequests > 0 ? round(($totalExams ?? 0) / $totalRequests, 1) : 0 }} por solicitud</strong></li>
            <li style="margin-bottom: 8px;">El laboratorio ofrece <strong>{{ count($topExamenes ?? $examStats ?? []) }} tipos diferentes de exámenes</strong></li>
            <li style="margin-bottom: 0;">Participaron <strong>{{ count($topDoctores ?? $doctorStats ?? []) }} doctores activos</strong> en el período</li>
        </ul>
    </div>
</div>

@endsection
