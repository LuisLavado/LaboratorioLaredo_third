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
            <div class="stat-number text-pending">{{ count($serviceStats ?? []) }}</div>
            <div class="stat-label">Servicios Activos</div>
        </div>
    </div>
</div>

<!-- Ranking de Servicios Más Solicitados -->
<div class="section">
    <h3>🏥 SERVICIOS MÁS SOLICITADOS</h3>
    
    @if(!empty($serviceStats) && count($serviceStats) > 0)
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Pos.</th>
                    <th style="width: 40%;">Servicio</th>
                    <th style="width: 15%;">Solicitudes</th>
                    <th style="width: 12%;">% Total</th>
                    <th style="width: 15%;">Participación</th>
                    <th style="width: 10%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serviceStats as $index => $service)
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
                    <td class="font-bold">{{ $service->name ?? 'N/A' }}</td>
                    <td class="text-center font-bold">{{ $service->count ?? 0 }}</td>
                    <td class="text-center">{{ number_format($service->percentage ?? 0, 1) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $service->percentage ?? 0 }}%"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        @if(($service->percentage ?? 0) >= 25)
                            <span class="status-badge status-completed">Alto</span>
                        @elseif(($service->percentage ?? 0) >= 10)
                            <span class="status-badge status-in-progress">Medio</span>
                        @else
                            <span class="status-badge status-pending">Bajo</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 30px; color: #6c757d; font-style: italic; background: #f8f9fa; border-radius: 8px;">
            ⚠️ No se encontraron datos de servicios para el período seleccionado
        </div>
    @endif
</div>

<!-- Exámenes Más Solicitados por Servicio Top -->
@if(!empty($serviceStats) && !empty($topExamsByService))
    @foreach($serviceStats->take(3) as $service)
        @if(!empty($topExamsByService[$service->id]))
            <div class="section" style="page-break-inside: avoid;">
                <h3>🔬 {{ strtoupper($service->name ?? 'SERVICIO') }} - EXÁMENES MÁS SOLICITADOS</h3>
                
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">Pos.</th>
                            <th style="width: 45%;">Examen</th>
                            <th style="width: 15%;">Cantidad</th>
                            <th style="width: 12%;">% Servicio</th>
                            <th style="width: 10%;">% Total</th>
                            <th style="width: 10%;">Ranking</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topExamsByService[$service->id] as $examIndex => $exam)
                        <tr>
                            <td class="text-center font-bold">
                                @if($examIndex == 0)
                                    🥇
                                @elseif($examIndex == 1)
                                    🥈
                                @elseif($examIndex == 2)
                                    🥉
                                @else
                                    #{{ $examIndex + 1 }}
                                @endif
                            </td>
                            <td class="font-bold">{{ $exam->name ?? 'N/A' }}</td>
                            <td class="text-center font-bold">{{ $exam->count ?? 0 }}</td>
                            <td class="text-center">{{ number_format($exam->service_percentage ?? 0, 1) }}%</td>
                            <td class="text-center">{{ number_format($exam->total_percentage ?? 0, 1) }}%</td>
                            <td class="text-center">
                                @if($examIndex == 0)
                                    <span class="status-badge status-completed">TOP</span>
                                @elseif($examIndex < 3)
                                    <span class="status-badge status-in-progress">Alto</span>
                                @else
                                    <span class="status-badge status-pending">Norm</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach
@endif

<!-- Análisis de Distribución de Servicios -->
@if(!empty($serviceStats) && count($serviceStats) > 0)
    <div class="section">
        <h3>📊 ANÁLISIS DE DISTRIBUCIÓN DE SERVICIOS</h3>
        
        @php
            $totalServices = count($serviceStats);
            $highDemandServices = $serviceStats->where('percentage', '>=', 20)->count();
            $mediumDemandServices = $serviceStats->where('percentage', '>=', 10)->where('percentage', '<', 20)->count();
            $lowDemandServices = $serviceStats->where('percentage', '<', 10)->count();
            $avgRequestsPerService = round($serviceStats->avg('count'), 1);
            $top3Percentage = $serviceStats->take(3)->sum('percentage') ?? 0;
        @endphp
        
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Métrica</th>
                    <th>Valor</th>
                    <th>Análisis</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-bold">🏆 Servicio Líder:</td>
                    <td class="text-center font-bold">{{ $serviceStats->first()->name ?? 'N/A' }}</td>
                    <td>{{ $serviceStats->first()->count ?? 0 }} solicitudes ({{ number_format($serviceStats->first()->percentage ?? 0, 1) }}%)</td>
                </tr>
                <tr>
                    <td class="font-bold">📊 Promedio por Servicio:</td>
                    <td class="text-center font-bold">{{ $avgRequestsPerService }}</td>
                    <td>Solicitudes promedio por servicio activo</td>
                </tr>
                <tr>
                    <td class="font-bold">📈 Concentración Top 3:</td>
                    <td class="text-center font-bold">{{ number_format($top3Percentage, 1) }}%</td>
                    <td>Porcentaje de los 3 servicios principales</td>
                </tr>
                <tr>
                    <td class="font-bold">⚖️ Distribución:</td>
                    <td class="text-center">{{ $highDemandServices }}/{{ $mediumDemandServices }}/{{ $lowDemandServices }}</td>
                    <td>Alta/Media/Baja demanda</td>
                </tr>
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
                    <td class="font-bold" style="background: #fff;">🏥 Servicios Activos:</td>
                    <td class="text-right font-bold" style="background: #fff;">{{ count($serviceStats ?? []) }}</td>
                </tr>
                @if(!empty($serviceStats) && count($serviceStats) > 0)
                    @php
                        $topService = $serviceStats->first();
                        $avgRequestsPerService = round($serviceStats->avg('count'), 1);
                        $avgExamsPerService = ($totalServices ?? 1) > 0 ? round(($totalExams ?? 0) / ($totalServices ?? 1), 1) : 0;
                    @endphp
                <tr style="background: #e3f2fd;">
                    <td class="font-bold">🏆 Servicio Líder:</td>
                    <td class="text-right font-bold">{{ $topService->name ?? 'N/A' }} ({{ $topService->count ?? 0 }})</td>
                </tr>
                <tr style="background: #e8f5e8;">
                    <td class="font-bold">📈 Promedio Solicitudes/Servicio:</td>
                    <td class="text-right font-bold">{{ $avgRequestsPerService }}</td>
                </tr>
                <tr style="background: #fff3cd;">
                    <td class="font-bold">⚖️ Promedio Exámenes/Servicio:</td>
                    <td class="text-right font-bold">{{ $avgExamsPerService }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
