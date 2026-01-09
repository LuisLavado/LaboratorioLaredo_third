<?php

use Illuminate\Support\Facades\Broadcast;

// Log para verificar que este archivo se está cargando
\Log::info('🔵 CHANNELS.PHP LOADED');

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal privado para notificaciones de usuario
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal privado para cada doctor (resultados listos)
Broadcast::channel('doctor.{userId}', function ($user, $userId) {
    \Log::info('Broadcasting Channel Authorization - START', [
        'channel' => "doctor.{$userId}",
        'user_id' => $user->id ?? 'no-id',
        'user_role' => $user->role ?? 'no-role',
        'requested_userId' => $userId,
    ]);
    
    try {
        // Verificar que el usuario existe
        if (!$user || !isset($user->id)) {
            \Log::error('Broadcasting Auth - No user or user ID');
            return false;
        }
        
        // Verificar que el usuario autenticado sea el mismo del canal
        $authorized = (int) $user->id === (int) $userId;
        
        \Log::info('Broadcasting Authorization Result', [
            'authorized' => $authorized,
            'user_id' => $user->id,
            'channel_userId' => $userId,
            'will_return' => $authorized ? 'user_data' : 'false',
        ]);
        
        // Para canales privados, devolver true o array con datos del usuario
        // Reverb/Pusher aceptan ambos formatos
        return $authorized;
        
    } catch (\Exception $e) {
        \Log::error('Broadcasting Channel Authorization Exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return false;
    }
});

// Canal público para administradores - permite monitorear usuarios conectados
Broadcast::channel('administradores', function ($user) {
    // Canal público - cualquier usuario autenticado puede escuchar
    return true;
});
