<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DetalleSolicitud;
use App\Models\Examen;
use App\Models\Solicitud;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DetalleSolicitudController extends Controller
{
    /**
     * Mostrar todos los detalles de solicitudes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $detalles = DetalleSolicitud::with(['examen', 'solicitud.paciente'])->get();
        return response()->json([
            'success' => true,
            'data' => $detalles
        ]);
    }

    /**
     * Mostrar detalles de solicitudes por ID de solicitud.
     *
     * @param  int  $solicitudId
     * @return \Illuminate\Http\Response
     */
    public function getDetallesBySolicitud($solicitudId)
    {
        // Cargar la solicitud con todas las relaciones necesarias de una vez
        $solicitud = Solicitud::with([
            'paciente',
            'servicio',
            'examenes.categoria',
            'user'
        ])->find($solicitudId);

        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada'
            ], 404);
        }

        // Verificar si existen detalles para esta solicitud
        $detallesExistentes = DetalleSolicitud::where('solicitud_id', $solicitudId)->exists();

        // Si no existen detalles, crearlos a partir de los exámenes de la solicitud
        if (!$detallesExistentes && $solicitud->examenes->count() > 0) {
            $detallesData = [];
            foreach ($solicitud->examenes as $examen) {
                $detallesData[] = [
                    'solicitud_id' => $solicitudId,
                    'examen_id' => $examen->id,
                    'estado' => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            // Inserción masiva para mejor rendimiento
            DetalleSolicitud::insert($detallesData);
        }

        // Obtener los detalles con todas las relaciones necesarias de una vez
        $detalles = DetalleSolicitud::with([
            'examen.categoria',
            'solicitud.paciente',
            'solicitud.servicio',
            'solicitud.user',
            'registrador',
            'resultados',
            'valoresResultado.campoExamen' // Incluir valores dinámicos
        ])
        ->where('solicitud_id', $solicitudId)
        ->get();

        // Procesar resultados genéricos solo para detalles que lo necesiten
        foreach ($detalles as $detalle) {
            // Si no hay resultados específicos pero hay observaciones, crear un resultado genérico
            if ((!$detalle->resultados || $detalle->resultados->isEmpty()) && $detalle->observaciones && $detalle->estado === 'completado') {
                $resultadoGenerico = new \App\Models\ResultadoExamen([
                    'detalle_solicitud_id' => $detalle->id,
                    'examen_id' => $detalle->examen_id,
                    'nombre_parametro' => 'Resultado',
                    'valor' => 'Ver observaciones',
                    'unidad' => '',
                    'referencia' => ''
                ]);
                $detalle->resultados = collect([$resultadoGenerico]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $detalles
        ]);
    }

    /**
     * Almacenar un nuevo detalle de solicitud.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'solicitud_id' => 'required|exists:solicitudes,id',
            'examen_id' => 'required|exists:examenes,id',
            'estado' => 'nullable|string|in:pendiente,en_proceso,completado',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar si el detalle ya existe para evitar duplicados
        $existente = DetalleSolicitud::where([
            'solicitud_id' => $request->solicitud_id,
            'examen_id' => $request->examen_id,
        ])->first();

        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => 'Este examen ya está asociado a esta solicitud',
                'data' => $existente
            ], 409);
        }

        $detalle = DetalleSolicitud::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Detalle de solicitud creado correctamente',
            'data' => $detalle
        ], 201);
    }

    /**
     * Mostrar un detalle de solicitud específico.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $detalle = DetalleSolicitud::with([
            'examen.categoria',
            'solicitud.paciente',
            'solicitud.servicio',
            'solicitud.user',
            'registrador',
            'resultados',
            'valoresResultado.campoExamen'
        ])->find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de solicitud no encontrado'
            ], 404);
        }

        // Si no hay resultados específicos pero hay observaciones, crear un resultado genérico
        if ((!$detalle->resultados || $detalle->resultados->isEmpty()) && $detalle->observaciones && $detalle->estado === 'completado') {
            $resultadoGenerico = new \App\Models\ResultadoExamen([
                'detalle_solicitud_id' => $detalle->id,
                'examen_id' => $detalle->examen_id,
                'nombre_parametro' => 'Resultado',
                'valor' => 'Ver observaciones',
                'unidad' => '',
                'referencia' => ''
            ]);
            $detalle->resultados = collect([$resultadoGenerico]);
        }

        return response()->json([
            'success' => true,
            'data' => $detalle
        ]);
    }

    /**
     * Actualizar un detalle de solicitud específico.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $detalle = DetalleSolicitud::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de solicitud no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'solicitud_id' => 'sometimes|exists:solicitudes,id',
            'examen_id' => 'sometimes|exists:examenes,id',
            'estado' => 'nullable|string|in:pendiente,en_proceso,completado',
            'resultados' => 'nullable|array',
            'observaciones' => 'nullable|string',
            'fecha_resultado' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Si se cambia examen_id o solicitud_id, verificar que no exista ya
        if (($request->filled('examen_id') && $request->examen_id != $detalle->examen_id) ||
            ($request->filled('solicitud_id') && $request->solicitud_id != $detalle->solicitud_id)) {

            $solicitudId = $request->filled('solicitud_id') ? $request->solicitud_id : $detalle->solicitud_id;
            $examenId = $request->filled('examen_id') ? $request->examen_id : $detalle->examen_id;

            $existente = DetalleSolicitud::where([
                'solicitud_id' => $solicitudId,
                'examen_id' => $examenId,
            ])->where('id', '!=', $id)->first();

            if ($existente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este examen ya está asociado a esta solicitud',
                ], 409);
            }
        }

        // Si se está actualizando a completado y hay resultados, registrar quién lo completó
        if ($request->filled('estado') && $request->estado === 'completado' && $request->filled('resultados')) {
            $request->merge([
                'registrado_por' => Auth::id(),
                'fecha_resultado' => now(),
                'completed_at' => now() // Registrar la fecha y hora exacta de completado
            ]);
        }

        $detalle->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Detalle de solicitud actualizado correctamente',
            'data' => $detalle
        ]);
    }

    /**
     * Eliminar un detalle de solicitud específico.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $detalle = DetalleSolicitud::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de solicitud no encontrado'
            ], 404);
        }

        $detalle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Detalle de solicitud eliminado correctamente'
        ]);
    }

    /**
     * Registrar resultados para un detalle de solicitud.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Actualizar el estado de un detalle de solicitud.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function actualizarEstado(Request $request, $id)
    {
        $detalle = DetalleSolicitud::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de solicitud no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|in:pendiente,en_proceso,completado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Si el estado es "completado", registrar la fecha y hora de completado
        if ($request->estado === 'completado') {
            $detalle->update([
                'estado' => $request->estado,
                'completed_at' => now() // Registrar la fecha y hora exacta de completado
            ]);
        } else {
            $detalle->update([
                'estado' => $request->estado
            ]);
        }

        // Cargar relaciones necesarias
        $detalle->load('examen', 'solicitud.paciente', 'registrador', 'resultados');

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'data' => $detalle
        ]);
    }

    public function registrarResultados(Request $request, $id)
    {
        $detalle = DetalleSolicitud::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de solicitud no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'resultados' => 'required|array',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Actualizar el detalle de solicitud
        $detalle->update([
            'observaciones' => $request->observaciones,
            'estado' => 'completado',
            'fecha_resultado' => now(),
            'completed_at' => now(), // Registrar la fecha y hora exacta de completado
            'registrado_por' => Auth::id()
        ]);

        // Eliminar resultados anteriores si existen
        \App\Models\ResultadoExamen::where('detalle_solicitud_id', $detalle->id)->delete();

        // Guardar los nuevos resultados en la tabla resultados_examen
        foreach ($request->resultados as $resultado) {
            \App\Models\ResultadoExamen::create([
                'detalle_solicitud_id' => $detalle->id,
                'examen_id' => $detalle->examen_id,
                'nombre_parametro' => $resultado['nombre_parametro'] ?? 'Resultado',
                'valor' => $resultado['valor'] ?? '',
                'unidad' => $resultado['unidad'] ?? '',
                'referencia' => $resultado['referencia'] ?? ''
            ]);
        }

        // Cargar los resultados recién creados
        $detalle->resultados = \App\Models\ResultadoExamen::where('detalle_solicitud_id', $detalle->id)->get();

        // Asegurarse de que los resultados estén disponibles en la respuesta
        $detalle->load('examen', 'solicitud.paciente', 'registrador');

        // Cargar la solicitud completa para verificar si todos los exámenes están completados
        $solicitud = $detalle->solicitud;
        $solicitud->load('detalles');

        // Verificar si todos los exámenes están completados
        $todosCompletados = true;
        $algunoEnProceso = false;

        foreach ($solicitud->detalles as $det) {
            if ($det->estado !== 'completado') {
                $todosCompletados = false;
                if ($det->estado === 'en_proceso') {
                    $algunoEnProceso = true;
                }
            }
        }

        // Actualizar el estado de la solicitud en la base de datos
        // Esto es importante para que el frontend no tenga que calcularlo cada vez
        if ($todosCompletados) {
            // Si todos están completados, marcar la solicitud como completada
            $solicitud->update(['estado' => 'completado']);
            app(WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.completed');
        } else if ($algunoEnProceso || $detalle->estado === 'completado') {
            // Si al menos uno está en proceso o el que acabamos de actualizar está completado,
            // marcar la solicitud como en proceso
            $solicitud->update(['estado' => 'en_proceso']);
            app(WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.updated');
        } else {
            // Si ninguno está en proceso ni completado, marcar como pendiente
            $solicitud->update(['estado' => 'pendiente']);
            app(WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.updated');
        }

        return response()->json([
            'success' => true,
            'message' => 'Resultados registrados correctamente',
            'data' => $detalle
        ]);
    }
}