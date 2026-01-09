<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Laboratorio</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
        }
        h1 {
            color: #333366;
            text-align: center;
        }
        .info {
            margin-bottom: 20px;
        }
        .info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .even {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Laboratorio Laredo - Reporte {{ ucfirst($type) }}</h1>
    
    <div class="info">
        <p><strong>Periodo:</strong> {{ $periodo['inicio'] }} - {{ $periodo['fin'] }}</p>
        <p><strong>Generado:</strong> {{ $generado }}</p>
    </div>
    
    <h2>Resumen General</h2>
    <table>
        <tr>
            <th>Total Solicitudes</th>
            <th>Total Pacientes</th>
            <th>Total Exámenes</th>
        </tr>
        <tr>
            <td>{{ $totales['solicitudes'] }}</td>
            <td>{{ $totales['pacientes'] }}</td>
            <td>{{ $totales['examenes_realizados'] }}</td>
        </tr>
    </table>
    
    @if($type == 'patients' && isset($patients) && count($patients) > 0)
        <h2>Pacientes más frecuentes</h2>
        <table>
            <tr>
                <th>Nombre</th>
                <th>Documento</th>
                <th># Solicitudes</th>
                <th># Exámenes</th>
            </tr>
            @foreach(array_slice($patients, 0, 5) as $index => $patient)
                <tr class="{{ $index % 2 == 0 ? 'even' : '' }}">
                    <td>{{ $patient->nombre }} {{ $patient->apellido }}</td>
                    <td>{{ $patient->documento }}</td>
                    <td>{{ $patient->total_solicitudes }}</td>
                    <td>{{ $patient->total_examenes }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    
    @if($type == 'exams' && isset($examenes) && count($examenes) > 0)
        <h2>Exámenes más solicitados</h2>
        <table>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th># Realizados</th>
            </tr>
            @foreach(array_slice($examenes, 0, 5) as $index => $examen)
                <tr class="{{ $index % 2 == 0 ? 'even' : '' }}">
                    <td>{{ $examen->codigo }}</td>
                    <td>{{ $examen->nombre }}</td>
                    <td>{{ $examen->categoria }}</td>
                    <td>{{ $examen->total_realizados }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    
    @if($type == 'doctors' && isset($doctores) && count($doctores) > 0)
        <h2>Médicos más activos</h2>
        <table>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th># Solicitudes</th>
                <th># Pacientes</th>
            </tr>
            @foreach(array_slice($doctores, 0, 5) as $index => $doctor)
                <tr class="{{ $index % 2 == 0 ? 'even' : '' }}">
                    <td>{{ $doctor->nombre }}</td>
                    <td>{{ $doctor->email }}</td>
                    <td>{{ $doctor->total_solicitudes }}</td>
                    <td>{{ $doctor->total_pacientes }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    
    @if($type == 'services' && isset($servicios) && count($servicios) > 0)
        <h2>Servicios más utilizados</h2>
        <table>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th># Solicitudes</th>
                <th># Pacientes</th>
            </tr>
            @foreach(array_slice($servicios, 0, 5) as $index => $servicio)
                <tr class="{{ $index % 2 == 0 ? 'even' : '' }}">
                    <td>{{ $servicio->nombre }}</td>
                    <td>{{ $servicio->descripcion }}</td>
                    <td>{{ $servicio->total_solicitudes }}</td>
                    <td>{{ $servicio->total_pacientes }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
