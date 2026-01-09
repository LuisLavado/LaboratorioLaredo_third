<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte de Pacientes Detallado - Laboratorio Clínico Laredo' }}</title>
    <meta name="description" content="Reporte Detallado de Pacientes del Laboratorio Clínico Laredo">
    <meta name="author" content="Laboratorio Clínico Laredo">
    <meta name="subject" content="Reporte de Actividad por Pacientes">
    <meta name="keywords" content="laboratorio, pacientes, análisis, clínico, solicitudes">
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
            margin-bottom: 15px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #4472c4;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        /* Secciones */
        .section {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-header {
            background: #6c757d;
            color: white;
            padding: 12px 15px;
            border-radius: 8px 8px 0 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .section-content {
            padding: 15px;
        }
        
        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        th, td {
            border: 1px solid #dee2e6;
            padding: 6px;
            text-align: left;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
            text-align: center;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        /* Estados y badges */
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pendiente {
            background: #ffc107;
            color: #212529;
        }
        
        .status-completado {
            background: #28a745;
            color: white;
        }
        
        .status-en-proceso {
            background: #17a2b8;
            color: white;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            color: #666;
            font-size: 9px;
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
        
        /* Evitar saltos de página en elementos importantes */
        .no-break {
            page-break-inside: avoid;
        }
        
        /* Gráficos de distribución */
        .distribution-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .mini-chart {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
        }
        
        .chart-value {
            font-size: 16px;
            font-weight: bold;
            color: #4472c4;
        }
        
        .chart-label {
            font-size: 8px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <!-- Encabezado principal -->
    <div class="main-header">
        <h1>{{ $title ?? 'Reporte de Pacientes Detallado' }}</h1>
        <div class="subtitle">Sistema de Laboratorio Clínico Laredo</div>
        <div class="subtitle">Análisis Detallado de Actividad de Pacientes</div>
    </div>

    <!-- Información del período -->
    <div class="period-info">
        Período de Análisis: {{ $startDate->format('d/m/Y') ?? 'N/A' }} - {{ $endDate->format('d/m/Y') ?? 'N/A' }}
        @if(isset($generatedBy))
        | Generado por: {{ $generatedBy }}
        @endif
        | Fecha: {{ date('d/m/Y H:i:s') }}
    </div>

    <!-- Título del reporte -->
    <div class="report-title-block">
        <h2>Reporte Detallado de Pacientes</h2>
    </div>

    <!-- Resumen ejecutivo -->
    <div class="executive-summary no-break">
        <div class="summary-title">RESUMEN EJECUTIVO</div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ $totalPatients ?? 0 }}</div>
                <div class="stat-label">Total Pacientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $totalRequests ?? 0 }}</div>
                <div class="stat-label">Total Solicitudes</div>
            </div>

        </div>

        <!-- Estados de exámenes -->
        @if(isset($pendingCount) || isset($inProcessCount) || isset($completedCount))
        <div class="distribution-grid">
            <div class="mini-chart">
                <div class="chart-value">{{ $pendingCount ?? 0 }}</div>
                <div class="chart-label">Pendientes</div>
            </div>
            <div class="mini-chart">
                <div class="chart-value">{{ $inProcessCount ?? 0 }}</div>
                <div class="chart-label">En Proceso</div>
            </div>
            <div class="mini-chart">
                <div class="chart-value">{{ $completedCount ?? 0 }}</div>
                <div class="chart-label">Completados</div>
            </div>
        </div>
        @endif
    </div>

    <!-- Distribución por género -->
    @if(isset($genderStats) && count($genderStats) > 0)
    <div class="section no-break">
        <div class="section-header">Distribución por Género</div>
        <div class="section-content">
            <div class="distribution-grid">
                @foreach($genderStats as $gender)
                <div class="mini-chart">
                    <div class="chart-value">{{ $gender['count'] ?? $gender->count ?? 0 }}</div>
                    <div class="chart-label">{{ ucfirst($gender['name'] ?? $gender->name ?? $gender->sexo ?? 'No definido') }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Distribución por edad -->
    @if(isset($ageStats) && count($ageStats) > 0)
    <div class="section no-break">
        <div class="section-header">Distribución por Grupos de Edad</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Grupo de Edad</th>
                        <th>Cantidad</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalAge = collect($ageStats)->sum('count');
                    @endphp
                    @foreach($ageStats as $age)
                    <tr>
                        <td>{{ $age['name'] ?? $age->name ?? 'No definido' }}</td>
                        <td class="text-center">{{ $age['count'] ?? $age->count ?? 0 }}</td>
                        <td class="text-center">
                            {{ $totalAge > 0 ? number_format((($age['count'] ?? $age->count ?? 0) / $totalAge) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Top pacientes más activos -->
    @if(isset($topPatients) && count($topPatients) > 0)
    <div class="section no-break">
        <div class="section-header">Pacientes con Mayor Actividad</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Ranking</th>
                        <th>Paciente</th>
                        <th>Solicitudes</th>
                        <th>Promedio Mensual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topPatients as $index => $patient)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $patient['name'] ?? $patient->name ?? 'Sin nombre' }}</td>
                        <td class="text-center">{{ $patient['count'] ?? $patient->count ?? 0 }}</td>
                        <td class="text-center">
                            {{ number_format(($patient['count'] ?? $patient->count ?? 0) / max(1, (strtotime($endDate ?? 'now') - strtotime($startDate ?? '30 days ago')) / (30 * 24 * 3600)), 1) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Estados de exámenes detallado -->
    @if(isset($examStatusStats) && count($examStatusStats) > 0)
    <div class="section no-break">
        <div class="section-header">Estado de Exámenes por Paciente</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Cantidad</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalExamStatus = collect($examStatusStats)->sum('count');
                    @endphp
                    @foreach($examStatusStats as $status)
                    <tr>
                        <td>
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $status['name'] ?? $status->name ?? '')) }}">
                                {{ $status['name'] ?? $status->name ?? 'No definido' }}
                            </span>
                        </td>
                        <td class="text-center">{{ $status['count'] ?? $status->count ?? 0 }}</td>
                        <td class="text-center">
                            {{ $totalExamStatus > 0 ? number_format((($status['count'] ?? $status->count ?? 0) / $totalExamStatus) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Lista detallada de pacientes -->
    @if(isset($patients) && count($patients) > 0)
    <div class="section">
        <div class="section-header">Lista Detallada de Pacientes</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>Documento</th>
                        <th>Edad</th>
                        <th>Sexo</th>
                        <th>Solicitudes</th>
                        <th>Exámenes</th>
                        <th>Última Visita</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patients->take(50) as $patient)
                    <tr>
                        <td>
                            @php
                                $patientName = trim(($patient->nombres ?? '') . ' ' . ($patient->apellidos ?? ''));
                                if (empty($patientName)) {
                                    $patientName = 'Paciente ID: ' . $patient->id;
                                }
                            @endphp
                            {{ $patientName }}
                        </td>
                        <td class="text-center">{{ $patient->documento ?? $patient->dni ?? 'Sin documento' }}</td>
                        <td class="text-center">{{ $patient->edad ?? 'Sin edad' }}</td>
                        <td class="text-center">{{ ucfirst($patient->sexo ?? $patient->gender ?? 'No especificado') }}</td>
                        <td class="text-center">{{ $patient->total_solicitudes ?? $patient->requests_count ?? 0 }}</td>
                        <td class="text-center">{{ $patient->total_examenes ?? $patient->exams_count ?? 0 }}</td>
                        <td class="text-center">
                            {{ $patient->ultima_visita ? date('d/m/Y', strtotime($patient->ultima_visita)) : 'Sin visitas' }}
                        </td>
                        <td class="text-center">
                            @if(($patient->examenes_pendientes ?? 0) > 0)
                                <span class="status-badge status-pendiente">Con Pendientes</span>
                            @else
                                <span class="status-badge status-completado">Al Día</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            @if(count($patients) > 50)
            <p style="text-align: center; color: #666; font-style: italic; margin-top: 10px;">
                Se muestran los primeros 50 pacientes de {{ count($patients) }} total.
            </p>
            @endif
        </div>
    </div>
    @endif

    <!-- Estadísticas generales de pacientes -->
    @if(isset($patientStats) && count($patientStats) > 0)
    @php 
        \Log::info('Paciente en estadísticas generales', [
            'patientStats' => $patientStats,
        ]);
    @endphp
    <div class="section no-break">
        <div class="section-header">Estadísticas Adicionales de Pacientes</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>Documento</th>
                        <th>Total Solicitudes</th>
                        <th>Última Visita</th>
                        <th>Actividad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patientStats as $stat)
                    <tr>
                        <td>
                            @php
                                // Primero intentar con el name del array, luego con nombres/apellidos del objeto
                                $statName = '';
                                if (isset($stat['name'])) {
                                    $statName = $stat['name'];
                                } else {
                                    $statName = trim(($stat->nombres ?? $stat['nombres'] ?? '') . ' ' . ($stat->apellidos ?? $stat['apellidos'] ?? ''));
                                }
                                if (empty($statName)) {
                                    $statName = 'Paciente sin nombre';
                                }
                            @endphp
                            {{ $statName }}
                        </td>

                        <td class="text-center">{{ $stat->documento ?? $stat['documento'] ?? $stat->dni ?? $stat['dni'] ?? 'Sin documento' }}</td>
                        <td class="text-center">{{ $stat->total_solicitudes ?? $stat['total_solicitudes'] ?? $stat->count ?? $stat['count'] ?? 0 }}</td>
                        <td class="text-center">
                            @php
                                $ultimaVisita = $stat->ultima_visita ?? $stat['ultima_visita'] ?? null;
                            @endphp
                            {{ $ultimaVisita ? date('d/m/Y', strtotime($ultimaVisita)) : 'Sin visitas' }}
                        </td>
                        <td class="text-center">
                            @php
                                $count = $stat->total_solicitudes ?? $stat['total_solicitudes'] ?? $stat->count ?? $stat['count'] ?? 0;
                                $activity = $count >= 10 ? 'Alta' : ($count >= 5 ? 'Media' : 'Baja');
                                $class = $count >= 10 ? 'completado' : ($count >= 5 ? 'en-proceso' : 'pendiente');
                            @endphp
                            <span class="status-badge status-{{ $class }}">{{ $activity }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Resultados Detallados por Paciente -->
    @if(isset($patientStats) && count($patientStats) > 0)
    <div class="section no-break" style="page-break-before: always;">
        <div class="section-header">RESULTADOS DETALLADOS POR PACIENTE</div>

        @foreach($patientStats as $index => $patient)
            @php
                // Obtener información del paciente
                $patientName = $patient['name'] ?? 'Paciente sin nombre';
                $resultadosDetallados = $patient['resultados_detallados'] ?? [];
            @endphp

            <div class="patient-results" style="margin-bottom: 30px; {{ $index > 0 ? 'page-break-before: always;' : '' }}">
                <h3 style="background: #1f4e79; color: white; padding: 8px 15px; margin: 15px 0 10px 0; font-size: 14px;">
                    {{ $patientName }} - Resultados de Exámenes
                </h3>

                @if(!empty($resultadosDetallados))
                <table style="width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 20px;">
                    <thead>
                        <tr style="background: #dae3f3; color: #1f4e79;">
                            <th style="border: 1px solid #4472c4; padding: 6px; text-align: center; font-weight: bold;">Fecha</th>
                            <th style="border: 1px solid #4472c4; padding: 6px; text-align: center; font-weight: bold;">Solicitud</th>
                            <th style="border: 1px solid #4472c4; padding: 6px; text-align: center; font-weight: bold;">Examen</th>
                            <th style="border: 1px solid #4472c4; padding: 6px; text-align: center; font-weight: bold;">Campo/Tipo</th>
                            <th style="border: 1px solid #4472c4; padding: 6px; text-align: center; font-weight: bold;">Resultado</th>
                            <th style="border: 1px solid #4472c4; padding: 6px; text-align: center; font-weight: bold;">Referencia</th>
                            <th style="border: 1px solid #4472c4; padding: 6px; text-align: center; font-weight: bold;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultadosDetallados as $resultado)
                        @php
                            // Procesar datos con validación para evitar caracteres especiales
                            $fecha = 'Sin fecha';
                            if (!empty($resultado['fecha'])) {
                                try {
                                    $fecha = \Carbon\Carbon::parse($resultado['fecha'])->format('d/m/Y');
                                } catch (\Exception $e) {
                                    $fecha = 'Fecha inválida';
                                }
                            }

                            // Limpiar y validar datos de texto
                            $numeroRecibo = !empty($resultado['numero_recibo']) ?
                                           htmlspecialchars($resultado['numero_recibo'], ENT_QUOTES, 'UTF-8') :
                                           (!empty($resultado['solicitud_id']) ?
                                            htmlspecialchars($resultado['solicitud_id'], ENT_QUOTES, 'UTF-8') :
                                            'S/N');

                            $examenNombre = !empty($resultado['examen_nombre']) ?
                                           htmlspecialchars($resultado['examen_nombre'], ENT_QUOTES, 'UTF-8') :
                                           'Sin especificar';

                            $campoNombre = !empty($resultado['campo_nombre']) ?
                                          htmlspecialchars($resultado['campo_nombre'], ENT_QUOTES, 'UTF-8') :
                                          'Resultado general';

                            $resultadoValor = !empty($resultado['resultado_valor']) ?
                                             htmlspecialchars($resultado['resultado_valor'], ENT_QUOTES, 'UTF-8') :
                                             (!empty($resultado['resultado_directo']) ?
                                              htmlspecialchars($resultado['resultado_directo'], ENT_QUOTES, 'UTF-8') :
                                              'Pendiente');

                            $valorReferencia = !empty($resultado['valor_referencia']) ?
                                              htmlspecialchars($resultado['valor_referencia'], ENT_QUOTES, 'UTF-8') :
                                              'Sin referencia';

                            $unidad = !empty($resultado['unidad']) ?
                                     ' ' . htmlspecialchars($resultado['unidad'], ENT_QUOTES, 'UTF-8') :
                                     '';

                            // Formatear estado con validación
                            $estadoRaw = $resultado['estado_examen'] ?? '';
                            $estado = 'SIN ESTADO';
                            switch (strtolower(trim($estadoRaw))) {
                                case 'completado':
                                case 'normal':
                                    $estado = 'COMPLETADO';
                                    break;
                                case 'pendiente':
                                    $estado = 'PENDIENTE';
                                    break;
                                case 'en_proceso':
                                case 'en proceso':
                                    $estado = 'EN PROCESO';
                                    break;
                                default:
                                    if (!empty($estadoRaw)) {
                                        $estado = strtoupper(htmlspecialchars(trim($estadoRaw), ENT_QUOTES, 'UTF-8'));
                                    }
                            }
                        @endphp
                        <tr style="{{ $loop->even ? 'background-color: #f8f9fa;' : '' }}">
                            <td style="border: 1px solid #4472c4; padding: 4px; text-align: center; font-size: 9px;">{!! $fecha !!}</td>
                            <td style="border: 1px solid #4472c4; padding: 4px; text-align: center; font-size: 9px;">{!! $numeroRecibo !!}</td>
                            <td style="border: 1px solid #4472c4; padding: 4px; font-size: 9px; word-wrap: break-word;">{!! $examenNombre !!}</td>
                            <td style="border: 1px solid #4472c4; padding: 4px; font-size: 9px; word-wrap: break-word;">{!! $campoNombre !!}</td>
                            <td style="border: 1px solid #4472c4; padding: 4px; text-align: center; font-size: 9px; font-weight: bold;">{!! $resultadoValor !!}{!! $unidad !!}</td>
                            <td style="border: 1px solid #4472c4; padding: 4px; text-align: center; font-size: 9px;">{!! $valorReferencia !!}</td>
                            <td style="border: 1px solid #4472c4; padding: 4px; text-align: center; font-size: 9px; font-weight: bold;
                                @if($estado === 'COMPLETADO') color: #28a745;
                                @elseif($estado === 'PENDIENTE') color: #ffc107;
                                @elseif($estado === 'EN PROCESO') color: #007bff;
                                @endif">{!! $estado !!}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div style="text-align: center; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; margin: 10px 0;">
                    <p style="color: #6c757d; font-style: italic; margin: 0; font-size: 11px;">
                        📋 No se encontraron resultados de exámenes para este paciente en el período especificado.
                    </p>
                    <p style="color: #6c757d; font-size: 10px; margin: 5px 0 0 0;">
                        Período: {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}
                    </p>
                </div>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p><strong>Laboratorio Clínico Laredo</strong> - Reporte Detallado de Pacientes</p>
        <p>Generado el {{ date('d/m/Y') }} a las {{ date('H:i:s') }} | Página 1</p>
        <p>Este reporte contiene información confidencial del laboratorio</p>
    </div>
</body>
</html>
