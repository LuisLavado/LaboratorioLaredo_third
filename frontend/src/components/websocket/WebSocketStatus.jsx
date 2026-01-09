import React, { useState, useEffect } from 'react';
import { useWebSocketContext } from './WebSocketProvider';
import { useAuth } from '../../contexts/AuthContext';
import {
  WifiIcon,
  SignalSlashIcon,
  ExclamationCircleIcon,
  CheckCircleIcon,
  ClockIcon,
  ArrowPathIcon
} from '@heroicons/react/24/outline';

const WebSocketStatus = ({ showDetails = false, className = '' }) => {
  const { user } = useAuth();
  const {
    isConnected,
    connectionStatus,
    unreadCount,
    connect,
    disconnect
  } = useWebSocketContext();

  const [showStatusDetails, setShowStatusDetails] = useState(false);

  // Auto-ocultar detalles después de 3 segundos
  useEffect(() => {
    if (showStatusDetails) {
      const timer = setTimeout(() => {
        setShowStatusDetails(false);
      }, 3000);
      return () => clearTimeout(timer);
    }
  }, [showStatusDetails]);

  const getStatusIcon = () => {
    switch (connectionStatus) {
      case 'connected':
        return <WifiIcon className="w-4 h-4 text-green-500" />;
      case 'connecting':
      case 'reconnecting':
        return <ArrowPathIcon className="w-4 h-4 text-yellow-500 animate-spin" />;
      case 'error':
        return <ExclamationCircleIcon className="w-4 h-4 text-red-500" />;
      default:
        return <SignalSlashIcon className="w-4 h-4 text-gray-500" />;
    }
  };

  const getStatusText = () => {
    switch (connectionStatus) {
      case 'connected':
        return 'Conectado';
      case 'connecting':
        return 'Conectando...';
      case 'reconnecting':
        return 'Reconectando...';
      case 'error':
        return 'Error de conexión';
      default:
        return 'Desconectado';
    }
  };

  const getStatusColor = () => {
    switch (connectionStatus) {
      case 'connected':
        return 'text-green-600 bg-green-50 border-green-200';
      case 'connecting':
      case 'reconnecting':
        return 'text-yellow-600 bg-yellow-50 border-yellow-200';
      case 'error':
        return 'text-red-600 bg-red-50 border-red-200';
      default:
        return 'text-gray-600 bg-gray-50 border-gray-200';
    }
  };

  const handleStatusClick = () => {
    setShowStatusDetails(!showStatusDetails);
  };

  const handleReconnect = () => {
    if (isConnected) {
      disconnect();
      setTimeout(() => connect(), 1000);
    } else {
      connect();
    }
  };

  if (!showDetails) {
    // Versión compacta para el header
    return (
      <div className={`relative ${className}`}>
        <button
          onClick={handleStatusClick}
          className={`flex items-center space-x-2 px-3 py-1 rounded-full border text-xs font-medium transition-colors ${getStatusColor()}`}
        >
          {getStatusIcon()}
          <span>{getStatusText()}</span>
          {unreadCount > 0 && (
            <span className="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 min-w-[1.25rem] text-center">
              {unreadCount > 9 ? '9+' : unreadCount}
            </span>
          )}
        </button>

        {showStatusDetails && (
          <div className="absolute top-full right-0 mt-2 w-64 bg-white border rounded-lg shadow-lg z-50 p-3">
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-medium text-gray-900">Estado WebSocket</span>
                {getStatusIcon()}
              </div>
              
              <div className="text-sm text-gray-600">
                <div>Estado: <span className="font-medium">{getStatusText()}</span></div>
                <div>Usuario: <span className="font-medium">{user?.role}</span></div>
                <div>Notificaciones: <span className="font-medium">{unreadCount}</span></div>
              </div>

              <div className="text-xs text-gray-500">
                {user?.role === 'doctor' && 'Recibirás notificaciones cuando los resultados estén listos'}
                {user?.role === 'laboratorio' && 'Recibirás notificaciones de nuevas solicitudes'}
              </div>

              {connectionStatus === 'error' && (
                <button
                  onClick={handleReconnect}
                  className="w-full mt-2 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600"
                >
                  Reconectar
                </button>
              )}
            </div>
          </div>
        )}
      </div>
    );
  }

  // Versión detallada para el dashboard
  return (
    <div className={`bg-white dark:bg-gray-800 rounded-lg shadow p-4 ${className}`}>
      <div className="flex items-center justify-between mb-3">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">
          Estado de Notificaciones
        </h3>
        {getStatusIcon()}
      </div>

      <div className="space-y-3">
        {/* Estado de conexión */}
        <div className="flex items-center justify-between">
          <span className="text-sm text-gray-600 dark:text-gray-400">Conexión:</span>
          <span className={`text-sm font-medium ${
            isConnected ? 'text-green-600' : 'text-red-600'
          }`}>
            {getStatusText()}
          </span>
        </div>

        {/* Rol del usuario */}
        <div className="flex items-center justify-between">
          <span className="text-sm text-gray-600 dark:text-gray-400">Rol:</span>
          <span className="text-sm font-medium text-gray-900 dark:text-white">
            {user?.role === 'doctor' ? 'Doctor' : 'Laboratorio'}
          </span>
        </div>

        {/* Notificaciones no leídas */}
        <div className="flex items-center justify-between">
          <span className="text-sm text-gray-600 dark:text-gray-400">Notificaciones:</span>
          <span className="text-sm font-medium text-gray-900 dark:text-white">
            {unreadCount} sin leer
          </span>
        </div>

        {/* Información específica del rol */}
        <div className="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
          <div className="flex items-start space-x-2">
            <CheckCircleIcon className="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
            <div className="text-xs text-blue-700 dark:text-blue-300">
              {user?.role === 'doctor' && (
                <>
                  <strong>Como doctor:</strong> Recibirás notificaciones automáticas cuando los resultados de tus solicitudes estén listos.
                </>
              )}
              {user?.role === 'laboratorio' && (
                <>
                  <strong>Como laboratorio:</strong> Recibirás notificaciones automáticas cuando se creen nuevas solicitudes.
                </>
              )}
            </div>
          </div>
        </div>

        {/* Botón de reconexión si hay error */}
        {connectionStatus === 'error' && (
          <button
            onClick={handleReconnect}
            className="w-full mt-3 px-4 py-2 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition-colors"
          >
            <ArrowPathIcon className="w-4 h-4 inline mr-2" />
            Reconectar
          </button>
        )}

        {/* Indicador de tiempo real */}
        {isConnected && (
          <div className="flex items-center justify-center mt-3 text-xs text-green-600 dark:text-green-400">
            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></div>
            Notificaciones en tiempo real activas
          </div>
        )}
      </div>
    </div>
  );
};

export default WebSocketStatus;
