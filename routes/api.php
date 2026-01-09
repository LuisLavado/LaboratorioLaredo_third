<?php

use App\Http\Controllers\ExamenController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DetalleSolicitudController;
use App\Http\Controllers\DniController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReportNotificationController;
use App\Http\Controllers\AdminController;
// use App\Http\Controllers\SolicitudCambioController; // Comentado temporalmente
use App\Http\Controllers\TemporaryFileController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\Api\WebSocketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Rutas públicas de autenticación
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/dni/{dni}', [DniController::class, 'consultar']);

// Health check para Docker
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'Laboratorio Laredo API'
    ]);
});

// Rutas de Broadcasting Authentication (protegidas con Sanctum)
// IMPORTANTE: Debe estar FUERA del grupo de rutas protegidas para evitar conflictos
Route::post('/broadcasting/auth', function (Request $request) {
    // Debug: Log de la solicitud
    \Log::info('Broadcasting Auth Request', [
        'headers' => $request->headers->all(),
        'user' => $request->user(),
        'socket_id' => $request->input('socket_id'),
        'channel_name' => $request->input('channel_name'),
    ]);

    // Validar que el usuario esté autenticado con Sanctum
    if (!$request->user()) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    try {
        // Usar el broadcaster configurado para autenticar el canal
        $result = Broadcast::auth($request);
        \Log::info('Broadcasting Auth Success', ['result' => $result]);
        return $result;
    } catch (\Exception $e) {
        \Log::error('Broadcasting Auth Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => $e->getMessage()], 403);
    }
})->middleware('auth:sanctum');

// Ruta para eventos en tiempo real (ahora es JSON en lugar de SSE)
Route::get('/events', [WebhookController::class, 'events']);

// Rutas para verificación de QR
Route::post('/qr/verify', [QrController::class, 'verify']);

// Ruta para verificar estado del servidor
Route::get('/health-check', function() {
    return response()->json(['status' => 'ok', 'message' => 'Server is running']);
});

// Rutas protegidas
Route::middleware(['auth:sanctum'])->group(function () {
    // Cerrar sesión
    Route::post('/logout', [UserController::class, 'logout']);

    // Obtener información del usuario autenticado
    Route::get('/me', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
        ]);
    });

    // Rutas optimizadas para el dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\DashboardController::class, 'getStats']);
        Route::get('/pending-requests', [\App\Http\Controllers\DashboardController::class, 'getPendingRequests']);
        Route::get('/recent-activity', [\App\Http\Controllers\DashboardController::class, 'getRecentActivity']);

        // Rutas para dashboard de doctor
        Route::get('/doctor/stats', [\App\Http\Controllers\DashboardController::class, 'getDoctorStats']);
        Route::get('/doctor/recent-requests', [\App\Http\Controllers\DashboardController::class, 'getDoctorRecentRequests']);
    });

    // Rutas de notificaciones
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::get('/unread', [\App\Http\Controllers\NotificationController::class, 'unread']);
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy']);
        Route::delete('/read/clear', [\App\Http\Controllers\NotificationController::class, 'clearRead']);
    });

    // Rutas para usuarios
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::get('/users/by-ids', [UserController::class, 'getUsersByIds']);
    Route::get('/users/{id}/activity', [UserController::class, 'getUserActivity']);

    // Rutas para webhooks
    Route::post('/webhooks/register', [WebhookController::class, 'register']);
    Route::post('/webhooks/unregister', [WebhookController::class, 'unregister']);
    Route::get('/user/webhook-config', [WebhookController::class, 'getConfig']);

    // Rutas para QR
    Route::get('/solicitudes/{solicitud}/qr', [SolicitudController::class, 'generateQr']);

    // Rutas de pacientes
    Route::apiResource('pacientes', PacienteController::class);
    Route::get('/pacientes/search/dni/{dni}', [PacienteController::class, 'searchByDNI']);
    Route::get('/pacientes/search', [PacienteController::class, 'search']);
    Route::get('/pacientes/trashed', [PacienteController::class, 'trashed'])->name('pacientes.trashed');
    Route::get('/pacientes-eliminados', [PacienteController::class, 'getPacientesEliminados'])->name('pacientes.eliminados');
    Route::patch('/pacientes/restore/{paciente_id}', [PacienteController::class, 'restore']);
    Route::delete('/pacientes/force/{paciente_id}', [PacienteController::class, 'forceDelete']);
    Route::apiResource('resultados-examen', \App\Http\Controllers\ResultadoExamenController::class);
    // Rutas de exámenes
    Route::apiResource('examenes', ExamenController::class);
    Route::get('/examenes-inactivos', [ExamenController::class, 'getInactivos']);
    Route::get('/examenes-simples-sin-perfiles', [ExamenController::class, 'getSimplesSinPerfiles']);

    // Rutas para campos de examen
    Route::post('/campos-examen/multiple', [\App\Http\Controllers\CampoExamenController::class, 'storeMultiple']);
    Route::post('/campos-examen/reorder', [\App\Http\Controllers\CampoExamenController::class, 'reorder']);
    Route::post('/campos-examen/{campoExamen}/desactivar', [\App\Http\Controllers\CampoExamenController::class, 'desactivar']);
    Route::post('/campos-examen/{campoExamen}/reactivar', [\App\Http\Controllers\CampoExamenController::class, 'reactivar']);
    Route::apiResource('campos-examen', \App\Http\Controllers\CampoExamenController::class);

    // Rutas para exámenes compuestos
    Route::post('/examenes-compuestos/reorder', [\App\Http\Controllers\ExamenCompuestoController::class, 'reorder']);
    Route::apiResource('examenes-compuestos', \App\Http\Controllers\ExamenCompuestoController::class);

    // Rutas para valores de resultado
    Route::get('/valores-resultado/plantilla', [\App\Http\Controllers\ValorResultadoController::class, 'plantilla']);
    Route::post('/valores-resultado/validar', [\App\Http\Controllers\ValorResultadoController::class, 'validar']);
    Route::get('/valores-resultado/exportar', [\App\Http\Controllers\ValorResultadoController::class, 'exportar']);
    Route::post('/valores-resultado/campo', [\App\Http\Controllers\ValorResultadoController::class, 'storeCampo']);
    Route::post('/valores-resultado/batch', [\App\Http\Controllers\ValorResultadoController::class, 'storeBatch']);
    Route::post('/valores-resultado/simple', [\App\Http\Controllers\ValorResultadoController::class, 'storeSimple']);
    Route::get('/valores-resultado/detalle/{detalleSolicitudId}', [\App\Http\Controllers\ValorResultadoController::class, 'getByDetalle']);

    // Rutas para exportación de resultados a PDF
    Route::get('/resultados/solicitud/{solicitud_id}/pdf', [\App\Http\Controllers\ValorResultadoController::class, 'exportarSolicitudPDF']);
    Route::get('/resultados/detalle/{detalle_solicitud_id}/pdf', [\App\Http\Controllers\ValorResultadoController::class, 'exportarDetallePDF']);

    Route::apiResource('valores-resultado', \App\Http\Controllers\ValorResultadoController::class);

    // Rutas de solicitudes
    Route::apiResource('solicitudes', SolicitudController::class);
    Route::patch('/solicitudes/{solicitud}/resultado', [SolicitudController::class, 'updateResultado']);
    Route::patch('/solicitudes/{solicitud}/estado', [SolicitudController::class, 'updateEstado']);
    Route::get('/solicitudes-con-estado', [SolicitudController::class, 'getWithStatus']);

    // Rutas de servicios
    Route::get('servicios-con-stats', [ServicioController::class, 'getWithStats']);
    Route::get('servicios-inactivos', [ServicioController::class, 'getInactive']);
    Route::patch('servicios/{servicio}/activar', [ServicioController::class, 'activate']);
    Route::apiResource('servicios', ServicioController::class);

    // Rutas de categorías
    Route::apiResource('categorias', CategoriaController::class);

    // Rutas para DetalleSolicitud
    Route::get('/detallesolicitud', [DetalleSolicitudController::class, 'index']);
    Route::get('/detallesolicitud/{id}', [DetalleSolicitudController::class, 'show']);
    Route::post('/detallesolicitud', [DetalleSolicitudController::class, 'store']);
    Route::put('/detallesolicitud/{id}', [DetalleSolicitudController::class, 'update']);
    Route::delete('/detallesolicitud/{id}', [DetalleSolicitudController::class, 'destroy']);
    Route::get('/solicitudes/{solicitudId}/detalles', [DetalleSolicitudController::class, 'getDetallesBySolicitud']);
    Route::post('/detallesolicitud/{id}/resultados', [DetalleSolicitudController::class, 'registrarResultados']);
    Route::patch('/detallesolicitud/{id}/estado', [DetalleSolicitudController::class, 'actualizarEstado']);
});



// Ruta temporal para probar PDF sin autenticación
Route::get('/reportes/pdf', [ReporteController::class, 'generatePDF']);

// Rutas para reportes y estadísticas
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reportes', [ReporteController::class, 'getReports']);
    Route::get('/reportes/excel', [ReporteController::class, 'generateExcel']);

    Route::get('/reportes/chart-data', [ReporteController::class, 'getChartData']);
    Route::get('/reportes/check-data', [ReporteController::class, 'checkDataAvailability']);

    // Ruta para estadísticas del dashboard (DESHABILITADA - usar DashboardController en su lugar)
    // Route::get('/dashboard/stats', [ReporteController::class, 'dashboardStats']);

    // Rutas para notificaciones WhatsApp
    Route::post('/reportes/send-whatsapp', [ReportNotificationController::class, 'sendWhatsApp']);
    Route::get('/reportes/notification-history', [ReportNotificationController::class, 'getNotificationHistory']);
});

// Rutas públicas para descarga de archivos temporales
Route::get('/files/temporary/{token}', [TemporaryFileController::class, 'download'])->name('temporary-file.download');
Route::get('/files/temporary/{token}/info', [TemporaryFileController::class, 'info']);

// Rutas específicas para doctores
Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    // Rutas para que los doctores puedan gestionar pacientes
    Route::get('/doctor/pacientes', [PacienteController::class, 'index']);
    Route::post('/doctor/pacientes', [PacienteController::class, 'store']);
    Route::get('/doctor/pacientes/{paciente}', [PacienteController::class, 'show']);
    Route::put('/doctor/pacientes/{paciente}', [PacienteController::class, 'update']);

    // Rutas para que los doctores puedan crear solicitudes
    Route::get('/doctor/solicitudes', [SolicitudController::class, 'doctorSolicitudes']);
    Route::post('/doctor/solicitudes', [SolicitudController::class, 'storeDoctorSolicitud']);
    Route::get('/doctor/solicitudes/{solicitud}', [SolicitudController::class, 'show']);

    // Búsqueda de pacientes para doctores
    Route::get('/doctor/pacientes/search/dni/{dni}', [PacienteController::class, 'searchByDNI']);
    Route::get('/doctor/pacientes/search', [PacienteController::class, 'search']);
});

// Rutas específicas para técnicos de laboratorio
Route::middleware(['auth:sanctum', 'role:laboratorio'])->group(function () {
    // Rutas específicas para laboratorio si es necesario
});

// Rutas de administración (solo para administradores)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard de administrador
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);

    // Gestión de usuarios
    Route::get('/admin/usuarios', [AdminController::class, 'usuarios']);
    Route::get('/admin/usuarios/{id}', [AdminController::class, 'obtenerUsuario']);
    Route::get('/admin/usuarios/{id}/activity', [AdminController::class, 'obtenerActividadUsuario']);
    Route::post('/admin/usuarios', [AdminController::class, 'crearUsuario']);
    Route::put('/admin/usuarios/{id}', [AdminController::class, 'actualizarUsuario']);
    Route::patch('/admin/usuarios/{id}/desactivar', [AdminController::class, 'desactivarUsuario']);
    Route::patch('/admin/usuarios/{id}/activar', [AdminController::class, 'activarUsuario']);
    Route::post('/admin/usuarios/{id}/cerrar-sesion', [AdminController::class, 'cerrarSesionUsuario']);

    // Usuarios en línea
    Route::get('/admin/usuarios-en-linea', [AdminController::class, 'usuariosEnLinea']);

    // WebSocket - Gestión de conexiones en tiempo real
    Route::get('/admin/connected-users', [WebSocketController::class, 'getConnectedUsers']);
    Route::post('/admin/disconnect-user/{userId}', [WebSocketController::class, 'disconnectUser']);
    Route::post('/admin/cleanup-connections', [WebSocketController::class, 'cleanupConnections']);

    // Centros de salud
    Route::get('/admin/centros-salud', [AdminController::class, 'centrosSalud']);
    Route::post('/admin/centros-salud', [AdminController::class, 'crearCentroSalud']);
    Route::put('/admin/centros-salud/{id}', [AdminController::class, 'actualizarCentroSalud']);
    Route::delete('/admin/centros-salud/{id}', [AdminController::class, 'eliminarCentroSalud']);

    // Actividades y estadísticas detalladas
    Route::get('/admin/actividades-detalladas', [AdminController::class, 'actividadesDetalladas']);
    Route::get('/admin/actividades-por-fecha', [AdminController::class, 'actividadesPorFecha']);
    Route::get('/admin/estadisticas-usuario/{id}', [AdminController::class, 'estadisticasUsuario']);
    Route::get('/admin/resumen-por-dia', [AdminController::class, 'resumenPorDia']);

    // Solicitudes de cambio - Comentadas temporalmente
    // Route::get('/admin/solicitudes-cambio', [SolicitudCambioController::class, 'index']);
    // Route::get('/admin/solicitudes-cambio/{id}', [SolicitudCambioController::class, 'show']);
    // Route::patch('/admin/solicitudes-cambio/{id}/aprobar', [SolicitudCambioController::class, 'aprobar']);
    // Route::patch('/admin/solicitudes-cambio/{id}/rechazar', [SolicitudCambioController::class, 'rechazar']);
    // Route::post('/admin/solicitudes-cambio/{id}/aplicar', [SolicitudCambioController::class, 'aplicarCambios']);
    // Route::get('/admin/solicitudes-cambio-stats', [SolicitudCambioController::class, 'estadisticas']);
});

// WebSocket - Rutas públicas para registrar conexiones/desconexiones
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/websocket/connect', [WebSocketController::class, 'userConnect']);
    Route::post('/websocket/disconnect', [WebSocketController::class, 'userDisconnect']);
    Route::post('/websocket/update-activity', [WebSocketController::class, 'updateActivity']);
});

// Rutas para crear solicitudes de cambio (usuarios autenticados) - Comentadas temporalmente
Route::middleware(['auth:sanctum'])->group(function () {
    // Route::post('/solicitudes-cambio', [SolicitudCambioController::class, 'store']);
    // Route::get('/mis-solicitudes-cambio', [SolicitudCambioController::class, 'index']);
});
