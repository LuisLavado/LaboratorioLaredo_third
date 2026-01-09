<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SSEController extends Controller
{
    /**
     * Stream notifications using Server-Sent Events
     */
    public function notifications(Request $request): Response
    {
        $user = $request->user();

        return response()->stream(function () use ($user) {
            // Configurar headers para SSE
            echo "data: " . json_encode(['type' => 'connected', 'message' => 'Conectado a notificaciones']) . "\n\n";
            ob_flush();
            flush();

            $lastCheck = now();

            while (true) {
                // Verificar nuevas notificaciones
                $newNotifications = $user->notifications()
                    ->where('created_at', '>', $lastCheck)
                    ->get();

                if ($newNotifications->count() > 0) {
                    foreach ($newNotifications as $notification) {
                        $data = [
                            'type' => 'notification',
                            'id' => $notification->id,
                            'data' => $notification->data,
                            'created_at' => $notification->created_at->toISOString(),
                        ];

                        echo "data: " . json_encode($data) . "\n\n";
                        ob_flush();
                        flush();
                    }

                    $lastCheck = now();
                }

                // Enviar heartbeat cada 30 segundos
                echo "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => now()->toISOString()]) . "\n\n";
                ob_flush();
                flush();

                // Esperar 2 segundos antes de la siguiente verificación (más eficiente)
                sleep(2);

                // Verificar si la conexión sigue activa
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Para nginx
        ]);
    }

    /**
     * Get initial notifications data
     */
    public function initial(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->limit(10)
            ->get();

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get recent events for real-time updates (fallback to SSE)
     */
    public function events(Request $request)
    {
        $user = $request->user();
        $since = $request->get('since', now()->subMinutes(5)->timestamp);

        // Obtener eventos desde caché
        $cacheKey = "sse_events_" . now()->format('Y-m-d-H-i');
        $events = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        // También verificar la clave anterior por si acaso
        $previousKey = "sse_events_" . now()->subMinutes(1)->format('Y-m-d-H-i');
        $previousEvents = \Illuminate\Support\Facades\Cache::get($previousKey, []);

        // Combinar eventos
        $allEvents = array_merge($previousEvents, $events);

        // Filtrar eventos según el rol del usuario
        $filteredEvents = array_filter($allEvents, function($event) use ($user) {
            switch ($event['event']) {
                case 'solicitud.created':
                    // Solo para laboratorio
                    return $user->role === 'laboratorio';
                case 'solicitud.completed':
                    // Solo para el doctor que creó la solicitud
                    return $user->role === 'doctor' && $event['doctor']['id'] == $user->id;
                default:
                    return true;
            }
        });

        // Filtrar por timestamp
        $recentEvents = array_filter($filteredEvents, function($event) use ($since) {
            return strtotime($event['timestamp']) > $since;
        });

        return response()->json([
            'success' => true,
            'events' => array_values($recentEvents),
            'timestamp' => now()->timestamp,
        ]);
    }
}
