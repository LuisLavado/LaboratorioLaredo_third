import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { authAPI } from '../../services/api';
import toast from 'react-hot-toast';

export default function WebhookSettings() {
  const [loading, setLoading] = useState(false);
  const [hasWebhook, setHasWebhook] = useState(false);
  const [currentWebhook, setCurrentWebhook] = useState(null);
  
  const { register, handleSubmit, setValue, formState: { errors } } = useForm();
  
  // Cargar la configuración actual de webhook
  useEffect(() => {
    const fetchWebhookConfig = async () => {
      try {
        const response = await fetch('/api/user/webhook-config');
        const data = await response.json();
        
        if (data.webhook_url) {
          setHasWebhook(true);
          setCurrentWebhook({
            url: data.webhook_url,
            events: JSON.parse(data.webhook_events || '[]')
          });
          
          // Establecer valores en el formulario
          setValue('endpoint_url', data.webhook_url);
          setValue('events', JSON.parse(data.webhook_events || '[]'));
        }
      } catch (error) {
        console.error('Error al cargar configuración de webhook:', error);
      }
    };
    
    fetchWebhookConfig();
  }, [setValue]);
  
  // Manejar el envío del formulario
  const onSubmit = async (data) => {
    setLoading(true);
    
    try {
      const response = await authAPI.registerWebhook({
        endpoint_url: data.endpoint_url,
        events: data.events
      });
      
      setHasWebhook(true);
      setCurrentWebhook({
        url: data.endpoint_url,
        events: data.events
      });
      
      toast.success('Webhook configurado correctamente');
    } catch (error) {
      console.error('Error al configurar webhook:', error);
      toast.error('Error al configurar webhook');
    } finally {
      setLoading(false);
    }
  };
  
  // Eliminar webhook
  const handleUnregister = async () => {
    setLoading(true);
    
    try {
      await authAPI.unregisterWebhook();
      setHasWebhook(false);
      setCurrentWebhook(null);
      setValue('endpoint_url', '');
      setValue('events', []);
      
      toast.success('Webhook eliminado correctamente');
    } catch (error) {
      console.error('Error al eliminar webhook:', error);
      toast.error('Error al eliminar webhook');
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
      <div className="px-4 py-5 sm:px-6">
        <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
          Configuración de Webhooks
        </h3>
        <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
          Configure webhooks para recibir notificaciones en tiempo real
        </p>
      </div>
      
      <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
        <form onSubmit={handleSubmit(onSubmit)}>
          <div className="space-y-6">
            <div>
              <label htmlFor="endpoint_url" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                URL del Endpoint
              </label>
              <div className="mt-1">
                <input
                  type="url"
                  id="endpoint_url"
                  className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
                  placeholder="https://ejemplo.com/webhook"
                  {...register('endpoint_url', { 
                    required: 'La URL es requerida',
                    pattern: {
                      value: /^https?:\/\/.+/,
                      message: 'Debe ser una URL válida'
                    }
                  })}
                />
                {errors.endpoint_url && (
                  <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.endpoint_url.message}</p>
                )}
              </div>
              <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                URL donde se enviarán las notificaciones de eventos
              </p>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Eventos a notificar
              </label>
              <div className="mt-2 space-y-2">
                <div className="flex items-center">
                  <input
                    id="event-created"
                    type="checkbox"
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded"
                    value="solicitud.created"
                    {...register('events')}
                  />
                  <label htmlFor="event-created" className="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    Solicitud creada
                  </label>
                </div>
                
                <div className="flex items-center">
                  <input
                    id="event-updated"
                    type="checkbox"
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded"
                    value="solicitud.updated"
                    {...register('events')}
                  />
                  <label htmlFor="event-updated" className="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    Solicitud actualizada
                  </label>
                </div>
                
                <div className="flex items-center">
                  <input
                    id="event-completed"
                    type="checkbox"
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded"
                    value="solicitud.completed"
                    {...register('events')}
                  />
                  <label htmlFor="event-completed" className="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    Solicitud completada
                  </label>
                </div>
              </div>
            </div>
            
            <div className="flex justify-end space-x-3">
              {hasWebhook && (
                <button
                  type="button"
                  onClick={handleUnregister}
                  disabled={loading}
                  className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  {loading ? 'Eliminando...' : 'Eliminar Webhook'}
                </button>
              )}
              
              <button
                type="submit"
                disabled={loading}
                className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                {loading ? 'Guardando...' : 'Guardar Configuración'}
              </button>
            </div>
          </div>
        </form>
      </div>
      
      {hasWebhook && currentWebhook && (
        <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6">
          <h4 className="text-sm font-medium text-gray-500 dark:text-gray-400">Configuración actual</h4>
          <div className="mt-2">
            <p className="text-sm text-gray-900 dark:text-white">
              <span className="font-medium">URL:</span> {currentWebhook.url}
            </p>
            <p className="text-sm text-gray-900 dark:text-white mt-1">
              <span className="font-medium">Eventos:</span>{' '}
              {currentWebhook.events.map(event => {
                const eventName = event === 'solicitud.created' 
                  ? 'Solicitud creada' 
                  : event === 'solicitud.updated' 
                    ? 'Solicitud actualizada' 
                    : 'Solicitud completada';
                return <span key={event} className="inline-block bg-gray-100 dark:bg-gray-700 rounded-full px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300 mr-2 mb-2">{eventName}</span>;
              })}
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
