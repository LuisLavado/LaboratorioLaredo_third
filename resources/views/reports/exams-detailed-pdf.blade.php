@extends('reports.clean-pdf')

@section('content')
<!-- Estadísticas Principales -->
<div class="stats-section">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number text-total">{{ $totalRequests ?? 0 }}</div>
            <div class="stat-label">Total Solicitudes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-completed">{{ $totalPatients ?? 0 }}</div>
            <div class="stat-label">Total Pacientes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-in-progress">{{ $totalExams ?? 0 }}</div>
            <div class="stat-label">Total Exámenes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-pending">{{ count($examStats ?? []) }}</div>
            <div class="stat-label">Tipos de Exámenes</div>
        </div>
    </div>
</div>

<!-- Ranking de Exámenes Más Solicitados -->
<div class="section">
    <h3 class="section-title">EXÁMENES MÁS SOLICITADOS</h3>
    
    @if(!empty($examStats) && count($examStats) > 0)
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Pos.</th>
                    <th style="width: 12%;">Código</th>
                    <th style="width: 35%;">Examen</th>
                    <th style="width: 18%;">Categoría</th>
                    <th style="width: 12%;">Cantidad</th>
                    <th style="width: 10%;">% Total</th>
                    <th style="width: 5%;">Nivel</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalCount = collect($examStats)->sum('count');
                @endphp
                @foreach($examStats as $index => $exam)
                <tr>
                    <td class="text-center font-bold">
                        @if($index == 0)
                            <span class="medal gold">1°</span>
                        @elseif($index == 1)
                            <span class="medal silver">2°</span>
                        @elseif($index == 2)
                            <span class="medal bronze">3°</span>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </td>
                    <td class="text-center">{{ $exam->id ?? '-' }}</td>
                    <td class="font-bold">{{ $exam->name ?? $exam->nombre ?? 'Sin nombre' }}</td>
                    <td class="text-center">{{ $exam->category ?? $exam->categoria ?? 'Sin categoría' }}</td>
                    <td class="text-center font-bold">{{ $exam->count ?? 0 }}</td>
                    <td class="text-center">
                        @php
                            $percentage = $totalCount > 0 ? round(($exam->count / $totalCount) * 100, 2) : 0;
                        @endphp
                        {{ $percentage }}%
                    </td>
                    <td class="text-center">
                        @if($percentage >= 20)
                            <span class="badge badge-high">Alto</span>
                        @elseif($percentage >= 10)
                            <span class="badge badge-medium">Medio</span>
                        @else
                            <span class="badge badge-low">Bajo</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <p>No hay datos de exámenes disponibles para el período seleccionado.</p>
        </div>
    @endif
</div>

<!-- Nueva Página: Análisis por Categorías -->
<div class="page-break"></div>

<div class="section">
    <h3 class="section-title">ANÁLISIS POR CATEGORÍAS</h3>
    
    @if(!empty($examStats) && count($examStats) > 0)
        @php
            $categoriesData = [];
            foreach($examStats as $exam) {
                $categoryName = $exam->category ?? $exam->categoria ?? 'Sin categoría';
                if (!isset($categoriesData[$categoryName])) {
                    $categoriesData[$categoryName] = [
                        'count' => 0, 
                        'exams' => [], 
                        'percentage' => 0,
                        'avg_per_exam' => 0
                    ];
                }
                $categoriesData[$categoryName]['count'] += $exam->count ?? 0;
                $categoriesData[$categoryName]['exams'][] = $exam;
            }
            
            $totalExamsCount = array_sum(array_column($categoriesData, 'count'));
            foreach($categoriesData as $key => $category) {
                $categoriesData[$key]['percentage'] = $totalExamsCount > 0 ? round(($category['count'] / $totalExamsCount) * 100, 1) : 0;
                $categoriesData[$key]['avg_per_exam'] = count($category['exams']) > 0 ? round($category['count'] / count($category['exams']), 1) : 0;
            }
            arsort($categoriesData);
        @endphp
        
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Categoría</th>
                    <th style="width: 15%;">Tipos Examen</th>
                    <th style="width: 15%;">Total Realizados</th>
                    <th style="width: 15%;">% del Total</th>
                    <th style="width: 15%;">Promedio/Examen</th>
                    <th style="width: 15%;">Importancia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoriesData as $categoryName => $data)
                <tr>
                    <td class="font-bold">{{ $categoryName }}</td>
                    <td class="text-center">{{ count($data['exams']) }}</td>
                    <td class="text-center font-bold">{{ $data['count'] }}</td>
                    <td class="text-center">{{ $data['percentage'] }}%</td>
                    <td class="text-center">{{ $data['avg_per_exam'] }}</td>
                    <td class="text-center">
                        @if($data['percentage'] >= 30)
                            <span class="badge badge-critical">Crítica</span>
                        @elseif($data['percentage'] >= 20)
                            <span class="badge badge-high">Alta</span>
                        @elseif($data['percentage'] >= 10)
                            <span class="badge badge-medium">Media</span>
                        @else
                            <span class="badge badge-low">Baja</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Resumen de Categorías -->
        <div class="summary-box" style="margin-top: 20px;">
            <h4>Resumen del Análisis por Categorías</h4>
            <div class="summary-content">
                <p><strong>Total de Categorías:</strong> {{ count($categoriesData) }}</p>
                <p><strong>Categoría Principal:</strong> {{ array_key_first($categoriesData) }} ({{ $categoriesData[array_key_first($categoriesData)]['percentage'] }}%)</p>
                <p><strong>Diversidad de Servicios:</strong> 
                    @if(count($categoriesData) >= 5)
                        Excelente diversidad
                    @elseif(count($categoriesData) >= 3)
                        Buena diversidad
                    @else
                        Diversidad limitada
                    @endif
                </p>
            </div>
        </div>
    @endif
</div>

<!-- Nueva Página: Detalle de Exámenes por Categoría -->
<div class="page-break"></div>

<div class="section">
    <h3 class="section-title">DETALLE POR CATEGORÍAS</h3>
    
    @if(!empty($categoriesData))
        @foreach($categoriesData as $categoryName => $data)
            <div class="category-detail">
                <h4 class="category-title">{{ $categoryName }}</h4>
                <div class="category-stats">
                    <span class="stat-item">Exámenes: {{ count($data['exams']) }}</span>
                    <span class="stat-item">Total: {{ $data['count'] }}</span>
                    <span class="stat-item">Participación: {{ $data['percentage'] }}%</span>
                </div>
                
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Código</th>
                            <th style="width: 45%;">Nombre del Examen</th>
                            <th style="width: 15%;">Cantidad</th>
                            <th style="width: 15%;">% Categoría</th>
                            <th style="width: 10%;">Ranking</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['exams'] as $exam)
                        <tr>
                            <td class="text-center">{{ $exam->id ?? '-' }}</td>
                            <td>{{ $exam->name ?? $exam->nombre ?? 'Sin nombre' }}</td>
                            <td class="text-center">{{ $exam->count ?? 0 }}</td>
                            <td class="text-center">
                                @php
                                    $categoryPercentage = $data['count'] > 0 ? round(($exam->count / $data['count']) * 100, 1) : 0;
                                @endphp
                                {{ $categoryPercentage }}%
                            </td>
                            <td class="text-center">
                                @if($categoryPercentage >= 50)
                                    <span class="ranking top">★★★</span>
                                @elseif($categoryPercentage >= 25)
                                    <span class="ranking high">★★</span>
                                @else
                                    <span class="ranking normal">★</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
</div>

<!-- Nueva Página: Indicadores de Rendimiento -->
<div class="page-break"></div>

<div class="section">
    <h3 class="section-title">INDICADORES DE RENDIMIENTO</h3>
    
    @php
        $totalExams = count($examStats ?? []);
        $totalRequested = collect($examStats)->sum('count');
        $avgPerExam = $totalExams > 0 ? round($totalRequested / $totalExams, 2) : 0;
        $categoriesCount = count($categoriesData ?? []);
    @endphp
    
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-value">{{ $totalExams }}</div>
            <div class="kpi-label">Tipos de Exámenes</div>
            <div class="kpi-trend">Disponibles en el período</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">{{ $totalRequested }}</div>
            <div class="kpi-label">Total Realizados</div>
            <div class="kpi-trend">{{ $avgPerExam }} promedio por tipo</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">{{ $categoriesCount }}</div>
            <div class="kpi-label">Categorías Activas</div>
            <div class="kpi-trend">Diversidad de servicios</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">{{ $totalPatients ?? 0 }}</div>
            <div class="kpi-label">Pacientes Atendidos</div>
            <div class="kpi-trend">
                @if($totalPatients > 0)
                    {{ round($totalRequested / $totalPatients, 1) }} exámenes/paciente
                @else
                    Sin datos
                @endif
            </div>
        </div>
    </div>

    <!-- Análisis de Concentración -->
    <div class="analysis-section">
        <h4>Análisis de Concentración de Demanda</h4>
        @php
            $top3Count = collect($examStats)->take(3)->sum('count');
            $concentrationRate = $totalRequested > 0 ? round(($top3Count / $totalRequested) * 100, 1) : 0;
        @endphp
        
        <div class="analysis-content">
            <p><strong>Concentración en Top 3:</strong> {{ $concentrationRate }}%</p>
            <p><strong>Interpretación:</strong> 
                @if($concentrationRate >= 60)
                    <span class="text-high">Alta concentración</span> - Los 3 exámenes principales representan la mayoría de la demanda
                @elseif($concentrationRate >= 40)
                    <span class="text-medium">Concentración moderada</span> - Existe diversidad pero con exámenes predominantes
                @else
                    <span class="text-low">Baja concentración</span> - La demanda está bien distribuida entre diferentes exámenes
                @endif
            </p>
            
            <div class="recommendations">
                <h5>Recomendaciones:</h5>
                <ul>
                    @if($concentrationRate >= 60)
                        <li>Optimizar recursos para los exámenes de mayor demanda</li>
                        <li>Evaluar capacidad de procesamiento para exámenes principales</li>
                        <li>Considerar protocolos especializados para alta demanda</li>
                    @elseif($concentrationRate >= 40)
                        <li>Mantener equilibrio entre exámenes frecuentes y especializados</li>
                        <li>Revisar eficiencia en exámenes de demanda media</li>
                        <li>Optimizar inventario según patrones de demanda</li>
                    @else
                        <li>Aprovechar la diversidad para servicios especializados</li>
                        <li>Revisar rentabilidad de exámenes de baja demanda</li>
                        <li>Considerar paquetes de exámenes complementarios</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@section('extra-styles')
<style>
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

.category-detail {
    margin-bottom: 25px;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
}

.category-title {
    color: #1f4e79;
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: bold;
}

.category-stats {
    margin-bottom: 15px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    background-color: #f5f5f5;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: bold;
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9px;
}

.detail-table th {
    background-color: #e3f2fd;
    padding: 6px;
    border: 1px solid #1976d2;
    font-weight: bold;
    text-align: center;
}

.detail-table td {
    padding: 5px;
    border: 1px solid #ddd;
}

.ranking.top { color: #d32f2f; }
.ranking.high { color: #f57c00; }
.ranking.normal { color: #388e3c; }

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
</style>
@endsection
