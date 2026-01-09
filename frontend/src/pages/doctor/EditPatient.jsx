import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { patientsAPI } from '../../services/api';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export default function EditPatient() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [patient, setPatient] = useState(null);

  // Initialize form with default values
  const { register, handleSubmit, reset, watch, formState: { errors } } = useForm({
    defaultValues: {
      dni: '',
      nombres: '',
      apellidos: '',
      fecha_nacimiento: '',
      celular: '',
      historia_clinica: '',
      sexo: '',
      edad_gestacional: ''
    }
  });

  // Watch the sexo field to conditionally show/hide edad_gestacional
  const selectedSex = watch('sexo');

  // Fetch patient data
  const { data: patientData, isLoading, error } = useQuery(
    ['patient', id],
    () => patientsAPI.getById(id).then(res => res.data),
    {
      staleTime: 0,
      cacheTime: 0,
      refetchOnWindowFocus: false,
    }
  );

  // Set form values when patient data is loaded
  useEffect(() => {
    if (patientData?.paciente) {
      const patientInfo = patientData.paciente;
      setPatient(patientInfo);

      // Format date for the date input (YYYY-MM-DD)
      let formattedDate = '';
      if (patientInfo.fecha_nacimiento) {
        try {
          const date = new Date(patientInfo.fecha_nacimiento);
          formattedDate = date.toISOString().split('T')[0];
        } catch (error) {
          console.error('Error formatting date:', error);
        }
      }

      // Reset form with patient data
      reset({
        dni: patientInfo.dni || '',
        nombres: patientInfo.nombres || '',
        apellidos: patientInfo.apellidos || '',
        fecha_nacimiento: formattedDate,
        celular: patientInfo.celular || '',
        historia_clinica: patientInfo.historia_clinica || '',
        sexo: patientInfo.sexo || '',
        edad_gestacional: patientInfo.edad_gestacional || ''
      });
    }
  }, [patientData, reset]);

  // Update patient mutation
  const updatePatientMutation = useMutation(
    (patientData) => patientsAPI.update(id, patientData),
    {
      onSuccess: () => {
        toast.success('Paciente actualizado con éxito');
        queryClient.invalidateQueries(['patient', id]);
        queryClient.invalidateQueries(['patients']);
        setIsSubmitting(false);
        navigate(`/doctor/pacientes/${id}`);
      },
      onError: (error) => {
        console.error('Error updating patient:', error);
        toast.error(error.response?.data?.message || 'Error al actualizar paciente');
        setIsSubmitting(false);
      }
    }
  );

  // Handle form submission
  const onSubmit = (data) => {
    setIsSubmitting(true);
    // Usar el DNI como historia clínica
    const patientData = {
      ...data,
      historia_clinica: data.dni
    };
    updatePatientMutation.mutate(patientData);
  };

  if (isLoading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
        <div className="flex">
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
              Error al cargar datos del paciente
            </h3>
            <div className="mt-2 text-sm text-red-700 dark:text-red-300">
              <p>
                {error.message || 'Ha ocurrido un error. Por favor intente nuevamente.'}
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <Link
              to={`/doctor/pacientes/${id}`}
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </Link>
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
              Editar Paciente
            </h1>
          </div>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-2">
                <label htmlFor="dni" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  DNI
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="dni"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3 border"
                    {...register('dni', { required: 'El DNI es requerido' })}
                  />
                  {errors.dni && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.dni.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="nombres" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Nombres
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="nombres"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3 border"
                    {...register('nombres', { required: 'Los nombres son requeridos' })}
                  />
                  {errors.nombres && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.nombres.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="apellidos" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Apellidos
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="apellidos"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3 border"
                    {...register('apellidos', { required: 'Los apellidos son requeridos' })}
                  />
                  {errors.apellidos && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.apellidos.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="fecha_nacimiento" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Fecha de Nacimiento
                </label>
                <div className="mt-1">
                  <input
                    type="date"
                    id="fecha_nacimiento"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3 border"
                    {...register('fecha_nacimiento')}
                  />
                </div>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="celular" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Celular
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="celular"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3 border"
                    {...register('celular')}
                  />
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="sexo" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Sexo
                </label>
                <div className="mt-1">
                  <select
                    id="sexo"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3 border"
                    {...register('sexo', { required: 'El sexo es requerido' })}
                  >
                    <option value="">Seleccionar</option>
                    <option value="masculino">Masculino</option>
                    <option value="femenino">Femenino</option>
                  </select>
                  {errors.sexo && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.sexo.message}</p>
                  )}
                </div>
              </div>

              {selectedSex === 'femenino' && (
                <div className="sm:col-span-3">
                  <label htmlFor="edad_gestacional" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Edad Gestacional (semanas)
                  </label>
                  <div className="mt-1">
                    <input
                      type="number"
                      id="edad_gestacional"
                      className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3 border"
                      {...register('edad_gestacional', {
                        min: {
                          value: 0,
                          message: 'La edad gestacional no puede ser negativa'
                        },
                        max: {
                          value: 45,
                          message: 'La edad gestacional no puede ser mayor a 45 semanas'
                        }
                      })}
                    />
                    {errors.edad_gestacional && (
                      <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.edad_gestacional.message}</p>
                    )}
                  </div>
                </div>
              )}
            </div>

            <div className="flex justify-end space-x-3">
              <Link
                to={`/doctor/pacientes/${id}`}
                className="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-700 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                disabled={isSubmitting}
                className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
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
