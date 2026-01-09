<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Events\UserOnline;
use App\Events\UserOffline;
use App\Events\ActiveUsersUpdated;

class WebSocketConnectionService
{
    private const CACHE_KEY = 'websocket_connected_users';
    private const CACHE_TTL = 3600; // 1 hora

    /**
     * Registrar un usuario como conectado
     */
    public function userConnected($userId, $name, $role)
    {
        $connectedUsers = $this->getConnectedUsers();
        
        // Agregar o actualizar usuario
        $connectedUsers[$userId] = [
            'id' => $userId,
            'name' => $name,
            'role' => $role,
            'connected_at' => now()->toIso8601String(),
            'last_activity' => now()->toIso8601String(),
        ];

        $this->saveConnectedUsers($connectedUsers);

        // Broadcast evento de usuario conectado
        broadcast(new UserOnline($userId, $name, $role))->toOthers();

        // Broadcast lista actualizada de usuarios
        $this->broadcastActiveUsers();

        return $connectedUsers[$userId];
    }

    /**
     * Registrar un usuario como desconectado
     */
    public function userDisconnected($userId)
    {
        $connectedUsers = $this->getConnectedUsers();
        
        if (isset($connectedUsers[$userId])) {
            $user = $connectedUsers[$userId];
            unset($connectedUsers[$userId]);
            
            $this->saveConnectedUsers($connectedUsers);

            // Broadcast evento de usuario desconectado
            broadcast(new UserOffline($user['id'], $user['name'], $user['role']))->toOthers();

            // Broadcast lista actualizada de usuarios
            $this->broadcastActiveUsers();

            return $user;
        }

        return null;
    }

    /**
     * Actualizar la última actividad de un usuario
     */
    public function updateUserActivity($userId)
    {
        $connectedUsers = $this->getConnectedUsers();
        
        if (isset($connectedUsers[$userId])) {
            $connectedUsers[$userId]['last_activity'] = now()->toIso8601String();
            $this->saveConnectedUsers($connectedUsers);
            return true;
        }

        return false;
    }

    /**
     * Obtener todos los usuarios conectados
     */
    public function getConnectedUsers(): array
    {
        return Cache::get(self::CACHE_KEY, []);
    }

    /**
     * Obtener usuarios conectados formateados para el frontend
     */
    public function getFormattedConnectedUsers(): array
    {
        $connectedUsers = $this->getConnectedUsers();
        
        return [
            'usuarios' => array_values($connectedUsers),
            'total' => count($connectedUsers),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Guardar usuarios conectados en cache
     */
    private function saveConnectedUsers(array $users): void
    {
        Cache::put(self::CACHE_KEY, $users, self::CACHE_TTL);
    }

    /**
     * Broadcast lista de usuarios activos
     */
    public function broadcastActiveUsers(): void
    {
        $users = array_values($this->getConnectedUsers());
        broadcast(new ActiveUsersUpdated($users));
    }

    /**
     * Limpiar conexiones inactivas (más de 5 minutos sin actividad)
     */
    public function cleanupInactiveConnections(): int
    {
        $connectedUsers = $this->getConnectedUsers();
        $cleaned = 0;
        $now = now();

        foreach ($connectedUsers as $userId => $user) {
            $lastActivity = \Carbon\Carbon::parse($user['last_activity']);
            
            // Si el usuario ha estado inactivo por más de 5 minutos
            if ($now->diffInMinutes($lastActivity) > 5) {
                unset($connectedUsers[$userId]);
                $cleaned++;

                // Broadcast evento de usuario desconectado
                broadcast(new UserOffline($user['id'], $user['name'], $user['role']))->toOthers();
            }
        }

        if ($cleaned > 0) {
            $this->saveConnectedUsers($connectedUsers);
            $this->broadcastActiveUsers();
        }

        return $cleaned;
    }

    /**
     * Forzar desconexión de un usuario específico
     */
    public function forceDisconnectUser($userId): bool
    {
        $user = $this->userDisconnected($userId);
        
        if ($user) {
            // Aquí podrías agregar lógica adicional como cerrar sesión del usuario
            // o invalidar su token
            return true;
        }

        return false;
    }

    /**
     * Limpiar todas las conexiones
     */
    public function clearAllConnections(): int
    {
        $connectedUsers = $this->getConnectedUsers();
        $count = count($connectedUsers);
        
        Cache::forget(self::CACHE_KEY);
        
        // Broadcast lista vacía
        $this->broadcastActiveUsers();

        return $count;
    }
}
