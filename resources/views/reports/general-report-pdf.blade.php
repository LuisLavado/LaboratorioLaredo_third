@extends('reports.base-report')

@section('content')
    <!-- Compact Summary -->
    <div class="report-section">
        <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            <strong>📊 Resumen General:</strong>
            {{ $totalRequests ?? 0 }} solicitudes •
            {{ $totalPatients ?? 0 }} pacientes •
            {{ $totalExams ?? 0 }} exámenes
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="report-section">
        <h2>Distribución por Estado</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Progreso</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = ($pendingCount ?? 0) + ($inProcessCount ?? 0) + ($completedCount ?? 0);
                    $total = $total > 0 ? $total : 1; // Avoid division by zero
                @endphp
                <tr>
                    <td class="bold status-pending">Pendientes</td>
                    <td class="text-center">{{ $pendingCount ?? 0 }}</td>
                    <td class="text-center">{{ round((($pendingCount ?? 0) / $total) * 100, 2) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ round((($pendingCount ?? 0) / $total) * 100, 2) }}%; background: #f59e0b;"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bold status-processing">En Proceso</td>
                    <td class="text-center">{{ $inProcessCount ?? 0 }}</td>
                    <td class="text-center">{{ round((($inProcessCount ?? 0) / $total) * 100, 2) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ round((($inProcessCount ?? 0) / $total) * 100, 2) }}%; background: #3b82f6;"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bold status-completed">Completados</td>
                    <td class="text-center">{{ $completedCount ?? 0 }}</td>
                    <td class="text-center">{{ round((($completedCount ?? 0) / $total) * 100, 2) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ round((($completedCount ?? 0) / $total) * 100, 2) }}%; background: #10b981;"></div>
                        </div>
                    </td>
                </tr>
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td class="bold">Total</td>
                    <td class="text-center bold">{{ $total }}</td>
                    <td class="text-center bold">100%</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Daily Statistics -->
    @if(isset($dailyStats) && count($dailyStats) > 0)
    <div class="report-section">
        <h2>Estadísticas Diarias</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Pacientes</th>
                    <th class="text-center">Exámenes</th>
                    <th class="text-center">Promedio Exámenes/Solicitud</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyStats as $stat)
                <tr>
                    <td>
                        @php
                            $date = null;
                            if (is_object($stat)) {
                                $date = $stat->date ?? $stat->fecha ?? null;
                            } elseif (is_array($stat)) {
                                $date = $stat['date'] ?? $stat['fecha'] ?? null;
                            }
                        @endphp
                        @if($date)
                            {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number">
                        @if(is_object($stat))
                            {{ $stat->count ?? $stat->total ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['count'] ?? $stat['total'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number">
                        @if(is_object($stat))
                            {{ $stat->patientCount ?? $stat->patients ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['patientCount'] ?? $stat['patients'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number">
                        @if(is_object($stat))
                            {{ $stat->examCount ?? $stat->exams ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['examCount'] ?? $stat['exams'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number">
                        @php
                            $count = 0;
                            $examCount = 0;
                            if (is_object($stat)) {
                                $count = $stat->count ?? $stat->total ?? 0;
                                $examCount = $stat->examCount ?? $stat->exams ?? 0;
                            } elseif (is_array($stat)) {
                                $count = $stat['count'] ?? $stat['total'] ?? 0;
                                $examCount = $stat['examCount'] ?? $stat['exams'] ?? 0;
                            }
                        @endphp
                        {{ $count > 0 ? round($examCount / $count, 2) : 0 }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection
