import React, { useState } from 'react';
import { Switch } from '@headlessui/react';
import { BellIcon, SpeakerWaveIcon, SpeakerXMarkIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

// Función para obtener la configuración de notificaciones del localStorage
const getNotificationSettings = () => {
  const settings = localStorage.getItem('notification_settings');
  if (settings) {
    try {
      const parsedSettings = JSON.parse(settings);
      console.log('Configuración de notificaciones cargada:', parsedSettings);
      return parsedSettings;
    } catch (error) {
      console.error('Error al parsear la configuración de notificaciones:', error);
    }
  }

  // Valores por defecto
  const defaultSettings = {
    enabled: true,
    sound: true
  };

  console.log('Usando configuración de notificaciones por defecto:', defaultSettings);

  // Guardar los valores por defecto en localStorage
  localStorage.setItem('notification_settings', JSON.stringify(defaultSettings));

  return defaultSettings;
};

// Función para guardar la configuración de notificaciones en localStorage
const saveNotificationSettings = (settings) => {
  localStorage.setItem('notification_settings', JSON.stringify(settings));
};

export default function NotificationSettings() {
  // Estado para la configuración de notificaciones
  const [settings, setSettings] = useState(getNotificationSettings());

  // Función para cambiar la configuración
  const updateSetting = (key, value) => {
    const newSettings = { ...settings, [key]: value };
    setSettings(newSettings);
    saveNotificationSettings(newSettings);

    // Log para depuración
    console.log(`Configuración actualizada: ${key} = ${value}`);
    console.log('Nueva configuración completa:', newSettings);

    // Mostrar mensaje de confirmación
    toast.success(`Configuración de notificaciones actualizada`, {
      duration: 2000,
      position: 'bottom-center',
    });
  };

  // Función para mostrar una notificación de prueba
  const showTestNotification = () => {
    toast.custom((t) => (
      <div className="max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
        <div className="p-4">
          <div className="flex items-start">
            <div className="flex-shrink-0">
              <BellIcon className="h-6 w-6 text-primary-400" aria-hidden="true" />
            </div>
            <div className="ml-3 w-0 flex-1 pt-0.5">
              <p className="text-sm font-medium text-gray-900 dark:text-white">Notificación de prueba</p>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Esta es una notificación de prueba</p>
            </div>
            <div className="ml-4 flex-shrink-0 flex">
              <button
                className="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                onClick={() => toast.dismiss(t.id)}
              >
                <span className="sr-only">Cerrar</span>
                <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    ), {
      duration: 5000,
      position: 'top-right',
    });

    // Reproducir sonido si está activado
    if (settings.sound) {
      try {
        const audio = new Audio('/notification.mp3');
        audio.volume = 1.0;
        audio.play().catch(err => console.error('Error reproduciendo sonido:', err));
      } catch (error) {
        console.error('Error al reproducir sonido:', error);
      }
    }
  };

  return (
    <div className="px-4 py-5 sm:p-6">
      <div className="space-y-6">
        {/* Notificaciones en la aplicación */}
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <BellIcon className="h-5 w-5 text-gray-500 dark:text-gray-400 mr-3" />
            <div>
              <p className="text-sm font-medium text-gray-900 dark:text-white">
                Notificaciones en la aplicación
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Mostrar notificaciones cuando ocurran eventos importantes
              </p>
            </div>
          </div>
          <Switch
            checked={settings.enabled}
            onChange={(checked) => updateSetting('enabled', checked)}
            className={`${
              settings.enabled ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'
            } relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2`}
          >
            <span
              className={`${
                settings.enabled ? 'translate-x-6' : 'translate-x-1'
              } inline-block h-4 w-4 transform rounded-full bg-white transition-transform`}
            />
          </Switch>
        </div>

        {/* Sonido de notificaciones */}
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            {settings.sound ? (
              <SpeakerWaveIcon className="h-5 w-5 text-gray-500 dark:text-gray-400 mr-3" />
            ) : (
              <SpeakerXMarkIcon className="h-5 w-5 text-gray-500 dark:text-gray-400 mr-3" />
            )}
            <div>
              <p className="text-sm font-medium text-gray-900 dark:text-white">
                Sonido de notificaciones
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Reproducir un sonido cuando se muestre una notificación
              </p>
            </div>
          </div>
          <Switch
            checked={settings.sound}
            onChange={(checked) => updateSetting('sound', checked)}
            className={`${
              settings.sound ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'
            } relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2`}
          >
            <span
              className={`${
                settings.sound ? 'translate-x-6' : 'translate-x-1'
              } inline-block h-4 w-4 transform rounded-full bg-white transition-transform`}
            />
          </Switch>
        </div>

        {/* Botón de prueba */}
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <BellIcon className="h-5 w-5 text-gray-500 dark:text-gray-400 mr-3" />
            <div>
              <p className="text-sm font-medium text-gray-900 dark:text-white">
                Probar notificaciones
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Muestra una notificación de prueba para verificar la configuración
              </p>
            </div>
          </div>
          <button
            onClick={showTestNotification}
            className="px-3 py-1 text-xs font-medium text-white bg-primary-600 rounded hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          >
            Probar
          </button>
        </div>

        {/* Información sobre notificaciones */}
        <div className="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
          <h3 className="text-sm font-medium text-gray-900 dark:text-white mb-2">Información sobre notificaciones</h3>
          <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
            Las notificaciones en tiempo real te alertarán cuando ocurran eventos importantes:
          </p>
          <ul className="list-disc pl-5 text-xs text-gray-500 dark:text-gray-400">
            <li className="mb-1">Para <strong>técnicos de laboratorio</strong>: Cuando un doctor crea una nueva solicitud</li>
            <li className="mb-1">Para <strong>doctores</strong>: Cuando los resultados de una solicitud están disponibles</li>
          </ul>
        </div>
      </div>
    </div>
  );
}
