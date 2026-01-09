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
            <div class="stat-number text-pending">{{ count($doctorStats ?? []) }}</div>
            <div class="stat-label">Doctores Activos</div>
        </div>
    </div>
</div>

<!-- Ranking de Doctores por Actividad -->
<div class="section">
    <h3>👨‍⚕️ RANKING DE DOCTORES POR ACTIVIDAD</h3>
    
    @if(!empty($doctorStats) && count($doctorStats) > 0)
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Pos.</th>
                    <th style="width: 40%;">Doctor</th>
                    <th style="width: 15%;">Solicitudes</th>
                    <th style="width: 12%;">% Total</th>
                    <th style="width: 15%;">Actividad</th>
                    <th style="width: 10%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doctorStats as $index => $doctor)
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
                    <td class="font-bold">{{ $doctor->name ?? 'N/A' }}</td>
                    <td class="text-center font-bold">{{ $doctor->count ?? 0 }}</td>
                    <td class="text-center">{{ number_format($doctor->percentage ?? 0, 1) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $doctor->percentage ?? 0 }}%"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        @if(($doctor->percentage ?? 0) >= 20)
                            <span class="status-badge status-completed">Alto</span>
                        @elseif(($doctor->percentage ?? 0) >= 10)
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
            ⚠️ No se encontraron datos de doctores para el período seleccionado
        </div>
    @endif
</div>

<!-- Análisis de Productividad -->
@if(!empty($doctorStats) && count($doctorStats) > 0)
    <div class="section">
        <h3>📈 ANÁLISIS DE PRODUCTIVIDAD</h3>
        
        @php
            $topDoctor = $doctorStats->first();
            $avgSolicitudes = $doctorStats->avg('count');
            $totalDoctors = count($doctorStats);
            $highPerformers = $doctorStats->where('percentage', '>=', 15)->count();
            $mediumPerformers = $doctorStats->where('percentage', '>=', 5)->where('percentage', '<', 15)->count();
            $lowPerformers = $doctorStats->where('percentage', '<', 5)->count();
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
                    <td class="font-bold">🏆 Doctor Más Activo:</td>
                    <td class="text-center font-bold">{{ $topDoctor->name ?? 'N/A' }}</td>
                    <td>{{ $topDoctor->count ?? 0 }} solicitudes ({{ number_format($topDoctor->percentage ?? 0, 1) }}%)</td>
                </tr>
                <tr>
                    <td class="font-bold">📊 Promedio por Doctor:</td>
                    <td class="text-center font-bold">{{ number_format($avgSolicitudes, 1) }}</td>
                    <td>Solicitudes promedio por doctor activo</td>
                </tr>
                <tr>
                    <td class="font-bold">📈 Doctores Alto Rendimiento:</td>
                    <td class="text-center font-bold">{{ $highPerformers }}</td>
                    <td>Doctores con ≥15% de participación</td>
                </tr>
                <tr>
                    <td class="font-bold">⚖️ Distribución:</td>
                    <td class="text-center">{{ $highPerformers }}/{{ $mediumPerformers }}/{{ $lowPerformers }}</td>
                    <td>Alto/Medio/Bajo rendimiento</td>
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
                    <td class="font-bold" style="background: #fff;">👨‍⚕️ Doctores Activos:</td>
                    <td class="text-right font-bold" style="background: #fff;">{{ count($doctorStats ?? []) }}</td>
                </tr>
                @if(!empty($doctorStats) && count($doctorStats) > 0)
                    @php
                        $topDoctor = $doctorStats->first();
                        $avgSolicitudes = round($doctorStats->avg('count'), 1);
                        $avgPacientes = ($totalDoctors ?? 1) > 0 ? round(($totalPatients ?? 0) / ($totalDoctors ?? 1), 1) : 0;
                    @endphp
                <tr style="background: #e3f2fd;">
                    <td class="font-bold">🏆 Doctor Líder:</td>
                    <td class="text-right font-bold">{{ $topDoctor->name ?? 'N/A' }} ({{ $topDoctor->count ?? 0 }})</td>
                </tr>
                <tr style="background: #e8f5e8;">
                    <td class="font-bold">📈 Promedio Solicitudes/Doctor:</td>
                    <td class="text-right font-bold">{{ $avgSolicitudes }}</td>
                </tr>
                <tr style="background: #fff3cd;">
                    <td class="font-bold">⚖️ Promedio Pacientes/Doctor:</td>
                    <td class="text-right font-bold">{{ $avgPacientes }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
