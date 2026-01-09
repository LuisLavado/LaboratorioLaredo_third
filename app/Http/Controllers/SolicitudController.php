<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WebhookController;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudController extends Controller
{
    public function index(): JsonResponse
    {
        $solicitudes = Solicitud::with(['paciente', 'examenes', 'user', 'servicio', 'detalles'])->get();
        return response()->json($solicitudes);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'servicio_id' => ['required', 'exists:servicios,id'],
            'detalle_servicio_id' => ['nullable', 'exists:detalle_servicios,id'],
            'servicio' => ['sometimes', 'string'],
            'numero_recibo' => ['nullable', 'string'],
            'rdr' => ['required', 'boolean'],
            'sis' => ['required', 'boolean'],
            'exon' => ['required', 'boolean'],
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'examenes' => ['required', 'array'],
            'examenes.*' => ['exists:examenes,id'],
        ]);

        $solicitud = Solicitud::create([
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'servicio_id' => $request->servicio_id,
            'detalle_servicio_id' => $request->detalle_servicio_id,
            'numero_recibo' => $request->numero_recibo,
            'rdr' => $request->rdr,
            'sis' => $request->sis,
            'exon' => $request->exon,
            'paciente_id' => $request->paciente_id,
            'user_id' => $request->user()->id,
        ]);

        $solicitud->examenes()->attach($request->examenes);

        // Trigger webhook notification
        app(WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.created');

        return response()->json($solicitud->load(['paciente', 'examenes', 'user', 'servicio', 'detalles']), 201);
    }

    public function show($id): JsonResponse
    {
        // Manual lookup instead of route model binding
        \Log::info('Manual lookup for solicitud ID:', ['id' => $id]);

        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            \Log::error('Solicitud not found with manual lookup', ['id' => $id]);
            return response()->json(['error' => 'Solicitud not found'], 404);
        }

        \Log::info('Solicitud found:', [
            'id' => $solicitud->id,
            'paciente_id' => $solicitud->paciente_id,
            'servicio_id' => $solicitud->servicio_id,
            'user_id' => $solicitud->user_id
        ]);

        // Cargar todas las relaciones necesarias
        $solicitud->load([
            'paciente',
            'examenes.categoria',
            'user',
            'servicio',
            'detalles.examen.categoria'
        ]);

        // Debug: Check if relations loaded
        \Log::info('Relations loaded:', [
            'paciente_loaded' => $solicitud->paciente ? true : false,
            'servicio_loaded' => $solicitud->servicio ? true : false,
            'user_loaded' => $solicitud->user ? true : false,
            'examenes_count' => $solicitud->examenes->count(),
            'detalles_count' => $solicitud->detalles->count()
        ]);

        // Verificar si existen detalles para esta solicitud
        $detallesExistentes = $solicitud->detalles->count() > 0;

        // Si no existen detalles, crearlos a partir de los exámenes de la solicitud
        if (!$detallesExistentes && $solicitud->examenes->count() > 0) {
            foreach ($solicitud->examenes as $examen) {
                \App\Models\DetalleSolicitud::create([
                    'solicitud_id' => $solicitud->id,
                    'examen_id' => $examen->id,
                    'estado' => 'pendiente'
                ]);
            }

            // Recargar los detalles
            $solicitud->load('detalles.examen.categoria');
        }

        return response()->json($solicitud);
    }

    public function update(Request $request, $id): JsonResponse
    {
        // Manual lookup instead of route model binding
        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json(['error' => 'Solicitud not found'], 404);
        }

        $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'servicio_id' => ['required', 'exists:servicios,id'],
            'detalle_servicio_id' => ['nullable', 'exists:detalle_servicios,id'],
            'servicio' => ['sometimes', 'string'],
            'numero_recibo' => ['nullable', 'string'],
            'rdr' => ['required', 'boolean'],
            'sis' => ['required', 'boolean'],
            'exon' => ['required', 'boolean'],
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'examenes' => ['required', 'array'],
            'examenes.*' => ['exists:examenes,id'],
        ]);

        \Log::info('Updating solicitud:', [
            'id' => $solicitud->id,
            'examenes_to_sync' => $request->examenes
        ]);

        $solicitud->update([
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'servicio_id' => $request->servicio_id,
            'detalle_servicio_id' => $request->detalle_servicio_id,
            'numero_recibo' => $request->numero_recibo,
            'rdr' => $request->rdr,
            'sis' => $request->sis,
            'exon' => $request->exon,
            'paciente_id' => $request->paciente_id,
        ]);

        // Sync examenes with the solicitud
        $solicitud->examenes()->sync($request->examenes);

        // Recargar la solicitud con sus relaciones para el webhook
        $solicitud->load(['paciente', 'examenes', 'user', 'servicio', 'detalles']);

        // Trigger webhook notification for solicitud updated
        \Log::info("🔔 Enviando webhook para solicitud editada", [
            'solicitud_id' => $solicitud->id,
            'user_id' => $request->user()->id
        ]);

        try {
            } catch (\Exception $e) {
                \Log::error("❌ Error enviando webhook para solicitud {$solicitud->id}: " . $e->getMessage());
            }

            // Disparar evento de broadcasting para WebSocket (notificación en tiempo real)
            try {
                event(new \App\Events\SolicitudCreated($solicitud));
                \Log::info('🔔 Evento SolicitudCreated disparado', ['solicitud_id' => $solicitud->id]);
            } catch (\Exception $e) {
                \Log::error('❌ Error disparando evento SolicitudCreated: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error("❌ Error enviando webhook para solicitud editada {$solicitud->id}: " . $e->getMessage());
        }

        return response()->json($solicitud);
    }

    public function destroy(Solicitud $solicitud): JsonResponse
    {
        $solicitud->delete();

        return response()->json(null, 204);
    }

    public function updateResultado(Request $request, Solicitud $solicitud): JsonResponse
    {
        $request->validate([
            'examen_id' => ['required', 'exists:examenes,id'],
            'resultado' => ['required', 'string'],
        ]);

        // Buscar el detalle de solicitud correspondiente
        $detalle = $solicitud->detalles()->where('examen_id', $request->examen_id)->first();

        if (!$detalle) {
            return response()->json([
                'message' => 'No se encontró el examen en esta solicitud'
            ], 404);
        }

        // Actualizar el detalle
        $detalle->update([
            'observaciones' => $request->resultado,
            'estado' => 'completado',
            'fecha_resultado' => now(),
            'registrado_por' => $request->user()->id
        ]);

        // Recargar la solicitud con sus relaciones
        $solicitud->load(['paciente', 'examenes', 'user', 'detalles']);

        // Verificar si todos los exámenes están completados
        $todosCompletados = true;
        foreach ($solicitud->detalles as $det) {
            if ($det->estado !== 'completado') {
                $todosCompletados = false;
                break;
            }
        }

        // Enviar notificación de webhook
        if ($todosCompletados) {
            // Si todos están completados, enviar evento de solicitud completada
            app(WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.completed');
        } else {
            // Si no todos están completados, enviar evento de solicitud actualizada
            app(WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.updated');
        }

        return response()->json($solicitud);
    }

    /**
     * Actualizar el estado de una solicitud
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEstado(Request $request, Solicitud $solicitud): JsonResponse
    {
        $request->validate([
            'estado' => 'required|string|in:pendiente,en_proceso,completado',
        ]);

        // Obtener todos los detalles de la solicitud
        $detalles = $solicitud->detalles;

        // Actualizar el estado de todos los detalles
        foreach ($detalles as $detalle) {
            // No cambiar los detalles que ya están completados
            if ($detalle->estado !== 'completado') {
                $detalle->update(['estado' => $request->estado]);
            }
        }

        // Actualizar el estado de la solicitud
        $solicitud->update(['estado' => $request->estado]);

        // Recargar la solicitud con sus detalles
        $solicitud->load(['paciente', 'servicio', 'detalles']);

        // Asegurarse de que estado_calculado esté disponible en la respuesta
        $solicitud->estado_calculado = $request->estado;

        // Trigger webhook notification
        app(WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.updated');

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'data' => $solicitud
        ]);
    }

    /**
     * Obtener todas las solicitudes con su estado calculado
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWithStatus(Request $request): JsonResponse
    {
        // Iniciar la consulta
        $query = Solicitud::with(['paciente', 'servicio', 'detalles.examen', 'user'])
            ->orderBy('created_at', 'desc');

        // Filtrar por estado si se proporciona
        if ($request->has('estado')) {
            $estado = $request->input('estado');
            $query->where('estado', $estado);
        }

        // Filtrar por fecha
        if ($request->has('not_today') && $request->input('not_today') === 'true') {
            // Excluir solicitudes de hoy
            $today = now()->startOfDay();
            $query->where('created_at', '<', $today);

            // Depuración
            \Log::info('Filtrando solicitudes anteriores a hoy', [
                'today' => $today->toDateTimeString(),
                'query' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
        } elseif ($request->has('date')) {
            // Filtrar por una fecha específica
            $date = $request->input('date');
            $query->whereDate('created_at', $date);
        }

        // Ejecutar la consulta
        $solicitudes = $query->get();

        // Procesar las solicitudes para asegurarnos de que todas tengan un estado_calculado
        $solicitudesConEstado = $solicitudes->map(function ($solicitud) {
            // Si la solicitud ya tiene un estado en la base de datos, usarlo
            if ($solicitud->estado) {
                $solicitud->estado_calculado = $solicitud->estado;
                return $solicitud;
            }

            // Si no tiene estado, calcularlo basado en los detalles
            $estado = 'pendiente';

            if ($solicitud->detalles->count() > 0) {
                $completados = $solicitud->detalles->where('estado', 'completado')->count();
                $enProceso = $solicitud->detalles->where('estado', 'en_proceso')->count();
                $pendientes = $solicitud->detalles->where('estado', 'pendiente')->count();

                if ($completados === $solicitud->detalles->count()) {
                    $estado = 'completado';
                } elseif ($enProceso > 0 || ($completados > 0 && $pendientes > 0)) {
                    $estado = 'en_proceso';
                }

                // Actualizar el estado en la base de datos para futuras consultas
                $solicitud->update(['estado' => $estado]);
            }

            // Agregar el estado calculado a la solicitud
            $solicitud->estado_calculado = $estado;

            return $solicitud;
        });

        return response()->json($solicitudesConEstado);
    }

    /**
     * Obtener solicitudes creadas por un doctor
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function doctorSolicitudes(Request $request): JsonResponse
    {
        $user = $request->user();
        $solicitudes = Solicitud::with(['paciente', 'examenes', 'servicio', 'detalles.examen'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Procesar las solicitudes para asegurarnos de que todas tengan un estado_calculado
        $solicitudesConEstado = $solicitudes->map(function ($solicitud) {
            // Si la solicitud ya tiene un estado en la base de datos, usarlo
            if ($solicitud->estado) {
                $solicitud->estado_calculado = $solicitud->estado;
                return $solicitud;
            }

            // Si no tiene estado, calcularlo basado en los detalles
            $estado = 'pendiente';

            if ($solicitud->detalles->count() > 0) {
                $completados = $solicitud->detalles->where('estado', 'completado')->count();
                $enProceso = $solicitud->detalles->where('estado', 'en_proceso')->count();
                $pendientes = $solicitud->detalles->where('estado', 'pendiente')->count();

                if ($completados === $solicitud->detalles->count()) {
                    $estado = 'completado';
                } elseif ($enProceso > 0 || ($completados > 0 && $pendientes > 0)) {
                    $estado = 'en_proceso';
                }

                // Actualizar el estado en la base de datos para futuras consultas
                $solicitud->update(['estado' => $estado]);
            }

            // Agregar el estado calculado a la solicitud
            $solicitud->estado_calculado = $estado;

            return $solicitud;
        });

        return response()->json($solicitudesConEstado);
    }

    /**
     * Crear una solicitud desde un doctor
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeDoctorSolicitud(Request $request): JsonResponse
    {
        \Log::info("🚀 INICIO - storeDoctorSolicitud ejecutándose", [
            'user_id' => $request->user()->id,
            'timestamp' => now(),
            'timezone' => config('app.timezone'),
            'server_time' => date('Y-m-d H:i:s'),
            'laravel_now' => now()->format('Y-m-d H:i:s'),
            'request_data' => $request->all()
        ]);

        $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'servicio_id' => ['required', 'exists:servicios,id'],
            'numero_recibo' => ['nullable', 'string'],
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'examenes' => ['required', 'array'],
            'examenes.*' => ['exists:examenes,id'],
        ]);

        $solicitud = Solicitud::create([
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'servicio_id' => $request->servicio_id,
            'numero_recibo' => $request->numero_recibo,
            'rdr' => $request->rdr ?? false,
            'sis' => $request->sis ?? false,
            'exon' => $request->exon ?? false,
            'paciente_id' => $request->paciente_id,
            'user_id' => $request->user()->id,
        ]);

        \Log::info("📝 SOLICITUD CREADA", [
            'solicitud_id' => $solicitud->id,
            'paciente_id' => $solicitud->paciente_id,
            'user_id' => $solicitud->user_id,
            'fecha_enviada' => $request->fecha,
            'fecha_guardada' => $solicitud->fecha,
            'created_at' => $solicitud->created_at->format('Y-m-d H:i:s'),
            'created_at_timezone' => $solicitud->created_at->timezone->getName(),
            'fresh_from_db' => $solicitud->fresh()->created_at->format('Y-m-d H:i:s')
        ]);

        $solicitud->examenes()->attach($request->examenes);

        \Log::info("🔗 EXAMENES ADJUNTADOS", [
            'solicitud_id' => $solicitud->id,
            'examenes_count' => count($request->examenes)
        ]);

        // Trigger webhook notification
        \Log::info("🔔 Intentando enviar webhook para solicitud creada", [
            'solicitud_id' => $solicitud->id,
            'doctor_id' => $solicitud->user_id,
            'paciente' => $solicitud->paciente_id
        ]);

        try {
            app(WebhookController::class)->triggerSolicitudWebhook($solicitud, 'solicitud.created');
            \Log::info("✅ Webhook enviado exitosamente para solicitud {$solicitud->id}");
        } catch (\Exception $e) {
            \Log::error("❌ Error enviando webhook para solicitud {$solicitud->id}: " . $e->getMessage());
        }

        // Disparar evento de broadcasting para WebSocket (notificación en tiempo real al laboratorio)
        try {
            event(new \App\Events\SolicitudCreated($solicitud));
            \Log::info('🔔 Evento SolicitudCreated disparado', [
                'solicitud_id' => $solicitud->id,
                'channel' => 'laboratory'
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error disparando evento SolicitudCreated: ' . $e->getMessage());
        }

        return response()->json($solicitud->load(['paciente', 'examenes', 'user', 'servicio']), 201);
    }

    /**
     * Generar código QR para una solicitud
     *
     * @param Solicitud $solicitud
     * @return JsonResponse
     */
    public function generateQr(Solicitud $solicitud): JsonResponse
    {
        // Cargar relaciones necesarias
        $solicitud->load(['paciente', 'examenes.categoria', 'servicio', 'user', 'detalles.examen.categoria']);

        // URL de producción para el frontend
        $frontendUrl = 'https://laboratorio.bonelektroniks.com';

        // Crear la URL para acceder a los resultados en el frontend
        $url = "{$frontendUrl}/resultados/{$solicitud->id}";

        // Generar una URL para un servicio de QR externo
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url);

        return response()->json([
            'success' => true,
            'qr_code' => $qrCodeUrl,
            'url' => $url,
            'solicitud' => $solicitud // Incluir los datos de la solicitud en la respuesta
        ]);
    }

    /**
     * Debug endpoint to check raw solicitud data
     */
    public function debug(Solicitud $solicitud): JsonResponse
    {
        // Get raw data without relations
        $rawData = $solicitud->getAttributes();

        // Check if related records exist
        $pacienteExists = $solicitud->paciente_id ? \App\Models\Paciente::find($solicitud->paciente_id) : null;
        $servicioExists = $solicitud->servicio_id ? \App\Models\Servicio::find($solicitud->servicio_id) : null;
        $userExists = $solicitud->user_id ? \App\Models\User::find($solicitud->user_id) : null;

        // Check pivot table for examenes
        $examenesFromPivot = \DB::table('detallesolicitud')
            ->where('solicitud_id', $solicitud->id)
            ->get();

        return response()->json([
            'raw_solicitud' => $rawData,
            'related_records_exist' => [
                'paciente' => $pacienteExists ? $pacienteExists->toArray() : null,
                'servicio' => $servicioExists ? $servicioExists->toArray() : null,
                'user' => $userExists ? $userExists->toArray() : null,
            ],
            'examenes_pivot' => $examenesFromPivot,
            'detalles_count' => $solicitud->detalles()->count()
        ]);
    }
}