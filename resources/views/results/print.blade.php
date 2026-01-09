<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Exámenes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #2563eb;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .patient-info {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .patient-info h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #2563eb;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .patient-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .patient-field {
            margin-bottom: 5px;
        }
        .patient-field strong {
            font-weight: bold;
            color: #555;
        }
        .request-info {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .request-info h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #2563eb;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .request-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .request-field {
            margin-bottom: 5px;
        }
        .request-field strong {
            font-weight: bold;
            color: #555;
        }
        .results {
            margin-bottom: 30px;
        }
        .results h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }
        .exam-result {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .exam-header {
            background-color: #2563eb;
            color: white;
            padding: 10px 15px;
        }
        .exam-header h3 {
            margin: 0;
            font-size: 16px;
        }
        .exam-body {
            padding: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .result-normal {
            color: #047857;
            font-weight: bold;
        }
        .result-abnormal {
            color: #dc2626;
            font-weight: bold;
        }
        .notes {
            margin-top: 10px;
            font-style: italic;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .signature {
            margin-top: 50px;
            text-align: center;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin: 10px auto;
        }
        .signature-name {
            font-weight: bold;
        }
        .signature-title {
            font-style: italic;
            color: #666;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(200, 200, 200, 0.2);
            z-index: -1;
            pointer-events: none;
        }
        @media print {
            body {
                padding: 0;
                margin: 15mm;
            }
            .no-print {
                display: none;
            }
            @page {
                size: A4;
                margin: 15mm;
            }
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .print-button:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Imprimir Resultados</button>

    <div class="watermark">LABORATORIO</div>

    <div class="header">
        <h1>LABORATORIO CLÍNICO</h1>
        <p>Resultados de Exámenes</p>
        <p>Fecha de emisión: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="patient-info">
        <h2>Información del Paciente</h2>
        <div class="patient-grid">
            <div class="patient-field">
                <strong>Nombre:</strong> {{ $solicitud->paciente->nombres }} {{ $solicitud->paciente->apellidos }}
            </div>
            <div class="patient-field">
                <strong>DNI:</strong> {{ $solicitud->paciente->dni }}
            </div>
            <div class="patient-field">
                <strong>Edad:</strong> {{ $solicitud->paciente->edad }} años
            </div>
            <div class="patient-field">
                <strong>Sexo:</strong> {{ ucfirst($solicitud->paciente->sexo) }}
            </div>
            @if($solicitud->paciente->edad_gestacional)
            <div class="patient-field">
                <strong>Edad Gestacional:</strong> {{ $solicitud->paciente->edad_gestacional }} semanas
            </div>
            @endif
            <div class="patient-field">
                <strong>Historia Clínica:</strong> {{ $solicitud->paciente->historia_clinica }}
            </div>
        </div>
    </div>

    <div class="request-info">
        <h2>Información de la Solicitud</h2>
        <div class="request-grid">
            <div class="request-field">
                <strong>Nº Solicitud:</strong> {{ $solicitud->id }}
            </div>
            <div class="request-field">
                <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($solicitud->created_at)->format('d/m/Y') }}
            </div>
            <div class="request-field">
                <strong>Servicio:</strong> {{ $solicitud->servicio->nombre }}
            </div>
            <div class="request-field">
                <strong>Médico:</strong> {{ $solicitud->medico ?? 'No especificado' }}
            </div>
            <div class="request-field">
                <strong>Tipo de Atención:</strong> {{ ucfirst($solicitud->tipo_atencion) }}
            </div>
            <div class="request-field">
                <strong>Estado:</strong> {{ ucfirst($solicitud->estado) }}
            </div>
        </div>
    </div>

    <div class="results">
        <h2>Resultados de Exámenes</h2>

        @foreach($detalles as $detalle)
            <div class="exam-result">
                <div class="exam-header">
                    <h3>{{ $detalle->examen->nombre }} ({{ $detalle->examen->codigo }})</h3>
                </div>
                <div class="exam-body">
                    @if($detalle->resultado)
                        <table>
                            <thead>
                                <tr>
                                    <th>Parámetro</th>
                                    <th>Resultado</th>
                                    <th>Valores de Referencia</th>
                                    <th>Unidades</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalle->resultado->parametros as $parametro)
                                    <tr>
                                        <td>{{ $parametro['nombre'] }}</td>
                                        <td class="{{ $parametro['estado'] == 'normal' ? 'result-normal' : 'result-abnormal' }}">
                                            {{ $parametro['valor'] }}
                                        </td>
                                        <td>{{ $parametro['referencia'] }}</td>
                                        <td>{{ $parametro['unidad'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if($detalle->resultado->observaciones)
                            <div class="notes">
                                <strong>Observaciones:</strong> {{ $detalle->resultado->observaciones }}
                            </div>
                        @endif
                    @else
                        <p>No hay resultados disponibles para este examen.</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="signature">
        <div class="signature-line"></div>
        @if($solicitud->user && $solicitud->user->role == 'laboratorio')
            <p class="signature-name">{{ $solicitud->user->nombre }} {{ $solicitud->user->apellido }}</p>
            <p class="signature-title">Encargado de Laboratorio</p>
        @else
            <p class="signature-name">{{ $solicitud->user ? $solicitud->user->nombre . ' ' . $solicitud->user->apellido : 'Dr. Juan Pérez Rodríguez' }}</p>
            <p class="signature-title">{{ $solicitud->user && $solicitud->user->role == 'doctor' ? 'Doctor' : 'Director del Laboratorio' }}</p>
            @if($solicitud->user && $solicitud->user->role == 'doctor' && $solicitud->user->colegiatura)
                <p class="signature-title">CMP: {{ $solicitud->user->colegiatura }}</p>
            @endif
        @endif
    </div>

    <div class="footer">
        <p>Este informe ha sido generado electrónicamente y es válido sin firma.</p>
        <p>© {{ date('Y') }} Laboratorio Clínico - Todos los derechos reservados</p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Uncomment the line below to automatically open print dialog when page loads
            // window.print();
        };
    </script>
</body>
</html>
