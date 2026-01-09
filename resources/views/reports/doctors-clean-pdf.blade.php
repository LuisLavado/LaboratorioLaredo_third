@extends('reports.base-report')

@section('content')
    <!-- Compact Summary -->
    <div class="report-section">
        <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            <strong>📊 Resumen:</strong>
            {{ $totalRequests ?? 0 }} solicitudes •
            {{ isset($doctorStats) ? count($doctorStats) : 0 }} doctores •
            {{ $totalPatients ?? 0 }} pacientes •
            {{ $totalExams ?? 0 }} exámenes
        </div>
    </div>

    <!-- Doctor Statistics -->
    @if(isset($doctorStats) && count($doctorStats) > 0)
    <div class="report-section">
        <h2>Estadísticas por Doctor</h2>
        <table class="report-table compact-table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Doctor</th>
                    <th>Especialidad</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Pacientes</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Progreso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doctorStats as $index => $stat)
                <tr>
                    <td class="text-center bold compact-cell">{{ $index + 1 }}</td>
                    <td class="compact-cell">
                        @if(is_object($stat))
                            {{ $stat->name ?? $stat->nombre ?? $stat->doctor_name ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['name'] ?? $stat['nombre'] ?? $stat['doctor_name'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="compact-cell">
                        @if(is_object($stat))
                            {{ $stat->especialidad ?? $stat->specialty ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['especialidad'] ?? $stat['specialty'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number compact-cell">
                        @if(is_object($stat))
                            {{ $stat->count ?? $stat->total_requests ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['count'] ?? $stat['total_requests'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number compact-cell">
                        @if(is_object($stat))
                            {{ $stat->patient_count ?? $stat->patients ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['patient_count'] ?? $stat['patients'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number compact-cell">
                        @if(is_object($stat))
                            {{ $stat->percentage ?? 0 }}%
                        @elseif(is_array($stat))
                            {{ $stat['percentage'] ?? 0 }}%
                        @else
                            0%
                        @endif
                    </td>
                    <td class="compact-cell">
                        <div class="progress-bar" style="height: 12px;">
                            @php
                                $percentage = 0;
                                if (is_object($stat)) {
                                    $percentage = $stat->percentage ?? 0;
                                } elseif (is_array($stat)) {
                                    $percentage = $stat['percentage'] ?? 0;
                                }
                            @endphp
                            <div class="progress-fill" style="width: {{ $percentage }}%; height: 12px;"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Top 10 Most Active Doctors -->
    @if(isset($doctorStats) && count($doctorStats) > 10)
    <div class="report-section">
        <h2>Top 10 Doctores Más Activos</h2>
        <table class="report-table compact-table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Doctor</th>
                    <th>Especialidad</th>
                    <th>Colegiatura</th>
                    <th class="text-center">Sol.</th>
                    <th class="text-center">Pac.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doctorStats->take(10) as $index => $stat)
                <tr>
                    <td class="text-center bold compact-cell">{{ $index + 1 }}</td>
                    <td class="compact-cell">
                        @if(is_object($stat))
                            {{ $stat->name ?? $stat->nombre ?? $stat->doctor_name ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['name'] ?? $stat['nombre'] ?? $stat['doctor_name'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="compact-cell">
                        @if(is_object($stat))
                            {{ $stat->especialidad ?? $stat->specialty ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['especialidad'] ?? $stat['specialty'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center compact-cell">
                        @if(is_object($stat))
                            {{ $stat->colegiatura ?? $stat->license ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['colegiatura'] ?? $stat['license'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number compact-cell">
                        @if(is_object($stat))
                            {{ $stat->count ?? $stat->total_requests ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['count'] ?? $stat['total_requests'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number compact-cell">
                        @if(is_object($stat))
                            {{ $stat->patient_count ?? $stat->patients ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['patient_count'] ?? $stat['patients'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Doctor Performance Analysis -->
    @if(isset($doctorStats) && isset($resultStats) && count($doctorStats) > 0)
    <div class="report-section">
        <h2>Análisis de Rendimiento por Doctor</h2>
        <table class="report-table compact-table">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Pend.</th>
                    <th class="text-center">Proc.</th>
                    <th class="text-center">Comp.</th>
                    <th class="text-center">Tasa</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doctorStats as $doctor)
                @php
                    $doctorId = is_object($doctor) ? $doctor->id : $doctor['id'];
                    $stats = $resultStats[$doctorId] ?? [];
                    $pendingCount = $stats['pendingCount'] ?? 0;
                    $inProcessCount = $stats['inProcessCount'] ?? 0;
                    $completedCount = $stats['completedCount'] ?? 0;
                    $totalRequests = $pendingCount + $inProcessCount + $completedCount;
                    $completionRate = $totalRequests > 0 ? round(($completedCount / $totalRequests) * 100, 2) : 0;
                @endphp
                <tr>
                    <td class="compact-cell">
                        @if(is_object($doctor))
                            {{ $doctor->name ?? $doctor->nombre ?? 'N/A' }}
                        @elseif(is_array($doctor))
                            {{ $doctor['name'] ?? $doctor['nombre'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number compact-cell">{{ $totalRequests }}</td>
                    <td class="text-center compact-cell">
                        @if($pendingCount > 0)
                            <span class="status-pending">{{ $pendingCount }}</span>
                        @else
                            <span class="muted">0</span>
                        @endif
                    </td>
                    <td class="text-center compact-cell">
                        @if($inProcessCount > 0)
                            <span class="status-processing">{{ $inProcessCount }}</span>
                        @else
                            <span class="muted">0</span>
                        @endif
                    </td>
                    <td class="text-center compact-cell">
                        @if($completedCount > 0)
                            <span class="status-completed">{{ $completedCount }}</span>
                        @else
                            <span class="muted">0</span>
                        @endif
                    </td>
                    <td class="text-center compact-cell">
                        <span class="number">{{ $completionRate }}%</span>
                        @if($completionRate >= 90)
                            <span class="status-completed">●</span>
                        @elseif($completionRate >= 70)
                            <span class="status-processing">●</span>
                        @elseif($completionRate > 0)
                            <span class="status-pending">●</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Specialty Analysis -->
    @if(isset($specialtyStats) && count($specialtyStats) > 0)
    <div class="report-section">
        <h2>Análisis por Especialidad</h2>
        <table class="report-table compact-table">
            <thead>
                <tr>
                    <th>Especialidad</th>
                    <th class="text-center">Doctores</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Promedio por Doctor</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($specialtyStats as $specialty)
                <tr>
                    <td class="compact-cell">{{ $specialty->especialidad ?? 'Sin Especialidad' }}</td>
                    <td class="text-center number compact-cell">{{ $specialty->doctor_count }}</td>
                    <td class="text-center number compact-cell">{{ $specialty->total_requests }}</td>
                    <td class="text-center number compact-cell">{{ round($specialty->avg_per_doctor, 2) }}</td>
                    <td class="text-center number compact-cell">{{ $specialty->percentage ?? 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Monthly Doctor Activity -->
    @if(isset($monthlyDoctorActivity) && count($monthlyDoctorActivity) > 0)
    <div class="report-section">
        <h2>Actividad Mensual de Doctores</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th class="text-center">Doctores Activos</th>
                    <th class="text-center">Total Solicitudes</th>
                    <th class="text-center">Promedio por Doctor</th>
                    <th class="text-center">Nuevos Doctores</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyDoctorActivity as $activity)
                <tr>
                    <td>{{ $activity->month }}</td>
                    <td class="text-center number">{{ $activity->active_doctors }}</td>
                    <td class="text-center number">{{ $activity->total_requests }}</td>
                    <td class="text-center number">{{ round($activity->avg_per_doctor, 2) }}</td>
                    <td class="text-center number">{{ $activity->new_doctors ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Doctor Workload Distribution -->
    @if(isset($workloadDistribution) && count($workloadDistribution) > 0)
    <div class="report-section">
        <h2>Distribución de Carga de Trabajo</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Rango de Solicitudes</th>
                    <th class="text-center">Cantidad de Doctores</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Total Solicitudes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($workloadDistribution as $range)
                <tr>
                    <td>{{ $range->range }}</td>
                    <td class="text-center number">{{ $range->doctor_count }}</td>
                    <td class="text-center number">{{ $range->percentage }}%</td>
                    <td class="text-center number">{{ $range->total_requests }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection
