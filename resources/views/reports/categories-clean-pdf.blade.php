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
        <h2>📊 Estadísticas por Categoría</h2>
        <table class="report-table compact-table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Categoría</th>
                    <th class="text-center">Exámenes</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Progreso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryStats as $index => $stat)
                <tr>
                    <td class="text-center bold compact-cell">{{ $index + 1 }}</td>
                    <td class="compact-cell">
                        @if(is_object($stat))
                            {{ $stat->name ?? $stat->nombre ?? $stat->category_name ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['name'] ?? $stat['nombre'] ?? $stat['category_name'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number compact-cell">
                        @if(is_object($stat))
                            {{ $stat->exam_count ?? $stat->exams ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['exam_count'] ?? $stat['exams'] ?? 0 }}
                        @else
                            0
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

    <!-- Top Exams by Category -->
    @if(isset($topExamsByCategory) && count($topExamsByCategory) > 0)
    <div class="report-section">
        <h2>📊 Exámenes Más Solicitados por Categoría</h2>

        @foreach($categoryStats as $category)
        @php
            $categoryId = is_object($category) ? $category->id : $category['id'];
            $categoryName = is_object($category) ? ($category->name ?? $category->nombre ?? 'N/A') : ($category['name'] ?? $category['nombre'] ?? 'N/A');
            $exams = $topExamsByCategory[$categoryId] ?? [];
        @endphp

        @if(count($exams) > 0)
        <div class="mb-20">
            <h3>{{ $categoryName }}</h3>
            <table class="report-table compact-table">
                <thead>
                    <tr>
                        <th>Pos.</th>
                        <th>Examen</th>
                        <th>Código</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">% de la Categoría</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exams as $index => $exam)
                    @php
                        $examName = is_object($exam) ? ($exam->name ?? $exam->nombre ?? 'N/A') : ($exam['name'] ?? $exam['nombre'] ?? 'N/A');
                        $examCode = is_object($exam) ? ($exam->code ?? 'N/A') : ($exam['code'] ?? 'N/A');
                        $count = is_object($exam) ? ($exam->count ?? 0) : ($exam['count'] ?? 0);

                        // Calcular porcentaje dentro de la categoría
                        $totalExamsInCategory = collect($exams)->sum(function($e) {
                            return is_object($e) ? ($e->count ?? 0) : ($e['count'] ?? 0);
                        });
                        $categoryPercentage = $totalExamsInCategory > 0 ? round(($count / $totalExamsInCategory) * 100, 2) : 0;
                    @endphp
                    <tr>
                        <td class="text-center bold compact-cell">{{ $index + 1 }}</td>
                        <td class="compact-cell">{{ $examName }}</td>
                        <td class="text-center compact-cell">{{ $examCode }}</td>
                        <td class="text-center number compact-cell">{{ $count }}</td>
                        <td class="text-center number compact-cell">{{ $categoryPercentage }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @endforeach
    </div>
    @endif
@endsection
