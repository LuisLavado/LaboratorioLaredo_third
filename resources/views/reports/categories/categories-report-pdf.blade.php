<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte de Categorías' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .header .subtitle {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            color: #2563eb;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #374151;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 10px;
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? 'Reporte de Categorías' }}</h1>
        <div class="subtitle">Análisis por Categorías de Exámenes</div>
        <div class="subtitle">
            Período: {{ $startDate ?? 'N/A' }} - {{ $endDate ?? 'N/A' }}
        </div>
        @if(isset($generatedBy))
        <div class="subtitle">Generado por: {{ $generatedBy }}</div>
        @endif
        <div class="subtitle">Fecha de generación: {{ date('d/m/Y H:i:s') }}</div>
    </div>

    <!-- Estadísticas principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $totalRequests ?? 0 }}</div>
            <div class="stat-label">Total Solicitudes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalPatients ?? 0 }}</div>
            <div class="stat-label">Total Pacientes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalExams ?? 0 }}</div>
            <div class="stat-label">Total Exámenes</div>
        </div>
    </div>

    <!-- Estadísticas por categoría -->
    @if(isset($categoryStats) && count($categoryStats) > 0)
    <div class="section">
        <h2 class="section-title">Estadísticas por Categoría</h2>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryStats as $category)
                <tr>
                    <td>{{ $category->name ?? $category->nombre ?? 'Sin nombre' }}</td>
                    <td class="text-center">{{ $category->count ?? 0 }}</td>
                    <td class="text-center">{{ $category->percentage ?? 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Exámenes por categoría -->
    @if(isset($topExamsByCategory) && count($topExamsByCategory) > 0)
    <div class="section">
        <h2 class="section-title">Exámenes Más Solicitados por Categoría</h2>
        @foreach($topExamsByCategory as $categoryId => $exams)
            @if(count($exams) > 0)
            <h3 style="color: #2563eb; font-size: 14px; margin-top: 20px;">{{ $exams[0]->category ?? 'Categoría ' . $categoryId }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Examen</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exams as $exam)
                    <tr>
                        <td>{{ $exam->name ?? 'Sin nombre' }}</td>
                        <td class="text-center">{{ $exam->count ?? 0 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>Reporte de Categorías del Sistema de Laboratorio</p>
        <p>{{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
