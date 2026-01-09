<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte de Servicios' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .header {
            background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .header .subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        .header .period {
            margin-top: 10px;
            font-size: 12px;
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
            padding: 0 10px;
        }

        .summary-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .summary-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #1976D2;
            margin-bottom: 5px;
        }

        .summary-card .label {
            font-size: 10px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section {
            margin-bottom: 25px;
            padding: 0 10px;
        }

        .section-title {
            background: #2E7D32;
            color: white;
            padding: 12px 15px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .table th {
            background: #37474F;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            border: 1px solid #455A64;
        }

        .table td {
            padding: 8px;
            border: 1px solid #e0e0e0;
            font-size: 10px;
        }

        .table tr:nth-child(even) {
            background: #f5f5f5;
        }

        .table tr:hover {
            background: #e3f2fd;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-success {
            background: #4CAF50;
            color: white;
        }

        .badge-warning {
            background: #FF9800;
            color: white;
        }

        .badge-danger {
            background: #F44336;
            color: white;
        }

        .badge-info {
            background: #2196F3;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .footer {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-top: 2px solid #1976D2;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        .performance-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .performance-excellent { background: #4CAF50; }
        .performance-good { background: #8BC34A; }
        .performance-average { background: #FFC107; }
        .performance-poor { background: #F44336; }

        @media print {
            .header {
                margin-bottom: 15px;
            }
            
            .summary-grid {
                margin-bottom: 20px;
            }
            
            .section {
                page-break-inside: avoid;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $title ?? 'Reporte de Servicios' }}</h1>
        <div class="subtitle">Sistema de Laboratorio Clínico</div>
        <div class="period">
            Período: {{ $startDate ?? 'N/A' }} - {{ $endDate ?? 'N/A' }}
        </div>
    </div>

    <!-- Resumen Estadístico -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="number">{{ $totalRequests ?? 0 }}</div>
            <div class="label">Total Solicitudes</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ $totalPatients ?? 0 }}</div>
            <div class="label">Pacientes Únicos</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ count($serviceStats ?? []) }}</div>
            <div class="label">Servicios Activos</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ $totalExams ?? 0 }}</div>
            <div class="label">Exámenes Realizados</div>
        </div>
    </div>

    <!-- Lista de Servicios -->
    @if(!empty($serviceStats) && count($serviceStats) > 0)
    <div class="section">
        <div class="section-title">📋 Lista de Servicios por Demanda</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Nombre del Servicio</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Participación</th>
                    <th class="text-center">Performance</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serviceStats as $index => $service)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $service->name ?? $service->nombre ?? 'Sin nombre' }}</td>
                    <td class="text-center">{{ $service->count ?? 0 }}</td>
                    <td class="text-center">{{ round((($service->count ?? 0) / max($totalRequests, 1)) * 100, 1) }}%</td>
                    <td class="text-center">
                        @php
                            $count = $service->count ?? 0;
                            if ($count > 50) {
                                $performance = 'excellent';
                                $badge = 'success';
                                $text = 'Excelente';
                            } elseif ($count > 20) {
                                $performance = 'good';
                                $badge = 'info';
                                $text = 'Bueno';
                            } elseif ($count > 5) {
                                $performance = 'average';
                                $badge = 'warning';
                                $text = 'Promedio';
                            } else {
                                $performance = 'poor';
                                $badge = 'danger';
                                $text = 'Bajo';
                            }
                        @endphp
                        <span class="performance-indicator performance-{{ $performance }}"></span>
                        <span class="badge badge-{{ $badge }}">{{ $text }}</span>
                    </td>
                    <td class="text-center">
                        @if(($service->count ?? 0) > 0)
                            <span class="badge badge-success">Activo</span>
                        @else
                            <span class="badge badge-danger">Inactivo</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Exámenes por Servicio -->
    @if(!empty($topExamsByService))
    <div class="section">
        <div class="section-title">🔬 Exámenes Más Solicitados por Servicio</div>
        @foreach($topExamsByService as $serviceId => $exams)
            @if(!empty($exams) && count($exams) > 0)
                @php
                    $serviceName = collect($serviceStats ?? [])->firstWhere('id', $serviceId)->name ?? 'Servicio #' . $serviceId;
                @endphp
                <h4 style="margin: 15px 0 10px 0; color: #1976D2; font-size: 12px;">{{ $serviceName }}</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Examen</th>
                            <th>Categoría</th>
                            <th class="text-center">Solicitudes</th>
                            <th class="text-center">% del Servicio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exams as $exam)
                        <tr>
                            <td>{{ $exam->name ?? 'Sin nombre' }}</td>
                            <td>{{ $exam->category ?? 'General' }}</td>
                            <td class="text-center">{{ $exam->count ?? 0 }}</td>
                            <td class="text-center">
                                @php
                                    $serviceTotal = collect($serviceStats ?? [])->firstWhere('id', $serviceId)->count ?? 1;
                                    $percentage = round((($exam->count ?? 0) / $serviceTotal) * 100, 1);
                                @endphp
                                {{ $percentage }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    </div>
    @endif

    <!-- Mensaje si no hay datos -->
    @if(empty($serviceStats) || count($serviceStats) == 0)
    <div class="no-data">
        <h3>📊 No hay datos de servicios disponibles</h3>
        <p>No se encontraron servicios con actividad en el período seleccionado ({{ $startDate ?? 'N/A' }} - {{ $endDate ?? 'N/A' }}).</p>
        <p>Verifique:</p>
        <ul style="list-style: none; margin-top: 10px;">
            <li>• Las fechas del período de consulta</li>
            <li>• La configuración de servicios en el sistema</li>
            <li>• Los filtros aplicados al reporte</li>
        </ul>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div>
            <strong>Laboratorio Clínico - Reporte de Servicios</strong><br>
            Generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i:s') }}
            @if(!empty($generatedBy))
                por {{ $generatedBy }}
            @endif
        </div>
        <div style="margin-top: 8px; font-size: 9px; color: #999;">
            Este reporte contiene información confidencial del laboratorio
        </div>
    </div>
</body>
</html>
