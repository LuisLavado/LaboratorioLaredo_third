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
    <h3>🔬 EXÁMENES MÁS SOLICITADOS</h3>
    
    @if(!empty($examStats) && count($examStats) > 0)
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Pos.</th>
                    <th style="width: 12%;">Código</th>
                    <th style="width: 40%;">Examen</th>
                    <th style="width: 12%;">Cantidad</th>
                    <th style="width: 10%;">% Total</th>
                    <th style="width: 18%;">Demanda</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examStats as $index => $exam)
                <tr>
                    <td class="text-center font-bold">
                        @if($index == 0)
                            🥇
                        @elseif($index == 1)
                            🥈
                        @elseif($index == 2)
                            🥉
                        @else
                            #{{ $index + 1 }}
                        @endif
                    </td>
                    <td class="text-center text-sm font-bold">{{ $exam->code ?? 'N/A' }}</td>
                    <td class="font-bold">{{ $exam->name ?? 'N/A' }}</td>
                    <td class="text-center font-bold">{{ $exam->count ?? 0 }}</td>
                    <td class="text-center">{{ number_format($exam->percentage ?? 0, 1) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $exam->percentage ?? 0 }}%"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 30px; color: #6c757d; font-style: italic; background: #f8f9fa; border-radius: 8px;">
            ⚠️ No se encontraron datos de exámenes para el período seleccionado
        </div>
    @endif
</div>

<!-- Análisis por Categorías -->
@if(!empty($examStats) && count($examStats) > 0)
    @php
        $categoriesData = [];
        foreach($examStats as $exam) {
            $categoryName = $exam->category ?? 'Sin categoría';
            if (!isset($categoriesData[$categoryName])) {
                $categoriesData[$categoryName] = ['count' => 0, 'exams' => [], 'percentage' => 0];
            }
            $categoriesData[$categoryName]['count'] += $exam->count ?? 0;
            $categoriesData[$categoryName]['exams'][] = $exam;
        }
        
        $totalExamsCount = array_sum(array_column($categoriesData, 'count'));
        foreach($categoriesData as $key => $category) {
            $categoriesData[$key]['percentage'] = $totalExamsCount > 0 ? round(($category['count'] / $totalExamsCount) * 100, 1) : 0;
        }
        arsort($categoriesData);
    @endphp
    
    <div class="section">
        <h3>📊 ANÁLISIS POR CATEGORÍAS</h3>
        
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Pos.</th>
                    <th style="width: 40%;">Categoría</th>
                    <th style="width: 15%;">Exámenes</th>
                    <th style="width: 12%;">% Total</th>
                    <th style="width: 15%;">Participación</th>
                    <th style="width: 10%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @php $catIndex = 0; @endphp
                @foreach($categoriesData as $categoryName => $categoryData)
                <tr>
                    <td class="text-center font-bold">
                        @if($catIndex == 0)
                            🥇
                        @elseif($catIndex == 1)
                            🥈
                        @elseif($catIndex == 2)
                            🥉
                        @else
                            #{{ $catIndex + 1 }}
                        @endif
                    </td>
                    <td class="font-bold">{{ $categoryName }}</td>
                    <td class="text-center font-bold">{{ $categoryData['count'] }}</td>
                    <td class="text-center">{{ $categoryData['percentage'] }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $categoryData['percentage'] }}%"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        @if($categoryData['percentage'] >= 25)
                            <span class="status-badge status-completed">Alta</span>
                        @elseif($categoryData['percentage'] >= 10)
                            <span class="status-badge status-in-progress">Media</span>
                        @else
                            <span class="status-badge status-pending">Baja</span>
                        @endif
                    </td>
                </tr>
                @php $catIndex++; @endphp
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<!-- Top 10 Exámenes Detallado -->
@if(!empty($examStats) && count($examStats) > 0)
    <div class="section">
        <h3>🏆 TOP 10 EXÁMENES MÁS DEMANDADOS</h3>
        
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Ranking</th>
                    <th style="width: 10%;">Código</th>
                    <th style="width: 37%;">Examen</th>
                    <th style="width: 15%;">Categoría</th>
                    <th style="width: 10%;">Cantidad</th>
                    <th style="width: 10%;">% Total</th>
                    <th style="width: 10%;">Tendencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examStats->take(10) as $index => $exam)
                <tr style="{{ $index < 3 ? 'background: #f0f8ff;' : '' }}">
                    <td class="text-center font-bold">
                        @if($index == 0)
                            🏆 1°
                        @elseif($index == 1)
                            🥈 2°
                        @elseif($index == 2)
                            🥉 3°
                        @else
                            #{{ $index + 1 }}
                        @endif
                    </td>
                    <td class="text-center text-sm font-bold">{{ $exam->code ?? 'N/A' }}</td>
                    <td class="font-bold">{{ $exam->name ?? 'N/A' }}</td>
                    <td class="text-sm">{{ $exam->category ?? 'Sin categoría' }}</td>
                    <td class="text-center font-bold">{{ $exam->count ?? 0 }}</td>
                    <td class="text-center">{{ number_format($exam->percentage ?? 0, 1) }}%</td>
                    <td class="text-center">
                        @if(($exam->percentage ?? 0) >= 15)
                            📈 Alta
                        @elseif(($exam->percentage ?? 0) >= 5)
                            📊 Media
                        @else
                            📉 Baja
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<!-- Resumen Ejecutivo -->
<div class="section" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid #3498db;">
    <h3 style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">📋 RESUMEN EJECUTIVO</h3>
    
    <div style="padding: 20px;">
        <table class="modern-table" style="margin: 0;">
            <tbody>
                <tr>
                    <td class="font-bold" style="background: #fff; width: 40%;">📊 Total de Solicitudes:</td>
                    <td class="text-right font-bold" style="background: #fff;">{{ $totalRequests ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="font-bold" style="background: #fff;">👥 Total de Pacientes:</td>
                    <td class="text-right font-bold" style="background: #fff;">{{ $totalPatients ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="font-bold" style="background: #fff;">🔬 Total de Exámenes:</td>
                    <td class="text-right font-bold" style="background: #fff;">{{ $totalExams ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="font-bold" style="background: #fff;">📋 Tipos de Exámenes:</td>
                    <td class="text-right font-bold" style="background: #fff;">{{ count($examStats ?? []) }}</td>
                </tr>
                @if(!empty($examStats) && count($examStats) > 0)
                    @php
                        $topExam = $examStats->first();
                        $top5Percentage = $examStats->take(5)->sum('percentage') ?? 0;
                        $avgExamsPerRequest = ($totalRequests ?? 0) > 0 ? round(($totalExams ?? 0) / ($totalRequests ?? 1), 2) : 0;
                    @endphp
                <tr style="background: #e3f2fd;">
                    <td class="font-bold">🏆 Examen Más Solicitado:</td>
                    <td class="text-right font-bold">{{ $topExam->name ?? 'N/A' }} ({{ $topExam->count ?? 0 }})</td>
                </tr>
                <tr style="background: #e8f5e8;">
                    <td class="font-bold">📈 Concentración Top 5:</td>
                    <td class="text-right font-bold">{{ number_format($top5Percentage, 1) }}% del total</td>
                </tr>
                <tr style="background: #fff3cd;">
                    <td class="font-bold">⚖️ Promedio Exámenes/Solicitud:</td>
                    <td class="text-right font-bold">{{ $avgExamsPerRequest }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
