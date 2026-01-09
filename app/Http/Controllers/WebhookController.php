<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Register a webhook endpoint for a user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint_url' => ['required', 'url'],
            'events' => ['required', 'array'],
            'events.*' => ['string', 'in:solicitud.created,solicitud.updated,solicitud.completed'],
        ]);

        $user = $request->user();
        $user->webhook_url = $request->endpoint_url;
        $user->webhook_events = json_encode($request->events);
        $user->save();

        return response()->json([
            'message' => 'Webhook registrado correctamente',
            'webhook' => [
                'endpoint_url' => $user->webhook_url,
                'events' => json_decode($user->webhook_events),
            ]
        ]);
    }

    /**
     * Unregister a webhook endpoint for a user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unregister(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->webhook_url = null;
        $user->webhook_events = null;
        $user->save();

        return response()->json([
            'message' => 'Webhook eliminado correctamente'
        ]);
    }

    /**
     * Get the webhook configuration for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getConfig(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'webhook_url' => $user->webhook_url,
            'webhook_events' => $user->webhook_events
        ]);
    }

    /**
     * Get recent events as JSON
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function events(Request $request)
    {
        // Obtener el tiempo desde el cual se deben recuperar eventos
        $since = $request->query('since', 0);

        // Obtener eventos recientes de la caché
        $allEvents = \Illuminate\Support\Facades\Cache::get('recent_events', []);

        // Filtrar eventos más recientes que el timestamp proporcionado
        $recentEvents = array_filter($allEvents, function($event) use ($since) {
            // Convertir timestamp ISO 8601 a timestamp Unix
            $eventTime = strtotime($event['timestamp']);
            return $eventTime > $since / 1000; // Convertir milisegundos a segundos
        });

        // Ordenar eventos por timestamp (más antiguos primero)
        usort($recentEvents, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Eventos recientes recuperados correctamente',
            'events' => array_values($recentEvents), // Reindexar array
            'timestamp' => now()->timestamp * 1000 // Timestamp actual en milisegundos
        ]);
    }

    /**
     * Trigger a webhook notification for a solicitud
     *
     * @param Solicitud $solicitud
     * @param string $event
     * @return void
     */
    public function triggerSolicitudWebhook(Solicitud $solicitud, string $event): void
    {
        // Get the doctor who created the solicitud
        $doctor = User::where('role', 'doctor')->find($solicitud->user_id);

        // Get the lab technicians
        $labTechnicians = User::where('role', 'laboratorio')->get();

        // Combine all users who should receive the webhook
        $users = collect([$doctor])->merge($labTechnicians)->filter();

        // Si el evento es solicitud.created, verificar si fue creado por un doctor
        // Si fue creado por un técnico de laboratorio, no enviar notificación
        if ($event === 'solicitud.created') {
            // Obtener el usuario que creó la solicitud
            $creator = User::find($solicitud->user_id);

            // Si el creador no es un doctor, no enviar notificación
            if (!$creator || $creator->role !== 'doctor') {
                \Illuminate\Support\Facades\Log::info("Evento {$event} para solicitud {$solicitud->id} ignorado porque no fue creado por un doctor");
                return;
            }
        }

        // Preparar los datos del evento
        $eventData = [
            'event' => $event,
            'solicitud_id' => $solicitud->id,
            'timestamp' => now()->toIso8601String(),
            'data' => $solicitud->load(['paciente', 'examenes', 'user', 'servicio']),
        ];

        // Guardar el evento en la caché para que esté disponible para polling
        // Usamos una lista de eventos recientes (últimos 100)
        $recentEvents = \Illuminate\Support\Facades\Cache::get('recent_events', []);
        array_unshift($recentEvents, $eventData); // Agregar al inicio
        $recentEvents = array_slice($recentEvents, 0, 100); // Mantener solo los últimos 100
        \Illuminate\Support\Facades\Cache::put('recent_events', $recentEvents, 3600 * 24); // Guardar por 24 horas

        // Log para depuración
        \Illuminate\Support\Facades\Log::info("Evento {$event} para solicitud {$solicitud->id} guardado en caché");

        // Enviar webhook al servidor dedicado en AWS EC2
        $this->sendToWebhookServer($solicitud, $event);

        // Enviar webhooks a los usuarios configurados (mantener compatibilidad)
        foreach ($users as $user) {
            if (!$user->webhook_url || !$user->webhook_events) {
                continue;
            }

            $events = json_decode($user->webhook_events, true);

            if (!in_array($event, $events)) {
                continue;
            }

            // Send the webhook notification
            try {
                $client = new \GuzzleHttp\Client();
                $client->post($user->webhook_url, [
                    'json' => $eventData,
                    'timeout' => 5,
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending webhook notification: ' . $e->getMessage());
            }
        }

        // Guardar el evento en un archivo para SSE
        // Esto es una alternativa simple a usar Redis o un sistema de mensajería
        $eventFilePath = storage_path('app/events/' . time() . '_' . uniqid() . '.json');

        // Asegurarse de que el directorio exista
        if (!file_exists(storage_path('app/events'))) {
            mkdir(storage_path('app/events'), 0755, true);
        }

        // Guardar el evento en un archivo
        file_put_contents($eventFilePath, json_encode([
            'event' => $event,
            'data' => json_encode($eventData)
        ]));

        Log::info('Evento guardado para SSE: ' . $event);
    }

    /**
     * Enviar webhook al servidor dedicado en AWS EC2
     *
     * @param Solicitud $solicitud
     * @param string $event
     * @return void
     */
    private function sendToWebhookServer(Solicitud $solicitud, string $event): void
    {
        $webhookServerUrl = env('WEBHOOK_SERVER_URL');
        $webhookSecret = env('WEBHOOK_SECRET');

        if (!$webhookServerUrl || !$webhookSecret) {
            Log::warning('WEBHOOK_SERVER_URL o WEBHOOK_SECRET no configurados');
            return;
        }

        $endpoint = $webhookServerUrl . '/api/webhooks/generic';

        $payload = [
            'event' => $event,
            'solicitud_id' => $solicitud->id,
            'data' => $solicitud->load(['paciente', 'examenes', 'user', 'servicio'])
        ];

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'Authorization' => "Bearer {$webhookSecret}",
                    'Content-Type' => 'application/json'
                ],
                'timeout' => env('WEBHOOK_TIMEOUT', 10)
            ]);

            Log::info("Webhook enviado al servidor AWS EC2", [
                'url' => $endpoint,
                'event' => $event,
                'solicitud_id' => $solicitud->id,
                'status' => $response->getStatusCode()
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando webhook al servidor AWS EC2:', [
                'url' => $endpoint,
                'event' => $event,
                'solicitud_id' => $solicitud->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
