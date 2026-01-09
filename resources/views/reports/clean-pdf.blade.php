<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte Laboratorio Laredo' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.5;
            color: #2c3e50;
            background: #ffffff;
            font-size: 10px;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }

        /* Header Profesional */
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-info {
            flex: 2;
        }

        .company-info h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .company-details {
            font-size: 11px;
            line-height: 1.4;
            color: #ecf0f1;
        }

        .report-info {
            flex: 1;
            text-align: right;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 6px;
            backdrop-filter: blur(10px);
        }

        .report-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            color: white;
        }

        .report-details {
            font-size: 11px;
            line-height: 1.6;
        }

        .detail-item {
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }

        .detail-label {
            font-weight: 600;
            color: #bdc3c7;
        }

        .detail-value {
            color: white;
            font-weight: 500;
        }

        /* Estadísticas Mejoradas */
        .stats-section {
            margin: 25px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #007bff;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
            display: block;
        }

        .stat-label {
            font-size: 10px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Secciones y Tablas Profesionales */
        .section {
            margin: 30px 0;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .section h3 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            color: white;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            padding: 15px 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            background: white;
        }

        .modern-table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 12px 8px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .modern-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .modern-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .modern-table tbody tr:hover {
            background: #e3f2fd;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .text-sm { font-size: 9px; }
        .mb-4 { margin-bottom: 16px; }
        .mt-4 { margin-top: 16px; }

        /* Progress Bars Profesionales */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* Footer Mejorado */
        .footer {
            margin-top: 40px;
            border-top: 2px solid #3498db;
            padding-top: 15px;
            font-size: 10px;
            color: #6c757d;
            text-align: center;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
        }

        /* Colores específicos para estadísticas */
        .text-total { color: #8b5cf6; font-weight: 700; }
        .text-pending { color: #f59e0b; font-weight: 700; }
        .text-in-progress { color: #3b82f6; font-weight: 700; }
        .text-completed { color: #10b981; font-weight: 700; }

        /* Badges para estados */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #cce5ff; color: #004085; }
        .status-completed { background: #d1e7dd; color: #0f5132; }

        /* Responsive para PDF */
        @media print {
            .container { padding: 10px; }
            .header { padding: 15px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .section { margin: 20px 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="company-info">
                    <h1>🔬 LABORATORIO LAREDO</h1>
                    <div class="company-details">
                        <strong>Excelencia en Diagnóstico Médico</strong><br>
                        📧 info@laboratoriolaredo.com | 📞 +51 999 888 777<br>
                        📍 Av. Principal 123, Laredo, Perú
                    </div>
                </div>
                <div class="report-info">
                    <div class="report-title">{{ $title ?? 'Reporte de Laboratorio' }}</div>
                    <div class="report-details">
                        <div class="detail-item">
                            <span class="detail-label">📅 Período:</span>
                            <span class="detail-value">{{ $startDate ?? 'N/A' }} - {{ $endDate ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">📋 Tipo:</span>
                            <span class="detail-value">{{ $reportType ?? 'General' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">👤 Usuario:</span>
                            <span class="detail-value">{{ $generatedBy ?? 'Sistema' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">🕐 Generado:</span>
                            <span class="detail-value">{{ now()->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @yield('content')

        <!-- Footer -->
        <div class="footer">
            <strong>🔬 Laboratorio Laredo</strong> - Excelencia en Diagnóstico Médico | 
            Reporte generado automáticamente el {{ now()->format('d/m/Y \a \l\a\s H:i') }}
        </div>
    </div>
</body>
</html>
