<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebSocketConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebSocketController extends Controller
{
    protected $connectionService;

    public function __construct(WebSocketConnectionService $connectionService)
    {
         Log::info('WebSocketController => __construct');
        $this->connectionService = $connectionService;
    }

    /**
     * Obtener lista de usuarios conectados
     */
    public function getConnectedUsers(Request $request)
    {
        try {
            $data = $this->connectionService->getFormattedConnectedUsers();

            Log::info('Usuarios conectados solicitados', [
                'total' => $data['total'],
                'admin_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios conectados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios conectados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar un usuario como conectado
     */
    public function userConnect(Request $request)
    {
        Log::info('WebSocketController => userConnect', [
            '$request' => $request->all()
        ]);

        $validated = $request->validate([
            'userId' => 'required|integer',
            'name' => 'required|string',
            'role' => 'required|string',
        ]);

        try {
            $user = $this->connectionService->userConnected(
                $validated['userId'],
                $validated['name'],
                $validated['role']
            );

            Log::info('Usuario conectado vía WebSocket', [
                'userId' => $validated['userId'],
                'name' => $validated['name'],
                'role' => $validated['role']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario conectado exitosamente',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error al registrar usuario conectado', [
                'error' => $e->getMessage(),
                'userId' => $validated['userId']
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar conexión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar un usuario como desconectado
     */
    public function userDisconnect(Request $request)
    {
        $validated = $request->validate([
            'userId' => 'required|integer',
        ]);

        try {
            $user = $this->connectionService->userDisconnected($validated['userId']);

            if ($user) {
                Log::info('Usuario desconectado vía WebSocket', [
                    'userId' => $validated['userId'],
                    'name' => $user['name']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Usuario desconectado exitosamente',
                    'data' => $user
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado en conexiones activas'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al registrar usuario desconectado', [
                'error' => $e->getMessage(),
                'userId' => $validated['userId']
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar desconexión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forzar desconexión de un usuario (admin only)
     */
    public function disconnectUser(Request $request, $userId)
    {
        try {
            // Verificar que el usuario sea admin
            if ($request->user()->role !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $success = $this->connectionService->forceDisconnectUser($userId);

            if ($success) {
                Log::info('Usuario desconectado forzadamente por admin', [
                    'userId' => $userId,
                    'admin_id' => $request->user()->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Usuario desconectado exitosamente'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado en conexiones activas'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al desconectar usuario', [
                'error' => $e->getMessage(),
                'userId' => $userId,
                'admin_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al desconectar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar conexiones inactivas
     */
    public function cleanupConnections(Request $request)
    {
        try {
            // Verificar que el usuario sea admin
            if ($request->user()->role !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $cleaned = $this->connectionService->cleanupInactiveConnections();

            Log::info('Limpieza de conexiones ejecutada', [
                'cleaned' => $cleaned,
                'admin_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se limpiaron {$cleaned} conexiones inactivas",
                'cleaned' => $cleaned
            ]);
        } catch (\Exception $e) {
            Log::error('Error al limpiar conexiones', [
                'error' => $e->getMessage(),
                'admin_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar conexiones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar actividad de usuario
     */
    public function updateActivity(Request $request)
    {
        $validated = $request->validate([
            'userId' => 'required|integer',
        ]);

        try {
            $success = $this->connectionService->updateUserActivity($validated['userId']);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Actividad actualizada'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar actividad',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
