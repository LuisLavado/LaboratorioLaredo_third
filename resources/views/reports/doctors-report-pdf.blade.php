@extends('reports.clean-pdf')

@section('content')
<!-- Sección de estadísticas principales -->
<div class="stats-section">
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number text-total">{{ $totalRequests ?? 0 }}</div>
            <div class="stat-label">Total Solicitudes</div>
            <div class="stat-percentage">100% del período</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number text-pending">{{ $totalPatients ?? 0 }}</div>
            <div class="stat-label">Total Pacientes</div>
            <div class="stat-percentage">Únicos en el período</div>
        </div>
        <div class="stat-card in-progress">
            <div class="stat-number text-in-progress">{{ $totalExams ?? 0 }}</div>
            <div class="stat-label">Total Exámenes</div>
            <div class="stat-percentage">Solicitados</div>
        </div>
        <div class="stat-card completed">
            <div class="stat-number text-completed">{{ count($doctorStats ?? []) }}</div>
            <div class="stat-label">Doctores Activos</div>
            <div class="stat-percentage">Con solicitudes</div>
        </div>
    </div>
</div>

<!-- Ranking de Doctores -->
@if(count($doctorStats ?? []) > 0)
<div class="section">
    <h3 style="color: #1e293b; margin-bottom: 25px; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
        👨‍⚕️ Ranking de Doctores por Actividad
    </h3>
    
    <table class="modern-table">
        <thead>
            <tr>
                <th>Posición</th>
                <th>Doctor</th>
                <th>Especialidad</th>
                <th>Solicitudes</th>
                <th>Porcentaje</th>
                <th>Actividad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doctorStats as $index => $doctor)
            <tr>
                <td>
                    @if($index < 3)
                        <span style="font-size: 18px;">
                            @if($index == 0) 🥇
                            @elseif($index == 1) 🥈
                            @else 🥉
                            @endif
                        </span>
                    @else
                        <span class="font-bold" style="color: #64748b;">#{{ $index + 1 }}</span>
                    @endif
                </td>
                <td>
                    <div class="font-bold">{{ $doctor->name ?? 'N/A' }}</div>
                    @if($doctor->colegiatura)
                        <div class="text-sm" style="color: #64748b;">CMP: {{ $doctor->colegiatura }}</div>
                    @endif
                </td>
                <td>
                    <span class="status-badge {{ $doctor->role === 'laboratorio' ? 'status-pending' : 'status-completed' }}">
                        {{ $doctor->especialidad ?? 'No especificada' }}
                    </span>
                </td>
                <td class="font-bold text-center">{{ $doctor->count ?? 0 }}</td>
                <td class="font-bold text-center">{{ $doctor->percentage ?? 0 }}%</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="flex: 1; background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="width: {{ $doctor->percentage ?? 0 }}%; height: 100%; background: linear-gradient(90deg, #3b82f6, #1d4ed8); transition: width 0.3s ease;"></div>
                        </div>
                        <span class="text-sm font-bold">{{ $doctor->percentage ?? 0 }}%</span>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Estadísticas de Resultados por Doctor -->
@if(count($resultStats ?? []) > 0)
<div class="section">
    <h3 style="color: #1e293b; margin-bottom: 25px; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
        📈 Estado de Exámenes por Doctor
    </h3>
    
    <table class="modern-table">
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Pendientes</th>
                <th>En Proceso</th>
                <th>Completados</th>
                <th>Total</th>
                <th>Tasa de Finalización</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doctorStats as $doctor)
                @php
                    $stats = $resultStats[$doctor->id] ?? ['pendingCount' => 0, 'inProcessCount' => 0, 'completedCount' => 0];
                    $total = $stats['pendingCount'] + $stats['inProcessCount'] + $stats['completedCount'];
                    $completionRate = $total > 0 ? round(($stats['completedCount'] / $total) * 100, 1) : 0;
                @endphp
            <tr>
                <td class="font-bold">{{ $doctor->name ?? 'N/A' }}</td>
                <td class="text-center">
                    <span class="status-badge status-pending">{{ $stats['pendingCount'] }}</span>
                </td>
                <td class="text-center">
                    <span class="status-badge status-in-progress">{{ $stats['inProcessCount'] }}</span>
                </td>
                <td class="text-center">
                    <span class="status-badge status-completed">{{ $stats['completedCount'] }}</span>
                </td>
                <td class="font-bold text-center">{{ $total }}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="flex: 1; background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden;">
                            <div style="width: {{ $completionRate }}%; height: 100%; background: linear-gradient(90deg, #10b981, #059669);"></div>
                        </div>
                        <span class="text-sm font-bold">{{ $completionRate }}%</span>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Análisis de Productividad -->
@if(count($doctorStats ?? []) > 0)
<div class="section">
    <h3 style="color: #1e293b; margin-bottom: 25px; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
        🎯 Análisis de Productividad
    </h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        @foreach($doctorStats->take(6) as $index => $doctor)
            @php
                $stats = $resultStats[$doctor->id] ?? ['pendingCount' => 0, 'inProcessCount' => 0, 'completedCount' => 0];
                $total = $stats['pendingCount'] + $stats['inProcessCount'] + $stats['completedCount'];
                $completionRate = $total > 0 ? round(($stats['completedCount'] / $total) * 100, 1) : 0;
                
                // Determinar color según productividad
                $productivityColor = '#ef4444'; // Rojo por defecto
                if ($completionRate >= 80) $productivityColor = '#10b981'; // Verde
                elseif ($completionRate >= 60) $productivityColor = '#f59e0b'; // Amarillo
                elseif ($completionRate >= 40) $productivityColor = '#f97316'; // Naranja
            @endphp
        <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                @if($index < 3)
                    <span style="font-size: 20px;">
                        @if($index == 0) 🥇
                        @elseif($index == 1) 🥈
                        @else 🥉
                        @endif
                    </span>
                @endif
                <div>
                    <div class="font-bold" style="font-size: 16px;">{{ $doctor->name ?? 'N/A' }}</div>
                    <div style="font-size: 12px; color: #64748b;">{{ $doctor->especialidad ?? 'No especificada' }}</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                <div style="text-align: center; padding: 10px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 18px; font-weight: 700; color: #1f2937;">{{ $doctor->count ?? 0 }}</div>
                    <div style="font-size: 12px; color: #64748b;">Solicitudes</div>
                </div>
                <div style="text-align: center; padding: 10px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 18px; font-weight: 700; color: {{ $productivityColor }};">{{ $completionRate }}%</div>
                    <div style="font-size: 12px; color: #64748b;">Finalización</div>
                </div>
            </div>
            
            <div style="background: #f1f5f9; padding: 10px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px;">
                    <span style="color: #f59e0b;">Pendientes: {{ $stats['pendingCount'] }}</span>
                    <span style="color: #3b82f6;">En Proceso: {{ $stats['inProcessCount'] }}</span>
                    <span style="color: #10b981;">Completados: {{ $stats['completedCount'] }}</span>
                </div>
                <div style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                    @php
                        $pendingPercent = $total > 0 ? ($stats['pendingCount'] / $total) * 100 : 0;
                        $inProcessPercent = $total > 0 ? ($stats['inProcessCount'] / $total) * 100 : 0;
                        $completedPercent = $total > 0 ? ($stats['completedCount'] / $total) * 100 : 0;
                    @endphp
                    <div style="display: flex; height: 100%;">
                        <div style="width: {{ $pendingPercent }}%; background: #f59e0b;"></div>
                        <div style="width: {{ $inProcessPercent }}%; background: #3b82f6;"></div>
                        <div style="width: {{ $completedPercent }}%; background: #10b981;"></div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Resumen ejecutivo -->
<div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); padding: 30px; border-radius: 15px; margin-top: 30px; border: 1px solid #e2e8f0;">
    <h3 style="color: #1e293b; margin-bottom: 20px; font-size: 20px; font-weight: 700;">📊 Resumen Ejecutivo</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div style="text-align: center;">
            <div style="font-size: 24px; font-weight: 800; color: #8b5cf6; margin-bottom: 5px;">
                {{ $totalRequests ?? 0 }}
            </div>
            <div style="font-size: 14px; color: #64748b;">Solicitudes Totales</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 24px; font-weight: 800; color: #10b981; margin-bottom: 5px;">
                {{ count($doctorStats ?? []) }}
            </div>
            <div style="font-size: 14px; color: #64748b;">Doctores Activos</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 24px; font-weight: 800; color: #3b82f6; margin-bottom: 5px;">
                {{ $totalPatients ?? 0 }}
            </div>
            <div style="font-size: 14px; color: #64748b;">Pacientes Únicos</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 24px; font-weight: 800; color: #f59e0b; margin-bottom: 5px;">
                {{ $totalExams ?? 0 }}
            </div>
            <div style="font-size: 14px; color: #64748b;">Exámenes Solicitados</div>
        </div>
    </div>
    
    @if(count($doctorStats ?? []) > 0)
    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #d1d5db;">
        <h4 style="color: #374151; margin-bottom: 15px; font-size: 16px; font-weight: 600;">🏆 Doctor Más Activo</h4>
        <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #d1d5db;">
            <div style="font-size: 18px; font-weight: 700; color: #1f2937;">{{ $doctorStats[0]->name ?? 'N/A' }}</div>
            <div style="font-size: 14px; color: #6b7280; margin-top: 5px;">
                {{ $doctorStats[0]->count ?? 0 }} solicitudes ({{ $doctorStats[0]->percentage ?? 0 }}% del total)
                @if($doctorStats[0]->especialidad)
                    • {{ $doctorStats[0]->especialidad }}
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
