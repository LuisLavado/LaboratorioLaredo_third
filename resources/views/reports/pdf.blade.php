@extends('reports.base-report')

@section('content')
    <!-- Dynamic Content Based on Report Type -->
    @if(isset($type))
        @switch($type)
            @case('general')
                @include('reports.general-content')
                @break
            @case('exams')
                @include('reports.exams-content')
                @break
            @case('services')
                @include('reports.services-content')
                @break
            @case('doctors')
                @include('reports.doctors-content')
                @break
            @case('patients')
                @include('reports.patients-content')
                @break
            @case('categories')
                @include('reports.categories-content')
                @break
            @case('doctor_personal')
                @include('reports.doctor-personal-content')
                @break
            @default
                @include('reports.default-content')
        @endswitch
    @else
        @include('reports.default-content')
    @endif
@endsection

{{-- Default Content --}}
@section('default-content')
<div class="report-section">
    <h2>Reporte del Sistema</h2>
    
    @if(isset($data) && is_array($data))
        <!-- Compact Summary -->
        <div class="report-section">
            <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
                <strong>📊 Resumen:</strong>
                @if(isset($data['totalRequests']))
                    {{ $data['totalRequests'] }} solicitudes
                @endif
                @if(isset($data['totalPatients']))
                    • {{ $data['totalPatients'] }} pacientes
                @endif
                @if(isset($data['totalExams']))
                    • {{ $data['totalExams'] }} exámenes
                @endif
            </div>
        </div>

        <!-- Status Distribution -->
        @if(isset($data['pendingCount']) || isset($data['inProcessCount']) || isset($data['completedCount']))
        <div class="report-section">
            <h3>Distribución por Estado</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $total = ($data['pendingCount'] ?? 0) + ($data['inProcessCount'] ?? 0) + ($data['completedCount'] ?? 0);
                        $total = $total > 0 ? $total : 1;
                    @endphp
                    
                    @if(isset($data['pendingCount']))
                    <tr>
                        <td class="status-pending">Pendientes</td>
                        <td class="text-center">{{ $data['pendingCount'] }}</td>
                        <td class="text-center">{{ round(($data['pendingCount'] / $total) * 100, 2) }}%</td>
                    </tr>
                    @endif
                    
                    @if(isset($data['inProcessCount']))
                    <tr>
                        <td class="status-processing">En Proceso</td>
                        <td class="text-center">{{ $data['inProcessCount'] }}</td>
                        <td class="text-center">{{ round(($data['inProcessCount'] / $total) * 100, 2) }}%</td>
                    </tr>
                    @endif
                    
                    @if(isset($data['completedCount']))
                    <tr>
                        <td class="status-completed">Completados</td>
                        <td class="text-center">{{ $data['completedCount'] }}</td>
                        <td class="text-center">{{ round(($data['completedCount'] / $total) * 100, 2) }}%</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @endif

        <!-- Daily Statistics -->
        @if(isset($data['dailyStats']) && count($data['dailyStats']) > 0)
        <div class="report-section">
            <h3>Estadísticas Diarias</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="text-center">Solicitudes</th>
                        <th class="text-center">Pacientes</th>
                        <th class="text-center">Exámenes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['dailyStats'] as $stat)
                    <tr>
                        <td>{{ $stat->date }}</td>
                        <td class="text-center number">{{ $stat->count }}</td>
                        <td class="text-center number">{{ $stat->patientCount ?? 0 }}</td>
                        <td class="text-center number">{{ $stat->examCount ?? 0 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Generic Statistics Table -->
        @if(isset($data['stats']) && count($data['stats']) > 0)
        <div class="report-section">
            <h3>Estadísticas</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Elemento</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['stats'] as $stat)
                    <tr>
                        <td>{{ $stat->name ?? $stat->elemento ?? 'N/A' }}</td>
                        <td class="text-center number">{{ $stat->count ?? $stat->cantidad ?? 0 }}</td>
                        <td class="text-center number">{{ $stat->percentage ?? $stat->porcentaje ?? 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @else
        <div class="report-section">
            <p style="text-align: center; color: #6b7280; font-style: italic; padding: 40px;">
                No hay datos disponibles para mostrar en este reporte.
            </p>
        </div>
    @endif
</div>
@endsection
