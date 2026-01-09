<?php

namespace App\Http\Controllers;

use App\Models\ValorResultado;
use App\Models\DetalleSolicitud;
use App\Models\CampoExamen;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ValorResultadoController extends Controller
{
    /**
     * Obtener valores de resultado de un detalle de solicitud
     */
    public function index(Request $request): JsonResponse
    {
        $detalleSolicitudId = $request->query('detalle_solicitud_id');

        if ($detalleSolicitudId) {
            $detalle = DetalleSolicitud::with([
                'valoresResultado.campoExamen',
                'examen.campos',
                'examen.examenesHijos.campos'
            ])->findOrFail($detalleSolicitudId);

            // Asegurar que el examen tenga todos los campos (incluyendo los por defecto)
            $detalle->examen->todos_los_campos = $detalle->examen->todosLosCampos();

            return response()->json([
                'detalle' => $detalle,
                'valores_por_seccion' => $detalle->valoresPorSeccion()
            ]);
        }

        $valores = ValorResultado::with(['detalleSolicitud', 'campoExamen'])->get();
        return response()->json($valores);
    }

    /**
     * Mostrar un valor de resultado específico
     */
    public function show(ValorResultado $valorResultado): JsonResponse
    {
        return response()->json($valorResultado->load(['detalleSolicitud', 'campoExamen']));
    }

    /**
     * Obtener valores existentes para un detalle de solicitud
     */
    public function getByDetalle($detalleSolicitudId): JsonResponse
    {
        $valores = ValorResultado::where('detalle_solicitud_id', $detalleSolicitudId)
            ->with('campoExamen')
            ->get();

        return response()->json($valores);
    }

    /**
     * Guardar un campo individual (para auto-guardado) - OPTIMIZADO
     */
    public function storeCampo(Request $request): JsonResponse
    {
        $request->validate([
            'detalle_solicitud_id' => 'required|exists:detallesolicitud,id',
            'campo_examen_id' => 'required|exists:campos_examen,id',
            'valor' => 'required',
            'observaciones' => 'nullable|string'
        ]);

        // Usar transacción para mejor rendimiento
        return \DB::transaction(function () use ($request) {
            $valor = ValorResultado::updateOrCreate(
                [
                    'detalle_solicitud_id' => $request->detalle_solicitud_id,
                    'campo_examen_id' => $request->campo_examen_id
                ],
                [
                    'valor' => $request->valor,
                    'observaciones' => $request->observaciones
                ]
            );

            // Cargar la relación campoExamen solo una vez
            $valor->load('campoExamen');

            // Optimización: Solo verificar completitud si es necesario
            $detalleSolicitud = DetalleSolicitud::findOrFail($request->detalle_solicitud_id);

            // Verificar completitud de forma más eficiente
            $estadoAnterior = $detalleSolicitud->estado;
            $nuevoEstado = $this->verificarEstadoDetalle($detalleSolicitud);

            if ($estadoAnterior !== $nuevoEstado) {
                $detalleSolicitud->update([
                    'estado' => $nuevoEstado,
                    'fecha_resultado' => $nuevoEstado === 'completado' ? now() : $detalleSolicitud->fecha_resultado,
                    'registrado_por' => $nuevoEstado === 'completado' ? $request->user()->id : $detalleSolicitud->registrado_por
                ]);

                // Solo actualizar estado de solicitud si el detalle cambió de estado
                $solicitud = $detalleSolicitud->solicitud;
                if ($solicitud) {
                    $this->actualizarEstadoSolicitudOptimizado($solicitud);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Campo guardado correctamente',
                'campo_nombre' => $valor->campoExamen->nombre,
                'valor' => $valor,
                'detalle_estado' => $detalleSolicitud->estado,
                'solicitud_estado' => $detalleSolicitud->solicitud ? $detalleSolicitud->solicitud->estado : null
            ]);
        });
    }

    /**
     * Verificar estado del detalle de forma optimizada
     */
    private function verificarEstadoDetalle(DetalleSolicitud $detalle): string
    {
        // Si ya está completado, no verificar de nuevo
        if ($detalle->estado === 'completado') {
            return 'completado';
        }

        // Verificar si tiene resultados completos de forma más eficiente
        if ($detalle->tieneResultadosCompletosOptimizado()) {
            return 'completado';
        }

        // Si tiene algún valor, está en proceso
        $tieneValores = $detalle->valoresResultado()->exists() || !empty($detalle->resultado);
        return $tieneValores ? 'en_proceso' : 'pendiente';
    }

    /**
     * Guardar múltiples campos en batch (OPTIMIZADO para rendimiento)
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $request->validate([
            'detalle_solicitud_id' => 'required|exists:detallesolicitud,id',
            'campos' => 'required|array|min:1',
            'campos.*.campo_examen_id' => 'required|exists:campos_examen,id',
            'campos.*.valor' => 'required',
            'campos.*.observaciones' => 'nullable|string'
        ]);

        return \DB::transaction(function () use ($request) {
            $detalleSolicitud = DetalleSolicitud::findOrFail($request->detalle_solicitud_id);
            $valores = [];
            $camposGuardados = 0;

            // Procesar todos los campos en una sola transacción
            foreach ($request->campos as $campoData) {
                try {
                    $valor = ValorResultado::updateOrCreate(
                        [
                            'detalle_solicitud_id' => $request->detalle_solicitud_id,
                            'campo_examen_id' => $campoData['campo_examen_id']
                        ],
                        [
                            'valor' => $campoData['valor'],
                            'observaciones' => $campoData['observaciones'] ?? null
                        ]
                    );

                    $valores[] = $valor;
                    $camposGuardados++;
                } catch (\Exception $e) {
                    \Log::error("Error guardando campo en batch", [
                        'campo_examen_id' => $campoData['campo_examen_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Verificar estado del detalle una sola vez al final
            $estadoAnterior = $detalleSolicitud->estado;
            $nuevoEstado = $this->verificarEstadoDetalle($detalleSolicitud);

            if ($estadoAnterior !== $nuevoEstado) {
                $detalleSolicitud->update([
                    'estado' => $nuevoEstado,
                    'fecha_resultado' => $nuevoEstado === 'completado' ? now() : $detalleSolicitud->fecha_resultado,
                    'registrado_por' => $nuevoEstado === 'completado' ? $request->user()->id : $detalleSolicitud->registrado_por
                ]);

                // Actualizar estado de solicitud una sola vez
                $this->actualizarEstadoSolicitudOptimizado($detalleSolicitud->solicitud);
            }

            return response()->json([
                'status' => true,
                'message' => "Batch guardado: {$camposGuardados} campos",
                'campos_guardados' => $camposGuardados,
                'detalle_estado' => $detalleSolicitud->estado,
                'valores' => $valores
            ]);
        });
    }

    /**
     * Guardar resultado simple para exámenes sin campos definidos
     */
    public function storeSimple(Request $request): JsonResponse
    {
        $request->validate([
            'detalle_solicitud_id' => 'required|exists:detallesolicitud,id',
            'valor' => 'required',
            'observaciones' => 'nullable|string'
        ]);

        $detalleSolicitud = DetalleSolicitud::findOrFail($request->detalle_solicitud_id);

        // Para exámenes sin campos, guardamos en la tabla de resultados clásicos
        // o creamos un campo temporal
        $detalleSolicitud->update([
            'resultado' => $request->valor,
            'observaciones' => $request->observaciones,
            'estado' => 'completado',
            'fecha_resultado' => now(),
            'registrado_por' => $request->user()->id
        ]);

        $this->actualizarEstadoSolicitud($detalleSolicitud->solicitud);

        return response()->json([
            'status' => true,
            'message' => 'Resultado simple guardado correctamente',
            'detalle_estado' => $detalleSolicitud->estado
        ]);
    }

    /**
     * Guardar valores de resultado para un detalle de solicitud
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'detalle_solicitud_id' => 'required|exists:detallesolicitud,id',
            'valores' => 'required|array',
            'valores.*.campo_examen_id' => 'required|exists:campos_examen,id',
            'valores.*.valor' => 'required',
            'valores.*.observaciones' => 'nullable|string'
        ]);

        $detalleSolicitud = DetalleSolicitud::findOrFail($request->detalle_solicitud_id);
        $valores = [];

        foreach ($request->valores as $valorData) {
            $valor = ValorResultado::updateOrCreate(
                [
                    'detalle_solicitud_id' => $request->detalle_solicitud_id,
                    'campo_examen_id' => $valorData['campo_examen_id']
                ],
                [
                    'valor' => $valorData['valor'],
                    'observaciones' => $valorData['observaciones'] ?? null
                ]
            );

            $valores[] = $valor->load('campoExamen');
        }

        // Verificar si todos los campos requeridos están completos
        if ($detalleSolicitud->tieneResultadosCompletos()) {
            $detalleSolicitud->update([
                'estado' => 'completado',
                'fecha_resultado' => now(),
                'registrado_por' => $request->user()->id
            ]);
        } else {
            $detalleSolicitud->update([
                'estado' => 'en_proceso'
            ]);
        }

        // Verificar si toda la solicitud está completa
        $this->actualizarEstadoSolicitud($detalleSolicitud->solicitud);

        return response()->json([
            'valores' => $valores,
            'detalle' => $detalleSolicitud->fresh()
        ], 201);
    }

    /**
     * Actualizar un valor específico
     */
    public function update(Request $request, ValorResultado $valorResultado): JsonResponse
    {
        $request->validate([
            'valor' => 'required',
            'observaciones' => 'nullable|string'
        ]);

        $valorResultado->update($request->only(['valor', 'observaciones']));

        // Verificar si el detalle está completo después de la actualización
        $detalleSolicitud = $valorResultado->detalleSolicitud;

        if ($detalleSolicitud->tieneResultadosCompletos()) {
            $detalleSolicitud->update([
                'estado' => 'completado',
                'fecha_resultado' => now(),
                'registrado_por' => $request->user()->id
            ]);
        } else {
            $detalleSolicitud->update([
                'estado' => 'en_proceso'
            ]);
        }

        // Verificar si toda la solicitud está completa
        $this->actualizarEstadoSolicitud($detalleSolicitud->solicitud);

        return response()->json($valorResultado->load('campoExamen'));
    }

    /**
     * Eliminar un valor de resultado
     */
    public function destroy(ValorResultado $valorResultado): JsonResponse
    {
        $valorResultado->delete();
        return response()->json(null, 204);
    }

    /**
     * Obtener plantilla de campos para un examen
     */
    public function plantilla(Request $request): JsonResponse
    {
        $request->validate([
            'examen_id' => 'required|exists:examenes,id'
        ]);

        $examen = \App\Models\Examen::with([
            'campos' => function($query) {
                $query->activos();
            },
            'examenesHijos.campos' => function($query) {
                $query->activos();
            }
        ])->findOrFail($request->examen_id);

        $campos = $examen->todosLosCampos();

        // Para exámenes híbridos, crear estructura especial
        if ($examen->tipo === 'hibrido') {
            $camposPorExamen = $campos->groupBy('examen_origen_nombre');

            // COMPATIBILIDAD: También crear campos_por_seccion usando nombre de examen como "sección"
            $camposPorSeccionCompatible = collect();
            foreach ($camposPorExamen as $nombreExamen => $camposExamen) {
                $camposPorSeccionCompatible[$nombreExamen] = $camposExamen;
            }

            return response()->json([
                'examen' => $examen,
                'campos_por_seccion' => $camposPorSeccionCompatible, // Para compatibilidad con frontend actual
                'campos_por_examen' => $camposPorExamen, // Para futuras mejoras
                'es_hibrido' => true
            ]);
        } else {
            $camposPorSeccion = $campos->groupBy('seccion');

            return response()->json([
                'examen' => $examen,
                'campos_por_seccion' => $camposPorSeccion,
                'es_hibrido' => false
            ]);
        }
    }

    /**
     * Validar valores contra rangos de referencia
     */
    public function validar(Request $request): JsonResponse
    {
        $request->validate([
            'valores' => 'required|array',
            'valores.*.campo_examen_id' => 'required|exists:campos_examen,id',
            'valores.*.valor' => 'required'
        ]);

        $validaciones = [];

        foreach ($request->valores as $valorData) {
            try {
                $campo = CampoExamen::findOrFail($valorData['campo_examen_id']);
                $valor = $valorData['valor'];

                // Limpiar el valor de espacios y caracteres extraños
                $valor = trim($valor);

                // Validar que no sea un valor problemático
                if ($valor === '-1' || $valor === '' || $valor === null) {
                    \Log::warning("Valor problemático detectado en validación", [
                        'campo_id' => $campo->id,
                        'valor_original' => $valorData['valor'],
                        'valor_limpio' => $valor
                    ]);
                }

                $enRango = $campo->validarRango($valor);

                $validaciones[] = [
                    'campo_examen_id' => $campo->id,
                    'campo_nombre' => $campo->nombre,
                    'valor' => $valor,
                    'valor_original' => $valorData['valor'], // Para debugging
                    'en_rango' => $enRango,
                    'valor_referencia' => $campo->valor_referencia,
                    'unidad' => $campo->unidad,
                    'tipo_campo' => $campo->tipo
                ];
            } catch (\Exception $e) {
                \Log::error("Error en validación de campo", [
                    'campo_id' => $valorData['campo_examen_id'] ?? 'unknown',
                    'valor' => $valorData['valor'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);

                // En caso de error, devolver una validación que indique el problema
                $validaciones[] = [
                    'campo_examen_id' => $valorData['campo_examen_id'] ?? null,
                    'campo_nombre' => 'Error',
                    'valor' => $valorData['valor'] ?? '',
                    'en_rango' => false,
                    'valor_referencia' => 'Error en validación',
                    'unidad' => '',
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json($validaciones);
    }

    /**
     * Exportar resultados en formato estructurado
     */
    public function exportar(Request $request): JsonResponse
    {
        $request->validate([
            'detalle_solicitud_id' => 'required|exists:detallesolicitud,id'
        ]);

        $detalle = DetalleSolicitud::with([
            'solicitud.paciente',
            'examen',
            'valoresResultado.campoExamen' => function($query) use ($request) {
                // Incluir campos inactivos que tienen valores para esta solicitud
                $query->paraMostrarResultados($request->detalle_solicitud_id);
            }
        ])->findOrFail($request->detalle_solicitud_id);

        $resultado = [
            'paciente' => [
                'nombres' => $detalle->solicitud->paciente->nombres,
                'apellidos' => $detalle->solicitud->paciente->apellidos,
                'dni' => $detalle->solicitud->paciente->dni,
                'edad' => $detalle->solicitud->paciente->edad
            ],
            'examen' => [
                'nombre' => $detalle->examen->nombre,
                'codigo' => $detalle->examen->codigo
            ],
            'fecha_resultado' => $detalle->fecha_resultado,
            'valores_por_seccion' => []
        ];

        // Verificar si tiene valores dinámicos (campos definidos)
        $valoresDinamicos = $detalle->valoresResultado;

        if ($valoresDinamicos->count() > 0) {
            // Examen con campos dinámicos
            $resultado['valores_por_seccion'] = $detalle->valoresPorSeccion()->map(function($valores, $seccion) {
                return [
                    'seccion' => $seccion,
                    'valores' => $valores->map(function($valor) {
                        return [
                            'campo' => $valor->campoExamen->nombre,
                            'valor' => $valor->valor,
                            'unidad' => $valor->campoExamen->unidad,
                            'valor_referencia' => $valor->campoExamen->valor_referencia,
                            'fuera_rango' => $valor->fuera_rango,
                            'observaciones' => $valor->observaciones
                        ];
                    })
                ];
            })->values();
        } else if ($detalle->resultado) {
            // Examen con resultado clásico (sin campos definidos)
            $resultado['valores_por_seccion'] = collect([
                [
                    'seccion' => 'RESULTADO',
                    'valores' => collect([
                        [
                            'campo' => 'Resultado',
                            'valor' => $detalle->resultado,
                            'unidad' => null,
                            'valor_referencia' => 'Según criterio médico',
                            'fuera_rango' => false,
                            'observaciones' => $detalle->observaciones
                        ]
                    ])
                ]
            ]);
        } else {
            // No hay resultados
            $resultado['valores_por_seccion'] = collect([]);
        }

        return response()->json($resultado);
    }

    /**
     * Exportar resultados de una solicitud completa a PDF
     */
    public function exportarSolicitudPDF($solicitud_id)
    {
        $solicitud = Solicitud::with([
            'paciente',
            'user',
            'servicio',
            'detalles' => function($query) {
                $query->where('estado', 'completado');
            },
            'detalles.examen',
            'detalles.valoresResultado.campoExamen',
            'detalles.registrador'
        ])->findOrFail($solicitud_id);

        // Obtener solo los detalles completados
        $detallesCompletados = $solicitud->detalles;

        if ($detallesCompletados->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No hay exámenes completados para esta solicitud'
            ], 404);
        }

        // Preparar datos para la vista
        $resultadosPorDetalle = [];
        $totalParametros = 0;

        foreach ($detallesCompletados as $detalle) {
            $valores = $detalle->valoresResultado;
            $resultadosPorDetalle[$detalle->id] = $valores;
            $totalParametros += $valores->count();
        }

        $viewData = [
            'solicitud' => $solicitud,
            'detallesCompletados' => $detallesCompletados,
            'resultadosPorDetalle' => $resultadosPorDetalle,
            'totalParametros' => $totalParametros,
            'title' => 'Resultados de Laboratorio',
            'generatedBy' => auth()->user()->nombre . ' ' . auth()->user()->apellido,
        ];

        try {
            // Generar PDF
            $pdf = Pdf::loadView('results.results-compact-pdf', $viewData);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

            $fileName = "resultados_laboratorio_{$solicitud->paciente->dni}_{$solicitud->id}_" . now()->format('Y-m-d') . ".pdf";

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de solicitud', [
                'solicitud_id' => $solicitud_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Error al generar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar resultados de un detalle específico a PDF
     */
    public function exportarDetallePDF($detalle_solicitud_id)
    {
        $detalle = DetalleSolicitud::with([
            'solicitud.paciente',
            'solicitud.user',
            'solicitud.servicio',
            'examen',
            'valoresResultado.campoExamen',
            'registrador'
        ])->findOrFail($detalle_solicitud_id);

        if ($detalle->estado !== 'completado') {
            return response()->json([
                'status' => false,
                'message' => 'El examen no está completado'
            ], 400);
        }

        // Preparar datos para la vista
        $resultadosPorDetalle = [];
        $resultadosPorDetalle[$detalle->id] = $detalle->valoresResultado;

        $viewData = [
            'solicitud' => $detalle->solicitud,
            'detallesCompletados' => collect([$detalle]),
            'resultadosPorDetalle' => $resultadosPorDetalle,
            'totalParametros' => $detalle->valoresResultado->count(),
            'title' => 'Resultado de Examen - ' . $detalle->examen->nombre,
            'generatedBy' => auth()->user()->nombre . ' ' . auth()->user()->apellido,
        ];

        try {
            // Generar PDF
            $pdf = Pdf::loadView('results.results-compact-pdf', $viewData);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

            $fileName = "resultado_{$detalle->examen->codigo}_{$detalle->solicitud->paciente->dni}_" . now()->format('Y-m-d') . ".pdf";

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de detalle', [
                'detalle_solicitud_id' => $detalle_solicitud_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Error al generar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el estado de la solicitud basado en el estado de sus detalles
     */
    private function actualizarEstadoSolicitud(\App\Models\Solicitud $solicitud): void
    {
        $detalles = $solicitud->detalles;

        // Contar estados de los detalles
        $totalDetalles = $detalles->count();
        $completados = $detalles->where('estado', 'completado')->count();
        $enProceso = $detalles->where('estado', 'en_proceso')->count();

        // Determinar el estado de la solicitud
        if ($completados === $totalDetalles) {
            // Todos los exámenes están completados
            $nuevoEstado = 'completado';
        } elseif ($enProceso > 0 || $completados > 0) {
            // Al menos un examen está en proceso o completado
            $nuevoEstado = 'en_proceso';
        } else {
            // Todos los exámenes están pendientes
            $nuevoEstado = 'pendiente';
        }

        // Actualizar solo si el estado cambió
        if ($solicitud->estado !== $nuevoEstado) {
            $estadoAnterior = $solicitud->estado;
            $solicitud->update(['estado' => $nuevoEstado]);

            \Log::info("Estado de solicitud actualizado", [
                'solicitud_id' => $solicitud->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
                'total_detalles' => $totalDetalles,
                'completados' => $completados,
                'en_proceso' => $enProceso
            ]);

            // Disparar webhook cuando la solicitud se completa
            if ($nuevoEstado === 'completado') {
                \Log::info("Disparando webhook para solicitud completada", [
                    'solicitud_id' => $solicitud->id
                ]);

                // Recargar la solicitud con todas sus relaciones
                $solicitud->load(['paciente', 'examenes', 'user', 'servicio']);

                // Disparar evento de broadcasting para WebSocket (notificación en tiempo real al doctor)
                event(new \App\Events\SolicitudCompleted($solicitud));
                
                \Log::info("🔔 Evento SolicitudCompleted disparado", [
                    'solicitud_id' => $solicitud->id,
                    'doctor_id' => $solicitud->user_id,
                    'channel' => 'private-doctor.' . $solicitud->user_id
                ]);

                // Disparar webhook
                app(\App\Http\Controllers\WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.completed');
            } elseif ($nuevoEstado === 'en_proceso' && $estadoAnterior === 'pendiente') {
                // Disparar webhook cuando la solicitud pasa a en proceso
                \Log::info("Disparando webhook para solicitud en proceso", [
                    'solicitud_id' => $solicitud->id
                ]);

                $solicitud->load(['paciente', 'examenes', 'user', 'servicio']);
                app(\App\Http\Controllers\WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.updated');
            }
        }
    }

    /**
     * Actualizar el estado de la solicitud de forma optimizada
     * Evita consultas innecesarias y webhooks frecuentes
     */
    private function actualizarEstadoSolicitudOptimizado(\App\Models\Solicitud $solicitud): void
    {
        // Usar consulta más eficiente para contar estados
        $estadosCount = $solicitud->detalles()
            ->selectRaw('estado, COUNT(*) as count')
            ->groupBy('estado')
            ->pluck('count', 'estado')
            ->toArray();

        $totalDetalles = array_sum($estadosCount);
        $completados = $estadosCount['completado'] ?? 0;
        $enProceso = $estadosCount['en_proceso'] ?? 0;

        // Determinar el estado de la solicitud
        if ($completados === $totalDetalles) {
            $nuevoEstado = 'completado';
        } elseif ($enProceso > 0 || $completados > 0) {
            $nuevoEstado = 'en_proceso';
        } else {
            $nuevoEstado = 'pendiente';
        }

        // Actualizar solo si el estado cambió
        if ($solicitud->estado !== $nuevoEstado) {
            $estadoAnterior = $solicitud->estado;
            $solicitud->update(['estado' => $nuevoEstado]);

            // Log más conciso
            \Log::info("Estado solicitud actualizado: {$solicitud->id} ({$estadoAnterior} → {$nuevoEstado})");

            // Disparar webhooks solo para cambios importantes
            if ($nuevoEstado === 'completado') {
                // Cargar relaciones necesarias
                $solicitud->load(['paciente', 'examenes', 'user', 'servicio']);
                
                // Disparar evento de broadcasting para WebSocket (notificación en tiempo real al doctor)
                event(new \App\Events\SolicitudCompleted($solicitud));
                
                \Log::info("🔔 Evento SolicitudCompleted disparado", [
                    'solicitud_id' => $solicitud->id,
                    'doctor_id' => $solicitud->user_id,
                    'channel' => 'private-doctor.' . $solicitud->user_id
                ]);
                
                // Usar queue para webhooks para no bloquear la respuesta
                \Queue::push(function() use ($solicitud) {
                    app(\App\Http\Controllers\WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.completed');
                });
            }
        }
    }
}
