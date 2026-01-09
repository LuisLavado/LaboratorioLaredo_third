@extends('reports.base-report')

@section('content')
    <!-- Compact Summary -->
    <div class="report-section">
        @if(isset($selected_exams_only) && $selected_exams_only)
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
                <strong>🔍 Reporte Filtrado:</strong>
                {{ $totalRequests ?? 0 }} solicitudes •
                {{ $totalExams ?? 0 }} exámenes •
                {{ isset($examStats) ? count($examStats) : 0 }} tipos seleccionados
            </div>
        @else
            <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
                <strong>📊 Reporte General:</strong>
                {{ $totalRequests ?? 0 }} solicitudes •
                {{ $totalExams ?? 0 }} exámenes •
                {{ isset($examStats) ? count($examStats) : 0 }} tipos
            </div>
        @endif
    </div>

    <!-- Filter Information -->
    @if(isset($selected_exams_only) && $selected_exams_only)
    <div class="report-section">
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong>🔍 Reporte Filtrado:</strong> Este reporte muestra únicamente los exámenes seleccionados en los filtros de búsqueda.
        </div>
    </div>
    @endif

    <!-- Exam Statistics -->
    @if(isset($examStats) && count($examStats) > 0)
    <div class="report-section">
        @if(isset($selected_exams_only) && $selected_exams_only)
            <h2>🔍 Estadísticas - Exámenes Seleccionados</h2>
        @else
            <h2>📊 Estadísticas por Examen - Vista General</h2>
        @endif
        <table class="report-table compact-table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Examen</th>
                    <th>Código</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Progreso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examStats as $index => $stat)
                <tr>
                    <td class="text-center bold compact-cell">{{ $index + 1 }}</td>
                    <td class="compact-cell">
                        @if(is_object($stat))
                            {{ $stat->name ?? $stat->nombre ?? $stat->exam_name ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['name'] ?? $stat['nombre'] ?? $stat['exam_name'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center compact-cell">
                        @if(is_object($stat))
                            {{ $stat->codigo ?? $stat->code ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['codigo'] ?? $stat['code'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number compact-cell">
                        @if(is_object($stat))
                            {{ $stat->count ?? $stat->total ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['count'] ?? $stat['total'] ?? 0 }}
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

    <!-- Top 10 Most Requested Exams -->
    @if(isset($examStats) && count($examStats) > 10)
    <div class="report-section">
        @if(isset($selected_exams_only) && $selected_exams_only)
            <h2>🔍 Top 10 - Exámenes Seleccionados</h2>
        @else
            <h2>📊 Top 10 Exámenes Más Solicitados</h2>
        @endif
        <table class="report-table compact-table">
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
                @foreach($examStats->take(10) as $index => $stat)
                <tr>
                    <td class="text-center bold">{{ $index + 1 }}</td>
                    <td>
                        @if(is_object($stat))
                            {{ $stat->name ?? $stat->nombre ?? $stat->exam_name ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['name'] ?? $stat['nombre'] ?? $stat['exam_name'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">
                        @if(is_object($stat))
                            {{ $stat->codigo ?? $stat->code ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['codigo'] ?? $stat['code'] ?? 'N/A' }}
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
                            {{ $stat->percentage ?? 0 }}%
                        @elseif(is_array($stat))
                            {{ $stat['percentage'] ?? 0 }}%
                        @else
                            0%
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Exam Categories Analysis -->
    @if(isset($categoryStats) && count($categoryStats) > 0)
    <div class="report-section">
        <h2>Análisis por Categorías</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th class="text-center">Cantidad de Exámenes</th>
                    <th class="text-center">Total Solicitudes</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryStats as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td class="text-center number">{{ $category->exam_count ?? 0 }}</td>
                    <td class="text-center number">{{ $category->count }}</td>
                    <td class="text-center number">{{ $category->percentage ?? 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Processing Time Analysis -->
    @if(isset($processingTimeStats) && count($processingTimeStats) > 0)
    <div class="report-section">
        <h2>Tiempo Promedio de Procesamiento</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Examen</th>
                    <th>Código</th>
                    <th class="text-center">Tiempo Promedio</th>
                    <th class="text-center">Muestras Procesadas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($processingTimeStats as $stat)
                <tr>
                    <td>{{ $stat->examen->nombre ?? 'N/A' }}</td>
                    <td class="text-center">{{ $stat->examen->codigo ?? 'N/A' }}</td>
                    <td class="text-center">
                        @if($stat->avg_hours > 0)
                            {{ round($stat->avg_hours, 2) }} horas
                            <br><small>({{ floor($stat->avg_hours) }}h {{ round(($stat->avg_hours - floor($stat->avg_hours)) * 60) }}m)</small>
                        @else
                            {{ round($stat->avg_hours * 60) }} minutos
                        @endif
                    </td>
                    <td class="text-center number">{{ $stat->sample_count ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Monthly Trends -->
    @if(isset($monthlyTrends) && count($monthlyTrends) > 0)
    <div class="report-section">
        <h2>Tendencias Mensuales</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Exámenes</th>
                    <th class="text-center">Promedio Diario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrends as $trend)
                <tr>
                    <td>{{ $trend->month }}</td>
                    <td class="text-center number">{{ $trend->requests }}</td>
                    <td class="text-center number">{{ $trend->exams }}</td>
                    <td class="text-center number">{{ round($trend->daily_average, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection
