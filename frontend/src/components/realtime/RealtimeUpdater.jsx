import { useEffect, useState, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useAuth } from '../../contexts/AuthContext';
import toast from 'react-hot-toast';
import NotificationToast from '../notifications/NotificationToast';
import api from '../../services/api';
import { playNotificationSound, showSystemNotification } from '../../services/notificationService';

// Intervalo de verificación en milisegundos
const CHECK_INTERVAL = 300000; // 5 minutos - DESACTIVADO TEMPORALMENTE

// Función para obtener la configuración de notificaciones
const getNotificationSettings = () => {
  const settings = localStorage.getItem('notification_settings');
  if (settings) {
    try {
      const parsedSettings = JSON.parse(settings);
      return parsedSettings;
    } catch (error) {
      console.error('Error al parsear la configuración de notificaciones:', error);
    }
  }

  // Valores por defecto
  const defaultSettings = {
    enabled: true,
    sound: true,
    systemNotifications: false
  };

  // Guardar los valores por defecto en localStorage
  localStorage.setItem('notification_settings', JSON.stringify(defaultSettings));

  return defaultSettings;
};

// Componente para manejar actualizaciones en tiempo real
export default function RealtimeUpdater() {
  const queryClient = useQueryClient();
  const { user } = useAuth();

  // Referencias para los intervalos
  const intervalRef = useRef(null);

  // Estado para el seguimiento de solicitudes
  const [lastSolicitudes, setLastSolicitudes] = useState([]);
  const [initialized, setInitialized] = useState(false);

  // Estado para controlar las notificaciones mostradas
  const [lastNotificationTime, setLastNotificationTime] = useState(0);

  // Referencia para almacenar IDs de notificaciones ya mostradas (para doctor)
  // Usamos sessionStorage para persistir entre recargas de componentes
  const notifiedIdsRef = useRef(new Set());

  // Referencia para almacenar timestamps de notificaciones del laboratorio
  // { requestId: timestamp }
  const lastLabNotificationsRef = useRef({});

  // Cargar IDs notificados desde sessionStorage al iniciar
  useEffect(() => {
    try {
      const storedIds = sessionStorage.getItem('notified_request_ids');
      if (storedIds) {
        notifiedIdsRef.current = new Set(JSON.parse(storedIds));
      }
    } catch (error) {
      console.error('Error al cargar IDs notificados:', error);
    }
  }, []);

  // Función para notificar al usuario - con sistema completamente nuevo para laboratorio
  const notifyUser = async (title, message, link, id, isLabNotification = false) => {
    // Sistema completamente diferente para laboratorio y doctor
    if (isLabNotification) {
      // SISTEMA PARA LABORATORIO
      // Usar el ID de la solicitud como identificador único
      const requestId = id;

      // Crear un ID único para esta notificación
      const notificationUniqueId = `lab-${requestId}`;

      // Verificar si esta solicitud ya ha sido notificada en los últimos 60 segundos
      const now = Date.now();
      const lastNotificationForThisRequest = lastLabNotificationsRef.current[requestId] || 0;
      const timeSinceLastNotification = now - lastNotificationForThisRequest;

      // Si ha pasado menos de 60 segundos desde la última notificación para esta solicitud, ignorar
      if (timeSinceLastNotification < 60000) {
        return false;
      }

      // Registrar esta notificación
      lastLabNotificationsRef.current[requestId] = now;

      // Limpiar notificaciones antiguas (más de 5 minutos)
      Object.keys(lastLabNotificationsRef.current).forEach(key => {
        if (now - lastLabNotificationsRef.current[key] > 300000) {
          delete lastLabNotificationsRef.current[key];
        }
      });
    } else {
      // SISTEMA PARA DOCTOR
      // Crear un ID único para esta notificación específica
      const notificationUniqueId = `doc-${id}-${title.replace(/\s+/g, '-')}`;

      // Verificar si esta notificación ya se ha mostrado
      if (notifiedIdsRef.current.has(notificationUniqueId)) {
        return false;
      }

      // Agregar a la lista de notificaciones mostradas
      notifiedIdsRef.current.add(notificationUniqueId);
    }

    // Agregar a la lista de notificaciones mostradas (solo para doctor)
    // Para laboratorio ya se registró en lastLabNotificationsRef
    if (!isLabNotification) {
      notifiedIdsRef.current.add(notificationUniqueId);
    }

    // Guardar en sessionStorage para persistir entre recargas
    try {
      sessionStorage.setItem('notified_request_ids',
        JSON.stringify(Array.from(notifiedIdsRef.current)));
    } catch (error) {
      console.error('Error al guardar IDs notificados:', error);
    }

    // Mostrar notificación en la aplicación
    toast.custom((t) => (
      <NotificationToast
        title={title}
        message={message}
        link={link}
        onClose={() => toast.dismiss(t.id)}
      />
    ), {
      id: `${notificationUniqueId}-${Date.now()}`, // ID único con timestamp para evitar colisiones
      duration: 10000, // 10 segundos
      position: 'top-right',
    });

    // Reproducir sonido con enfoque simplificado y directo
    if (isLabNotification) {
      // Para laboratorio: enfoque más agresivo para garantizar que suene
      // Método 1: Reproducción directa
      try {
        const audio = new Audio('/notification.mp3');
        audio.volume = 1.0;

        // Intentar reproducir inmediatamente
        audio.play().catch(err => {
          console.error('Error en método 1:', err);

          // Método 2: Intentar con un elemento de audio en el DOM
          try {
            const audioElement = document.createElement('audio');
            audioElement.src = '/notification.mp3';
            audioElement.volume = 1.0;
            document.body.appendChild(audioElement);

            // Reproducir y luego eliminar
            audioElement.play()
              .then(() => {
                setTimeout(() => {
                  document.body.removeChild(audioElement);
                }, 2000);
              })
              .catch(err2 => {
                console.error('Error en método 2:', err2);
                document.body.removeChild(audioElement);
              });
          } catch (error2) {
            console.error('Error en método 2:', error2);
          }
        });
      } catch (error) {
        console.error('Error al reproducir sonido para laboratorio:', error);
      }
    } else {
      // Para doctor: enfoque normal
      try {
        const audio = new Audio('/notification.mp3');
        audio.volume = 1.0;
        audio.play().catch(err => console.error('Error reproduciendo sonido para doctor:', err));
      } catch (error) {
        console.error('Error al reproducir sonido para doctor:', error);
      }
    }

    // Intentar mostrar notificación del sistema si la página está en segundo plano
    if (document.visibilityState === 'hidden') {
      try {
        await showSystemNotification(title, {
          body: message,
          tag: notificationUniqueId,
          data: { url: link }
        });
      } catch (error) {
        console.error('Error al mostrar notificación del sistema:', error);
      }
    }

    // Actualizar el tiempo de la última notificación
    setLastNotificationTime(Date.now());

    // Limitar el tamaño del conjunto de IDs notificados (mantener solo los últimos 50)
    if (notifiedIdsRef.current.size > 50) {
      const idsArray = Array.from(notifiedIdsRef.current);
      notifiedIdsRef.current = new Set(idsArray.slice(-50));

      // Actualizar también en sessionStorage
      try {
        sessionStorage.setItem('notified_request_ids',
          JSON.stringify(Array.from(notifiedIdsRef.current)));
      } catch (error) {
        console.error('Error al actualizar registro de notificaciones:', error);
      }
    }

    // Ya no eliminamos las notificaciones después de un tiempo
    // para evitar duplicados incluso después de recargar la página

    return true;
  };

  // Función para mostrar notificación (versión simplificada que usa notifyUser)
  const showNotification = (title, message, link, id, isLabNotification = false) => {
    return notifyUser(title, message, link, id, isLabNotification);
  };

  // Función principal para verificar actualizaciones - simplificada y mejorada
  const checkForUpdates = async () => {
    if (!user) return;

    try {
      // Diferentes endpoints según el rol del usuario
      let endpoint, params;

      if (user.role === 'laboratorio') {
        // Para laboratorio: buscar solicitudes nuevas (recién creadas)
        endpoint = '/solicitudes';
        params = {
          _t: Date.now(), // Evitar caché
          limit: 15,  // Aumentado para capturar más solicitudes
          sort: 'desc'
        };
      } else if (user.role === 'doctor') {
        // Para doctor: buscar SOLO solicitudes completadas
        endpoint = '/doctor/solicitudes';
        params = {
          _t: Date.now(), // Evitar caché
          estado: 'completado',  // Solo solicitudes completadas
          limit: 15,  // Aumentado para capturar más solicitudes
          sort: 'desc'
        };
      } else {
        // Rol desconocido, no hacer nada
        return;
      }

      // Obtener las solicitudes más recientes
      const response = await api.get(endpoint, { params });

      if (!response.data || !Array.isArray(response.data) || response.data.length === 0) {
        return;
      }

      // Si es la primera carga, solo guardar las solicitudes sin mostrar notificaciones
      if (!initialized) {
        const ids = response.data.map(s => s.id);
        setLastSolicitudes(ids);
        setInitialized(true);

        // Invalidar consultas para asegurar que los datos estén actualizados
        if (user.role === 'laboratorio') {
          queryClient.invalidateQueries(['requests']);
          queryClient.invalidateQueries(['dashboard']);
        } else if (user.role === 'doctor') {
          queryClient.invalidateQueries(['doctor-requests']);
        }

        // Guardar los IDs en el registro de notificaciones para evitar mostrar notificaciones para solicitudes existentes
        for (const id of ids) {
          const notificationId = user.role === 'laboratorio' ? `lab-${id}` : `doc-${id}-Resultados-disponibles`;
          notifiedIdsRef.current.add(notificationId);
        }

        // Guardar en sessionStorage
        try {
          sessionStorage.setItem('notified_request_ids', JSON.stringify(Array.from(notifiedIdsRef.current)));
        } catch (error) {
          console.error('Error al guardar IDs notificados:', error);
        }

        return;
      }

      // Verificar si hay nuevas solicitudes
      const currentIds = response.data.map(s => s.id);

      // Filtrar solicitudes que no estaban en la lista anterior
      const newSolicitudes = response.data.filter(s => !lastSolicitudes.includes(s.id));

      // Si hay nuevas solicitudes, mostrar notificaciones
      if (newSolicitudes.length > 0) {
        if (user.role === 'laboratorio') {
          // Para laboratorio: procesar TODAS las solicitudes nuevas
          // Ordenamos por ID de forma descendente para asegurarnos de procesar las más recientes primero
          const sortedSolicitudes = [...newSolicitudes].sort((a, b) => b.id - a.id);

          // Verificar si hay solicitudes nuevas
          if (sortedSolicitudes.length === 0) {
            return;
          }

          // Procesar SOLO la solicitud más reciente para evitar duplicados
          const solicitud = sortedSolicitudes[0]; // Solo la más reciente

          // Verificar si la solicitud fue creada por un doctor
          if (!solicitud.user || solicitud.user.role !== 'doctor') {
            return;
          }

          // Usar el ID de la solicitud para la notificación
          const doctorName = solicitud.user?.name || 'Un doctor';
          const patientName = solicitud.paciente
            ? `${solicitud.paciente.nombres} ${solicitud.paciente.apellidos}`
            : 'un paciente';

          // Mostrar notificación con ID único
          showNotification(
            'Nueva solicitud recibida',
            `${doctorName} ha creado una solicitud para ${patientName}`,
            `/solicitudes/${solicitud.id}`,
            solicitud.id, // Usar el ID de la solicitud
            true // Es una notificación de laboratorio
          );

          // Si hay más solicitudes nuevas, mostrar un mensaje adicional (solo una vez)
          if (sortedSolicitudes.length > 1) {
            const additionalCount = sortedSolicitudes.length - 1;
            const notificationKey = `additional-${Date.now()}`;

            // Verificar si ya se mostró una notificación similar en los últimos 30 segundos
            const now = Date.now();
            const lastAdditionalNotification = sessionStorage.getItem('last_additional_notification');
            const timeSinceLastNotification = lastAdditionalNotification ? now - parseInt(lastAdditionalNotification) : Infinity;

            // Solo mostrar si han pasado más de 30 segundos desde la última notificación similar
            if (timeSinceLastNotification > 30000) {
              // Guardar timestamp de esta notificación
              sessionStorage.setItem('last_additional_notification', now.toString());

              // Esperar un segundo para no solapar notificaciones
              setTimeout(() => {
                toast.success(`Hay ${additionalCount} solicitud${additionalCount > 1 ? 'es' : ''} adicional${additionalCount > 1 ? 'es' : ''} sin revisar`, {
                  id: notificationKey, // Usar ID único
                  duration: 5000,
                  position: 'bottom-right',
                });
              }, 1000);
            }
          }

        } else if (user.role === 'doctor') {
          // Para doctor: procesar solo la solicitud más reciente para evitar duplicados
          // Ordenamos por ID de forma descendente para asegurarnos de procesar la más reciente primero
          const sortedSolicitudes = [...newSolicitudes].sort((a, b) => b.id - a.id);
          const newestSolicitud = sortedSolicitudes[0]; // Solo la más reciente

          // Verificar que la solicitud esté completada
          if (newestSolicitud.estado === 'completado' || newestSolicitud.estado_calculado === 'completado') {
            const patientName = newestSolicitud.paciente
              ? `${newestSolicitud.paciente.nombres} ${newestSolicitud.paciente.apellidos}`
              : 'un paciente';

            // Mostrar notificación con ID de la solicitud
            showNotification(
              'Resultados disponibles',
              `Los resultados para ${patientName} están listos`,
              `/doctor/solicitudes/${newestSolicitud.id}/resultados`,
              newestSolicitud.id // Usar el ID de la solicitud directamente
            );
          }
        }

        // Invalidar consultas para actualizar la UI inmediatamente
        if (user.role === 'laboratorio') {
          queryClient.invalidateQueries(['requests']);
          queryClient.invalidateQueries(['dashboard']);
        } else if (user.role === 'doctor') {
          queryClient.invalidateQueries(['doctor-requests']);
        }
      }

      // Actualizar la lista de IDs conocidos
      setLastSolicitudes(currentIds);

    } catch (error) {
      console.error('Error al verificar actualizaciones:', error);
    }
  };

  // Efecto DESACTIVADO - Solo usar WebSocket, no polling
  useEffect(() => {
    if (!user) return;

    // No inicializar polling - solo WebSocket
    return () => {
      // RealtimeUpdater desmontado - Polling desactivado
    };

    // Para laboratorio: siempre limpiar el registro de notificaciones al iniciar
    // Para doctor: solo limpiar si el usuario ha cambiado
    if (user.role === 'laboratorio') {
      // Limpiar el registro de notificaciones
      notifiedIdsRef.current.clear();

      // Limpiar también en sessionStorage
      try {
        sessionStorage.removeItem('notified_request_ids');
      } catch (error) {
        console.error('Error al limpiar registro de notificaciones:', error);
      }
    } else {
      // Para doctor: verificar si el usuario ha cambiado
      const lastUserId = sessionStorage.getItem('last_user_id');

      if (lastUserId !== user.id.toString()) {
        // Limpiar el registro de notificaciones
        notifiedIdsRef.current.clear();

        // Limpiar también en sessionStorage
        try {
          sessionStorage.removeItem('notified_request_ids');
        } catch (error) {
          console.error('Error al limpiar registro de notificaciones:', error);
        }
      }
    }

    // Guardar el ID del usuario actual
    sessionStorage.setItem('last_user_id', user.id.toString());
    sessionStorage.setItem('last_user_role', user.role);

    // Forzar la configuración de notificaciones habilitadas
    const defaultSettings = {
      enabled: true,
      sound: true,
      systemNotifications: false
    };
    localStorage.setItem('notification_settings', JSON.stringify(defaultSettings));

    // Mostrar mensaje de inicio
    toast.success(`WebSocket activado para ${user.role === 'laboratorio' ? 'laboratorio' : 'doctor'}`, {
      id: `websocket-active-${user.role}`,
      duration: 3000,
      position: 'bottom-center',
    });

    // Limpiar al desmontar
    return () => {
      // RealtimeUpdater desmontado - Solo WebSocket activo
    };
  }, [user?.id]); // Solo depende del ID del usuario para evitar reinicios innecesarios

  // Efecto de invalidación DESACTIVADO - Solo WebSocket
  useEffect(() => {
    if (!user) return;

    // No configurar intervalos de invalidación
    return () => {
      // Efecto de invalidación desmontado
    };
  }, [user?.id, queryClient]);

  // Este componente no renderiza nada visible
  return null;
}
