<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Análisis de Rendimiento - Servicios</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.4; 
            color: #1a1a1a; 
            font-size: 11px; 
        }
        .container { max-width: 210mm; margin: 0 auto; padding: 15px; }
        
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px solid #e74c3c;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #c0392b;
            font-size: 22px;
            margin-bottom: 8px;
        }
        
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .performance-card {
            background: #fff5f5;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid #e74c3c;
        }
        
        .performance-number {
            font-size: 18px;
            font-weight: bold;
            color: #c0392b;
        }
        
        .performance-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th {
            background: #c0392b;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 10px;
        }
        
        tr:nth-child(even) { background-color: #fff5f5; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .level-excelente { color: #27ae60; font-weight: bold; }
        .level-bueno { color: #2ecc71; }
        .level-regular { color: #f39c12; }
        .level-bajo { color: #e67e22; }
        .level-critico { color: #e74c3c; font-weight: bold; }
        
        .section {
            margin: 25px 0;
        }
        
        .section h3 {
            color: #c0392b;
            font-size: 14px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 5px;
        }
        
        .highlight-box {
            background: #fff5f5;
            padding: 15px;
            border-left: 4px solid #e74c3c;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .metric-box {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
            border-left: 3px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📈 Análisis de Rendimiento de Servicios</h1>
            <p>{{ $period['formatted'] }}</p>
        </div>

        <div class="performance-grid">
            <div class="performance-card">
                <div class="performance-number">{{ count(array_filter($performanceAnalysis, function($p) { return $p['level'] == 'Excelente'; })) }}</div>
                <div class="performance-label">RENDIMIENTO EXCELENTE</div>
            </div>
            <div class="performance-card">
                <div class="performance-number">{{ count(array_filter($performanceAnalysis, function($p) { return in_array($p['level'], ['Excelente', 'Bueno']); })) }}</div>
                <div class="performance-label">ALTO RENDIMIENTO</div>
            </div>
            <div class="performance-card">
                <div class="performance-number">{{ number_format(array_sum(array_column($performanceAnalysis, 'score')) / max(count($performanceAnalysis), 1), 1) }}</div>
                <div class="performance-label">SCORE PROMEDIO</div>
            </div>
        </div>

        <div class="highlight-box">
            <strong>📊 Análisis Ejecutivo:</strong> 
            Se evaluaron {{ count($performanceAnalysis) }} servicios activos. 
            El rendimiento promedio del laboratorio es de {{ number_format(array_sum(array_column($performanceAnalysis, 'score')) / max(count($performanceAnalysis), 1), 1) }} puntos.
        </div>

        <div class="section">
            <h3>🏆 TOP PERFORMERS - Mejores Servicios por Rendimiento</h3>
            @if(!empty($performanceAnalysis))
                <table>
                    <thead>
                        <tr>
                            <th style="width: 35%;">Servicio</th>
                            <th style="width: 12%;">Solicitudes</th>
                            <th style="width: 13%;">Recurrencia</th>
                            <th style="width: 12%;">Eficiencia</th>
                            <th style="width: 10%;">Score</th>
                            <th style="width: 18%;">Nivel</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($performanceAnalysis, 0, 15) as $analysis)
                        <tr>
                            <td class="font-bold">{{ $analysis['name'] }}</td>
                            <td class="text-right">{{ number_format($analysis['solicitudes']) }}</td>
                            <td class="text-right">{{ $analysis['recurrencia'] }}x</td>
                            <td class="text-right">{{ $analysis['eficiencia'] }}</td>
                            <td class="text-center font-bold">{{ $analysis['score'] }}</td>
                            <td class="text-center level-{{ strtolower($analysis['level']) }}">
                                {{ $analysis['level'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="section">
            <h3>📊 MÉTRICAS DE RENDIMIENTO</h3>
            
            <div class="metric-box">
                <strong>🎯 Recurrencia:</strong> Promedio de solicitudes por paciente único.
                <br><small>Mayor recurrencia indica fidelidad y confianza del paciente.</small>
            </div>
            
            <div class="metric-box">
                <strong>⚡ Eficiencia:</strong> Solicitudes promedio por examen incluido.
                <br><small>Mayor eficiencia indica optimización del servicio.</small>
            </div>
            
            <div class="metric-box">
                <strong>🏆 Score:</strong> Puntuación integral considerando volumen, recurrencia y eficiencia.
                <br><small>Score máximo: 100 puntos. Excelente: 80+, Bueno: 60+, Regular: 40+</small>
            </div>
        </div>

        <div class="section">
            <h3>🎯 SERVICIOS POR NIVEL DE RENDIMIENTO</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nivel</th>
                        <th>Cantidad</th>
                        <th>Porcentaje</th>
                        <th>Score Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $niveles = ['Excelente', 'Bueno', 'Regular', 'Bajo', 'Crítico'];
                        $totalServicios = count($performanceAnalysis);
                    @endphp
                    @foreach($niveles as $nivel)
                        @php
                            $serviciosNivel = array_filter($performanceAnalysis, function($p) use ($nivel) { return $p['level'] == $nivel; });
                            $cantidadNivel = count($serviciosNivel);
                            $scorePromedio = $cantidadNivel > 0 ? array_sum(array_column($serviciosNivel, 'score')) / $cantidadNivel : 0;
                        @endphp
                        @if($cantidadNivel > 0)
                        <tr>
                            <td class="level-{{ strtolower($nivel) }} font-bold">{{ $nivel }}</td>
                            <td class="text-center">{{ $cantidadNivel }}</td>
                            <td class="text-center">{{ $totalServicios > 0 ? number_format(($cantidadNivel / $totalServicios) * 100, 1) : 0 }}%</td>
                            <td class="text-center">{{ number_format($scorePromedio, 1) }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #666; border-top: 1px solid #e0e0e0; padding-top: 10px;">
            📈 Análisis de Rendimiento generado el {{ $generatedAt }} | 🏥 Laboratorio Laredo
        </div>
    </div>
</body>
</html>
