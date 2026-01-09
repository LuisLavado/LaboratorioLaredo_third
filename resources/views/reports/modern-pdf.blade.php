<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Reporte' }}</title>
    <style>
        /* Modern Report Style with Gradients and Shadows */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #1a202c;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        
        /* Modern Header with Gradient */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            margin: -20px -20px 30px -20px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .header .subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .header .date {
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Modern Cards with Shadows */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        
        .card h3 {
            font-size: 11px;
            color: #718096;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .card .value {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Modern Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .table tbody tr:hover {
            background: #e6fffa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        
        /* Modern Sections */
        .section {
            margin: 40px 0;
            padding: 25px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        
        .section h2 {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
            position: relative;
        }
        
        .section h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        /* Progress Bars */
        .progress {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* Status Colors */
        .status-pending { 
            color: #f59e0b; 
            font-weight: 600;
        }
        .status-processing { 
            color: #3b82f6; 
            font-weight: 600;
        }
        .status-completed { 
            color: #10b981; 
            font-weight: 600;
        }
        
        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .font-medium { font-weight: 500; }
        
        /* Modern Footer */
        .footer {
            margin-top: 50px;
            padding: 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            text-align: center;
            font-size: 11px;
            color: #718096;
            border-top: 3px solid #667eea;
        }
        
        /* Print Optimizations */
        @media print {
            body { 
                background: #fff !important;
                font-size: 10px; 
            }
            .container { 
                box-shadow: none;
                border-radius: 0;
            }
            .header { 
                background: #667eea !important;
                border-radius: 0;
            }
            .card, .section { 
                break-inside: avoid;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Modern Header -->
        <div class="header">
            <h1>{{ $title ?? 'Reporte del Sistema' }}</h1>
            @if(isset($startDate) && isset($endDate))
                <div class="subtitle">Período: {{ $startDate }} - {{ $endDate }}</div>
            @endif
            <div class="date">Generado el {{ now()->format('d/m/Y H:i:s') }}</div>
        </div>

        <!-- Summary Cards -->
        @if(isset($totalRequests) || isset($totalPatients) || isset($totalExams))
        <div class="cards">
            @if(isset($totalRequests))
            <div class="card">
                <h3>Total Solicitudes</h3>
                <div class="value">{{ $totalRequests }}</div>
            </div>
            @endif
            
            @if(isset($totalPatients))
            <div class="card">
                <h3>Total Pacientes</h3>
                <div class="value">{{ $totalPatients }}</div>
            </div>
            @endif
            
            @if(isset($totalExams))
            <div class="card">
                <h3>Total Exámenes</h3>
                <div class="value">{{ $totalExams }}</div>
            </div>
            @endif
            
            @if(isset($totalDoctors))
            <div class="card">
                <h3>Total Doctores</h3>
                <div class="value">{{ $totalDoctors }}</div>
            </div>
            @endif
        </div>
        @endif

        <!-- Main Content -->
        @yield('content')

        <!-- Footer -->
        <div class="footer">
            <p><strong>© {{ date('Y') }} Laboratorio Clínico</strong></p>
            <p>Sistema de Reportes Profesional</p>
            @if(isset($generatedBy))
                <p>Generado por: {{ $generatedBy }}</p>
            @endif
        </div>
    </div>
</body>
</html>
