<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test PDF - {{ $title ?? 'Reporte General' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            background: #1f4e79;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .section {
            margin: 20px 0;
        }
        .stat-item {
            margin: 10px 0;
            padding: 10px;
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LABORATORIO CLÍNICO LAREDO</h1>
        <h2>REPORTE GENERAL - VERSIÓN TEST</h2>
    </div>
    
    <div class="section">
        <h3>Información del Período</h3>
        <p><strong>Período:</strong> {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}</p>
        <p><strong>Generado el:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>Generado por:</strong> {{ $generatedBy ?? 'Sistema' }}</p>
    </div>
    
    <div class="section">
        <h3>Resumen Ejecutivo</h3>
        <div class="stat-item">
            <strong>Total de Solicitudes:</strong> {{ number_format($totalRequests ?? 0) }}
        </div>
        <div class="stat-item">
            <strong>Total de Pacientes:</strong> {{ number_format($totalPatients ?? 0) }}
        </div>
        <div class="stat-item">
            <strong>Total de Exámenes:</strong> {{ number_format($totalExams ?? 0) }}
        </div>
        <div class="stat-item">
            <strong>Completados:</strong> {{ number_format($completedCount ?? 0) }}
        </div>
        <div class="stat-item">
            <strong>En Proceso:</strong> {{ number_format($inProcessCount ?? 0) }}
        </div>
        <div class="stat-item">
            <strong>Pendientes:</strong> {{ number_format($pendingCount ?? 0) }}
        </div>
    </div>
    
    @if(isset($patients) && is_countable($patients) && count($patients) > 0)
    <div class="section">
        <h3>Información de Pacientes</h3>
        <p><strong>Pacientes únicos encontrados:</strong> {{ count($patients) }}</p>
        
        @php
            $masculino = 0;
            $femenino = 0;
            $otros = 0;
            
            foreach($patients as $patient) {
                if (!$patient) continue; // Saltar si el paciente es null
                
                $sexo = strtolower($patient->sexo ?? '');
                if (in_array($sexo, ['m', 'masculino', 'hombre'])) {
                    $masculino++;
                } elseif (in_array($sexo, ['f', 'femenino', 'mujer'])) {
                    $femenino++;
                } else {
                    $otros++;
                }
            }
            
            $totalPacientes = count($patients);
        @endphp
        
        <div class="stat-item">
            <strong>Masculinos:</strong> {{ $masculino }} ({{ $totalPacientes > 0 ? number_format(($masculino / $totalPacientes) * 100, 1) : 0 }}%)
        </div>
        <div class="stat-item">
            <strong>Femeninos:</strong> {{ $femenino }} ({{ $totalPacientes > 0 ? number_format(($femenino / $totalPacientes) * 100, 1) : 0 }}%)
        </div>
        <div class="stat-item">
            <strong>No especificado:</strong> {{ $otros }} ({{ $totalPacientes > 0 ? number_format(($otros / $totalPacientes) * 100, 1) : 0 }}%)
        </div>
    </div>
    @else
    <div class="section">
        <h3>Información de Pacientes</h3>
        <p>No se encontraron pacientes en el período especificado.</p>
        <p><strong>Tipo de datos recibidos:</strong> {{ isset($patients) ? gettype($patients) : 'No definido' }}</p>
        @if(isset($patients) && is_countable($patients))
            <p><strong>Cantidad:</strong> {{ count($patients) }}</p>
        @endif
    </div>
    @endif
    
    @if(isset($solicitudes) && count($solicitudes) > 0)
    <div class="section">
        <h3>Últimas Solicitudes</h3>
        <p><strong>Solicitudes encontradas:</strong> {{ count($solicitudes) }}</p>
        @foreach(collect($solicitudes)->take(5) as $solicitud)
            <div class="stat-item">
                <strong>ID:</strong> {{ $solicitud->id ?? 'N/A' }} - 
                <strong>Fecha:</strong> {{ isset($solicitud->fecha) ? \Carbon\Carbon::parse($solicitud->fecha)->format('d/m/Y') : 'N/A' }}
            </div>
        @endforeach
    </div>
    @endif
    
    <div class="section">
        <h3>Información de Debug</h3>
        <div class="stat-item">
            <strong>Variables disponibles:</strong><br>
            - totalRequests: {{ isset($totalRequests) ? 'Sí' : 'No' }}<br>
            - totalPatients: {{ isset($totalPatients) ? 'Sí' : 'No' }}<br>
            - patients: {{ isset($patients) ? 'Sí (' . (is_countable($patients) ? count($patients) : 'no contable') . ')' : 'No' }}<br>
            - solicitudes: {{ isset($solicitudes) ? 'Sí (' . count($solicitudes) . ')' : 'No' }}<br>
            - examStats: {{ isset($examStats) ? 'Sí (' . count($examStats) . ')' : 'No' }}<br>
            - serviceStats: {{ isset($serviceStats) ? 'Sí (' . count($serviceStats) . ')' : 'No' }}
        </div>
    </div>
</body>
</html>
