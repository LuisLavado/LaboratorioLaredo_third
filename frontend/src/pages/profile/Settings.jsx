import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation } from '@tanstack/react-query';
import { useAuth } from '../../contexts/AuthContext';
import { CogIcon, MoonIcon, SunIcon, BellIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';
import api from '../../services/api';
import NotificationSettings from '../../components/settings/NotificationSettings';

export default function Settings() {
  const { user } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [darkMode, setDarkMode] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.theme === 'dark' ||
        (!('theme' in localStorage) &&
          window.matchMedia('(prefers-color-scheme: dark)').matches);
    }
    return false;
  });

  const { register, handleSubmit, reset, formState: { errors }, setError } = useForm();

  // Change password mutation
  const changePasswordMutation = useMutation(
    (passwordData) => api.post('/users/change-password', passwordData),
    {
      onSuccess: () => {
        toast.success('Contraseña actualizada con éxito');
        setIsSubmitting(false);
        reset();
      },
      onError: (error) => {
        console.error('Error changing password:', error);

        if (error.response?.data?.errors) {
          const serverErrors = error.response.data.errors;
          Object.keys(serverErrors).forEach(key => {
            setError(key, {
              type: 'server',
              message: serverErrors[key][0]
            });
          });
        } else {
          toast.error(error.response?.data?.message || 'Error al cambiar contraseña');
        }

        setIsSubmitting(false);
      }
    }
  );

  // Handle form submission
  const onSubmit = (data) => {
    if (data.new_password !== data.password_confirmation) {
      setError('password_confirmation', {
        type: 'manual',
        message: 'Las contraseñas no coinciden'
      });
      return;
    }

    setIsSubmitting(true);
    changePasswordMutation.mutate({
      current_password: data.current_password,
      password: data.new_password,
      password_confirmation: data.password_confirmation
    });
  };

  // Toggle dark mode
  const toggleDarkMode = () => {
    const newDarkMode = !darkMode;
    setDarkMode(newDarkMode);

    if (newDarkMode) {
      document.documentElement.classList.add('dark');
      localStorage.theme = 'dark';
    } else {
      document.documentElement.classList.remove('dark');
      localStorage.theme = 'light';
    }

    toast.success(`Modo ${newDarkMode ? 'oscuro' : 'claro'} activado`);
  };

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Configuración</h1>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Administra las preferencias de tu cuenta
        </p>
      </div>

      <div className="space-y-6">
        {/* Notification Settings */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 flex items-center">
            <div className="flex-shrink-0 h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
              <BellIcon className="h-8 w-8 text-primary-600 dark:text-primary-400" aria-hidden="true" />
            </div>
            <div className="ml-4">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Notificaciones
              </h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Configura las notificaciones en tiempo real
              </p>
            </div>
          </div>

          <div className="border-t border-gray-200 dark:border-gray-700">
            <NotificationSettings />
          </div>
        </div>

        {/* Appearance Settings */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 flex items-center">
            <div className="flex-shrink-0 h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
              {darkMode ? (
                <MoonIcon className="h-8 w-8 text-primary-600 dark:text-primary-400" aria-hidden="true" />
              ) : (
                <SunIcon className="h-8 w-8 text-primary-600 dark:text-primary-400" aria-hidden="true" />
              )}
            </div>
            <div className="ml-4">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Apariencia
              </h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Personaliza la apariencia de la aplicación
              </p>
            </div>
          </div>

          <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-sm font-medium text-gray-900 dark:text-white">Modo Oscuro</h4>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Cambia entre modo claro y oscuro
                </p>
              </div>
              <button
                type="button"
                onClick={toggleDarkMode}
                className={`${
                  darkMode ? 'bg-primary-600' : 'bg-gray-200'
                } relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500`}
              >
                <span className="sr-only">Usar modo oscuro</span>
                <span
                  className={`${
                    darkMode ? 'translate-x-5' : 'translate-x-0'
                  } pointer-events-none relative inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200`}
                >
                  <span
                    className={`${
                      darkMode ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200'
                    } absolute inset-0 h-full w-full flex items-center justify-center transition-opacity`}
                    aria-hidden="true"
                  >
                    <SunIcon className="h-3 w-3 text-gray-400" />
                  </span>
                  <span
                    className={`${
                      darkMode ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100'
                    } absolute inset-0 h-full w-full flex items-center justify-center transition-opacity`}
                    aria-hidden="true"
                  >
                    <MoonIcon className="h-3 w-3 text-primary-600" />
                  </span>
                </span>
              </button>
            </div>
          </div>
        </div>



        {/* Security Settings */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 flex items-center">
            <div className="flex-shrink-0 h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
              <CogIcon className="h-8 w-8 text-primary-600 dark:text-primary-400" aria-hidden="true" />
            </div>
            <div className="ml-4">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Seguridad
              </h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Administra la seguridad de tu cuenta
              </p>
            </div>
          </div>

          <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
              <div>
                <label htmlFor="current_password" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Contraseña Actual
                </label>
                <div className="mt-1">
                  <input
                    type="password"
                    id="current_password"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md"
                    {...register('current_password', { required: 'La contraseña actual es requerida' })}
                  />
                  {errors.current_password && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.current_password.message}</p>
                  )}
                </div>
              </div>

              <div>
                <label htmlFor="new_password" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Nueva Contraseña
                </label>
                <div className="mt-1">
                  <input
                    type="password"
                    id="new_password"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md"
                    {...register('new_password', {
                      required: 'La nueva contraseña es requerida',
                      minLength: {
                        value: 8,
                        message: 'La contraseña debe tener al menos 8 caracteres'
                      }
                    })}
                  />
                  {errors.new_password && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.new_password.message}</p>
                  )}
                </div>
              </div>

              <div>
                <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Confirmar Contraseña
                </label>
                <div className="mt-1">
                  <input
                    type="password"
                    id="password_confirmation"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md"
                    {...register('password_confirmation', {
                      required: 'La confirmación de contraseña es requerida'
                    })}
                  />
                  {errors.password_confirmation && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.password_confirmation.message}</p>
                  )}
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isSubmitting ? 'Cambiando...' : 'Cambiar Contraseña'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
