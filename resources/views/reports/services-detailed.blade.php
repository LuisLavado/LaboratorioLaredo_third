{{-- Vista especializada para servicios seleccionados con sus exámenes --}}

<div class="report-section">
    <h2>Servicios Seleccionados - Vista Detallada</h2>
    
    <!-- Summary Table -->
    <table class="report-table mb-20">
        <thead>
            <tr>
                <th>Servicio</th>
                <th class="text-center">Solicitudes</th>
                <th class="text-center">Porcentaje</th>
                <th class="text-center">Progreso</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['serviceStats'] as $stat)
            <tr>
                <td class="bold">{{ $stat->name }}</td>
                <td class="text-center number">{{ $stat->count }}</td>
                <td class="text-center number">{{ $stat->percentage }}%</td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $stat->percentage }}%;"></div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Detailed breakdown by service --}}
@foreach($data['serviceStats'] as $service)
<div class="report-section">
    <h3 style="color: #2563eb; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px;">{{ $service->name }}</h3>
    
    <div style="background-color: #f8f9fa; padding: 15px; margin-bottom: 15px; border-radius: 4px;">
        <p><strong>Total de Solicitudes:</strong> {{ $service->count }} ({{ $service->percentage }}% del total)</p>
        @if(isset($service->description))
        <p><strong>Descripción:</strong> {{ $service->description }}</p>
        @endif
    </div>

    @if(isset($service->exams) && count($service->exams) > 0)
    <h4 style="color: #374151; margin: 15px 0 10px 0;">Exámenes del Servicio</h4>
    <table class="report-table">
        <thead>
            <tr>
                <th>Posición</th>
                <th>Examen</th>
                <th>Código</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">% del Servicio</th>
                <th class="text-center">% del Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($service->exams as $index => $exam)
            <tr>
                <td class="text-center bold">{{ $index + 1 }}</td>
                <td>{{ $exam->name }}</td>
                <td class="text-center">{{ $exam->codigo ?? 'N/A' }}</td>
                <td class="text-center number">{{ $exam->count }}</td>
                <td class="text-center number">{{ $exam->service_percentage ?? 0 }}%</td>
                <td class="text-center number">{{ $exam->total_percentage ?? 0 }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align: center; color: #6b7280; font-style: italic; padding: 20px;">
        No hay exámenes registrados para este servicio en el período seleccionado.
    </div>
    @endif

    {{-- Service Performance Metrics --}}
    @if(isset($service->performance))
    <h4 style="color: #374151; margin: 20px 0 10px 0;">Métricas de Rendimiento</h4>
    <div class="summary-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
        <div class="summary-card">
            <h3>Tiempo Promedio</h3>
            <div class="value">{{ $service->performance->avg_time ?? 'N/A' }}</div>
        </div>
        <div class="summary-card">
            <h3>Tasa de Finalización</h3>
            <div class="value">{{ $service->performance->completion_rate ?? 0 }}%</div>
        </div>
        <div class="summary-card">
            <h3>Satisfacción</h3>
            <div class="value">{{ $service->performance->satisfaction ?? 'N/A' }}</div>
        </div>
    </div>
    @endif

    {{-- Monthly Trends for this Service --}}
    @if(isset($service->monthly_trends) && count($service->monthly_trends) > 0)
    <h4 style="color: #374151; margin: 20px 0 10px 0;">Tendencia Mensual</h4>
    <table class="report-table">
        <thead>
            <tr>
                <th>Mes</th>
                <th class="text-center">Solicitudes</th>
                <th class="text-center">Variación</th>
                <th class="text-center">Tendencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($service->monthly_trends as $index => $trend)
            <tr>
                <td>{{ $trend->month }}</td>
                <td class="text-center number">{{ $trend->requests }}</td>
                <td class="text-center">
                    @if($index > 0)
                        @php
                            $prevRequests = $service->monthly_trends[$index - 1]->requests;
                            $variation = $prevRequests > 0 ? round((($trend->requests - $prevRequests) / $prevRequests) * 100, 2) : 0;
                        @endphp
                        {{ $variation }}%
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @if($index > 0)
                        @if($variation > 0)
                            <span style="color: #10b981;">↗</span>
                        @elseif($variation < 0)
                            <span style="color: #ef4444;">↘</span>
                        @else
                            <span style="color: #6b7280;">→</span>
                        @endif
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Add separator between services --}}
@if(!$loop->last)
<div style="border-top: 1px solid #e5e7eb; margin: 30px 0;"></div>
@endif

@endforeach

{{-- Summary Analysis --}}
<div class="report-section">
    <h2>Análisis Comparativo de Servicios</h2>
    
    <table class="report-table">
        <thead>
            <tr>
                <th>Servicio</th>
                <th class="text-center">Solicitudes</th>
                <th class="text-center">Exámenes Únicos</th>
                <th class="text-center">Promedio Exámenes/Solicitud</th>
                <th class="text-center">Participación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['serviceStats'] as $service)
            <tr>
                <td>{{ $service->name }}</td>
                <td class="text-center number">{{ $service->count }}</td>
                <td class="text-center number">{{ isset($service->exams) ? count($service->exams) : 0 }}</td>
                <td class="text-center number">
                    @if(isset($service->exams) && $service->count > 0)
                        {{ round(array_sum(array_column($service->exams->toArray(), 'count')) / $service->count, 2) }}
                    @else
                        0
                    @endif
                </td>
                <td class="text-center number">{{ $service->percentage }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
