<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte' }}</title>
    <style>
        /* Base Report Generator Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }
        
        .report-header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        
        .report-header .subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .report-header .date-range {
            font-size: 12px;
            color: #888;
            font-style: italic;
        }
        
        /* Meta Information */
        .report-meta {
            background: #f8f9fa;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 0 4px 4px 0;
        }
        
        .report-meta h3 {
            font-size: 14px;
            color: #2563eb;
            margin-bottom: 10px;
        }
        
        .report-meta p {
            margin: 3px 0;
            font-size: 11px;
        }
        
        /* Table Styles */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background: #fff;
        }
        
        .report-table th {
            background: #2563eb;
            color: #fff;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #1d4ed8;
        }
        
        .report-table td {
            padding: 10px 8px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }
        
        .report-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .report-table tbody tr:hover {
            background: #f3f4f6;
        }
        
        /* Alignment Classes */
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        
        /* Summary Cards */
        .summary-section {
            margin: 25px 0;
        }
        
        .summary-section h2 {
            font-size: 16px;
            color: #2563eb;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .summary-card h3 {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
        }
        
        /* Section Styles */
        .report-section {
            margin: 30px 0;
        }
        
        .report-section h2 {
            font-size: 16px;
            color: #2563eb;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .report-section h3 {
            font-size: 14px;
            color: #374151;
            margin: 20px 0 10px 0;
        }
        
        /* Footer */
        .report-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
        
        /* Progress Bars */
        .progress-bar {
            width: 100%;
            height: 12px;
            background: #f3f4f6;
            border-radius: 6px;
            overflow: hidden;
            margin: 5px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: #2563eb;
            transition: width 0.3s ease;
        }
        
        /* Status Colors */
        .status-pending { color: #f59e0b; }
        .status-processing { color: #3b82f6; }
        .status-completed { color: #10b981; }
        .status-cancelled { color: #ef4444; }

        /* Compact table styles */
        .compact-table { margin-bottom: 15px; }
        .compact-table th, .compact-table td {
            padding: 6px 8px;
            font-size: 11px;
            line-height: 1.3;
        }
        .compact-cell {
            padding: 4px 6px !important;
            vertical-align: middle;
        }
        .muted { color: #9ca3af; }

        /* Results-specific styles */
        .result-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }

        .result-section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .exam-title {
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
            padding: 10px 15px;
            font-weight: 600;
            color: #374151;
        }

        .result-value {
            font-weight: bold;
        }

        .result-normal {
            color: #10b981;
        }

        .result-abnormal {
            color: #ef4444;
            background-color: #fef2f2;
        }

        .observation-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 8px 12px;
            margin-top: 10px;
            border-radius: 0 4px 4px 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            font-size: 12px;
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-left: 5px;
        }

        .status-normal {
            background-color: #10b981;
        }

        .status-abnormal {
            background-color: #ef4444;
        }
        
        /* Utility Classes */
        .mb-10 { margin-bottom: 10px; }
        .mb-15 { margin-bottom: 15px; }
        .mb-20 { margin-bottom: 20px; }
        .mt-10 { margin-top: 10px; }
        .mt-15 { margin-top: 15px; }
        .mt-20 { margin-top: 20px; }
        
        /* Print Styles */
        @media print {
            body {
                font-size: 11px;
                line-height: 1.3;
            }
            .container {
                padding: 10px;
            }
            .report-header h1 {
                font-size: 20px;
            }
            .summary-card {
                break-inside: avoid;
            }
            .report-table {
                break-inside: avoid;
                font-size: 10px;
            }
            .report-table th, .report-table td {
                padding: 3px 4px;
            }
            .report-table thead {
                display: table-header-group;
            }
            .result-section {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }
            .exam-title {
                page-break-after: avoid;
            }
            .info-grid {
                font-size: 10px;
            }
            .observation-box {
                page-break-inside: avoid;
            }
            .compact-table {
                page-break-inside: avoid;
            }
            .page-break {
                page-break-before: always;
            }
            .no-print {
                display: none;
            }
        }
        
        /* Number formatting */
        .number {
            font-family: 'Courier New', monospace;
            text-align: right;
        }
        
        .currency::before {
            content: '$';
        }
        
        .percentage::after {
            content: '%';
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="report-header">
            <h1>{{ $title ?? 'Reporte del Sistema' }}</h1>
            @if(isset($startDate) && isset($endDate))
                <div class="subtitle">Período: {{ $startDate }} - {{ $endDate }}</div>
            @endif
            <div class="date-range">Generado el {{ now()->format('d/m/Y H:i:s') }}</div>
        </div>

        <!-- Meta Information -->
        @if(isset($meta) && count($meta) > 0)
        <div class="report-meta">
            <h3>Información del Reporte</h3>
            @foreach($meta as $key => $value)
                <p><strong>{{ $key }}:</strong> {{ $value }}</p>
            @endforeach
        </div>
        @endif

        <!-- Main Content -->
        @yield('content')

        <!-- Footer -->
        <div class="report-footer">
            <p>© {{ date('Y') }} Laboratorio Clínico - Sistema de Reportes</p>
            @if(isset($generatedBy))
                <p>Generado por: {{ $generatedBy }}</p>
            @endif
        </div>
    </div>
</body>
</html>
