import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useAuth } from '../../contexts/AuthContext';
import { useMutation } from '@tanstack/react-query';
import { UserIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function Profile() {
  const { user, updateUser } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { register, handleSubmit, reset, formState: { errors } } = useForm();

  // Set form values when user data is available
  useEffect(() => {
    if (user) {
      reset({
        nombre: user.nombre || '',
        email: user.email || '',
        apellido: user.apellido || '',
        especialidad: user.especialidad || '',
        colegiatura: user.colegiatura || '',
      });
    }
  }, [user, reset]);

  // Update profile mutation
  const updateProfileMutation = useMutation(
    (userData) => api.put('/users/' + user.id, userData),
    {
      onSuccess: (response) => {
        const updatedUser = response.data.user || response.data;
        updateUser(updatedUser);
        toast.success('Perfil actualizado con éxito');
        setIsSubmitting(false);
      },
      onError: (error) => {
        console.error('Error updating profile:', error);
        toast.error(error.response?.data?.message || 'Error al actualizar perfil');
        setIsSubmitting(false);
      }
    }
  );

  // Handle form submission
  const onSubmit = (data) => {
    setIsSubmitting(true);
    updateProfileMutation.mutate(data);
  };

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Mi Perfil</h1>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Administra tu información personal
        </p>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6 flex items-center">
          <div className="flex-shrink-0 h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
            <UserIcon className="h-8 w-8 text-primary-600 dark:text-primary-400" aria-hidden="true" />
          </div>
          <div className="ml-4">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              Información Personal
            </h3>
            <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
              Detalles y configuración de tu cuenta
            </p>
          </div>
        </div>

        <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-3">
                <label htmlFor="nombre" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Nombre
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="nombre"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md"
                    {...register('nombre', { required: 'El nombre es requerido' })}
                  />
                  {errors.nombre && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.nombre.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="apellido" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Apellido
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="apellido"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md"
                    {...register('apellido', { required: 'El apellido es requerido' })}
                  />
                  {errors.apellido && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.apellido.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-6">
                <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Correo Electrónico
                </label>
                <div className="mt-1">
                  <input
                    type="email"
                    id="email"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md"
                    {...register('email', {
                      required: 'El correo electrónico es requerido',
                      pattern: {
                        value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                        message: 'Dirección de correo inválida'
                      }
                    })}
                  />
                  {errors.email && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.email.message}</p>
                  )}
                </div>
              </div>



              {user?.role === 'doctor' && (
                <>
                  <div className="sm:col-span-3">
                    <label htmlFor="especialidad" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Especialidad
                    </label>
                    <div className="mt-1">
                      <select
                        id="especialidad"
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md"
                        {...register('especialidad', { required: 'La especialidad es requerida' })}
                      >
                        <option value="">Seleccione una especialidad</option>
                        <option value="MEDICINA">MEDICINA</option>
                        <option value="PEDIATRÍA">PEDIATRÍA</option>
                        <option value="GINECOLOGÍA">GINECOLOGÍA</option>
                        <option value="EMERGENCIA">EMERGENCIA</option>
                        <option value="TRAUMATOLOGÍA">TRAUMATOLOGÍA</option>
                        <option value="OFTALMOLOGÍA">OFTALMOLOGÍA</option>
                        <option value="DERMATOLOGÍA">DERMATOLOGÍA</option>
                        <option value="CARDIOLOGÍA">CARDIOLOGÍA</option>
                        <option value="NEUROLOGÍA">NEUROLOGÍA</option>
                        <option value="PSIQUIATRÍA">PSIQUIATRÍA</option>
                        <option value="NUTRICIÓN">NUTRICIÓN</option>
                        <option value="ODONTOLOGÍA">ODONTOLOGÍA</option>
                      </select>
                      {errors.especialidad && (
                        <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.especialidad.message}</p>
                      )}
                    </div>
                  </div>

                  <div className="sm:col-span-3">
                    <label htmlFor="colegiatura" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Número de Colegiatura
                    </label>
                    <div className="mt-1">
                      <input
                        type="text"
                        id="colegiatura"
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md"
                        {...register('colegiatura', { required: 'El número de colegiatura es requerido' })}
                      />
                      {errors.colegiatura && (
                        <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.colegiatura.message}</p>
                      )}
                    </div>
                  </div>
                </>
              )}
            </div>

            <div className="flex justify-end">
              <button
                type="submit"
                disabled={isSubmitting}
                className="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? 'Guardando...' : 'Guardar Cambios'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
