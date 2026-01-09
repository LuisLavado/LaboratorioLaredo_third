import React, { useState, useEffect } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { 
  BellIcon, 
  CheckCircleIcon, 
  ExclamationCircleIcon, 
  XMarkIcon,
  WifiIcon,
  SignalSlashIcon
} from '@heroicons/react/24/outline';
import socketIOService from '../../services/socketIOService';

const SimpleNotifications = () => {
  const { user, isAuthenticated } = useAuth();
  const [isConnected, setIsConnected] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState('disconnected');
  const [notifications, setNotifications] = useState([]);
  const [showNotifications, setShowNotifications] = useState(false);

  // Función para conectar WebSocket
  const connectWebSocket = () => {
    if (!user || !isAuthenticated) {
      console.log('[SimpleNotifications] No hay usuario autenticado');
      return;
    }

    try {
      setConnectionStatus('connecting');
      
      const socket = socketIOService.connect({
        id: user.id,
        token: localStorage.getItem('token'),
        role: user.role,
        name: user.name || user.nombres
      });
      
      // Configurar callbacks
      socketIOService.on('onConnect', () => {
        setIsConnected(true);
        setConnectionStatus('connected');
        console.log('[SimpleNotifications] Conectado exitosamente');
      });

      socketIOService.on('onDisconnect', (reason) => {
        setIsConnected(false);
        setConnectionStatus('disconnected');
        console.log('[SimpleNotifications] Desconectado:', reason);
      });

      socketIOService.on('onError', (error) => {
        setConnectionStatus('error');
        console.error('[SimpleNotifications] Error:', error);
      });

      socketIOService.on('onNotification', (type, data) => {
        console.log('🔔 [SimpleNotifications] Notificación recibida:', type, data);
        console.log('👤 [SimpleNotifications] Usuario actual:', user?.role, user?.id);

        const notification = {
          id: Date.now(),
          type,
          data,
          timestamp: new Date().toISOString(),
          read: false
        };

        console.log('📝 [SimpleNotifications] Agregando notificación:', notification);
        setNotifications(prev => {
          const newNotifications = [notification, ...prev.slice(0, 9)];
          console.log('📋 [SimpleNotifications] Total notificaciones:', newNotifications.length);
          return newNotifications;
        });
      });

    } catch (error) {
      setConnectionStatus('error');
      console.error('[SimpleNotifications] Error al conectar:', error);
    }
  };

  // Auto-conectar cuando el usuario esté autenticado
  useEffect(() => {
    if (isAuthenticated && user) {
      connectWebSocket();
    }

    return () => {
      socketIOService.disconnect();
      setIsConnected(false);
      setConnectionStatus('disconnected');
    };
  }, [isAuthenticated, user]);

  // No mostrar si no está autenticado
  if (!isAuthenticated || !user) {
    return null;
  }

  const unreadNotifications = notifications.filter(n => !n.read);
  const unreadCount = unreadNotifications.length;

  // Debug: mostrar estado de notificaciones
  console.log('📊 [SimpleNotifications] Estado actual:', {
    totalNotifications: notifications.length,
    unreadCount,
    userRole: user?.role,
    isConnected,
    connectionStatus
  });

  const getStatusColor = () => {
    switch (connectionStatus) {
      case 'connected': return 'text-green-600';
      case 'connecting': return 'text-yellow-600';
      case 'error': return 'text-red-600';
      default: return 'text-gray-600';
    }
  };

  const getStatusText = () => {
    switch (connectionStatus) {
      case 'connected': return 'Conectado';
      case 'connecting': return 'Conectando...';
      case 'error': return 'Error';
      default: return 'Desconectado';
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
        return data.solicitud ? 
          `Nueva solicitud de ${data.solicitud.nombres} ${data.solicitud.apellidos}` :
          'Se ha creado una nueva solicitud';
      case 'solicitud.completed':
        return data.solicitud ? 
          `Los resultados de ${data.solicitud.nombres} ${data.solicitud.apellidos} están listos` :
          'Los resultados de una solicitud están listos';
      default:
        return data.message || 'Nueva notificación recibida';
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

  const markAsRead = (notificationId) => {
    setNotifications(prev => 
      prev.map(notif => 
        notif.id === notificationId 
          ? { ...notif, read: true }
          : notif
      )
    );
  };

  const clearAll = () => {
    setNotifications([]);
  };

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

      {/* Panel de notificaciones */}
      {showNotifications && (
        <div className="absolute top-12 right-0 w-80 bg-white border rounded-lg shadow-lg z-50 max-h-96 overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b">
            <h3 className="font-semibold text-gray-900">Notificaciones</h3>
            <div className="flex items-center space-x-2">
              {/* Indicador de conexión */}
              <div className={`flex items-center space-x-1 text-xs ${getStatusColor()}`}>
                {isConnected ? <WifiIcon className="w-3 h-3" /> : <SignalSlashIcon className="w-3 h-3" />}
                <span>{getStatusText()}</span>
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
            {notifications.length === 0 ? (
              <div className="p-4 text-center text-gray-500">
                <BellIcon className="w-8 h-8 mx-auto mb-2 text-gray-300" />
                <p>No hay notificaciones</p>
              </div>
            ) : (
              notifications.map((notification) => (
                <div
                  key={notification.id}
                  onClick={() => markAsRead(notification.id)}
                  className={`p-3 border-b hover:bg-gray-50 cursor-pointer transition-colors ${
                    !notification.read ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''
                  }`}
                >
                  <div className="flex items-start space-x-3">
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
                    {!notification.read && (
                      <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Footer */}
          {notifications.length > 0 && (
            <div className="p-3 border-t bg-gray-50 flex justify-between">
              <button
                onClick={clearAll}
                className="text-sm text-gray-600 hover:text-gray-900"
              >
                Limpiar todas
              </button>
              <button
                onClick={connectWebSocket}
                className="text-sm text-blue-600 hover:text-blue-900"
              >
                Reconectar
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default SimpleNotifications;
