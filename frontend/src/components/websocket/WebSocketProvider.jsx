import React, { createContext, useContext, useEffect, useState, useCallback, useRef } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { useQueryClient } from '@tanstack/react-query';
import { reverbService, notificationsAPI } from '../../services/api';
import {
  showNewRequestNotification,
  showResultsReadyNotification,
  showLabNotification,
  showDoctorNotification,
  checkNotificationPermission
} from '../../services/notificationService';

// Crear contexto para WebSocket
const WebSocketContext = createContext();

// Hook para usar el contexto de WebSocket
export const useWebSocketContext = () => {
  const context = useContext(WebSocketContext);
  if (!context) {
    throw new Error('useWebSocketContext debe ser usado dentro de WebSocketProvider');
  }
  return context;
};

// Proveedor de WebSocket
export const WebSocketProvider = ({ children }) => {
  const { user, isAuthenticated } = useAuth();
  const queryClient = useQueryClient();

  // Estado del WebSocket
  const [isConnected, setIsConnected] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState('disconnected');
  const [notifications, setNotifications] = useState([]);
  const [lastMessage, setLastMessage] = useState(null);

  // Función para marcar notificaciones relacionadas como leídas
  const markRelatedNotificationsAsRead = useCallback(async (solicitudId, notificationType) => {
    try {
      // Cargar todas las notificaciones de BD
      const response = await notificationsAPI.getAll();
      const notificationsData = response?.data?.notifications || response?.data || [];
      
      const relatedNotifications = Array.isArray(notificationsData)
        ? notificationsData.filter(n => {
            const nSolicitudId = n.data?.solicitud_id || n.data?.solicitud?.id;
            return n.type === notificationType && nSolicitudId === solicitudId && !n.read;
          })
        : [];
      
      // Marcar cada una como leída
      for (const notif of relatedNotifications) {
        await notificationsAPI.markAsRead(notif.id);
      }
      
    } catch (error) {
      console.error('[WebSocketProvider] Error marcando notificaciones relacionadas:', error);
    }
  }, []);

  // Función para manejar notificaciones recibidas
  const handleNotification = useCallback((type, data) => {
    // Marcar como persistente si es un tipo que se guarda en BD
    const isPersistent = ['solicitud.created', 'solicitud.completed'].includes(type);

    const notification = {
      id: Date.now(),
      type,
      data,
      timestamp: new Date().toISOString(),
      read: false,
      persistent: isPersistent // Marcar si se guardó en BD
    };

    // Si es una notificación de solicitud completada, marcar como leídas
    // todas las notificaciones de "solicitud.created" de esa misma solicitud
    if (type === 'solicitud.completed') {
      const solicitudId = data.solicitud_id || data.solicitud?.id;
      
      setNotifications(prev => 
        prev.map(n => {
          // Si es una notificación de solicitud.created de la misma solicitud
          const nSolicitudId = n.data?.solicitud_id || n.data?.solicitud?.id;
          if (n.type === 'solicitud.created' && nSolicitudId === solicitudId) {
            return { ...n, read: true, read_at: new Date().toISOString(), hidden: true };
          }
          return n;
        })
      );
      
      // También marcar en BD si existe
      markRelatedNotificationsAsRead(solicitudId, 'solicitud.created');
    }

    // Agregar a la lista de notificaciones
    setNotifications(prev => [notification, ...prev.slice(0, 49)]); // Mantener solo las últimas 50
    setLastMessage(notification);

    // Invalidar queries relevantes según el tipo de notificación
    switch (type) {
      case 'solicitud.created':
        // Nueva solicitud - actualizar dashboard y lista de solicitudes
        queryClient.invalidateQueries(['requests']);
        queryClient.invalidateQueries(['pending-requests']);
        queryClient.invalidateQueries(['dashboard-stats']);
        queryClient.invalidateQueries(['dashboard-pending']);
        queryClient.invalidateQueries(['dashboard-activity']);
        
        // Despachar evento personalizado para que el dashboard se actualice
        window.dispatchEvent(new CustomEvent('solicitud-created', { 
          detail: notification.data 
        }));
        break;

      case 'solicitud.completed':
        // Solicitud completada - actualizar solicitudes del doctor
        queryClient.invalidateQueries(['doctor-requests']);
        queryClient.invalidateQueries(['requests']);
        queryClient.invalidateQueries(['dashboard-stats']);
        queryClient.invalidateQueries(['dashboard-pending']);
        queryClient.invalidateQueries(['dashboard-activity']);
        
        // Despachar evento personalizado
        window.dispatchEvent(new CustomEvent('solicitud-completed', { 
          detail: notification.data 
        }));
        break;

      case 'solicitud.updated':
        // Solicitud actualizada - actualizar todas las vistas
        queryClient.invalidateQueries(['requests']);
        queryClient.invalidateQueries(['doctor-requests']);
        queryClient.invalidateQueries(['pending-requests']);
        queryClient.invalidateQueries(['dashboard-stats']);
        queryClient.invalidateQueries(['dashboard-pending']);
        queryClient.invalidateQueries(['dashboard-activity']);
        
        // Despachar evento personalizado
        window.dispatchEvent(new CustomEvent('solicitud-updated', { 
          detail: notification.data 
        }));
        break;

      default:
        // Para otros tipos, invalidar queries generales
        queryClient.invalidateQueries(['requests']);
        queryClient.invalidateQueries(['dashboard']);
    }
  }, [queryClient, markRelatedNotificationsAsRead]);

    // Referencias para los callbacks de listeners (para poder desuscribirse)
  const listenersRef = useRef({
    notification: null,
    result_ready: null,
    new_request: null
  });

  // Función para conectar
  const connect = useCallback(async () => {
    try {
      setConnectionStatus('connecting');

      // Conectar a Reverb según el role del usuario
      reverbService.connect(user.role);

      setIsConnected(true);
      setConnectionStatus('connected');

      // Cargar notificaciones persistentes desde la base de datos
      try {
        const response = await notificationsAPI.getAll();
        
        // Verificar si hay notificaciones en la respuesta
        const notificationsData = response?.data?.notifications || response?.data || [];
        
        // Convertir las notificaciones de la BD al formato del frontend
        // SOLO cargar las que NO están leídas
        const persistentNotifications = Array.isArray(notificationsData) 
          ? notificationsData
              .filter(notif => !notif.read) // Filtrar solo no leídas
              .map(notif => ({
                id: notif.id,
                type: notif.type,
                data: notif.data,
                timestamp: notif.created_at,
                read: notif.read,
                read_at: notif.read_at,
                persistent: true // Marcar como persistente
              }))
          : [];

        setNotifications(persistentNotifications);
      } catch (error) {
        console.error('[WebSocketProvider] Error cargando notificaciones persistentes:', error);
      }

      // Desuscribirse de listeners anteriores si existen
      if (listenersRef.current.notification) {
        reverbService.unsubscribe('notification', listenersRef.current.notification);
      }
      if (listenersRef.current.result_ready) {
        reverbService.unsubscribe('result_ready', listenersRef.current.result_ready);
      }
      if (listenersRef.current.new_request) {
        reverbService.unsubscribe('new_request', listenersRef.current.new_request);
      }

      // Crear y guardar referencias a los nuevos callbacks
      listenersRef.current.notification = (data) => {
        handleNotification(data.type || 'notification', data);
      };

      // Suscribirse a notificaciones generales
      reverbService.subscribe('notification', listenersRef.current.notification);

      // Suscribirse a eventos específicos según el role
      if (user.role === 'doctor') {
        listenersRef.current.result_ready = (data) => {
          handleNotification('solicitud.completed', data);
        };
        reverbService.subscribe('result_ready', listenersRef.current.result_ready);
      } else if (user.role === 'laboratorio' || user.role === 'admin') {
        listenersRef.current.new_request = (data) => {
          handleNotification('solicitud.created', data);
        };
        reverbService.subscribe('new_request', listenersRef.current.new_request);
      }

    } catch (error) {
      setConnectionStatus('error');
      console.error('[WebSocketProvider] Error al conectar:', error);
    }
  }, [user, isAuthenticated, handleNotification]);

  // Función para desconectar
  const disconnect = useCallback(() => {
    reverbService.disconnect();
    setIsConnected(false);
    setConnectionStatus('disconnected');
  }, []);

  // Función para marcar notificación como leída
  const markNotificationAsRead = useCallback(async (notificationId) => {
    try {
      // Encontrar la notificación
      const notification = notifications.find(n => n.id === notificationId);
      
      // Actualizar localmente primero (para respuesta inmediata en UI)
      setNotifications(prev =>
        prev.map(n => n.id === notificationId ? { ...n, read: true, read_at: new Date().toISOString() } : n)
      );
      
      // Si es persistente, actualizar en BD
      if (notification && notification.persistent) {
        // Si la notificación tiene un UUID (fue cargada desde BD), usarlo directamente
        if (typeof notificationId === 'string' && notificationId.includes('-')) {
          try {
            await notificationsAPI.markAsRead(notificationId);
            
            // Recargar notificaciones desde BD para asegurar sincronización
            try {
              const response = await notificationsAPI.getAll();
              const freshNotifications = response.data.notifications
                .filter(notif => !notif.read)
                .map(notif => ({
                  id: notif.id,
                  type: notif.type,
                  data: notif.data,
                  timestamp: notif.created_at,
                  read: notif.read,
                  read_at: notif.read_at,
                  persistent: true
                }));
              
              setNotifications(freshNotifications);
            } catch (reloadError) {
              console.error('⚠️ [markNotificationAsRead] Error al recargar notificaciones:', reloadError);
            }
            
          } catch (error) {
            console.error('❌ [markNotificationAsRead] Error al marcar en BD:', error.response?.data || error.message);
          }
        } else {
          // Si es una notificación temporal con ID numérico, buscar en BD por solicitud_id
          const solicitudId = notification.data?.solicitud_id || notification.data?.solicitud?.id;
          
          if (solicitudId) {
            try {
              // Cargar notificaciones de BD y buscar la que coincida
              const response = await notificationsAPI.getAll();
              
              const dbNotification = response.data.notifications.find(n => {
                const dbSolicitudId = n.data?.solicitud_id || n.data?.solicitud?.id;
                const typeMatches = n.type === notification.type;
                const idMatches = dbSolicitudId === solicitudId;
                const isUnread = !n.read;
                
                return typeMatches && idMatches && isUnread;
              });
              
              if (dbNotification) {
                await notificationsAPI.markAsRead(dbNotification.id);
                
                // Recargar notificaciones desde BD para asegurar sincronización
                try {
                  const freshResponse = await notificationsAPI.getAll();
                  const freshNotifications = freshResponse.data.notifications
                    .filter(notif => !notif.read)
                    .map(notif => ({
                      id: notif.id,
                      type: notif.type,
                      data: notif.data,
                      timestamp: notif.created_at,
                      read: notif.read,
                      read_at: notif.read_at,
                      persistent: true
                    }));
                  
                  setNotifications(freshNotifications);
                } catch (reloadError) {
                  console.error('⚠️ [markNotificationAsRead] Error al recargar notificaciones:', reloadError);
                }
                
              } else {
                console.warn(`⚠️ [markNotificationAsRead] No se encontró notificación en BD para solicitud_id: ${solicitudId}`);
              }
            } catch (error) {
              console.error('❌ [markNotificationAsRead] Error al buscar/marcar en BD:', error.response?.data || error.message);
            }
          }
        }
      }
      
    } catch (error) {
      console.error('❌ [markNotificationAsRead] Error general:', error);
      console.error('Error details:', {
        message: error.message,
        response: error.response?.data,
        status: error.response?.status
      });
    }
  }, [notifications]);

  // Función para marcar todas las notificaciones como leídas
  const markAllAsRead = useCallback(async () => {
    try {
      await notificationsAPI.markAllAsRead();
      
      setNotifications(prev =>
        prev.map(n => ({ ...n, read: true, read_at: new Date().toISOString() }))
      );
    } catch (error) {
      console.error('[WebSocketProvider] Error marcando todas como leídas:', error);
    }
  }, []);

  // Función para limpiar notificaciones
  const clearNotifications = useCallback(() => {
    setNotifications([]);
  }, []);

  // Auto-conectar cuando el usuario se autentique
  useEffect(() => {
    if (isAuthenticated && user) {
      // Solicitar permisos de notificación
      checkNotificationPermission();
      // Conectar WebSocket SIEMPRE (ya hay SSL en el servidor)
      connect();
    } else if (!isAuthenticated) {
      // Solo desconectar si definitivamente NO está autenticado
      disconnect();
    }

    // Cleanup al desmontar
    // En desarrollo (React Strict Mode) no desconectar para evitar perder la conexión
    // Solo desconectar en producción o cuando el usuario cierre sesión
    return () => {
      // No desconectar en cleanup porque React Strict Mode desmonta/monta componentes
      // La desconexión se manejará solo cuando el usuario cierre sesión explícitamente
    };
  }, [isAuthenticated, user, connect, disconnect]);

  // Manejar notificaciones específicas según el rol del usuario
  useEffect(() => {
    if (!lastMessage || !user) return;

    const { type, data } = lastMessage;

    switch (type) {
      case 'solicitud.created':
        // Solo mostrar al laboratorio
        if (user.role === 'laboratorio' || user.role === 'admin') {
          const patientName = data.solicitud?.paciente ?
            `${data.solicitud.paciente.nombres} ${data.solicitud.paciente.apellidos}` :
            'un paciente';

          showNewRequestNotification(patientName, data.solicitud_id);
          showLabNotification(
            '📋 Nueva solicitud',
            `Se ha creado una nueva solicitud de ${patientName}`,
            data.solicitud_id
          );
        }
        break;

      case 'solicitud.completed':
        // Solo mostrar al doctor que creó la solicitud
        if (user.role === 'doctor' && data.data?.user_id === user.id) {
          const patientName = data.data?.paciente ?
            `${data.data.paciente.nombres} ${data.data.paciente.apellidos}` :
            'un paciente';

          showResultsReadyNotification(patientName, data.solicitud_id);
          showDoctorNotification(
            '✅ Resultados disponibles',
            `Los resultados de ${patientName} están listos para revisar`,
            data.solicitud_id
          );
        }
        break;

      case 'solicitud.updated':
        // Mostrar a ambos roles según corresponda
        if (data.data?.paciente) {
          const patientName = `${data.data.paciente.nombres} ${data.data.paciente.apellidos}`;

          if (user.role === 'laboratorio' || user.role === 'admin') {
            showLabNotification(
              '🔄 Solicitud actualizada',
              `La solicitud de ${patientName} ha sido actualizada`,
              data.solicitud_id
            );
          } else if (user.role === 'doctor' && data.data?.user_id === user.id) {
            showDoctorNotification(
              '🔄 Solicitud actualizada',
              `Tu solicitud de ${patientName} ha sido actualizada`,
              data.solicitud_id
            );
          }
        }
        break;

      default:
        // Tipo de notificación no manejado
        break;
    }
  }, [lastMessage, user]);

  // Calcular notificaciones no leídas y no ocultas
  const unreadNotifications = notifications.filter(n => !n.read && !n.hidden);
  const unreadCount = unreadNotifications.length;

  // Valor del contexto
  const contextValue = {
    // Estado de conexión
    isConnected,
    connectionStatus,

    // Notificaciones
    notifications,
    unreadNotifications,
    unreadCount,
    lastMessage,

    // Funciones
    connect,
    disconnect,
    markNotificationAsRead,
    markAllAsRead,
    clearNotifications,

    // Información del usuario
    connectedUser: user,

    // Estado de autenticación
    isAuthenticated
  };

  return (
    <WebSocketContext.Provider value={contextValue}>
      {children}
    </WebSocketContext.Provider>
  );
};

export default WebSocketProvider;
