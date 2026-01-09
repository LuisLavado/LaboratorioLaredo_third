@extends('reports.base-report')

@section('content')
    <!-- Compact Summary -->
    <div class="report-section">
        <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            <strong>📊 Resumen de Resultados:</strong>
            {{ $totalRequests ?? 0 }} solicitudes •
            {{ $totalPatients ?? 0 }} pacientes •
            {{ $totalExams ?? 0 }} exámenes
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="report-section">
        <h2>Distribución por Estado de Resultados</h2>
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
        <h2>Estadísticas Diarias de Resultados</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Pendientes</th>
                    <th class="text-center">En Proceso</th>
                    <th class="text-center">Completados</th>
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
                            {{ $stat->total ?? $stat->count ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['total'] ?? $stat['count'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number status-pending">
                        @if(is_object($stat))
                            {{ $stat->pending ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['pending'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number status-processing">
                        @if(is_object($stat))
                            {{ $stat->in_process ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['in_process'] ?? 0 }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-center number status-completed">
                        @if(is_object($stat))
                            {{ $stat->completed ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['completed'] ?? 0 }}
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

    <!-- Processing Time Statistics -->
    @if(isset($processingTimeStats) && count($processingTimeStats) > 0)
    <div class="report-section">
        <h2>Estadísticas de Tiempo de Procesamiento</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Rango de Tiempo</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($processingTimeStats as $stat)
                <tr>
                    <td>{{ $stat->range ?? 'N/A' }}</td>
                    <td class="text-center number">{{ $stat->count ?? 0 }}</td>
                    <td class="text-center">{{ $stat->percentage ?? 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Status Counts Summary -->
    @if(isset($statusCounts) && count($statusCounts) > 0)
    <div class="report-section">
        <h2>Resumen Detallado por Estado</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Descripción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statusCounts as $status => $count)
                <tr>
                    <td class="bold 
                        @if($status === 'pendiente') status-pending
                        @elseif($status === 'en_proceso') status-processing
                        @elseif($status === 'completado') status-completed
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </td>
                    <td class="text-center number">{{ $count }}</td>
                    <td class="text-center">
                        @if($status === 'pendiente')
                            Resultados por procesar
                        @elseif($status === 'en_proceso')
                            Resultados en análisis
                        @elseif($status === 'completado')
                            Resultados finalizados
                        @else
                            {{ $status }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection
