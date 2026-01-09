import React, { useState, useEffect, useRef } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { notificationAPI } from '../../services/notificationService';
import { BellIcon } from '@heroicons/react/24/outline';
import { BellIcon as BellSolidIcon } from '@heroicons/react/24/solid';
import { useAuth } from '../../contexts/AuthContext';

const NotificationBell = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [lastTimestamp, setLastTimestamp] = useState(Date.now());
  const dropdownRef = useRef(null);
  const queryClient = useQueryClient();
  const { user } = useAuth(); // Obtener información del usuario

  // Debug: Verificar autenticación
  useEffect(() => {
    const token = localStorage.getItem('token');
    console.log('🔐 [NotificationBell] Estado de autenticación:', {
      user: user ? { id: user.id, role: user.role, email: user.email } : null,
      hasToken: !!token,
      tokenLength: token ? token.length : 0
    });
  }, [user]);

  // Obtener conteo de notificaciones no leídas (reducido el polling)
  const { data: unreadData } = useQuery({
    queryKey: ['unreadNotifications'],
    queryFn: notificationAPI.getUnreadCount,
    refetchInterval: 60000, // Refrescar cada 60 segundos (reducido de 30s)
    refetchOnWindowFocus: true,
    staleTime: 30000, // Considerar datos frescos por 30 segundos
  });

  // Obtener notificaciones recientes (solo cuando sea necesario)
  const { data: recentData } = useQuery({
    queryKey: ['recentNotifications', lastTimestamp],
    queryFn: () => notificationAPI.getRecentNotifications(lastTimestamp),
    refetchInterval: false, // Deshabilitado - WebSocket se encarga de esto
    enabled: false, // Solo ejecutar manualmente
    onSuccess: (data) => {
      if (data.timestamp > lastTimestamp) {
        setLastTimestamp(data.timestamp);
        queryClient.invalidateQueries(['unreadNotifications']);
      }
    }
  });

  // Obtener todas las notificaciones cuando se abre el dropdown
  const { data: notificationsData, isLoading, error } = useQuery({
    queryKey: ['allNotifications'],
    queryFn: () => {
      console.log('🔍 [NotificationBell] Ejecutando query para obtener notificaciones...');
      return notificationAPI.getNotifications({ per_page: 20 });
    },
    enabled: isOpen,
    refetchOnWindowFocus: false,
    onSuccess: (data) => {
      console.log('✅ [NotificationBell] Query exitosa, datos recibidos:', {
        dataKeys: Object.keys(data || {}),
        total: data?.total,
        dataLength: data?.data?.length,
        firstNotification: data?.data?.[0]
      });
    },
    onError: (error) => {
      console.error('❌ [NotificationBell] Error en query:', {
        message: error.message,
        status: error.response?.status,
        data: error.response?.data
      });
    }
  });

  const unreadCount = unreadData?.unread_count || 0;
  const allNotifications = notificationsData?.data || [];

  // Debug: Verificar estructura de datos
  console.log('🔍 [NotificationBell] Estructura de datos:', {
    notificationsData: notificationsData ? Object.keys(notificationsData) : null,
    dataPath1: notificationsData?.data ? 'notificationsData.data existe' : 'notificationsData.data NO existe',
    dataPath2: notificationsData?.data?.data ? 'notificationsData.data.data existe' : 'notificationsData.data.data NO existe',
    allNotificationsLength: allNotifications.length,
    firstNotification: allNotifications[0] ? {
      id: allNotifications[0].id,
      type: allNotifications[0].type
    } : null
  });

  // ✅ FILTRAR NOTIFICACIONES POR ROL DEL USUARIO
  const notifications = allNotifications.filter(notification => {
    const notificationType = notification.type;

    console.log(`🔍 [NotificationBell] Evaluando notificación:`, {
      id: notification.id,
      type: notificationType,
      userRole: user?.role,
      shouldShow: user?.role === 'laboratorio' ? notificationType === 'solicitud.created' :
                  user?.role === 'doctor' ? notificationType === 'solicitud.completed' : true,
      notificationData: notification.data
    });

    switch (user?.role) {
      case 'laboratorio':
        // Laboratorio solo ve notificaciones de solicitudes creadas
        return notificationType === 'solicitud.created';
      case 'doctor':
        // Doctores solo ven notificaciones de resultados completados
        return notificationType === 'solicitud.completed';
      default:
        // Otros roles ven todas las notificaciones
        return true;
    }
  });

  // Log para debugging
  console.log(`🔔 [NotificationBell] Usuario: ${user?.role}, Total: ${allNotifications.length}, Filtradas: ${notifications.length}`);
  console.log(`📋 [NotificationBell] Notificaciones filtradas:`, notifications.map(n => ({ id: n.id, type: n.type, message: n.data?.message })));
  console.log(`📊 [NotificationBell] Todas las notificaciones recibidas:`, allNotifications.map(n => ({ id: n.id, type: n.type, message: n.data?.message })));

  // Cerrar dropdown al hacer clic fuera
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  const handleMarkAsRead = async (uuid) => {
    try {
      await notificationAPI.markAsRead(uuid);
      queryClient.invalidateQueries(['unreadNotifications']);
      queryClient.invalidateQueries(['allNotifications']);
    } catch (error) {
      console.error('Error al marcar notificación como leída:', error);
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await notificationAPI.markAllAsRead();
      queryClient.invalidateQueries(['unreadNotifications']);
      queryClient.invalidateQueries(['allNotifications']);
    } catch (error) {
      console.error('Error al marcar todas las notificaciones como leídas:', error);
    }
  };

  const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'Hace un momento';
    if (diffInSeconds < 3600) return `Hace ${Math.floor(diffInSeconds / 60)} min`;
    if (diffInSeconds < 86400) return `Hace ${Math.floor(diffInSeconds / 3600)} h`;
    return `Hace ${Math.floor(diffInSeconds / 86400)} días`;
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case 'solicitud.created':
        return '🆕';
      case 'solicitud.updated':
        return '🔄';
      case 'solicitud.completed':
        return '✅';
      default:
        return '📋';
    }
  };

  const getNotificationColor = (data) => {
    if (!data.read_at) return 'bg-blue-50 border-l-4 border-l-blue-500';
    return 'bg-gray-50';
  };

  return (
    <div className="relative" ref={dropdownRef}>
      {/* Bell Icon */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg"
      >
        {unreadCount > 0 ? (
          <BellSolidIcon className="h-6 w-6 text-blue-600" />
        ) : (
          <BellIcon className="h-6 w-6" />
        )}
        
        {/* Badge de conteo */}
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-96 overflow-hidden">
          {/* Header */}
          <div className="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
            <h3 className="text-lg font-semibold text-gray-900">Notificaciones</h3>
            {unreadCount > 0 && (
              <button
                onClick={handleMarkAllAsRead}
                className="text-sm text-blue-600 hover:text-blue-800"
              >
                Marcar todas como leídas
              </button>
            )}
          </div>

          {/* Content */}
          <div className="max-h-80 overflow-y-auto">
            {isLoading ? (
              <div className="p-4 text-center text-gray-500">
                Cargando notificaciones...
              </div>
            ) : notifications.length === 0 ? (
              <div className="p-4 text-center text-gray-500">
                No tienes notificaciones
              </div>
            ) : (
              notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer ${getNotificationColor(notification)}`}
                  onClick={() => {
                    if (!notification.read_at) {
                      handleMarkAsRead(notification.id);
                    }
                    // Navegar a la URL si existe
                    if (notification.data?.action_url) {
                      window.location.href = notification.data.action_url;
                    }
                  }}
                >
                  <div className="flex items-start space-x-3">
                    <div className="text-2xl">
                      {getNotificationIcon(notification.type)}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-gray-900">
                        {notification.data?.title || 'Notificación'}
                      </p>
                      <p className="text-sm text-gray-600 mt-1">
                        {notification.data?.message || 'Sin mensaje'}
                      </p>
                      <p className="text-xs text-gray-400 mt-2">
                        {formatTimeAgo(notification.created_at)}
                      </p>
                    </div>
                    {!notification.read_at && (
                      <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Footer */}
          {notifications.length > 0 && (
            <div className="px-4 py-3 border-t border-gray-200 text-center">
              <button
                onClick={() => {
                  setIsOpen(false);
                  // Navegar a página de notificaciones si existe
                  // window.location.href = '/notificaciones';
                }}
                className="text-sm text-blue-600 hover:text-blue-800"
              >
                Ver todas las notificaciones
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default NotificationBell;
