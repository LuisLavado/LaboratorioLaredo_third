@extends('reports.base-report')

@section('content')
    <!-- Doctor Information -->
    @php
        $doctor = \App\Models\User::find(request()->input('doctor_id') ?: auth()->id());
    @endphp

    @if($doctor)
    <div class="report-section">
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #2563eb;">
            <h2 style="margin-top: 0; color: #2563eb; font-size: 18px;">Información del Doctor</h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div>
                    <p><strong>Nombre:</strong> {{ $doctor->nombre }} {{ $doctor->apellido }}</p>
                    @if($doctor->especialidad)
                    <p><strong>Especialidad:</strong> {{ $doctor->especialidad }}</p>
                    @endif
                </div>
                <div>
                    @if($doctor->colegiatura)
                    <p><strong>Colegiatura:</strong> {{ $doctor->colegiatura }}</p>
                    @endif
                    @if($doctor->email)
                    <p><strong>Email:</strong> {{ $doctor->email }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Compact Summary -->
    <div class="report-section">
        <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            <strong>📊 Resumen del Período:</strong>
            {{ $totalRequests ?? 0 }} solicitudes •
            {{ $pendingCount ?? 0 }} pendientes •
            {{ $inProcessCount ?? 0 }} en proceso •
            {{ $completedCount ?? 0 }} completados
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="report-section">
        <h2>1. Estado de Solicitudes</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Progreso Visual</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalReqs = ($totalRequests ?? 0) > 0 ? ($totalRequests ?? 0) : 1;
                    $pendingPercent = round((($pendingCount ?? 0) / $totalReqs) * 100, 2);
                    $inProcessPercent = round((($inProcessCount ?? 0) / $totalReqs) * 100, 2);
                    $completedPercent = round((($completedCount ?? 0) / $totalReqs) * 100, 2);
                @endphp
                <tr>
                    <td class="bold status-pending">Pendientes</td>
                    <td class="text-center">{{ $pendingCount ?? 0 }}</td>
                    <td class="text-center">{{ $pendingPercent }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $pendingPercent }}%; background: #f59e0b;"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bold status-processing">En Proceso</td>
                    <td class="text-center">{{ $inProcessCount ?? 0 }}</td>
                    <td class="text-center">{{ $inProcessPercent }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $inProcessPercent }}%; background: #3b82f6;"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bold status-completed">Completados</td>
                    <td class="text-center">{{ $completedCount ?? 0 }}</td>
                    <td class="text-center">{{ $completedPercent }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $completedPercent }}%; background: #10b981;"></div>
                        </div>
                    </td>
                </tr>
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td class="bold">Total</td>
                    <td class="text-center bold">{{ $totalRequests ?? 0 }}</td>
                    <td class="text-center bold">100%</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Top Exams -->
    @if(isset($examStats) && count($examStats) > 0)
    <div class="report-section">
        <h2>2. Exámenes Más Solicitados</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Examen</th>
                    <th>Código</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examStats as $index => $stat)
                <tr>
                    <td class="text-center bold">{{ $index + 1 }}</td>
                    <td>{{ $stat->name }}</td>
                    <td class="text-center">{{ $stat->codigo ?? 'N/A' }}</td>
                    <td class="text-center number">{{ $stat->count }}</td>
                    <td class="text-center number">{{ $stat->percentage ?? 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Top Patients -->
    @if(isset($patientStats) && count($patientStats) > 0)
    <div class="report-section">
        <h2>3. Pacientes con Más Solicitudes</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Paciente</th>
                    <th>DNI</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patientStats as $index => $stat)
                <tr>
                    <td class="text-center bold">{{ $index + 1 }}</td>
                    <td>{{ $stat->name }}</td>
                    <td class="text-center">{{ $stat->dni ?? 'N/A' }}</td>
                    <td class="text-center number">{{ $stat->count }}</td>
                    <td class="text-center number">{{ $stat->percentage ?? 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Performance Metrics -->
    <div class="report-section">
        <h2>4. Métricas de Rendimiento</h2>
        <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            @php
                $completionRate = ($totalRequests ?? 0) > 0 ? round((($completedCount ?? 0) / ($totalRequests ?? 0)) * 100, 2) : 0;
                $avgExamsPerRequest = 0;
                if (isset($totalExams) && ($totalRequests ?? 0) > 0) {
                    $avgExamsPerRequest = round($totalExams / ($totalRequests ?? 0), 2);
                }
            @endphp
            <strong>📈 Rendimiento:</strong>
            {{ $completionRate }}% tasa de finalización •
            {{ $avgExamsPerRequest }} exámenes promedio por solicitud
        </div>
    </div>

    <!-- Monthly Trends -->
    @if(isset($monthlyTrends) && count($monthlyTrends) > 0)
    <div class="report-section">
        <h2>5. Análisis de Tendencias (Últimos Meses)</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th class="text-center">Cantidad de Solicitudes</th>
                    <th class="text-center">Variación</th>
                    <th class="text-center">Tendencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrends as $index => $trend)
                <tr>
                    <td>{{ $trend->month }}</td>
                    <td class="text-center number">{{ $trend->count }}</td>
                    <td class="text-center">
                        @if($index > 0)
                            @php
                                $prevCount = $monthlyTrends[$index - 1]->count;
                                $variation = $prevCount > 0 ? round((($trend->count - $prevCount) / $prevCount) * 100, 2) : 0;
                            @endphp
                            {{ $variation }}%
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @if($index > 0)
                            @if($variation > 0)
                                <span style="color: #10b981;">↗ Crecimiento</span>
                            @elseif($variation < 0)
                                <span style="color: #ef4444;">↘ Decrecimiento</span>
                            @else
                                <span style="color: #6b7280;">→ Estable</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection
