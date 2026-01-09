@extends('reports.base-report')

@section('content')
    <!-- Compact Summary -->
    <div class="report-section">
        @if(isset($selected_services_only) && $selected_services_only)
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
                <strong>🔍 Reporte Filtrado:</strong>
                {{ $totalRequests ?? 0 }} solicitudes •
                {{ isset($serviceStats) ? count($serviceStats) : 0 }} servicios seleccionados •
                {{ $totalExams ?? 0 }} exámenes
            </div>
        @else
            <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
                <strong>📊 Reporte General:</strong>
                {{ $totalRequests ?? 0 }} solicitudes •
                {{ isset($serviceStats) ? count($serviceStats) : 0 }} servicios •
                {{ $totalExams ?? 0 }} exámenes
            </div>
        @endif
    </div>

    <!-- Filter Information -->
    @if(isset($selected_services_only) && $selected_services_only)
    <div class="report-section">
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong>🔍 Reporte Filtrado:</strong> Este reporte muestra únicamente los servicios seleccionados en los filtros de búsqueda.
        </div>
        
        <!-- Detailed Services with Exams -->
        @if(isset($serviceStats) && count($serviceStats) > 0)
        <h2>Servicios Seleccionados - Vista Detallada</h2>
        
        <!-- Summary Table -->
        <table class="report-table compact-table mb-20">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serviceStats as $stat)
                <tr>
                    <td class="bold compact-cell">
                        @if(is_object($stat))
                            {{ $stat->name ?? $stat->nombre ?? $stat->service_name ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['name'] ?? $stat['nombre'] ?? $stat['service_name'] ?? 'N/A' }}
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
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Detailed breakdown by service -->
        @foreach($serviceStats as $service)
        <div class="report-section">
            <h3>
                @if(is_object($service))
                    {{ $service->name ?? $service->nombre ?? $service->service_name ?? 'N/A' }}
                @elseif(is_array($service))
                    {{ $service['name'] ?? $service['nombre'] ?? $service['service_name'] ?? 'N/A' }}
                @else
                    N/A
                @endif
            </h3>
            <p><strong>Solicitudes:</strong>
                @if(is_object($service))
                    {{ $service->count ?? $service->total ?? 0 }} ({{ $service->percentage ?? 0 }}%)
                @elseif(is_array($service))
                    {{ $service['count'] ?? $service['total'] ?? 0 }} ({{ $service['percentage'] ?? 0 }}%)
                @else
                    0 (0%)
                @endif
            </p>

            @php
                $exams = null;
                if (is_object($service)) {
                    $exams = $service->exams ?? null;
                } elseif (is_array($service)) {
                    $exams = $service['exams'] ?? null;
                }
            @endphp

            @if($exams && count($exams) > 0)
            <h4>Exámenes del Servicio</h4>
            <table class="report-table compact-table">
                <thead>
                    <tr>
                        <th>Examen</th>
                        <th>Código</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">% del Servicio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exams as $exam)
                    <tr>
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
                                {{ $exam->service_percentage ?? $exam->percentage ?? 0 }}%
                            @elseif(is_array($exam))
                                {{ $exam['service_percentage'] ?? $exam['percentage'] ?? 0 }}%
                            @else
                                0%
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
        @endforeach
        @endif
    @else
        <!-- Standard Services Report -->
        @if(isset($serviceStats) && count($serviceStats) > 0)
        <div class="report-section">
            <h2>📊 Estadísticas por Servicio - Vista General</h2>
            <table class="report-table compact-table">
                <thead>
                    <tr>
                        <th>Posición</th>
                        <th>Servicio</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Porcentaje</th>
                        <th class="text-center">Progreso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serviceStats as $index => $stat)
                    <tr>
                        <td class="text-center bold">{{ $index + 1 }}</td>
                        <td>
                            @if(is_object($stat))
                                {{ $stat->name ?? $stat->nombre ?? $stat->service_name ?? 'N/A' }}
                            @elseif(is_array($stat))
                                {{ $stat['name'] ?? $stat['nombre'] ?? $stat['service_name'] ?? 'N/A' }}
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
    @endif

    <!-- Service-Exam Summary -->
    @if(isset($topExamsByService) && count($topExamsByService) > 0)
    <div class="report-section">
        @if(isset($selected_services_only) && $selected_services_only)
            <h2>🔍 Resumen de Exámenes - Servicios Seleccionados</h2>
        @else
            <h2>📊 Resumen de Exámenes por Servicio</h2>
        @endif
        <table class="report-table compact-table">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th class="text-center">Total Exámenes</th>
                    <th class="text-center">Tipos de Exámenes</th>
                    <th class="text-center">Examen Más Solicitado</th>
                    <th class="text-center">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serviceStats as $service)
                @php
                    $serviceId = is_object($service) ? $service->id : $service['id'];
                    $serviceName = is_object($service) ? ($service->name ?? $service->nombre ?? 'N/A') : ($service['name'] ?? $service['nombre'] ?? 'N/A');
                    $exams = $topExamsByService[$serviceId] ?? [];
                    $totalExams = collect($exams)->sum(function($e) {
                        return is_object($e) ? ($e->count ?? 0) : ($e['count'] ?? 0);
                    });
                    $uniqueExams = count($exams);
                    $topExam = collect($exams)->first();
                @endphp
                <tr>
                    <td class="compact-cell">{{ $serviceName }}</td>
                    <td class="text-center number compact-cell">{{ $totalExams }}</td>
                    <td class="text-center number compact-cell">{{ $uniqueExams }}</td>
                    <td class="compact-cell">
                        @if($topExam)
                            {{ is_object($topExam) ? ($topExam->name ?? $topExam->nombre ?? 'N/A') : ($topExam['name'] ?? $topExam['nombre'] ?? 'N/A') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number compact-cell">
                        @if($topExam)
                            {{ is_object($topExam) ? ($topExam->count ?? 0) : ($topExam['count'] ?? 0) }}
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

    <!-- Top Exams by Service -->
    @if(isset($topExamsByService) && count($topExamsByService) > 0)
    <div class="report-section">
        @if(isset($selected_services_only) && $selected_services_only)
            <h2>🔍 Exámenes Más Solicitados - Servicios Seleccionados</h2>
        @else
            <h2>📊 Exámenes Más Solicitados por Servicio</h2>
        @endif

        @foreach($serviceStats as $service)
        @php
            $serviceId = is_object($service) ? $service->id : $service['id'];
            $serviceName = is_object($service) ? ($service->name ?? $service->nombre ?? 'N/A') : ($service['name'] ?? $service['nombre'] ?? 'N/A');
            $exams = $topExamsByService[$serviceId] ?? [];
        @endphp

        @if(count($exams) > 0)
        <div class="mb-20">
            <h3>{{ $serviceName }}</h3>
            <table class="report-table compact-table">
                <thead>
                    <tr>
                        <th>Pos.</th>
                        <th>Examen</th>
                        <th>Categoría</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">% del Servicio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exams as $index => $exam)
                    @php
                        $examName = is_object($exam) ? ($exam->name ?? $exam->nombre ?? 'N/A') : ($exam['name'] ?? $exam['nombre'] ?? 'N/A');
                        $category = is_object($exam) ? ($exam->category ?? $exam->categoria ?? 'N/A') : ($exam['category'] ?? $exam['categoria'] ?? 'N/A');
                        $count = is_object($exam) ? ($exam->count ?? 0) : ($exam['count'] ?? 0);

                        // Calcular porcentaje dentro del servicio
                        $totalExamsInService = collect($exams)->sum(function($e) {
                            return is_object($e) ? ($e->count ?? 0) : ($e['count'] ?? 0);
                        });
                        $servicePercentage = $totalExamsInService > 0 ? round(($count / $totalExamsInService) * 100, 2) : 0;
                    @endphp
                    <tr>
                        <td class="text-center bold compact-cell">{{ $index + 1 }}</td>
                        <td class="compact-cell">{{ $examName }}</td>
                        <td class="compact-cell">{{ $category }}</td>
                        <td class="text-center number compact-cell">{{ $count }}</td>
                        <td class="text-center number compact-cell">{{ $servicePercentage }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @endforeach
    </div>
    @endif

    <!-- Service Performance Analysis -->
    @if(isset($servicePerformance) && count($servicePerformance) > 0)
    <div class="report-section">
        <h2>Análisis de Rendimiento por Servicio</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Tiempo Promedio</th>
                    <th class="text-center">Tasa de Finalización</th>
                </tr>
            </thead>
            <tbody>
                @foreach($servicePerformance as $performance)
                <tr>
                    <td>{{ $performance->name }}</td>
                    <td class="text-center number">{{ $performance->requests }}</td>
                    <td class="text-center">{{ $performance->avg_time ?? 'N/A' }}</td>
                    <td class="text-center">
                        <span class="number">{{ $performance->completion_rate ?? 0 }}%</span>
                        @if(isset($performance->completion_rate))
                            @if($performance->completion_rate >= 90)
                                <span class="status-completed">●</span>
                            @elseif($performance->completion_rate >= 70)
                                <span class="status-processing">●</span>
                            @else
                                <span class="status-pending">●</span>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Monthly Service Trends -->
    @if(isset($monthlyServiceTrends) && count($monthlyServiceTrends) > 0)
    <div class="report-section">
        <h2>Tendencias Mensuales por Servicio</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th class="text-center">Total Solicitudes</th>
                    <th class="text-center">Servicios Activos</th>
                    <th class="text-center">Promedio por Servicio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyServiceTrends as $trend)
                <tr>
                    <td>{{ $trend->month }}</td>
                    <td class="text-center number">{{ $trend->total_requests }}</td>
                    <td class="text-center number">{{ $trend->active_services }}</td>
                    <td class="text-center number">{{ round($trend->avg_per_service, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection
