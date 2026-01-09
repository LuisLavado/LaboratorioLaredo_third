import { useState, useEffect } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { patientsAPI, dniAPI } from '../../services/api';
import { ArrowLeftIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export default function NewPatient() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSearchingDni, setIsSearchingDni] = useState(false);
  const location = useLocation();

  // Obtener datos del paciente si vienen de la búsqueda por DNI
  const patientDataFromSearch = location.state?.patientData;

  const { register, handleSubmit, setValue, watch, formState: { errors } } = useForm({
    defaultValues: {
      nombres: patientDataFromSearch?.nombres || '',
      apellidos: patientDataFromSearch?.apellidos || '',
      dni: patientDataFromSearch?.dni || '',
      fecha_nacimiento: patientDataFromSearch?.fecha_nacimiento || '',
      sexo: patientDataFromSearch?.sexo || '',
      celular: '',
      historia_clinica: '',
      edad_gestacional: ''
    }
  });

  // Efecto para mostrar mensaje si hay datos precargados
  useEffect(() => {
    if (patientDataFromSearch) {
      toast.success('Datos del paciente cargados desde RENIEC');
    }
  }, [patientDataFromSearch]);

  // Observar el campo de sexo para mostrar/ocultar Edad Gestacional
  const selectedSex = watch('sexo');

  // Create mutation
  const createPatientMutation = useMutation(
    (patientData) => patientsAPI.create(patientData),
    {
      onSuccess: (response) => {
        // Invalidate and refetch patients list
        queryClient.invalidateQueries(['patients']);

        // Obtener el ID del paciente creado
        const patientId = response.data.paciente.id;

        // Mostrar mensaje de éxito
        toast.success('Paciente creado con éxito');

        // Navegar a la página de detalles del paciente
        navigate(`/doctor/pacientes/${patientId}`);
      },
      onError: (error) => {
        console.error('Error creating patient:', error);

        // Manejar diferentes tipos de errores
        if (error.response?.status === 422) {
          // Error de validación
          const validationErrors = error.response.data.errors;
          if (validationErrors) {
            // Mostrar cada error de validación como un toast
            Object.keys(validationErrors).forEach(field => {
              const errorMessage = validationErrors[field][0];
              toast.error(`Error en ${field}: ${errorMessage}`);
            });
          } else {
            toast.error('Error de validación en el formulario');
          }
        } else if (error.response?.status === 409) {
          // Conflicto (por ejemplo, DNI duplicado)
          toast.error('Ya existe un paciente con este DNI');
        } else if (error.response?.status === 500) {
          // Error del servidor
          toast.error('Error en el servidor. Por favor, inténtelo más tarde');
        } else {
          // Otros errores
          toast.error(error.response?.data?.message || 'Error al crear el paciente');
        }

        setIsSubmitting(false);
      }
    }
  );

  // Consultar DNI en RENIEC
  const handleDniSearch = async () => {
    const dni = watch('dni');

    if (!dni || dni.length !== 8) {
      toast.error('Ingrese un DNI válido de 8 dígitos');
      return;
    }

    setIsSearchingDni(true);

    try {
      const response = await dniAPI.consult(dni);

      if (response.data.success) {
        const personData = response.data.data;

        // Autocompletar campos
        setValue('nombres', personData.nombres);
        setValue('apellidos', `${personData.apellidos}`);

        // Si hay fecha de nacimiento, formatearla correctamente
        if (personData.fecha_nacimiento) {
          setValue('fecha_nacimiento', personData.fecha_nacimiento);
        }

        // Si hay sexo, establecerlo
        if (personData.sexo) {
          setValue('sexo', personData.sexo.toLowerCase() === 'masculino' ? 'masculino' : 'femenino');
        }

        toast.success('Datos obtenidos correctamente');
      } else {
        toast.error('No se encontraron datos para este DNI');
      }
    } catch (error) {
      console.error('Error al consultar DNI:', error);
      toast.error('Error al consultar el DNI');
    } finally {
      setIsSearchingDni(false);
    }
  };

  const onSubmit = (data) => {
    setIsSubmitting(true);
    // Usar el DNI como historia clínica
    const patientData = {
      ...data,
      historia_clinica: data.dni
    };
    createPatientMutation.mutate(patientData);
  };

  return (
    <div>
      <div className="mb-6">
        <div className="flex items-center">
          <Link
            to="/doctor/pacientes"
            className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
          </Link>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
            Nuevo Paciente
          </h1>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-3">
                <label htmlFor="dni" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  DNI
                </label>
                <div className="mt-1 relative">
                  <input
                    type="text"
                    id="dni"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                    {...register('dni', {
                      required: 'El DNI es requerido',
                      pattern: {
                        value: /^[0-9]{8}$/,
                        message: 'El DNI debe tener 8 dígitos numéricos'
                      }
                    })}
                  />
                  {errors.dni && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.dni.message}</p>
                  )}
                  <div className="absolute inset-y-0 right-0 flex items-center">
                    <button
                      type="button"
                      onClick={handleDniSearch}
                      disabled={isSearchingDni}
                      className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-r-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 h-12"
                    >
                      {isSearchingDni ? (
                        <div className="animate-spin h-5 w-5 border-t-2 border-b-2 border-white rounded-full"></div>
                      ) : (
                        <MagnifyingGlassIcon className="h-5 w-5" aria-hidden="true" />
                      )}
                    </button>
                  </div>
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="nombres" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Nombres
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="nombres"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                    {...register('nombres', {
                      required: 'Los nombres son requeridos',
                      maxLength: {
                        value: 255,
                        message: 'Los nombres no pueden tener más de 255 caracteres'
                      }
                    })}
                  />
                  {errors.nombres && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.nombres.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="apellidos" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Apellidos
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="apellidos"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                    {...register('apellidos', {
                      required: 'Los apellidos son requeridos',
                      maxLength: {
                        value: 255,
                        message: 'Los apellidos no pueden tener más de 255 caracteres'
                      }
                    })}
                  />
                  {errors.apellidos && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.apellidos.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="fecha_nacimiento" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Fecha de Nacimiento
                </label>
                <div className="mt-1">
                  <input
                    type="date"
                    id="fecha_nacimiento"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                    {...register('fecha_nacimiento')}
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
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                    {...register('sexo')}
                  >
                    <option value="">Seleccionar</option>
                    <option value="masculino">Masculino</option>
                    <option value="femenino">Femenino</option>
                  </select>
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="celular" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Celular
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="celular"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                    {...register('celular', {
                      pattern: {
                        value: /^[0-9]{9}$/,
                        message: 'Si ingresa un celular, debe tener 9 dígitos numéricos'
                      },
                      required: false
                    })}
                  />
                  {errors.celular && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.celular.message}</p>
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
                      className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
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
                to="/doctor/pacientes"
                className="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                disabled={isSubmitting}
                className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                {isSubmitting ? 'Guardando...' : 'Guardar'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
