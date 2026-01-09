import React, { useState } from 'react';
import toast from 'react-hot-toast';
import NotificationToast from './NotificationToast';
import { playNotificationSound, showSystemNotification, checkNotificationPermission } from '../../services/notificationService';

// Componente para probar notificaciones
export default function TestNotification() {
  const [isPlaying, setIsPlaying] = useState(false);

  // Función para reproducir sonido de notificación
  const playSound = () => {
    setIsPlaying(true);

    try {
      const audio = new Audio('/notification.mp3');
      audio.volume = 1.0; // Volumen al máximo
      audio.play().catch(err => console.error('Error reproduciendo sonido:', err));

      // Restablecer el estado después de un tiempo
      setTimeout(() => {
        setIsPlaying(false);
      }, 2000);
    } catch (error) {
      console.error('Error al reproducir sonido de notificación:', error);
      setIsPlaying(false);

      // Mostrar mensaje de error
      toast.error('Error al reproducir sonido. Verifica que tu navegador permita la reproducción de audio.', {
        duration: 3000,
        position: 'bottom-center',
      });
    }
  };

  // Función para solicitar permisos de notificación
  const requestPermission = async () => {
    const hasPermission = await checkNotificationPermission();
    if (hasPermission) {
      toast.success('Permisos de notificación concedidos', {
        duration: 3000,
        position: 'bottom-center',
      });
    } else {
      toast.error('No se pudieron obtener permisos de notificación', {
        duration: 3000,
        position: 'bottom-center',
      });
    }
  };

  // Función para mostrar notificación de prueba
  const showTestNotification = async () => {
    // Crear un ID único para esta notificación
    const notificationId = `test-notification-${Date.now()}`;

    // Mostrar notificación en la aplicación
    toast.custom((t) => (
      <NotificationToast
        title="Notificación de prueba"
        message="Esta es una notificación de prueba para verificar que el sistema funciona correctamente"
        link="#"
        onClose={() => toast.dismiss(t.id)}
      />
    ), {
      id: notificationId,
      duration: 8000,
      position: 'top-right',
    });

    // Reproducir sonido
    playSound();

    // Mostrar notificación del sistema
    await showSystemNotification('Notificación de prueba', {
      body: 'Esta es una notificación de prueba para verificar que el sistema funciona correctamente',
      tag: notificationId
    });
  };

  // Función para mostrar notificación de laboratorio
  const showLabNotification = async () => {
    // Crear un ID único para esta notificación
    const notificationId = `test-lab-notification-${Date.now()}`;

    // Mostrar notificación en la aplicación
    toast.custom((t) => (
      <NotificationToast
        title="Nueva solicitud recibida"
        message="Un doctor ha creado una solicitud para un paciente"
        link="/solicitudes"
        onClose={() => toast.dismiss(t.id)}
      />
    ), {
      id: notificationId,
      duration: 8000,
      position: 'top-right',
    });

    // Reproducir sonido
    playSound();

    // Mostrar notificación del sistema
    await showSystemNotification('Nueva solicitud recibida', {
      body: 'Un doctor ha creado una solicitud para un paciente',
      tag: notificationId,
      data: { url: '/solicitudes' }
    });

    // Mostrar una segunda notificación después de un breve retraso
    setTimeout(async () => {
      const notificationId2 = `test-lab-notification-2-${Date.now()}`;

      toast.custom((t) => (
        <NotificationToast
          title="Segunda solicitud recibida"
          message="Otro doctor ha creado una solicitud para otro paciente"
          link="/solicitudes"
          onClose={() => toast.dismiss(t.id)}
        />
      ), {
        id: notificationId2,
        duration: 8000,
        position: 'top-right',
      });

      // Reproducir sonido
      playSound();

      // Mostrar notificación del sistema
      await showSystemNotification('Segunda solicitud recibida', {
        body: 'Otro doctor ha creado una solicitud para otro paciente',
        tag: notificationId2,
        data: { url: '/solicitudes' }
      });
    }, 1500); // 1.5 segundos de retraso
  };

  // Función para mostrar notificación de doctor
  const showDoctorNotification = async () => {
    // Crear un ID único para esta notificación
    const notificationId = `test-doctor-notification-${Date.now()}`;

    // Mostrar notificación en la aplicación
    toast.custom((t) => (
      <NotificationToast
        title="Resultados disponibles"
        message="Los resultados para un paciente están listos"
        link="/doctor/resultados"
        onClose={() => toast.dismiss(t.id)}
      />
    ), {
      id: notificationId,
      duration: 8000,
      position: 'top-right',
    });

    // Reproducir sonido
    playSound();

    // Mostrar notificación del sistema
    await showSystemNotification('Resultados disponibles', {
      body: 'Los resultados para un paciente están listos',
      tag: notificationId,
      data: { url: '/doctor/resultados' }
    });
  };

  // Función para probar solo el sonido
  const testSoundOnly = () => {
    playSound();
    toast.success('Reproduciendo sonido de notificación...', {
      duration: 2000,
      position: 'bottom-center',
    });
  };

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap gap-3">
        <button
          onClick={showTestNotification}
          disabled={isPlaying}
          className="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
        >
          Notificación general
        </button>

        <button
          onClick={showLabNotification}
          disabled={isPlaying}
          className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
        >
          Notificación laboratorio
        </button>

        <button
          onClick={showDoctorNotification}
          disabled={isPlaying}
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
        >
          Notificación doctor
        </button>

        <button
          onClick={testSoundOnly}
          disabled={isPlaying}
          className="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50"
        >
          Probar solo sonido
        </button>

        <button
          onClick={requestPermission}
          disabled={isPlaying}
          className="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 disabled:opacity-50"
        >
          Solicitar permisos
        </button>
      </div>

      <div className="text-sm text-gray-500 dark:text-gray-400">
        {isPlaying ? 'Reproduciendo sonido...' : 'Haz clic en un botón para probar las notificaciones'}
      </div>

      <div className="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
        <h3 className="text-sm font-medium text-gray-900 dark:text-white mb-2">Información sobre notificaciones</h3>
        <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
          Las notificaciones en tiempo real te alertarán cuando ocurran eventos importantes:
        </p>
        <ul className="list-disc pl-5 text-xs text-gray-500 dark:text-gray-400">
          <li className="mb-1">Para <strong>técnicos de laboratorio</strong>: Cuando un doctor crea una nueva solicitud</li>
          <li className="mb-1">Para <strong>doctores</strong>: Cuando los resultados de una solicitud están disponibles</li>
          <li className="mb-1">Las notificaciones incluyen un sonido y una alerta visual</li>
          <li>Si concedes permisos, también recibirás notificaciones del sistema cuando la aplicación esté en segundo plano</li>
        </ul>
      </div>
    </div>
  );
}
