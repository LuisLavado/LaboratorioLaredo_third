import { useState, useEffect, useRef, useCallback } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useAuth } from '../contexts/AuthContext';
import { reverbService } from '../services/api';
import { showSystemNotification } from '../services/notificationService';

/**
 * Hook personalizado para manejar WebSocket con Laravel Reverb
 * Integrado con el sistema de notificaciones en tiempo real
 */
export const useWebSocket = (options = {}) => {
  const {
    autoConnect = true,
    reconnectAttempts = 5,
    reconnectInterval = 3000,
    enableNotifications = true,
  } = options;

  const { user, token } = useAuth();
  const queryClient = useQueryClient();
  const [isConnected, setIsConnected] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState('disconnected');
  const [lastMessage, setLastMessage] = useState(null);
  const [error, setError] = useState(null);
  const [notifications, setNotifications] = useState([]);

  const reconnectAttemptsRef = useRef(0);
  const reconnectTimeoutRef = useRef(null);

  // Función para manejar notificaciones recibidas
  const handleNotification = useCallback((type, data) => {
    console.log(`[WebSocket] Notificación recibida: ${type}`, data);

    const notification = {
      id: Date.now(),
      type,
      data,
      timestamp: new Date().toISOString(),
      read: false
    };

    // Agregar a la lista de notificaciones
    setNotifications(prev => [notification, ...prev.slice(0, 49)]); // Mantener solo las últimas 50

    // Mostrar notificación del sistema si está habilitado
    if (enableNotifications) {
      let title = 'Nueva notificación';
      let body = 'Tienes una nueva notificación';
      let icon = '🔔';

      if (type === 'solicitud.created') {
        title = '📋 Nueva solicitud';
        body = data.solicitud ?
          `Nueva solicitud de ${data.solicitud.nombres} ${data.solicitud.apellidos}` :
          'Se ha creado una nueva solicitud';
        icon = '📋';
      } else if (type === 'solicitud.completed') {
        title = '✅ Resultados listos';
        body = data.solicitud ?
          `Los resultados de ${data.solicitud.nombres} ${data.solicitud.apellidos} están listos` :
          'Los resultados de una solicitud están listos';
        icon = '✅';
      }

      showSystemNotification(title, {
        body,
        icon: '/favicon.ico',
        tag: `notification-${notification.id}`,
        data: {
          type,
          notificationId: notification.id,
          url: data.action_url
        }
      });
    }

    // Actualizar el último mensaje
    setLastMessage(notification);

    // Invalidar queries para actualizar la UI
    queryClient.invalidateQueries(['unreadNotifications']);
    queryClient.invalidateQueries(['allNotifications']);
  }, [enableNotifications, queryClient]);

  // Función para conectar
  const connect = useCallback((userToken = null) => {
    const authToken = userToken || token;

    if (!authToken || !user) {
      console.warn('[WebSocket] No hay token o usuario para conectar');
      return;
    }

    try {
      setConnectionStatus('connecting');
      setError(null);

      console.log(`[WebSocket] Conectando como ${user.role} (ID: ${user.id})`);

      // Conectar a Reverb según el role
      reverbService.connect(user.role);

      // Estado de conexión
      setIsConnected(true);
      setConnectionStatus('connected');
      setError(null);
      reconnectAttemptsRef.current = 0;
      console.log('[WebSocket] Conectado exitosamente a Reverb');

      // Suscribirse a notificaciones
      reverbService.subscribe('notification', (data) => {
        console.log('[WebSocket] Notificación recibida:', data);
        handleNotification(data.type || 'notification', data);
      });

      // Suscribirse a eventos específicos según el role
      if (user.role === 'doctor') {
        reverbService.subscribe('result_ready', (data) => {
          console.log('[WebSocket] Resultado listo:', data);
          handleNotification('solicitud.completed', data);
        });
      } else if (user.role === 'laboratorio') {
        reverbService.subscribe('new_request', (data) => {
          console.log('[WebSocket] Nueva solicitud:', data);
          handleNotification('solicitud.created', data);
        });
      }

    } catch (error) {
      setError(error);
      setConnectionStatus('error');
      console.error('[WebSocket] Error al conectar:', error);
      
      // Intentar reconectar
      if (reconnectAttemptsRef.current < reconnectAttempts) {
        attemptReconnect(authToken);
      }
    }
  }, [user, token, reconnectAttempts, handleNotification]);

  // Función para reconectar
  const attemptReconnect = useCallback((authToken) => {
    if (reconnectAttemptsRef.current >= reconnectAttempts) {
      console.log('[WebSocket] Máximo número de intentos de reconexión alcanzado');
      return;
    }

    // No reconectar si fue desconexión administrativa
    if (connectionStatus === 'admin_disconnected') {
      console.log('[WebSocket] 🚫 No se reconectará debido a desconexión administrativa');
      return;
    }

    reconnectAttemptsRef.current += 1;
    setConnectionStatus('reconnecting');

    console.log(`[WebSocket] Intentando reconectar... (${reconnectAttemptsRef.current}/${reconnectAttempts})`);

    reconnectTimeoutRef.current = setTimeout(() => {
      connect(authToken);
    }, reconnectInterval);
  }, [connect, reconnectAttempts, reconnectInterval, connectionStatus]);

  // Función para desconectar
  const disconnect = useCallback(() => {
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
    }

    reverbService.disconnect();
    setIsConnected(false);
    setConnectionStatus('disconnected');
    reconnectAttemptsRef.current = 0;
    console.log('[WebSocket] Desconectado manualmente');
  }, []);

  // Función para enviar ping (no necesario con Reverb - mantiene la conexión automáticamente)
  const ping = useCallback(() => {
    if (isConnected) {
      console.log('[WebSocket] Ping (automático con Reverb)');
      return true;
    } else {
      console.warn('[WebSocket] No está conectado');
      return false;
    }
  }, [isConnected]);

  // Función para marcar notificación como leída
  const markNotificationAsRead = useCallback((notificationId) => {
    setNotifications(prev =>
      prev.map(notif =>
        notif.id === notificationId
          ? { ...notif, read: true }
          : notif
      )
    );
  }, []);

  // Función para limpiar notificaciones
  const clearNotifications = useCallback(() => {
    setNotifications([]);
  }, []);

  // Auto-conectar si está habilitado
  useEffect(() => {
    if (autoConnect && user && token) {
      connect();
    }

    // Cleanup al desmontar
    return () => {
      if (reconnectTimeoutRef.current) {
        clearTimeout(reconnectTimeoutRef.current);
      }
      disconnect();
    };
  }, [autoConnect, user, token, connect, disconnect]);

  // Obtener estadísticas de conexión
  const getConnectionStats = useCallback(() => {
    return {
      connected: isConnected,
      status: connectionStatus,
      activeChannels: reverbService.activeChannels || [],
      user: user
    };
  }, [isConnected, connectionStatus, user]);

  return {
    // Estado de conexión
    isConnected,
    connectionStatus,
    error,

    // Notificaciones
    notifications,
    unreadNotifications: notifications.filter(n => !n.read),
    lastMessage,

    // Funciones de conexión
    connect,
    disconnect,
    ping,

    // Funciones de notificaciones
    markNotificationAsRead,
    clearNotifications,

    // Información adicional
    reconnectAttemptsLeft: reconnectAttempts - reconnectAttemptsRef.current,
    connectionStats: getConnectionStats(),

    // Usuario conectado
    connectedUser: user,
  };
};

export default useWebSocket;
