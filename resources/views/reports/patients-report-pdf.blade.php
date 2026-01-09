@extends('reports.base-report')

@section('content')
    <!-- Compact Summary -->
    <div class="report-section">
        <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            <strong>📊 Resumen:</strong>
            {{ $totalRequests ?? 0 }} solicitudes •
            {{ isset($patientStats) ? count($patientStats) : 0 }} pacientes •
            {{ $totalDoctors ?? 0 }} doctores •
            {{ $totalExams ?? 0 }} exámenes
        </div>
    </div>

    <!-- Patient Statistics -->
    @if(isset($patientStats) && count($patientStats) > 0)
    <div class="report-section">
        <h2>Estadísticas por Paciente</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Paciente</th>
                    <th>DNI</th>
                    <th>Edad</th>
                    <th>Género</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patientStats as $index => $stat)
                <tr>
                    <td class="text-center bold">{{ $index + 1 }}</td>
                    <td>
                        @if(is_object($stat))
                            {{ $stat->name ?? $stat->nombre ?? $stat->patient_name ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['name'] ?? $stat['nombre'] ?? $stat['patient_name'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">
                        @if(is_object($stat))
                            {{ $stat->dni ?? $stat->document ?? 'N/A' }}
                        @elseif(is_array($stat))
                            {{ $stat['dni'] ?? $stat['document'] ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            // Calcular edad si hay fecha de nacimiento
                            $edad = 'N/A';
                            if (is_object($stat)) {
                                if (isset($stat->fecha_nacimiento)) {
                                    $edad = \Carbon\Carbon::parse($stat->fecha_nacimiento)->age;
                                } elseif (isset($stat->edad)) {
                                    $edad = $stat->edad;
                                }
                            } elseif (is_array($stat)) {
                                if (isset($stat['fecha_nacimiento'])) {
                                    $edad = \Carbon\Carbon::parse($stat['fecha_nacimiento'])->age;
                                } elseif (isset($stat['edad'])) {
                                    $edad = $stat['edad'];
                                }
                            }
                        @endphp
                        {{ $edad }}
                    </td>
                    <td class="text-center">
                        @php
                            $sexo = null;
                            if (is_object($stat)) {
                                $sexo = $stat->sexo ?? $stat->genero ?? $stat->gender ?? null;
                            } elseif (is_array($stat)) {
                                $sexo = $stat['sexo'] ?? $stat['genero'] ?? $stat['gender'] ?? null;
                            }
                        @endphp
                        @if($sexo)
                            @if($sexo == 'M' || $sexo == 'Masculino')
                                <span style="color: #3b82f6;">♂ Masculino</span>
                            @elseif($sexo == 'F' || $sexo == 'Femenino')
                                <span style="color: #ec4899;">♀ Femenino</span>
                            @else
                                {{ $sexo }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number">
                        @if(is_object($stat))
                            {{ $stat->count ?? $stat->total_requests ?? 0 }}
                        @elseif(is_array($stat))
                            {{ $stat['count'] ?? $stat['total_requests'] ?? 0 }}
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

    <!-- Gender Distribution -->
    @if(isset($genderStats) && count($genderStats) > 0)
    <div class="report-section">
        <h2>Distribución por Género</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Género</th>
                    <th class="text-center">Total Solicitudes</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Progreso</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // $genderStats viene como array asociativo: ['M' => 50, 'F' => 30, null => 5]
                    $totalGenderRequests = array_sum($genderStats);
                @endphp
                @foreach($genderStats as $sexo => $count)
                <tr>
                    <td>
                        @if($sexo == 'M' || $sexo == 'Masculino')
                            <span style="color: #3b82f6;">♂ Masculino</span>
                        @elseif($sexo == 'F' || $sexo == 'Femenino')
                            <span style="color: #ec4899;">♀ Femenino</span>
                        @elseif(is_null($sexo) || $sexo == '')
                            <span style="color: #6b7280;">No Especificado</span>
                        @else
                            {{ $sexo }}
                        @endif
                    </td>
                    <td class="text-center number">{{ $count }}</td>
                    <td class="text-center number">
                        @php
                            $percentage = $totalGenderRequests > 0 ? round(($count / $totalGenderRequests) * 100, 2) : 0;
                        @endphp
                        {{ $percentage }}%
                    </td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $percentage }}%;
                                background: {{ ($sexo == 'M' || $sexo == 'Masculino') ? '#3b82f6' : (($sexo == 'F' || $sexo == 'Femenino') ? '#ec4899' : '#6b7280') }};"></div>
                        </div>
                    </td>
                </tr>
                @endforeach

                <!-- Total Row -->
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td class="bold">Total</td>
                    <td class="text-center bold">{{ $totalGenderRequests }}</td>
                    <td class="text-center bold">100%</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Age Group Analysis -->
    @if(isset($ageGroupStats) && count($ageGroupStats) > 0)
    <div class="report-section">
        <h2>Análisis por Grupo de Edad</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Grupo de Edad</th>
                    <th class="text-center">Cantidad de Pacientes</th>
                    <th class="text-center">Total Solicitudes</th>
                    <th class="text-center">Promedio por Paciente</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ageGroupStats as $group)
                <tr>
                    <td>{{ $group->age_group }}</td>
                    <td class="text-center number">{{ $group->patient_count }}</td>
                    <td class="text-center number">{{ $group->total_requests }}</td>
                    <td class="text-center number">{{ round($group->avg_per_patient, 2) }}</td>
                    <td class="text-center number">{{ $group->percentage ?? 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Top 15 Most Active Patients -->
    @if(isset($patientStats) && count($patientStats) > 15)
    <div class="report-section">
        <h2>Top 15 Pacientes Más Activos</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Paciente</th>
                    <th>DNI</th>
                    <th>Edad</th>
                    <th>Género</th>
                    <th class="text-center">Solicitudes</th>
                    <th class="text-center">Última Visita</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patientStats->take(15) as $index => $stat)
                <tr>
                    <td class="text-center bold">{{ $index + 1 }}</td>
                    <td>{{ $stat->name }}</td>
                    <td class="text-center">{{ $stat->dni ?? 'N/A' }}</td>
                    <td class="text-center">
                        @php
                            // Calcular edad si hay fecha de nacimiento
                            $edad = 'N/A';
                            if (is_object($stat)) {
                                if (isset($stat->fecha_nacimiento)) {
                                    $edad = \Carbon\Carbon::parse($stat->fecha_nacimiento)->age;
                                } elseif (isset($stat->edad)) {
                                    $edad = $stat->edad;
                                }
                            } elseif (is_array($stat)) {
                                if (isset($stat['fecha_nacimiento'])) {
                                    $edad = \Carbon\Carbon::parse($stat['fecha_nacimiento'])->age;
                                } elseif (isset($stat['edad'])) {
                                    $edad = $stat['edad'];
                                }
                            }
                        @endphp
                        {{ $edad }}
                    </td>
                    <td class="text-center">
                        @php
                            $sexo = null;
                            if (is_object($stat)) {
                                $sexo = $stat->sexo ?? $stat->genero ?? $stat->gender ?? null;
                            } elseif (is_array($stat)) {
                                $sexo = $stat['sexo'] ?? $stat['genero'] ?? $stat['gender'] ?? null;
                            }
                        @endphp
                        @if($sexo == 'M' || $sexo == 'Masculino')
                            <span style="color: #3b82f6;">♂</span>
                        @elseif($sexo == 'F' || $sexo == 'Femenino')
                            <span style="color: #ec4899;">♀</span>
                        @elseif($sexo)
                            {{ $sexo }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center number">{{ $stat->count }}</td>
                    <td class="text-center">{{ isset($stat->last_visit) ? \Carbon\Carbon::parse($stat->last_visit)->format('d/m/Y') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Patient Frequency Analysis -->
    @if(isset($frequencyAnalysis) && count($frequencyAnalysis) > 0)
    <div class="report-section">
        <h2>Análisis de Frecuencia de Visitas</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Frecuencia de Visitas</th>
                    <th class="text-center">Cantidad de Pacientes</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-center">Total Solicitudes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($frequencyAnalysis as $freq)
                <tr>
                    <td>{{ $freq->frequency_range }}</td>
                    <td class="text-center number">{{ $freq->patient_count }}</td>
                    <td class="text-center number">{{ $freq->percentage }}%</td>
                    <td class="text-center number">{{ $freq->total_requests }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Monthly Patient Activity -->
    @if(isset($monthlyPatientActivity) && count($monthlyPatientActivity) > 0)
    <div class="report-section">
        <h2>Actividad Mensual de Pacientes</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th class="text-center">Pacientes Activos</th>
                    <th class="text-center">Nuevos Pacientes</th>
                    <th class="text-center">Total Solicitudes</th>
                    <th class="text-center">Promedio por Paciente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyPatientActivity as $activity)
                <tr>
                    <td>{{ $activity->month }}</td>
                    <td class="text-center number">{{ $activity->active_patients }}</td>
                    <td class="text-center number">{{ $activity->new_patients ?? 0 }}</td>
                    <td class="text-center number">{{ $activity->total_requests }}</td>
                    <td class="text-center number">{{ round($activity->avg_per_patient, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection
