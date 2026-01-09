import React from 'react';
import { useWebSocketContext } from '../websocket/WebSocketProvider';
import { ExclamationTriangleIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';

const NotificationStatus = () => {
  const { connectionStatus, isConnected } = useWebSocketContext();

  // Verificar si estamos en producción HTTPS
  const isHTTPS = window.location.protocol === 'https:';
  const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';
  const isProductionHTTPS = isHTTPS && isProduction;

  // No mostrar nada si está conectado correctamente
  if (isConnected && connectionStatus === 'connected') {
    return null;
  }

  // Determinar el mensaje y estilo según el estado
  let message, icon, bgColor, textColor, borderColor;

  if (isProductionHTTPS) {
    message = 'Notificaciones en tiempo real no disponibles en producción HTTPS. Las actualizaciones requieren recargar la página.';
    icon = <ExclamationTriangleIcon className="h-5 w-5" />;
    bgColor = 'bg-yellow-50 dark:bg-yellow-900/20';
    textColor = 'text-yellow-800 dark:text-yellow-200';
    borderColor = 'border-yellow-200 dark:border-yellow-700';
  } else if (connectionStatus === 'error' || connectionStatus === 'disconnected') {
    message = 'Error de conexión con el servidor de notificaciones. Reintentando...';
    icon = <XCircleIcon className="h-5 w-5" />;
    bgColor = 'bg-red-50 dark:bg-red-900/20';
    textColor = 'text-red-800 dark:text-red-200';
    borderColor = 'border-red-200 dark:border-red-700';
  } else if (connectionStatus === 'connecting') {
    message = 'Conectando al servidor de notificaciones...';
    icon = <CheckCircleIcon className="h-5 w-5 animate-pulse" />;
    bgColor = 'bg-blue-50 dark:bg-blue-900/20';
    textColor = 'text-blue-800 dark:text-blue-200';
    borderColor = 'border-blue-200 dark:border-blue-700';
  } else {
    // Estado desconocido, no mostrar nada
    return null;
  }

  return (
    <div className={`rounded-md border ${borderColor} ${bgColor} p-3 mb-4`}>
      <div className="flex">
        <div className="flex-shrink-0">
          <div className={textColor}>
            {icon}
          </div>
        </div>
        <div className="ml-3">
          <p className={`text-sm font-medium ${textColor}`}>
            Estado de Notificaciones
          </p>
          <p className={`mt-1 text-sm ${textColor}`}>
            {message}
          </p>
          {isProductionHTTPS && (
            <p className={`mt-2 text-xs ${textColor} opacity-75`}>
              Para habilitar notificaciones en tiempo real, el servidor WebSocket necesita configuración HTTPS.
            </p>
          )}
        </div>
      </div>
    </div>
  );
};

export default NotificationStatus;
