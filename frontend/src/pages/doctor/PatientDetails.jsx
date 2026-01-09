import { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { patientsAPI, requestsAPI } from '../../services/api';
import { ArrowLeftIcon, PencilIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

// Función para calcular la edad a partir de la fecha de nacimiento
const calculateAge = (birthDate) => {
  if (!birthDate) return 'No registrada';

  try {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }

    return age >= 0 ? age.toString() : 'N/A';
  } catch (error) {
    console.error('Error al calcular edad:', error);
    return 'N/A';
  }
};

// Función para determinar el estado de una solicitud
const getRequestStatus = (request) => {
  // Priorizar el campo estado si existe
  if (request.estado) {
    return request.estado;
  }

  // Usar estado_calculado si existe
  if (request.estado_calculado) {
    return request.estado_calculado;
  }

  // Si tiene detalles, calcular el estado
  if (request.detalles && request.detalles.length > 0) {
    const completados = request.detalles.filter(d => d.estado === 'completado').length;
    const enProceso = request.detalles.filter(d => d.estado === 'en_proceso').length;

    if (completados === request.detalles.length) {
      return 'completado';
    } else if (enProceso > 0 || completados > 0) {
      return 'en_proceso';
    }
  }

  // Por defecto, pendiente
  return 'pendiente';
};

export default function PatientDetails() {
  const { id } = useParams();
  const navigate = useNavigate();

  // Estilos comunes
  const infoStyle = "mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2";

  // Fetch patient details
  const {
    data: patientData,
    isLoading: patientLoading,
    error: patientError
  } = useQuery(['patient', id], () =>
    patientsAPI.getById(id).then(res => res.data)
  );

  // Extract patient from the response
  const patient = patientData?.paciente;

  // Log para depuración
  console.log('Patient data:', patient);

  // Fetch patient's requests with status
  const {
    data: requests,
    isLoading: requestsLoading,
    error: requestsError
  } = useQuery(['patientRequests', id], async () => {
    try {
      // Intentar obtener las solicitudes con estado calculado
      const response = await requestsAPI.getAllWithStatus();
      return response.data.filter(req => req.paciente_id === parseInt(id));
    } catch (error) {
      console.error('Error al obtener solicitudes con estado:', error);
      // Fallback: obtener solicitudes normales
      const response = await requestsAPI.getAll();
      return response.data.filter(req => req.paciente_id === parseInt(id));
    }
  });

  if (patientLoading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (patientError) {
    return (
      <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
        <div className="flex">
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
              Error al cargar datos del paciente
            </h3>
            <div className="mt-2 text-sm text-red-700 dark:text-red-300">
              <p>
                {patientError.message || 'Ha ocurrido un error. Por favor intente nuevamente.'}
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
            <button
              type="button"
              onClick={() => navigate('/doctor/pacientes')}
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </button>
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
              Detalles del Paciente
            </h1>
          </div>
          <div className="flex space-x-3">
            <Link
              to={`/doctor/solicitudes/nueva?paciente=${id}`}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <DocumentTextIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
              Nueva Solicitud
            </Link>
            <Link
              to={`/doctor/pacientes/${id}/editar`}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <PencilIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
              Editar Paciente
            </Link>
          </div>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Información del Paciente
          </h3>
          <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Detalles personales y médicos
          </p>
        </div>
        <div className="border-t border-gray-200 dark:border-gray-700">
          <dl>
            <div className="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                Nombre completo
              </dt>
              <dd className={infoStyle}>
                {patient.nombres} {patient.apellidos}
              </dd>
            </div>
            <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                DNI
              </dt>
              <dd className={infoStyle}>
                {patient.dni}
              </dd>
            </div>
            <div className="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                Historia Clínica
              </dt>
              <dd className={infoStyle}>
                {patient.historia_clinica || 'No registrada'}
              </dd>
            </div>
            <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                Fecha de Nacimiento
              </dt>
              <dd className={infoStyle}>
                {patient.fecha_nacimiento ? (() => {
                  try {
                    // Intentar formatear la fecha
                    return format(new Date(patient.fecha_nacimiento), 'dd/MM/yyyy', { locale: es });
                  } catch (e) {
                    console.error('Error al formatear fecha de nacimiento:', e);
                    return patient.fecha_nacimiento;
                  }
                })() : 'No registrada'}
              </dd>
            </div>
            <div className="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                Edad
              </dt>
              <dd className={infoStyle}>
                {calculateAge(patient.fecha_nacimiento)} años
              </dd>
            </div>
            <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                Sexo
              </dt>
              <dd className={infoStyle}>
                {patient.sexo === 'masculino' ? 'Masculino' : patient.sexo === 'femenino' ? 'Femenino' : 'No especificado'}
              </dd>
            </div>
            <div className="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                Celular
              </dt>
              <dd className={infoStyle}>
                {patient.celular || 'No registrado'}
              </dd>
            </div>
            {patient.sexo === 'femenino' && patient.edad_gestacional && (
              <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                  Edad Gestacional
                </dt>
                <dd className={infoStyle}>
                  {patient.edad_gestacional} semanas
                </dd>
              </div>
            )}
          </dl>
        </div>
      </div>

      <div className="mt-8">
        <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
          Solicitudes de Exámenes
        </h3>

        {requestsLoading ? (
          <div className="flex justify-center py-8">
            <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
          </div>
        ) : requestsError ? (
          <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
            <div className="flex">
              <div className="ml-3">
                <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
                  Error al cargar solicitudes
                </h3>
                <div className="mt-2 text-sm text-red-700 dark:text-red-300">
                  <p>
                    {requestsError.message || 'Ha ocurrido un error. Por favor intente nuevamente.'}
                  </p>
                </div>
              </div>
            </div>
          </div>
        ) : requests?.length > 0 ? (
          // Log para depuración
          console.log('Requests with status:', requests),
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
            <ul className="divide-y divide-gray-200 dark:divide-gray-700">
              {requests.map((request) => (
                <li key={request.id}>
                  <Link to={`/doctor/solicitudes/${request.id}`} className="block hover:bg-gray-50 dark:hover:bg-gray-700">
                    <div className="px-4 py-4 sm:px-6">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-primary-600 dark:text-primary-400 truncate">
                          Solicitud #{request.id}
                        </p>
                        <div className="ml-2 flex-shrink-0 flex">
                          {(() => {
                            // Obtener el estado usando la función
                            const status = getRequestStatus(request);

                            // Determinar las clases de estilo basadas en el estado
                            let bgColorClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                            let statusText = 'Pendiente';

                            if (status === 'completado') {
                              bgColorClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                              statusText = 'Completado';
                            } else if (status === 'en_proceso') {
                              bgColorClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
                              statusText = 'En proceso';
                            }

                            return (
                              <p className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${bgColorClass}`}>
                                {statusText}
                              </p>
                            );
                          })()}
                        </div>
                      </div>
                      <div className="mt-2 sm:flex sm:justify-between">
                        <div className="sm:flex">
                          <p className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            Fecha: {(() => {
                              try {
                                return format(new Date(request.fecha), 'dd/MM/yyyy', { locale: es });
                              } catch (e) {
                                console.error('Error al formatear fecha de solicitud:', e);
                                return 'Fecha no disponible';
                              }
                            })()}
                          </p>
                          <p className="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:mt-0 sm:ml-6">
                            Servicio: {request.servicio?.nombre || 'No especificado'}
                          </p>
                        </div>
                      </div>
                    </div>
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        ) : (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
            <div className="px-4 py-5 sm:p-6 text-center">
              <p className="text-sm text-gray-500 dark:text-gray-400">
                No hay solicitudes registradas para este paciente
              </p>
              <div className="mt-4">
                <Link
                  to={`/doctor/solicitudes/nueva?paciente=${id}`}
                  className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  Crear nueva solicitud
                </Link>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
