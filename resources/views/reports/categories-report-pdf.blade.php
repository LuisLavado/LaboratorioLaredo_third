@extends('reports.base-report')

@section('content')
    <!-- Compact Summary -->
    <div class="report-section">
        <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            <strong>📊 Resumen:</strong>
            {{ $totalRequests ?? 0 }} solicitudes •
            {{ isset($categoryStats) ? count($categoryStats) : 0 }} categorías •
            {{ $totalPatients ?? 0 }} pacientes •
            {{ $totalExams ?? 0 }} exámenes
        </div>
    </div>

    <!-- Category Statistics -->
    @if(isset($categoryStats) && count($categoryStats) > 0)
    <div class="report-section">
        <h2>Estadísticas por Categoría</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th class="text-center">Exámenes</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Progreso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryStats as $index => $stat)
                <tr>
                    <td class="text-center bold">{{ $index + 1 }}</td>
                    <td>
                        @if(is_object($stat))
                            {{ $stat->name ?? $stat->nombre ?? $stat->category_name ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['name'] ?? $stat['nombre'] ?? $stat['category_name'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(is_object($stat))
                            {{ $stat->descripcion ?? $stat->description ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['descripcion'] ?? $stat['description'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number">
                        @if(is_object($stat))
                            {{ $stat->exam_count ?? $stat->exams ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['exam_count'] ?? $stat['exams'] ?? 0 }}
                        @else
                            0
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
                            {{ $stat->percentage ?? 0 }}%
                        @elseif(is_array($stat))
                            {{ $stat['percentage'] ?? 0 }}%
                        @else
                            0%
                        @endif
                    </td>
                    <td>
                        <div class="progress-bar">
                            @php
                                $percentage = 0;
                                if (is_object($stat)) {
                                    $percentage = $stat->percentage ?? 0;
                                } elseif (is_array($stat)) {
                                    $percentage = $stat['percentage'] ?? 0;
                                }
                            @endphp
                            <div class="progress-fill" style="width: {{ $percentage }}%;"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Top Exams by Category -->
    @if(isset($topExamsByCategory) && count($topExamsByCategory) > 0)
    <div class="report-section">
        <h2>Exámenes Más Solicitados por Categoría</h2>
        
        @foreach($topExamsByCategory as $categoryName => $exams)
        <div class="mb-20">
            <h3>{{ $categoryName }}</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Posición</th>
                        <th>Examen</th>
                        <th>Código</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">% de la Categoría</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exams as $index => $exam)
                    <tr>
                        <td class="text-center bold">{{ $index + 1 }}</td>
                        <td>
                            @if(is_object($exam))
                                {{ $exam->name ?? $exam->nombre ?? $exam->exam_name ?? 'N/A' }}
                            @elseif(is_array($exam))
                                {{ $exam['name'] ?? $exam['nombre'] ?? $exam['exam_name'] ?? 'N/A' }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="text-center">
                            @if(is_object($exam))
                                {{ $exam->codigo ?? $exam->code ?? 'N/A' }}
                            @elseif(is_array($exam))
                                {{ $exam['codigo'] ?? $exam['code'] ?? 'N/A' }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="text-center number">
                            @if(is_object($exam))
                                {{ $exam->count ?? $exam->total ?? 0 }}
                            @elseif(is_array($exam))
                                {{ $exam['count'] ?? $exam['total'] ?? 0 }}
                            @else
                                0
                            @endif
                        </td>
                        <td class="text-center number">
                            @if(is_object($exam))
                                {{ $exam->category_percentage ?? $exam->percentage ?? 0 }}%
                            @elseif(is_array($exam))
                                {{ $exam['category_percentage'] ?? $exam['percentage'] ?? 0 }}%
                            @else
                                0%
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Category Performance Analysis -->
    @if(isset($categoryPerformance) && count($categoryPerformance) > 0)
    <div class="report-section">
        <h2>Análisis de Rendimiento por Categoría</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Pendientes</th>
                    <th class="text-center">En Proceso</th>
                    <th class="text-center">Completados</th>
                    <th class="text-center">Tasa de Finalización</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryPerformance as $performance)
                <tr>
                    <td>{{ $performance->name }}</td>
                    <td class="text-center number">{{ $performance->total_requests }}</td>
                    <td class="text-center number status-pending">{{ $performance->pending ?? 0 }}</td>
                    <td class="text-center number status-processing">{{ $performance->in_process ?? 0 }}</td>
                    <td class="text-center number status-completed">{{ $performance->completed ?? 0 }}</td>
                    <td class="text-center">
                        @php
                            $completionRate = $performance->total_requests > 0 ? round(($performance->completed / $performance->total_requests) * 100, 2) : 0;
                        @endphp
                        <span class="number">{{ $completionRate }}%</span>
                        @if($completionRate >= 90)
                            <span class="status-completed">●</span>
                        @elseif($completionRate >= 70)
                            <span class="status-processing">●</span>
                        @else
                            <span class="status-pending">●</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Category Trends -->
    @if(isset($categoryTrends) && count($categoryTrends) > 0)
    <div class="report-section">
        <h2>Tendencias por Categoría</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th class="text-center">Mes Anterior</th>
                    <th class="text-center">Mes Actual</th>
                    <th class="text-center">Variación</th>
                    <th class="text-center">Tendencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryTrends as $trend)
                <tr>
                    <td>{{ $trend->name }}</td>
                    <td class="text-center number">{{ $trend->previous_month ?? 0 }}</td>
                    <td class="text-center number">{{ $trend->current_month ?? 0 }}</td>
                    <td class="text-center number">
                        @php
                            $variation = $trend->previous_month > 0 ? round((($trend->current_month - $trend->previous_month) / $trend->previous_month) * 100, 2) : 0;
                        @endphp
                        {{ $variation }}%
                    </td>
                    <td class="text-center">
                        @if($variation > 0)
                            <span style="color: #10b981;">↗ Crecimiento</span>
                        @elseif($variation < 0)
                            <span style="color: #ef4444;">↘ Decrecimiento</span>
                        @else
                            <span style="color: #6b7280;">→ Estable</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection
