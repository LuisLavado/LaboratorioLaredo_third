@extends('reports.base-report')

@section('content')
    <!-- Patient Information Header -->
    <div class="report-section">
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2563eb;">
            <div style="text-align: center; margin-bottom: 15px;">
                <h1 style="margin: 0; color: #2563eb; font-size: 20px; font-weight: bold;">
                    🏥 LABORATORIO CLÍNICO - RESULTADOS DE ANÁLISIS
                </h1>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; font-size: 12px;">
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
    </div>

    <!-- Compact Summary -->
    <div class="report-section">
        <div style="background: #f8fafc; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            <strong>📊 Resumen:</strong> 
            {{ count($detallesCompletados) }} exámenes completados • 
            {{ $totalParametros ?? 0 }} parámetros analizados • 
            Fecha de procesamiento: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Results by Exam -->
    @foreach($detallesCompletados as $detalle)
    <div class="report-section">
        <h2 style="color: #2563eb; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 15px; font-size: 16px;">
            🔬 {{ $detalle->examen->nombre }}
            @if($detalle->examen->codigo)
                <span style="color: #6b7280; font-size: 12px;">({{ $detalle->examen->codigo }})</span>
            @endif
        </h2>

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

                <table class="report-table compact-table" style="margin-bottom: 20px;">
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

                            // Verificar si está fuera de rango solo si hay valores de referencia válidos
                            if ($valor->campoExamen &&
                                isset($valor->campoExamen->valor_minimo) &&
                                isset($valor->campoExamen->valor_maximo) &&
                                is_numeric($valor->campoExamen->valor_minimo) &&
                                is_numeric($valor->campoExamen->valor_maximo) &&
                                is_numeric($valor->valor)) {

                                $valorNumerico = (float)$valor->valor;
                                $minimo = (float)$valor->campoExamen->valor_minimo;
                                $maximo = (float)$valor->campoExamen->valor_maximo;

                                $fueraRango = $valorNumerico < $minimo || $valorNumerico > $maximo;
                                if ($fueraRango) {
                                    $estadoColor = '#ef4444'; // Rojo si está fuera de rango
                                }
                            }
                        @endphp
                        <tr style="{{ $fueraRango ? 'background-color: #fef2f2;' : '' }}">
                            <td class="compact-cell" style="font-weight: 500;">
                                {{ $valor->campoExamen->nombre ?? 'Parámetro' }}
                            </td>
                            <td class="compact-cell text-center" style="font-weight: bold; color: {{ $estadoColor }};">
                                {{ $valor->valor }}
                            </td>
                            <td class="compact-cell text-center">
                                {{ $valor->campoExamen->unidad ?? '-' }}
                            </td>
                            <td class="compact-cell text-center" style="font-size: 11px;">
                                @if($valor->campoExamen)
                                    @if(isset($valor->campoExamen->valor_minimo) && isset($valor->campoExamen->valor_maximo) &&
                                        $valor->campoExamen->valor_minimo !== '' && $valor->campoExamen->valor_maximo !== '')
                                        {{ $valor->campoExamen->valor_minimo }} - {{ $valor->campoExamen->valor_maximo }}
                                    @elseif(isset($valor->campoExamen->valor_referencia) && $valor->campoExamen->valor_referencia !== '')
                                        {{ $valor->campoExamen->valor_referencia }}
                                    @else
                                        -
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="compact-cell text-center">
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
            <div style="background: #fffbeb; padding: 10px; border-radius: 4px; border-left: 4px solid #f59e0b; margin-top: 10px;">
                <p style="margin: 0; font-size: 12px;"><strong>📝 Observaciones:</strong> {{ $detalle->observaciones }}</p>
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
        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
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
    </div>
    @endforeach

    <!-- Footer with important notes -->
    <div class="report-section" style="margin-top: 30px; border-top: 2px solid #e5e7eb; padding-top: 15px;">
        <div style="background: #f3f4f6; padding: 12px; border-radius: 4px; font-size: 11px; color: #374151;">
            <p style="margin: 0 0 5px 0;"><strong>📋 NOTAS IMPORTANTES:</strong></p>
            <ul style="margin: 0; padding-left: 15px;">
                <li>Los valores marcados con ⚠ están fuera del rango de referencia normal</li>
                <li>Los resultados deben ser interpretados por un médico calificado</li>
                <li>Este reporte es válido únicamente con la firma y sello del laboratorio</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 20px; font-size: 10px; color: #6b7280;">
            <p style="margin: 0;">Documento generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</p>
            <p style="margin: 5px 0 0 0;">Sistema de Gestión de Laboratorio - Versión 2.0</p>
        </div>
    </div>
@endsection
