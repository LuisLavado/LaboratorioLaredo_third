import { useState, useEffect, useCallback } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { notificationAPI, showSystemNotification } from '../services/notificationService';

/**
 * Hook personalizado para manejar notificaciones en tiempo real
 */
export const useNotifications = (options = {}) => {
  const {
    enableSystemNotifications = true,
    pollingInterval = 10000, // 10 segundos
    enableSound = true,
  } = options;

  const [lastTimestamp, setLastTimestamp] = useState(Date.now());
  const [hasNewNotifications, setHasNewNotifications] = useState(false);
  const queryClient = useQueryClient();

  // Obtener conteo de notificaciones no leídas
  const {
    data: unreadData,
    isLoading: unreadLoading,
    error: unreadError
  } = useQuery(
    'unreadNotifications',
    notificationAPI.getUnreadCount,
    {
      refetchInterval: 30000, // Refrescar cada 30 segundos
      refetchOnWindowFocus: true,
      staleTime: 10000, // Considerar datos frescos por 10 segundos
    }
  );

  // Obtener notificaciones recientes para detectar nuevas
  const {
    data: recentData,
    isLoading: recentLoading
  } = useQuery(
    ['recentNotifications', lastTimestamp],
    () => notificationAPI.getRecentNotifications(lastTimestamp),
    {
      refetchInterval: pollingInterval,
      refetchOnWindowFocus: true,
      onSuccess: (data) => {
        if (data.timestamp > lastTimestamp) {
          const newNotifications = data.data || [];
          
          // Si hay nuevas notificaciones, procesarlas
          if (newNotifications.length > 0) {
            setHasNewNotifications(true);
            
            // Mostrar notificaciones del sistema para cada nueva notificación
            if (enableSystemNotifications) {
              newNotifications.forEach(notification => {
                showSystemNotification(
                  notification.data?.title || 'Nueva notificación',
                  {
                    body: notification.data?.message || 'Tienes una nueva notificación',
                    icon: '/favicon.ico',
                    data: {
                      url: notification.data?.action_url
                    }
                  }
                );
              });
            }
          }
          
          setLastTimestamp(data.timestamp);
          
          // Invalidar queries relacionadas para actualizar la UI
          queryClient.invalidateQueries('unreadNotifications');
          queryClient.invalidateQueries('allNotifications');
        }
      },
      onError: (error) => {
        console.error('Error al obtener notificaciones recientes:', error);
      }
    }
  );

  // Marcar una notificación como leída
  const markAsRead = useCallback(async (notificationId) => {
    try {
      await notificationAPI.markAsRead(notificationId);
      queryClient.invalidateQueries('unreadNotifications');
      queryClient.invalidateQueries('allNotifications');
      return true;
    } catch (error) {
      console.error('Error al marcar notificación como leída:', error);
      return false;
    }
  }, [queryClient]);

  // Marcar todas las notificaciones como leídas
  const markAllAsRead = useCallback(async () => {
    try {
      await notificationAPI.markAllAsRead();
      queryClient.invalidateQueries('unreadNotifications');
      queryClient.invalidateQueries('allNotifications');
      setHasNewNotifications(false);
      return true;
    } catch (error) {
      console.error('Error al marcar todas las notificaciones como leídas:', error);
      return false;
    }
  }, [queryClient]);

  // Eliminar una notificación
  const deleteNotification = useCallback(async (notificationId) => {
    try {
      await notificationAPI.deleteNotification(notificationId);
      queryClient.invalidateQueries('unreadNotifications');
      queryClient.invalidateQueries('allNotifications');
      return true;
    } catch (error) {
      console.error('Error al eliminar notificación:', error);
      return false;
    }
  }, [queryClient]);

  // Limpiar el estado de nuevas notificaciones
  const clearNewNotifications = useCallback(() => {
    setHasNewNotifications(false);
  }, []);

  // Forzar actualización de notificaciones
  const refreshNotifications = useCallback(() => {
    queryClient.invalidateQueries('unreadNotifications');
    queryClient.invalidateQueries('allNotifications');
    queryClient.invalidateQueries('recentNotifications');
  }, [queryClient]);

  // Obtener todas las notificaciones
  const getAllNotifications = useCallback(async (params = {}) => {
    try {
      const response = await notificationAPI.getNotifications(params);
      return response;
    } catch (error) {
      console.error('Error al obtener todas las notificaciones:', error);
      throw error;
    }
  }, []);

  // Efecto para limpiar el estado cuando el componente se desmonta
  useEffect(() => {
    return () => {
      setHasNewNotifications(false);
    };
  }, []);

  return {
    // Datos
    unreadCount: unreadData?.unread_count || 0,
    recentNotifications: recentData?.data || [],
    hasNewNotifications,
    
    // Estados de carga
    isLoading: unreadLoading || recentLoading,
    unreadLoading,
    recentLoading,
    
    // Errores
    error: unreadError,
    
    // Funciones
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearNewNotifications,
    refreshNotifications,
    getAllNotifications,
    
    // Metadatos
    lastTimestamp,
  };
};

export default useNotifications;
