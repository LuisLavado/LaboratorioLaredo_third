import React, { useEffect, useState, useCallback } from 'react';
import { useWebSocketContext } from '../websocket/WebSocketProvider';
import { useAuth } from '../../contexts/AuthContext';
import {
  BellIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  XMarkIcon,
  WifiIcon,
  SignalSlashIcon
} from '@heroicons/react/24/outline';

const RealtimeNotifications = () => {
  const { user, isAuthenticated } = useAuth();

  // Intentar obtener el contexto WebSocket, pero manejar el caso cuando no esté disponible
  let webSocketData = null;
  try {
    webSocketData = useWebSocketContext();
  } catch (error) {
    // El contexto no está disponible, probablemente porque el usuario no está autenticado
    console.log('[RealtimeNotifications] WebSocket context no disponible:', error.message);
  }

  // Si no hay contexto WebSocket disponible, mostrar estado desconectado
  const {
    isConnected = false,
    connectionStatus = 'disconnected',
    notifications = [],
    unreadNotifications = [],
    unreadCount = 0,
    markNotificationAsRead = () => {},
    markAllAsRead = () => {},
    clearNotifications = () => {},
    connect = () => {},
    disconnect = () => {}
  } = webSocketData || {};

  const [showNotifications, setShowNotifications] = useState(false);
  const [showConnectionStatus, setShowConnectionStatus] = useState(false);
  const [lastProcessedMessage, setLastProcessedMessage] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

  // Función para reproducir sonido de notificación
  const playNotificationSound = useCallback(() => {
    // Verificar configuración de sonido
    const settingsStr = localStorage.getItem('notification_settings');
    console.log('🔍 [playNotificationSound] Settings raw:', settingsStr);
    
    const settings = settingsStr ? JSON.parse(settingsStr) : { enabled: true, sound: true };
    console.log('🔍 [playNotificationSound] Settings parsed:', settings);
    console.log('🔍 [playNotificationSound] sound value:', settings.sound, 'type:', typeof settings.sound);
    
    // Verificar si las notificaciones están habilitadas
    if (settings.enabled === false) {
      console.log('🔇 [RealtimeNotifications] Notificaciones desactivadas por configuración');
      return;
    }
    
    // Verificar si el sonido está habilitado (verificar explícitamente false)
    if (settings.sound === false) {
      console.log('🔇 [RealtimeNotifications] Sonido desactivado por configuración');
      return;
    }

    try {
      const audio = new Audio('/notification.mp3');
      audio.volume = 1.0;
      audio.play().catch(err => {
        console.error('Error reproduciendo sonido:', err);
      });
      console.log('🔊 [RealtimeNotifications] Sonido de notificación reproducido');
    } catch (error) {
      console.log('[RealtimeNotifications] Error al reproducir sonido:', error);
    }
  }, []);

  // Mostrar estado de conexión temporalmente cuando cambie
  useEffect(() => {
    if (connectionStatus === 'connected' || connectionStatus === 'error') {
      setShowConnectionStatus(true);
      const timer = setTimeout(() => {
        setShowConnectionStatus(false);
      }, 3000);
      return () => clearTimeout(timer);
    }
  }, [connectionStatus]);

  // Manejar nuevas notificaciones cuando llegan (con control ultra estricto)
  useEffect(() => {
    const currentMessage = webSocketData?.lastMessage;

    if (currentMessage && user && currentMessage !== lastProcessedMessage && !isProcessing) {
      // Verificar si las notificaciones están habilitadas
      const settings = JSON.parse(localStorage.getItem('notification_settings') || '{}');
      console.log('📋 [RealtimeNotifications] Verificando configuración:', settings);
      
      if (settings.enabled === false) {
        console.log('🔇 [RealtimeNotifications] Notificaciones desactivadas por configuración');
        setLastProcessedMessage(currentMessage);
        return;
      }

      setIsProcessing(true);

      const { type, data } = currentMessage;

      console.log(`🔔 [RealtimeNotifications] Nueva notificación: ${type}`, data);
      console.log(`👤 [RealtimeNotifications] Usuario: ${user.role} (ID: ${user.id})`);

      // Marcar este mensaje como procesado INMEDIATAMENTE
      setLastProcessedMessage(currentMessage);

      // Procesar según el tipo de notificación y rol del usuario
      if (type === 'solicitud.created' && (user.role === 'laboratorio' || user.role === 'admin')) {
        console.log('📋 [RealtimeNotifications] Nueva solicitud para laboratorio');
        // Reproducir sonido solo si está habilitado
        playNotificationSound();
        // Ya no llamamos a showNewRequestNotification (notificación del sistema)
        // Solo se muestra en la UI
      } else if (type === 'solicitud.completed' && user.role === 'doctor') {
        console.log('✅ [RealtimeNotifications] Resultados listos para doctor');

        // El canal privado ya garantiza que solo llegue al doctor correcto
        // Verificación adicional por seguridad (comparación segura de tipos)
        const isForThisDoctor = String(data.user_id) === String(user.id);

        if (isForThisDoctor) {
          console.log('✅ [RealtimeNotifications] Notificación de resultados confirmada para este doctor');
          // Reproducir sonido solo si está habilitado
          playNotificationSound();
          // Ya no llamamos a showResultsReadyNotification (notificación del sistema)
          // Solo se muestra en la UI
        } else {
          console.log('⚠️ [RealtimeNotifications] Notificación de resultados no es para este doctor', {
            receivedUserId: data.user_id,
            currentUserId: user.id,
            match: String(data.user_id) === String(user.id)
          });
        }
      } else {
        console.log(`⚠️ [RealtimeNotifications] Notificación no aplicable para rol ${user.role} o tipo ${type}`);
      }

      // Liberar el lock después de un breve delay
      setTimeout(() => {
        setIsProcessing(false);
      }, 500);
    }
  }, [webSocketData?.lastMessage, user, lastProcessedMessage, isProcessing, playNotificationSound]);

  const getConnectionStatusColor = () => {
    switch (connectionStatus) {
      case 'connected': return 'text-green-500';
      case 'connecting': return 'text-yellow-500';
      case 'reconnecting': return 'text-orange-500';
      case 'error': return 'text-red-500';
      default: return 'text-gray-500';
    }
  };

  const getConnectionStatusText = () => {
    switch (connectionStatus) {
      case 'connected': return 'Conectado';
      case 'connecting': return 'Conectando...';
      case 'reconnecting': return 'Reconectando...';
      case 'error': return 'Error de conexión';
      default: return 'Desconectado';
    }
  };

  const handleNotificationClick = async (notification) => {
    console.log('👆 [handleNotificationClick] Click en notificación:', {
      id: notification.id,
      type: notification.type,
      persistent: notification.persistent,
      read: notification.read,
      data: notification.data
    });
    
    // IMPORTANTE: Esperar a que se complete el marcado antes de navegar
    console.log('⏳ [handleNotificationClick] Esperando a que se marque como leída...');
    await markNotificationAsRead(notification.id);
    console.log('✅ [handleNotificationClick] Notificación marcada, ahora navegando...');
    
    // Construir la URL según el tipo de notificación
    let url = null;
    
    if (notification.type === 'solicitud.created') {
      // Nueva solicitud - redirigir a la lista de solicitudes o detalle
      const solicitudId = notification.data?.solicitud_id || notification.data?.solicitud?.id;
      url = solicitudId ? `/solicitudes/${solicitudId}` : '/solicitudes';
    } else if (notification.type === 'solicitud.completed') {
      // Resultados listos - redirigir a los resultados del doctor
      const solicitudId = notification.data?.solicitud_id || notification.data?.solicitud?.id;
      url = solicitudId ? `/doctor/solicitudes/${solicitudId}/resultados` : '/doctor/solicitudes';
    } else if (notification.data?.action_url) {
      // Si tiene action_url personalizada, usar esa
      url = notification.data.action_url;
    }
    
    // Navegar a la URL si existe
    if (url) {
      console.log('🔀 [handleNotificationClick] Navegando a:', url);
      window.location.href = url;
    }
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case 'solicitud.created':
        return <ExclamationCircleIcon className="w-5 h-5 text-blue-500" />;
      case 'solicitud.completed':
        return <CheckCircleIcon className="w-5 h-5 text-green-500" />;
      default:
        return <BellIcon className="w-5 h-5 text-gray-500" />;
    }
  };

  const getNotificationTitle = (notification) => {
    switch (notification.type) {
      case 'solicitud.created':
        return '📋 Nueva solicitud';
      case 'solicitud.completed':
        return '✅ Resultados listos';
      default:
        return '🔔 Notificación';
    }
  };

  const getNotificationMessage = (notification) => {
    const data = notification.data;

    switch (notification.type) {
      case 'solicitud.created':
        // Intentar obtener el nombre del paciente de diferentes estructuras de datos
        const patientNameCreated = data.solicitud?.paciente ?
          `${data.solicitud.paciente.nombres} ${data.solicitud.paciente.apellidos}` :
          data.data?.paciente ?
          `${data.data.paciente.nombres} ${data.data.paciente.apellidos}` :
          data.data?.message || 'un paciente';

        return `Nueva solicitud de ${patientNameCreated}`;

      case 'solicitud.completed':
        // Intentar obtener el nombre del paciente de diferentes estructuras de datos
        const patientNameCompleted = data.solicitud?.paciente ?
          `${data.solicitud.paciente.nombres} ${data.solicitud.paciente.apellidos}` :
          data.data?.paciente ?
          `${data.data.paciente.nombres} ${data.data.paciente.apellidos}` :
          data.data?.message || 'un paciente';

        return `Los resultados de ${patientNameCompleted} están listos`;

      default:
        return data.message || data.data?.message || 'Nueva notificación recibida';
    }
  };

  const formatTime = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Ahora';
    if (diffMins < 60) return `${diffMins}m`;
    if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h`;
    return date.toLocaleDateString();
  };

  // No mostrar el componente si el usuario no está autenticado
  if (!isAuthenticated || !user) {
    return null;
  }

  return (
    <div className="relative">
      {/* Botón de notificaciones */}
      <button
        onClick={() => setShowNotifications(!showNotifications)}
        className="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
      >
        <BellIcon className="w-6 h-6" />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      {/* Estado de conexión */}
      {showConnectionStatus && (
        <div className={`absolute top-12 right-0 bg-white border rounded-lg shadow-lg p-2 z-50 ${getConnectionStatusColor()}`}>
          <div className="flex items-center space-x-2 text-sm">
            {isConnected ? <WifiIcon className="w-4 h-4" /> : <SignalSlashIcon className="w-4 h-4" />}
            <span>{getConnectionStatusText()}</span>
          </div>
        </div>
      )}

      {/* Panel de notificaciones */}
      {showNotifications && (
        <div className="absolute top-12 right-0 w-80 bg-white border rounded-lg shadow-lg z-50 max-h-96 overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b">
            <h3 className="font-semibold text-gray-900">Notificaciones</h3>
            <div className="flex items-center space-x-2">
              {/* Indicador de conexión */}
              <div className={`flex items-center space-x-1 text-xs ${getConnectionStatusColor()}`}>
                {isConnected ? <WifiIcon className="w-3 h-3" /> : <SignalSlashIcon className="w-3 h-3" />}
                <span>{getConnectionStatusText()}</span>
              </div>
              
              {/* Botón cerrar */}
              <button
                onClick={() => setShowNotifications(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <XMarkIcon className="w-4 h-4" />
              </button>
            </div>
          </div>

          {/* Lista de notificaciones */}
          <div className="max-h-64 overflow-y-auto">
            {unreadNotifications.length === 0 ? (
              <div className="p-4 text-center text-gray-500">
                <BellIcon className="w-8 h-8 mx-auto mb-2 text-gray-300" />
                <p>No hay notificaciones</p>
              </div>
            ) : (
              unreadNotifications.map((notification) => (
                <div
                  key={notification.id}
                  onClick={() => handleNotificationClick(notification)}
                  className="p-3 border-b hover:bg-gray-50 cursor-pointer transition-colors bg-blue-50 border-l-4 border-l-blue-500"
                >
                  <div className="flex items-start space-x-3">
                    {getNotificationIcon(notification.type)}
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm text-gray-900">
                        {getNotificationTitle(notification)}
                      </p>
                      <p className="text-sm text-gray-600 mt-1">
                        {getNotificationMessage(notification)}
                      </p>
                      <p className="text-xs text-gray-400 mt-1">
                        {formatTime(notification.timestamp)}
                      </p>
                    </div>
                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Footer */}
          {unreadNotifications.length > 0 && (
            <div className="p-3 border-t bg-gray-50">
              <button
                onClick={() => {
                  markAllAsRead();
                  setShowNotifications(false);
                }}
                className="w-full text-sm text-gray-600 hover:text-gray-900"
              >
                Marcar todas como leídas
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default RealtimeNotifications;
