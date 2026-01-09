@extends('reports.clean-pdf')

@section('content')
<!-- Estadísticas Principales -->
<div class="stats-section">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number text-total">{{ number_format($stats['totalSolicitudes'] ?? 0) }}</div>
            <div class="stat-label">Total Solicitudes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-completed">{{ number_format($stats['totalPacientes'] ?? 0) }}</div>
            <div class="stat-label">Total Pacientes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-in-progress">{{ number_format($stats['totalExamenes'] ?? 0) }}</div>
            <div class="stat-label">Total Exámenes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-pending">{{ number_format($stats['serviciosActivos'] ?? 0) }}</div>
            <div class="stat-label">Servicios Activos</div>
        </div>
    </div>
</div>

<!-- Nueva Página: Ranking de Servicios -->
<div class="page-break"></div>

<div class="section">
    <h3 class="section-title">🏥 SERVICIOS MÁS SOLICITADOS</h3>
    
    @if(!empty($topServices) && count($topServices) > 0)
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Pos.</th>
                    <th style="width: 45%;">Servicio</th>
                    <th style="width: 12%;">Solicitudes</th>
                    <th style="width: 10%;">% Total</th>
                    <th style="width: 10%;">Exámenes</th>
                    <th style="width: 15%;">Demanda</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topServices as $index => $service)
                <tr>
                    <td class="text-center font-bold">
                        @if($index == 0)
                            <span class="medal gold">1°</span>
                        @elseif($index == 1)
                            <span class="medal silver">2°</span>
                        @elseif($index == 2)
                            <span class="medal bronze">3°</span>
                        @else
                            {{ $service['position'] }}
                        @endif
                    </td>
                    <td class="font-bold">{{ $service['name'] }}</td>
                    <td class="text-center font-bold">{{ number_format($service['solicitudes']) }}</td>
                    <td class="text-center">{{ $service['percentage'] }}%</td>
                    <td class="text-center">{{ $service['exams'] }}</td>
                    <td class="text-center">
                        @if($service['level'] == 'Muy Alto')
                            <span class="badge badge-critical">Muy Alto</span>
                        @elseif($service['level'] == 'Alto')
                            <span class="badge badge-high">Alto</span>
                        @elseif($service['level'] == 'Medio')
                            <span class="badge badge-medium">Medio</span>
                        @elseif($service['level'] == 'Bajo')
                            <span class="badge badge-low">Bajo</span>
                        @else
                            <span class="badge badge-low">Sin Actividad</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <p>No hay datos de servicios disponibles para el período seleccionado.</p>
        </div>
    @endif
</div>

<!-- Nueva Página: Análisis por Exámenes -->
<div class="page-break"></div>

<div class="section">
    <h3 class="section-title">📊 ANÁLISIS POR NÚMERO DE EXÁMENES</h3>
    
    @if(!empty($servicesByExams))
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Rango</th>
                    <th style="width: 15%;">Servicios</th>
                    <th style="width: 15%;">Solicitudes</th>
                    <th style="width: 45%;">Ejemplos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($servicesByExams as $rango => $data)
                <tr>
                    <td class="font-bold">{{ $rango }}</td>
                    <td class="text-center">{{ number_format($data['count']) }}</td>
                    <td class="text-center font-bold">{{ number_format($data['solicitudes']) }}</td>
                    <td>{{ implode(', ', array_slice($data['servicios'], 0, 3)) }}{{ count($data['servicios']) > 3 ? '...' : '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Resumen del Análisis -->
        <div class="summary-box" style="margin-top: 20px;">
            <h4>Resumen del Análisis por Exámenes</h4>
            <div class="summary-content">
                @php
                    $totalServicios = array_sum(array_column($servicesByExams, 'count'));
                    $rangoMayorUso = array_key_first($servicesByExams);
                @endphp
                <p><strong>Total de Servicios Analizados:</strong> {{ $totalServicios }}</p>
                <p><strong>Rango Predominante:</strong> {{ $rangoMayorUso }}</p>
                <p><strong>Distribución:</strong> 
                    @if($servicesByExams['1 examen']['count'] > $totalServicios * 0.5)
                        Enfoque en servicios simples
                    @elseif($servicesByExams['11+ exámenes']['count'] > $totalServicios * 0.3)
                        Balance hacia servicios complejos
                    @else
                        Distribución equilibrada
                    @endif
                </p>
            </div>
        </div>
    @endif
</div>

<!-- Nueva Página: Análisis de Rendimiento -->
<div class="page-break"></div>

<div class="section">
    <h3 class="section-title">⚡ ANÁLISIS DE RENDIMIENTO</h3>
    
    @if(!empty($performanceAnalysis))
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Servicio</th>
                    <th style="width: 12%;">Solicitudes</th>
                    <th style="width: 12%;">Recurrencia</th>
                    <th style="width: 12%;">Eficiencia</th>
                    <th style="width: 10%;">Score</th>
                    <th style="width: 19%;">Nivel</th>
                </tr>
            </thead>
            <tbody>
                @foreach($performanceAnalysis as $analysis)
                <tr>
                    <td class="font-bold">{{ $analysis['name'] }}</td>
                    <td class="text-center">{{ number_format($analysis['solicitudes']) }}</td>
                    <td class="text-center">{{ $analysis['recurrencia'] }}</td>
                    <td class="text-center">{{ $analysis['eficiencia'] }}</td>
                    <td class="text-center font-bold">{{ $analysis['score'] }}</td>
                    <td class="text-center">
                        @if($analysis['level'] == 'Excelente')
                            <span class="badge badge-critical">Excelente</span>
                        @elseif($analysis['level'] == 'Bueno')
                            <span class="badge badge-high">Bueno</span>
                        @elseif($analysis['level'] == 'Regular')
                            <span class="badge badge-medium">Regular</span>
                        @elseif($analysis['level'] == 'Bajo')
                            <span class="badge badge-low">Bajo</span>
                        @else
                            <span class="badge badge-low">Crítico</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- KPIs de Rendimiento -->
        <div class="kpi-grid" style="margin-top: 25px;">
            @php
                $avgScore = collect($performanceAnalysis)->avg('score');
                $topPerformers = collect($performanceAnalysis)->where('score', '>=', 80)->count();
                $criticalServices = collect($performanceAnalysis)->where('level', 'Crítico')->count();
            @endphp
            <div class="kpi-card">
                <div class="kpi-value">{{ round($avgScore, 1) }}</div>
                <div class="kpi-label">Score Promedio</div>
                <div class="kpi-trend">De {{ count($performanceAnalysis) }} servicios</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value">{{ $topPerformers }}</div>
                <div class="kpi-label">Alto Rendimiento</div>
                <div class="kpi-trend">Score ≥ 80 puntos</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value">{{ $criticalServices }}</div>
                <div class="kpi-label">Necesitan Atención</div>
                <div class="kpi-trend">Rendimiento crítico</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value">{{ round((($topPerformers / count($performanceAnalysis)) * 100), 1) }}%</div>
                <div class="kpi-label">Eficiencia Global</div>
                <div class="kpi-trend">Servicios optimizados</div>
            </div>
        </div>
    @endif
</div>

<!-- Nueva Página: Análisis por Estado -->
<div class="page-break"></div>

<div class="section">
    <h3 class="section-title">📈 ANÁLISIS POR ESTADO DE DEMANDA</h3>
    
    @if(!empty($statusAnalysis))
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Estado</th>
                    <th style="width: 20%;">Servicios</th>
                    <th style="width: 20%;">Solicitudes</th>
                    <th style="width: 15%;">Promedio</th>
                    <th style="width: 15%;">% Servicios</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statusAnalysis as $estado => $data)
                <tr>
                    <td class="font-bold">{{ $estado }}</td>
                    <td class="text-center">{{ number_format($data['count']) }}</td>
                    <td class="text-center font-bold">{{ number_format($data['solicitudes']) }}</td>
                    <td class="text-center">{{ $data['count'] > 0 ? number_format($data['solicitudes'] / $data['count'], 1) : '0' }}</td>
                    <td class="text-center">{{ $totalServices > 0 ? number_format(($data['count'] / $totalServices) * 100, 1) : '0' }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Análisis de Distribución -->
        <div class="analysis-section" style="margin-top: 25px;">
            <h4>Análisis de Distribución de la Demanda</h4>
            @php
                $altaDemanda = $statusAnalysis['Alta Demanda']['count'] ?? 0;
                $demandaMedia = $statusAnalysis['Demanda Media']['count'] ?? 0;
                $bajaDemanda = $statusAnalysis['Baja Demanda']['count'] ?? 0;
                $sinActividad = $statusAnalysis['Sin Actividad']['count'] ?? 0;
                $especializado = $statusAnalysis['Especializado']['count'] ?? 0;
                
                $totalAnalizado = $altaDemanda + $demandaMedia + $bajaDemanda + $sinActividad + $especializado;
                $concentracion = $totalAnalizado > 0 ? round((($altaDemanda + $especializado) / $totalAnalizado) * 100, 1) : 0;
            @endphp
            
            <div class="analysis-content">
                <p><strong>Concentración de Demanda:</strong> {{ $concentracion }}%</p>
                <p><strong>Interpretación:</strong> 
                    @if($concentracion >= 60)
                        <span class="text-high">Alta concentración</span> - La demanda se enfoca en pocos servicios clave
                    @elseif($concentracion >= 40)
                        <span class="text-medium">Concentración moderada</span> - Equilibrio entre servicios principales y secundarios
                    @else
                        <span class="text-low">Baja concentración</span> - La demanda está bien distribuida
                    @endif
                </p>
                
                <div class="recommendations">
                    <h5>Recomendaciones Estratégicas:</h5>
                    <ul>
                        @if($altaDemanda > 0)
                            <li>Optimizar capacidad para {{ $altaDemanda }} servicio(s) de alta demanda</li>
                        @endif
                        @if($especializado > 0)
                            <li>Potenciar servicios especializados ({{ $especializado }} identificados)</li>
                        @endif
                        @if($sinActividad > 0)
                            <li>Evaluar continuidad de {{ $sinActividad }} servicio(s) sin actividad</li>
                        @endif
                        @if($demandaMedia > 0)
                            <li>Desarrollar estrategias de crecimiento para {{ $demandaMedia }} servicio(s) de demanda media</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>

@endsection

@section('extra-styles')
<style>
/* Estilos específicos para PDF de servicios - misma estructura que exámenes */
.medal {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    color: white;
    font-weight: bold;
    font-size: 10px;
}

.medal.gold { background-color: #FFD700; color: #8B4513; }
.medal.silver { background-color: #C0C0C0; color: #2F4F4F; }
.medal.bronze { background-color: #CD7F32; color: white; }

.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 8px;
    font-size: 9px;
    font-weight: bold;
    color: white;
}

.badge-critical { background-color: #d32f2f; }
.badge-high { background-color: #f57c00; }
.badge-medium { background-color: #388e3c; }
.badge-low { background-color: #1976d2; }

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.kpi-card {
    background: linear-gradient(135deg, #1976d2, #42a5f5);
    color: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.kpi-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.kpi-label {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 3px;
}

.kpi-trend {
    font-size: 9px;
    opacity: 0.9;
}

.analysis-section {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #1976d2;
}

.analysis-content {
    margin-top: 10px;
}

.text-high { color: #d32f2f; font-weight: bold; }
.text-medium { color: #f57c00; font-weight: bold; }
.text-low { color: #388e3c; font-weight: bold; }

.recommendations {
    margin-top: 15px;
    background-color: white;
    padding: 15px;
    border-radius: 6px;
}

.recommendations h5 {
    color: #1976d2;
    margin-bottom: 10px;
}

.recommendations ul {
    margin: 0;
    padding-left: 20px;
}

.recommendations li {
    margin-bottom: 5px;
    font-size: 10px;
}

.summary-box {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #1976d2;
}

.summary-content p {
    margin-bottom: 8px;
    font-size: 11px;
}
</style>
@endsection
