import { useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useAuth } from '../../contexts/AuthContext';
import toast from 'react-hot-toast';
import NotificationToast from '../notifications/NotificationToast';

// Componente para escuchar eventos mediante WebSocket
export default function EventListener() {
  const queryClient = useQueryClient();
  const { user } = useAuth();

  // Función para mostrar notificaciones
  const showNotification = (title, message, link, id) => {
    // Crear un ID único para esta notificación
    const notificationId = `event-${id}-${Date.now()}`;

    console.log(`[EventListener] Mostrando notificación: ${title} - ${message} - ID: ${notificationId}`);

    // Mostrar notificación en la aplicación
    toast.custom((t) => (
      <NotificationToast
        title={title}
        message={message}
        link={link}
        onClose={() => toast.dismiss(t.id)}
      />
    ), {
      id: notificationId,
      duration: 10000, // 10 segundos
      position: 'top-right',
    });

    // Reproducir sonido
    try {
      const audio = new Audio('/notification.mp3');
      audio.volume = 1.0;
      audio.play().catch(err => {
        console.error('[EventListener] Error reproduciendo sonido:', err);
        // Intentar reproducir de nuevo con un pequeño retraso
        setTimeout(() => {
          const audio2 = new Audio('/notification.mp3');
          audio2.volume = 1.0;
          audio2.play().catch(err2 => console.error('[EventListener] Error en segundo intento:', err2));
        }, 500);
      });
    } catch (error) {
      console.error('[EventListener] Error al reproducir sonido:', error);
    }
  };

  // Función para manejar eventos WebSocket
  const handleEvent = async (data) => {
    try {
      // Verificar si el mensaje es relevante para el usuario actual
      if (!user) return;

      console.log('[EventListener] Procesando evento:', data.event, data);

      // Manejar diferentes tipos de eventos
      switch (data.event) {
        case 'solicitud.created':
          // Solo notificar al laboratorio y solo si fue creado por un doctor
          if (user.role === 'laboratorio' && data.data?.user?.role === 'doctor') {
            // Obtener información del doctor y paciente
            const doctorName = data.data?.user?.name || 'Un doctor';
            const patientName = data.data?.paciente
              ? `${data.data.paciente.nombres} ${data.data.paciente.apellidos}`
              : 'un paciente';

            // Mostrar notificación
            showNotification(
              'Nueva solicitud recibida',
              `${doctorName} ha creado una solicitud para ${patientName}`,
              `/solicitudes/${data.solicitud_id}`,
              data.solicitud_id
            );

            console.log('[EventListener] Notificación mostrada para solicitud creada por doctor:', data.solicitud_id);

            // Invalidar consultas
            queryClient.invalidateQueries(['requests']);
            queryClient.invalidateQueries(['dashboard']);
          } else {
            console.log('[EventListener] Ignorando notificación para solicitud no creada por doctor o usuario no es laboratorio');
          }
          break;

        case 'solicitud.completed':
          // Solo notificar al doctor que creó la solicitud
          if (user.role === 'doctor' && data.data?.user_id === user.id) {
            // Obtener información del paciente
            const patientName = data.data?.paciente
              ? `${data.data.paciente.nombres} ${data.data.paciente.apellidos}`
              : 'un paciente';

            // Mostrar notificación
            showNotification(
              'Resultados disponibles',
              `Los resultados para ${patientName} están listos`,
              `/doctor/solicitudes/${data.solicitud_id}/resultados`,
              data.solicitud_id
            );

            // Invalidar consultas
            queryClient.invalidateQueries(['doctor-requests']);
          }
          break;

        case 'solicitud.updated':
          // Invalidar consultas para ambos roles
          if (user.role === 'laboratorio') {
            queryClient.invalidateQueries(['requests']);
            queryClient.invalidateQueries(['dashboard']);
          } else if (user.role === 'doctor') {
            queryClient.invalidateQueries(['doctor-requests']);
          }

          // Invalidar consultas específicas
          queryClient.invalidateQueries(['request', data.solicitud_id]);
          queryClient.invalidateQueries(['requestDetails', data.solicitud_id]);
          break;

        case 'resultado.created':
        case 'resultado.updated':
          // Invalidar consultas para ambos roles
          queryClient.invalidateQueries(['requestDetails', data.solicitud_id]);
          break;

        default:
          console.log('[EventListener] Evento no manejado:', data.event);
      }
    } catch (error) {
      console.error('[EventListener] Error al procesar evento:', error);
    }
  };

  // Efecto para iniciar WebSocket cuando el componente se monta
  useEffect(() => {
    if (!user) return;

    console.log('[EventListener] Iniciando WebSocket para:', user.role);

    // Importar dinámicamente el servicio WebSocket
    import('../../services/webSocketProvider').then(({ webSocketProvider }) => {
      // Configurar callbacks para eventos
      webSocketProvider.setCallbacks({
        onEvent: handleWebSocketEvent,
        onConnect: () => {
          console.log('[EventListener] WebSocket conectado');
          toast.success('Notificaciones WebSocket activadas', {
            id: 'events-connection',
            duration: 3000,
            position: 'bottom-center'
          });
        },
        onDisconnect: () => {
          console.log('[EventListener] WebSocket desconectado');
          toast.error('Conexión WebSocket perdida', {
            id: 'events-connection',
            duration: 3000,
            position: 'bottom-center'
          });
        },
        onError: (error) => {
          console.error('[EventListener] Error WebSocket:', error);
          toast.error('Error en conexión WebSocket', {
            id: 'events-connection',
            duration: 3000,
            position: 'bottom-center'
          });
        }
      });

      // Conectar WebSocket
      webSocketProvider.connect(user);
    });

    // Limpiar al desmontar
    return () => {
      import('../../services/webSocketProvider').then(({ webSocketProvider }) => {
        webSocketProvider.disconnect();
        console.log('[EventListener] WebSocket desconectado');
      });
    };
  }, [user?.id]); // Solo depende del ID del usuario

  // Función para manejar eventos WebSocket
  const handleWebSocketEvent = async (eventData) => {
    try {
      console.log('[EventListener] Evento WebSocket recibido:', eventData);
      await handleEvent(eventData);
    } catch (error) {
      console.error('[EventListener] Error al procesar evento WebSocket:', error);
    }
  };

  // Este componente no renderiza nada
  return null;
}
