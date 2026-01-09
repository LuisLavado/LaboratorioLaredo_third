import React, { useState } from 'react';
import { XMarkIcon, BeakerIcon, CheckCircleIcon } from '@heroicons/react/24/outline';

export default function DynamicSystemNotice() {
  const [isVisible, setIsVisible] = useState(() => {
    // Check if user has already dismissed this notice
    return !localStorage.getItem('dynamicSystemNoticeDismissed');
  });

  const handleDismiss = () => {
    setIsVisible(false);
    localStorage.setItem('dynamicSystemNoticeDismissed', 'true');
  };

  if (!isVisible) return null;

  return (
    <div className="bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 border border-primary-200 dark:border-primary-700 rounded-lg p-4 mb-6">
      <div className="flex">
        <div className="flex-shrink-0">
          <BeakerIcon className="h-6 w-6 text-primary-600 dark:text-primary-400" aria-hidden="true" />
        </div>
        <div className="ml-3 flex-1">
          <h3 className="text-sm font-medium text-primary-800 dark:text-primary-200">
            🚀 Sistema de Resultados Dinámicos
          </h3>
          <div className="mt-2 text-sm text-primary-700 dark:text-primary-300">
            <p className="mb-2">
              Sistema inteligente de resultados que:
            </p>
            <ul className="list-disc list-inside space-y-1 ml-4">
              <li className="flex items-center">
                <CheckCircleIcon className="h-4 w-4 text-green-500 mr-2 flex-shrink-0" />
                Se adapta automáticamente a cualquier tipo de examen
              </li>
              <li className="flex items-center">
                <CheckCircleIcon className="h-4 w-4 text-green-500 mr-2 flex-shrink-0" />
                Valida valores contra rangos de referencia en tiempo real
              </li>
              <li className="flex items-center">
                <CheckCircleIcon className="h-4 w-4 text-green-500 mr-2 flex-shrink-0" />
                Organiza campos por secciones (Serie Roja, Química, etc.)
              </li>
              <li className="flex items-center">
                <CheckCircleIcon className="h-4 w-4 text-green-500 mr-2 flex-shrink-0" />
                Soporta exámenes compuestos (perfiles) automáticamente
              </li>
            </ul>
            <div className="mt-3 p-3 bg-white dark:bg-gray-800 rounded-md border border-primary-200 dark:border-primary-600">
              <p className="text-sm font-medium text-primary-800 dark:text-primary-200">
                💡 <strong>Tip:</strong> El sistema ahora es completamente dinámico.
                Los exámenes se configuran con campos personalizados y los resultados
                se adaptan automáticamente a cada configuración.
              </p>
            </div>
          </div>
        </div>
        <div className="ml-auto pl-3">
          <div className="-mx-1.5 -my-1.5">
            <button
              type="button"
              onClick={handleDismiss}
              className="inline-flex rounded-md bg-primary-50 dark:bg-primary-900/20 p-1.5 text-primary-500 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900/40 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 focus:ring-offset-primary-50"
            >
              <span className="sr-only">Cerrar</span>
              <XMarkIcon className="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
