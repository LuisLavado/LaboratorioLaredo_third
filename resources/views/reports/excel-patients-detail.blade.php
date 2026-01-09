<!DOCTYPE html>
<html>
<head>
    <title>Detalle de Pacientes</title>
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
    <h1>Laboratorio Laredo - Detalle de Pacientes</h1>
    
    <div class="info">
        <p><strong>Periodo:</strong> {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
    </div>
    
    <table>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Documento</th>
            <th>Fecha Nacimiento</th>
            <th>Edad</th>
            <th># Solicitudes</th>
            <th># Exámenes</th>
        </tr>
        
        @foreach($patients as $index => $patient)
            <tr class="{{ $index % 2 == 0 ? 'even' : '' }}">
                <td>{{ $index + 1 }}</td>
                <td>{{ $patient->nombre }}</td>
                <td>{{ $patient->apellido }}</td>
                <td>{{ $patient->documento }}</td>
                <td>{{ $patient->fecha_nacimiento ? \Carbon\Carbon::parse($patient->fecha_nacimiento)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $patient->fecha_nacimiento ? \Carbon\Carbon::parse($patient->fecha_nacimiento)->age : 'N/A' }}</td>
                <td>{{ $patient->total_solicitudes }}</td>
                <td>{{ $patient->total_examenes }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
