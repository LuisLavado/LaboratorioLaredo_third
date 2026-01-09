/**
 * Servicio para manejar notificaciones
 */

// Función para reproducir sonido de notificación
export const playNotificationSound = () => {
  try {
    const audio = new Audio('/notification.mp3');
    audio.volume = 1.0; // Volumen al 100%

    // Intentar reproducir el sonido
    const playPromise = audio.play();

    // Manejar la promesa para evitar errores en navegadores que no soportan la reproducción automática
    if (playPromise !== undefined) {
      playPromise.catch(error => {
        console.error('Error al reproducir sonido de notificación:', error);

        // Intentar reproducir con un clic del usuario (simulado)
        document.addEventListener('click', function playOnClick() {
          audio.play().catch(e => console.error('Error en segundo intento:', e));
          document.removeEventListener('click', playOnClick);
        }, { once: true });
      });
    }

    return true;
  } catch (error) {
    console.error('Error al reproducir sonido de notificación:', error);
    return false;
  }
};

// Función para verificar si el navegador permite notificaciones del sistema
export const checkNotificationPermission = async () => {
  if (!('Notification' in window)) {
    return false;
  }

  if (Notification.permission === 'granted') {
    return true;
  }

  if (Notification.permission !== 'denied') {
    const permission = await Notification.requestPermission();
    return permission === 'granted';
  }

  return false;
};

// Función para mostrar una notificación del sistema
export const showSystemNotification = async (title, options = {}) => {
  const hasPermission = await checkNotificationPermission();

  if (!hasPermission) {
    return false;
  }

  try {
    // Configurar opciones por defecto
    const notificationOptions = {
      icon: '/favicon.ico',
      badge: '/favicon.ico',
      vibrate: [100, 50, 100], // Patrón de vibración para dispositivos móviles
      requireInteraction: false, // No requerir interacción del usuario
      silent: false, // No silenciar el sonido del sistema
      ...options
    };

    // Crear la notificación
    const notification = new Notification(title, notificationOptions);

    // Reproducir sonido
    playNotificationSound();

    // Manejar clic en la notificación
    notification.onclick = function() {
      // Enfocar la ventana si está en segundo plano
      window.focus();

      // Si hay una URL en los datos, navegar a ella
      if (options.data && options.data.url) {
        window.location.href = options.data.url;
      }

      // Cerrar la notificación
      this.close();
    };

    // Cerrar automáticamente después de 8 segundos
    setTimeout(() => {
      notification.close();
    }, 8000);

    return true;
  } catch (error) {
    console.error('Error al mostrar notificación del sistema:', error);
    return false;
  }
};

// Función para mostrar notificación de nueva solicitud (para laboratorio)
export const showNewRequestNotification = async (patientName, requestId) => {
  return await showSystemNotification('📋 Nueva solicitud', {
    body: `Nueva solicitud de ${patientName}`,
    icon: '/favicon.ico',
    tag: `new-request-${requestId}`,
    requireInteraction: true,
    data: {
      type: 'new-request',
      requestId,
      url: `/solicitudes/${requestId}`
    }
  });
};

// Función para mostrar notificación de resultados listos (para doctor)
export const showResultsReadyNotification = async (patientName, requestId) => {
  return await showSystemNotification('✅ Resultados listos', {
    body: `Los resultados de ${patientName} están disponibles`,
    icon: '/favicon.ico',
    tag: `results-ready-${requestId}`,
    requireInteraction: true,
    data: {
      type: 'results-ready',
      requestId,
      url: `/doctor/solicitudes/${requestId}/resultados`
    }
  });
};

// Función para mostrar notificación de solicitud actualizada
export const showRequestUpdatedNotification = async (patientName, requestId, status) => {
  const statusText = {
    'en_proceso': 'en proceso',
    'completado': 'completada',
    'pendiente': 'pendiente'
  };

  return await showSystemNotification('🔄 Solicitud actualizada', {
    body: `La solicitud de ${patientName} está ${statusText[status] || status}`,
    icon: '/favicon.ico',
    tag: `request-updated-${requestId}`,
    requireInteraction: false,
    data: {
      type: 'request-updated',
      requestId,
      status,
      url: `/solicitudes/${requestId}`
    }
  });
};

// Función para mostrar notificación personalizada para el laboratorio
export const showLabNotification = async (title, message, requestId = null) => {
  return await showSystemNotification(title, {
    body: message,
    icon: '/favicon.ico',
    tag: `lab-notification-${Date.now()}`,
    requireInteraction: false,
    data: {
      type: 'lab-notification',
      requestId,
      url: requestId ? `/solicitudes/${requestId}` : '/solicitudes'
    }
  });
};

// Función para mostrar notificación personalizada para el doctor
export const showDoctorNotification = async (title, message, requestId = null) => {
  return await showSystemNotification(title, {
    body: message,
    icon: '/favicon.ico',
    tag: `doctor-notification-${Date.now()}`,
    requireInteraction: false,
    data: {
      type: 'doctor-notification',
      requestId,
      url: requestId ? `/doctor/solicitudes/${requestId}` : '/doctor/solicitudes'
    }
  });
};

export default {
  playNotificationSound,
  checkNotificationPermission,
  showSystemNotification,
  showNewRequestNotification,
  showResultsReadyNotification,
  showRequestUpdatedNotification,
  showLabNotification,
  showDoctorNotification
};
