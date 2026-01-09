<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user (filtered by role)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Filtrar notificaciones por rol del usuario
        $query = $user->notifications();

        switch ($user->role) {
            case 'laboratorio':
                // Laboratorio solo ve notificaciones de solicitudes creadas
                $query->where('type', 'solicitud.created');
                break;
            case 'doctor':
                // Doctores solo ven notificaciones de resultados completados
                $query->where('type', 'solicitud.completed');
                break;
            // Otros roles ven todas las notificaciones (sin filtro)
        }

        $notifications = $query
            ->when($request->has('unread_only'), function ($query) {
                return $query->unread();
            })
            ->paginate($request->get('per_page', 15));

        // También filtrar el conteo de no leídas
        $unreadQuery = $user->unreadNotifications();
        switch ($user->role) {
            case 'laboratorio':
                $unreadQuery->where('type', 'solicitud.created');
                break;
            case 'doctor':
                $unreadQuery->where('type', 'solicitud.completed');
                break;
        }

        // Convertir la respuesta paginada al formato esperado por el frontend
        $paginatedData = $notifications->toArray();

        // Agregar información adicional
        $paginatedData['unread_count'] = $unreadQuery->count();
        $paginatedData['user_role'] = $user->role; // Para debugging
        $paginatedData['success'] = true;

        return response()->json($paginatedData);
    }

    /**
     * Get unread notifications count (filtered by role)
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Filtrar notificaciones no leídas por rol del usuario
        $query = $user->unreadNotifications();

        switch ($user->role) {
            case 'laboratorio':
                // Laboratorio solo ve notificaciones de solicitudes creadas
                $query->where('type', 'solicitud.created');
                break;
            case 'doctor':
                // Doctores solo ven notificaciones de resultados completados
                $query->where('type', 'solicitud.completed');
                break;
            // Otros roles ven todas las notificaciones (sin filtro)
        }

        return response()->json([
            'success' => true,
            'unread_count' => $query->count(),
            'user_role' => $user->role, // Para debugging
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada'
        ]);
    }

    /**
     * Get recent notifications for real-time updates
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->get('since', 0);

        // Convertir timestamp de milisegundos a segundos
        $sinceDate = $since > 0 ? date('Y-m-d H:i:s', $since / 1000) : null;

        $notifications = $user->notifications()
            ->when($sinceDate, function ($query, $date) {
                return $query->where('created_at', '>', $date);
            })
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'timestamp' => now()->timestamp * 1000,
            'unread_count' => $user->unreadNotifications()->count(),
            'polling_interval' => 30000, // Sugerir 30 segundos en lugar de 5
        ]);
    }

    /**
     * Optimized endpoint for dashboard polling
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        // Solo devolver datos esenciales para reducir el tráfico
        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'has_new' => $user->notifications()
                ->where('created_at', '>', now()->subMinutes(5))
                ->exists(),
            'timestamp' => now()->timestamp * 1000,
            'websocket_enabled' => true, // Indicar que WebSockets están disponibles
            'websocket_url' => env('REVERB_HOST', 'localhost') . ':' . env('REVERB_PORT', 8080),
        ]);
    }

    /**
     * Get WebSocket configuration for frontend
     */
    public function websocketConfig(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'config' => [
                'host' => env('REVERB_HOST', 'localhost'),
                'port' => env('REVERB_PORT', 8080),
                'key' => env('REVERB_APP_KEY', 'local-key'),
                'cluster' => 'mt1',
                'forceTLS' => false,
                'enabledTransports' => ['ws', 'wss'],
            ],
            'channels' => [
                'private' => 'notifications.' . $request->user()->id,
                'public' => 'laboratory',
            ]
        ]);
    }
}
