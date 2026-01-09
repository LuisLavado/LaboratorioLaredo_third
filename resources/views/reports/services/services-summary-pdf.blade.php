<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Servicios - Laboratorio Laredo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.4; 
            color: #1a1a1a; 
            font-size: 12px; 
        }
        .container { max-width: 210mm; margin: 0 auto; padding: 20px; }
        
        .header {
            text-align: center;
            padding: 15px 0;
            border-bottom: 2px solid #3498db;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }
        
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }
        
        tr:nth-child(even) { background-color: #f8f9fa; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .badge-success { background: #27ae60; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-secondary { background: #95a5a6; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Resumen Ejecutivo de Servicios</h1>
            <p>{{ $period['formatted'] }}</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ number_format($stats['totalSolicitudes']) }}</div>
                <div class="stat-label">TOTAL SOLICITUDES</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ number_format($stats['serviciosActivos']) }}</div>
                <div class="stat-label">SERVICIOS ACTIVOS</div>
            </div>
        </div>

        <h3>🏆 Top 5 Servicios Más Solicitados</h3>
        <table>
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Solicitudes</th>
                    <th>Participación</th>
                    <th>Nivel</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($topServices, 0, 5) as $service)
                <tr>
                    <td class="font-bold">{{ $service['name'] }}</td>
                    <td class="text-right">{{ number_format($service['solicitudes']) }}</td>
                    <td class="text-center">{{ $service['percentage'] }}%</td>
                    <td class="text-center">
                        <span class="badge 
                            @if($service['level'] == 'Muy Alto') badge-success
                            @elseif($service['level'] == 'Alto') badge-info
                            @elseif($service['level'] == 'Medio') badge-warning
                            @else badge-secondary
                            @endif
                        ">{{ $service['level'] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h3>📊 Distribución por Estado</h3>
        <table>
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Cantidad</th>
                    <th>Solicitudes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statusAnalysis as $status => $data)
                    @if($data['count'] > 0)
                    <tr>
                        <td>{{ $status }}</td>
                        <td class="text-center">{{ $data['count'] }}</td>
                        <td class="text-right">{{ number_format($data['solicitudes']) }}</td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
            Reporte generado el {{ $generatedAt }} | Laboratorio Laredo
        </div>
    </div>
</body>
</html>
