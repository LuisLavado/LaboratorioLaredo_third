<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte Detallado de Servicios - Laboratorio Clínico Laredo' }}</title>
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
        html { overflow: hidden; }
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
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
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
        .stat-label { font-weight: bold; color: #495057; }
        .stat-value { color: #2563eb; font-weight: bold; }
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
        .data-table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .no-break { page-break-inside: avoid; break-inside: avoid; }
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
        @media print {
            body { font-size: 10px; padding: 10px; overflow: hidden; }
            html { overflow: hidden; }
            .main-header { margin-bottom: 15px; }
            .period-info { margin: 15px 0; }
            @page { margin: 10mm; size: A4; }
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
        <h2>{{ $title ?? 'Reporte por Servicios' }}</h2>
    </div>
    <!-- Información del Período -->
    <div class="period-info">
       Período: {{ $startDate ? $startDate->format('d/m/Y') : 'N/A' }} al {{ $endDate ? $endDate->format('d/m/Y') : 'N/A' }} <br>
        Generado el: {{ $generatedAt ?? now()->format('d/m/Y H:i:s') }}
    </div>
    <!-- Resumen Ejecutivo -->
    <div class="executive-summary no-break">
        <div class="summary-title">RESUMEN EJECUTIVO</div>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total de Servicios:</span>
                <span class="stat-value">{{ number_format($totalServices ?? 0) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de Solicitudes:</span>
                <span class="stat-value">{{ number_format($totalRequests ?? 0) }}</span>
            </div>
        </div>
    </div>

    <!-- Lista de Servicios -->
    @if(isset($serviceStats) && count($serviceStats) > 0)
    <div class="section no-break">
        <div class="section-header">SERVICIOS REGISTRADOS ({{ count($serviceStats) }} servicios)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Posición</th>
                    <th style="width: 50%;">Nombre del Servicio</th>
                    <th style="width: 20%;">Solicitudes</th>
                    <th style="width: 20%;">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serviceStats as $index => $service)
                <tr>
                    <td class="text-center font-bold">{{ $index + 1 }}</td>
                    <td>{{ $service->name ?? 'Sin nombre' }}</td>
                    <td class="text-center">{{ number_format($service->count ?? 0) }}</td>
                    <td class="text-center">{{ number_format($service->percentage ?? 0, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="section no-break">
        <div class="section-header">SERVICIOS REGISTRADOS</div>
        <p>No hay datos de servicios disponibles.</p>
        <p>Debug: serviceStats existe = {{ isset($serviceStats) ? 'SÍ' : 'NO' }}</p>
        @if(isset($serviceStats))
        <p>Debug: cantidad = {{ count($serviceStats) }}</p>
        @endif
    </div>
    @endif

    <!-- Top 10 Servicios Más Utilizados -->
    @if(isset($serviceStats) && count($serviceStats) > 0)
    <div class="section no-break">
        <div class="section-header">TOP 10 SERVICIOS MÁS UTILIZADOS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Ranking</th>
                    <th style="width: 60%;">Servicio</th>
                    <th style="width: 15%;">Solicitudes</th>
                    <th style="width: 15%;">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $topServices = collect($serviceStats)->sortByDesc('count')->take(10);
                @endphp
                @foreach($topServices as $index => $service)
                <tr>
                    <td class="text-center font-bold">{{ $index + 1 }}</td>
                    <td>{{ $service->name ?? 'Sin nombre' }}</td>
                    <td class="text-center">{{ number_format($service->count ?? 0) }}</td>
                    <td class="text-center">{{ number_format($service->percentage ?? 0, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Resumen de Totales -->
    <div class="section no-break">
        <div class="section-header">RESUMEN DE TOTALES</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Métrica</th>
                    <th style="width: 30%;">Valor</th>
                    <th style="width: 30%;">Descripción</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-bold">Total de Servicios</td>
                    <td class="text-center">{{ number_format($totalServices ?? 0) }}</td>
                    <td>Servicios con actividad en el período</td>
                </tr>
                <tr>
                    <td class="font-bold">Total de Solicitudes</td>
                    <td class="text-center">{{ number_format($totalRequests ?? 0) }}</td>
                    <td>Solicitudes procesadas</td>
                </tr>
                <tr>
                    <td class="font-bold">Total de Pacientes</td>
                    <td class="text-center">{{ number_format($totalPatients ?? 0) }}</td>
                    <td>Pacientes únicos atendidos</td>
                </tr>
                <tr>
                    <td class="font-bold">Total de Exámenes</td>
                    <td class="text-center">{{ number_format($totalExams ?? 0) }}</td>
                    <td>Exámenes realizados</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Información del Reporte -->
    <div class="report-info no-break">
        <div class="report-info-title">INFORMACIÓN DEL REPORTE</div>
        <div><strong>Período:</strong> {{ $startDate ? $startDate->format('d/m/Y') : 'N/A' }} al {{ $endDate ? $endDate->format('d/m/Y') : 'N/A' }}</div>
        <div><strong>Generado el:</strong> {{ $generatedAt ?? now()->format('d/m/Y H:i:s') }}</div>
        <div><strong>Generado por:</strong> {{ $generatedBy ?? 'Sistema' }}</div>
        <div><strong>Tipo de reporte:</strong> Servicios Detallado</div>
        <div><strong>Total de servicios:</strong> {{ number_format($totalServices ?? 0) }}</div>
        <div><strong>Total de solicitudes:</strong> {{ number_format($totalRequests ?? 0) }}</div>
        <div><strong>Total de exámenes:</strong> {{ number_format($totalExams ?? 0) }}</div>
        <div><strong>Total de pacientes:</strong> {{ number_format($totalPatients ?? 0) }}</div>
    </div>
</body>
</html>
