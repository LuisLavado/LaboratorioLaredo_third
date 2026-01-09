<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Resultados de Laboratorio' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .patient-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2563eb;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            font-size: 12px;
        }
        .summary {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .exam-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .exam-title {
            background: #f8fafc;
            padding: 10px;
            border-left: 4px solid #2563eb;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .result-normal {
            color: #10b981;
            font-weight: bold;
        }
        .result-abnormal {
            color: #ef4444;
            background-color: #fef2f2;
            font-weight: bold;
        }
        .observation-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 10px;
            margin-top: 15px;
            font-size: 11px;
        }
        .footer {
            margin-top: 30px;
            border-top: 2px solid #e5e7eb;
            padding-top: 15px;
            background: #f3f4f6;
            padding: 12px;
            border-radius: 4px;
            font-size: 11px;
            color: #374151;
        }
        @media print {
            body { margin: 0; }
            .exam-section { page-break-inside: avoid; }
            .exam-title { page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>🏥 RESULTADOS DE LABORATORIO</h1>
        <p>Documento generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</p>
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <div class="info-grid">
            <div>
                <p style="margin: 3px 0;"><strong>PACIENTE:</strong> {{ $solicitud->paciente->nombres }} {{ $solicitud->paciente->apellidos }}</p>
                <p style="margin: 3px 0;"><strong>DNI:</strong> {{ $solicitud->paciente->dni }}</p>
                <p style="margin: 3px 0;"><strong>EDAD:</strong> {{ $solicitud->paciente->edad ?? 'N/A' }} años</p>
                @if($solicitud->paciente->sexo)
                <p style="margin: 3px 0;"><strong>SEXO:</strong> {{ ucfirst($solicitud->paciente->sexo) }}</p>
                @endif
            </div>
            <div>
                <p style="margin: 3px 0;"><strong>FECHA SOLICITUD:</strong> {{ \Carbon\Carbon::parse($solicitud->fecha)->format('d/m/Y') }}</p>
                <p style="margin: 3px 0;"><strong>HORA:</strong> {{ \Carbon\Carbon::parse($solicitud->hora)->format('H:i') }}</p>
                <p style="margin: 3px 0;"><strong>MÉDICO:</strong> {{ $solicitud->user->nombre }} {{ $solicitud->user->apellido }}</p>
                @if($solicitud->servicio)
                <p style="margin: 3px 0;"><strong>SERVICIO:</strong> {{ $solicitud->servicio->nombre }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="summary">
        <strong>📊 Resumen:</strong> 
        {{ count($detallesCompletados) }} exámenes completados • 
        {{ $totalParametros ?? 0 }} parámetros analizados • 
        Fecha de procesamiento: {{ now()->format('d/m/Y H:i') }}
    </div>

    <!-- Results by Exam -->
    @foreach($detallesCompletados as $detalle)
    <div class="exam-section">
        <div class="exam-title">
            🔬 {{ $detalle->examen->nombre }}
            @if($detalle->examen->codigo)
                <span style="color: #6b7280; font-size: 12px;">({{ $detalle->examen->codigo }})</span>
            @endif
        </div>

        @if(isset($resultadosPorDetalle[$detalle->id]) && count($resultadosPorDetalle[$detalle->id]) > 0)
            @php
                $resultados = $resultadosPorDetalle[$detalle->id];
                $secciones = $resultados->groupBy('campoExamen.seccion');
            @endphp

            @foreach($secciones as $nombreSeccion => $valoresPorSeccion)
                @if($secciones->count() > 1 && $nombreSeccion)
                <h3 style="color: #374151; font-size: 14px; margin: 15px 0 10px 0; font-weight: 600;">
                    📋 {{ $nombreSeccion }}
                </h3>
                @endif

                <table>
                    <thead>
                        <tr>
                            <th style="width: 35%;">Parámetro</th>
                            <th style="width: 20%; text-align: center;">Resultado</th>
                            <th style="width: 15%; text-align: center;">Unidad</th>
                            <th style="width: 25%; text-align: center;">Valores de Referencia</th>
                            <th style="width: 5%; text-align: center;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($valoresPorSeccion as $valor)
                        @php
                            $fueraRango = false;
                            $estadoColor = '#10b981'; // Verde por defecto
                            
                            // Verificar si está fuera de rango de forma segura
                            try {
                                if ($valor->campoExamen && 
                                    !empty($valor->campoExamen->valor_minimo) && 
                                    !empty($valor->campoExamen->valor_maximo) &&
                                    is_numeric($valor->campoExamen->valor_minimo) && 
                                    is_numeric($valor->campoExamen->valor_maximo) &&
                                    is_numeric($valor->valor)) {
                                    
                                    $valorNumerico = (float)$valor->valor;
                                    $minimo = (float)$valor->campoExamen->valor_minimo;
                                    $maximo = (float)$valor->campoExamen->valor_maximo;
                                    
                                    $fueraRango = $valorNumerico < $minimo || $valorNumerico > $maximo;
                                    if ($fueraRango) {
                                        $estadoColor = '#ef4444';
                                    }
                                }
                            } catch (\Exception $e) {
                                // Si hay error en la validación, continuar sin marcar como fuera de rango
                            }
                        @endphp
                        <tr class="{{ $fueraRango ? 'result-abnormal' : '' }}">
                            <td style="font-weight: 500;">
                                {{ $valor->campoExamen->nombre ?? 'Parámetro' }}
                            </td>
                            <td class="text-center" style="font-weight: bold; color: {{ $estadoColor }};">
                                {{ $valor->valor }}
                            </td>
                            <td class="text-center">
                                {{ $valor->campoExamen->unidad ?? '-' }}
                            </td>
                            <td class="text-center" style="font-size: 11px;">
                                @try
                                    @if($valor->campoExamen && !empty($valor->campoExamen->valor_minimo) && !empty($valor->campoExamen->valor_maximo))
                                        {{ $valor->campoExamen->valor_minimo }} - {{ $valor->campoExamen->valor_maximo }}
                                    @elseif($valor->campoExamen && !empty($valor->campoExamen->valor_referencia))
                                        {{ $valor->campoExamen->valor_referencia }}
                                    @else
                                        -
                                    @endif
                                @catch(\Exception $e)
                                    -
                                @endtry
                            </td>
                            <td class="text-center">
                                @if($fueraRango)
                                    <span style="color: #ef4444; font-weight: bold;">⚠</span>
                                @else
                                    <span style="color: #10b981; font-weight: bold;">✓</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach

            <!-- Observaciones del examen -->
            @if($detalle->observaciones)
            <div class="observation-box">
                <p style="margin: 0;"><strong>📝 Observaciones:</strong> {{ $detalle->observaciones }}</p>
            </div>
            @endif

        @elseif($detalle->resultado)
            <!-- Resultado simple (texto) -->
            <div style="background: #f8fafc; padding: 15px; border-radius: 4px; border: 1px solid #e5e7eb;">
                <p style="margin: 0; font-size: 14px;"><strong>Resultado:</strong> {{ $detalle->resultado }}</p>
                @if($detalle->observaciones)
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280;"><strong>Observaciones:</strong> {{ $detalle->observaciones }}</p>
                @endif
            </div>
        @else
            <!-- Sin resultados -->
            <div style="background: #fef2f2; padding: 10px; border-radius: 4px; border-left: 4px solid #ef4444;">
                <p style="margin: 0; font-size: 12px; color: #dc2626;">⚠ No se encontraron resultados para este examen</p>
            </div>
        @endif

        <!-- Información del procesamiento -->
        @if($detalle->fecha_resultado || $detalle->registrador)
        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280;">
            <div class="info-grid">
                <div>
                    @if($detalle->fecha_resultado)
                    <p style="margin: 2px 0;">📅 <strong>Fecha resultado:</strong> {{ \Carbon\Carbon::parse($detalle->fecha_resultado)->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                <div>
                    @if($detalle->registrador)
                    <p style="margin: 2px 0;">👤 <strong>Procesado por:</strong> {{ $detalle->registrador->nombre }} {{ $detalle->registrador->apellido }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
    @endforeach

    <!-- Footer -->
    <div class="footer">
        <p style="margin: 0 0 5px 0;"><strong>📋 NOTAS IMPORTANTES:</strong></p>
        <ul style="margin: 0; padding-left: 15px;">
            <li>Los valores marcados con ⚠ están fuera del rango de referencia normal</li>
            <li>Los resultados deben ser interpretados por un médico calificado</li>
            <li>Este reporte es válido únicamente con la firma y sello del laboratorio</li>
        </ul>
        
        <div style="text-align: center; margin-top: 20px; font-size: 10px; color: #6b7280;">
            <p style="margin: 0;">Sistema de Gestión de Laboratorio - Versión 2.0</p>
        </div>
    </div>
</body>
</html>
