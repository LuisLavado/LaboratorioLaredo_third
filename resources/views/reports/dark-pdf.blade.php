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
            color: #ffffff;
            background: #1a1a1a;
            font-size: 10px;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            background: #1a1a1a;
        }

        /* Header Profesional Oscuro */
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
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

        /* Estadísticas con Diseño Oscuro */
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
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            border: 2px solid #3498db;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
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
            background: #3498db;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            display: block;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 11px;
            color: #bdc3c7;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Colores específicos para diferentes tipos de estadísticas */
        .text-total { color: #3498db; }
        .text-completed { color: #27ae60; }
        .text-in-progress { color: #f39c12; }
        .text-pending { color: #e74c3c; }

        /* Secciones Oscuras */
        .section {
            margin: 30px 0;
            background: #2c3e50;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            border: 1px solid #34495e;
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
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            background: #2c3e50;
            color: #ffffff;
        }

        .modern-table th {
            background: #34495e;
            color: #ffffff;
            font-weight: 600;
            padding: 12px 8px;
            text-align: left;
            border-bottom: 2px solid #3498db;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .modern-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #34495e;
            vertical-align: middle;
            color: #ffffff;
        }

        .modern-table tbody tr:nth-child(even) {
            background: #34495e;
        }

        .modern-table tbody tr:hover {
            background: #3498db;
        }

        /* Estilos para badges y elementos especiales */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-success { background: #27ae60; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-secondary { background: #95a5a6; color: white; }

        /* Utilidades de texto */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .text-small { font-size: 8px; }
        .text-muted { color: #95a5a6; }

        /* Espaciado */
        .mb-2 { margin-bottom: 10px; }
        .mb-3 { margin-bottom: 15px; }
        .mb-4 { margin-bottom: 20px; }
        .mt-2 { margin-top: 10px; }
        .mt-3 { margin-top: 15px; }
        .mt-4 { margin-top: 20px; }

        /* Contenedores especiales */
        .info-box {
            background: #34495e;
            border: 1px solid #3498db;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            color: #ffffff;
        }

        .info-box h4 {
            color: #3498db;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Responsive para PDF */
        @media print {
            body { 
                background: #1a1a1a !important; 
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .container { 
                background: #1a1a1a !important; 
                margin: 0;
                padding: 10px;
            }
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #34495e;
            text-align: center;
            color: #95a5a6;
            font-size: 9px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="company-info">
                    <h1>LABORATORIO CLÍNICO LAREDO</h1>
                    <div class="company-details">
                        <div><strong>Dirección:</strong> Jr. Pizarro 618 - Trujillo, La Libertad</div>
                        <div><strong>Teléfono:</strong> (044) 481-516 | <strong>Email:</strong> info@laboratoriolaredo.com</div>
                        <div><strong>Autorización Sanitaria:</strong> N° 001-2024-DIRESA-LL</div>
                    </div>
                </div>
                <div class="report-info">
                    <div class="report-title">{{ $title ?? 'REPORTE DE LABORATORIO' }}</div>
                    <div class="report-details">
                        @if(isset($period))
                        <div class="detail-item">
                            <span class="detail-label">Período:</span>
                            <span class="detail-value">{{ $period['formatted'] ?? '' }}</span>
                        </div>
                        @endif
                        @if(isset($reportType))
                        <div class="detail-item">
                            <span class="detail-label">Tipo:</span>
                            <span class="detail-value">{{ $reportType }}</span>
                        </div>
                        @endif
                        <div class="detail-item">
                            <span class="detail-label">Generado:</span>
                            <span class="detail-value">{{ $generatedAt ?? now()->format('d/m/Y H:i:s') }}</span>
                        </div>
                        @if(isset($generatedBy))
                        <div class="detail-item">
                            <span class="detail-label">Por:</span>
                            <span class="detail-value">{{ $generatedBy }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        @yield('content')

        <!-- Footer -->
        <div class="footer">
            <p><strong>LABORATORIO CLÍNICO LAREDO</strong> - Reporte generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i:s') }}</p>
            <p>Este documento es confidencial y está destinado únicamente para uso interno del laboratorio.</p>
        </div>
    </div>
</body>
</html>
