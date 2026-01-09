import { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { requestsAPI, requestDetailsAPI } from '../../services/api';
import { ArrowLeftIcon, PrinterIcon, DocumentTextIcon, BeakerIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import toast from 'react-hot-toast';

// Status badge component
function StatusBadge({ status }) {
  let bgColor, textColor, label;

  switch (status) {
    case 'completado':
      bgColor = 'bg-green-100 dark:bg-green-900/30';
      textColor = 'text-green-800 dark:text-green-300';
      label = 'Completado';
      break;
    case 'en_proceso':
      bgColor = 'bg-blue-100 dark:bg-blue-900/30';
      textColor = 'text-blue-800 dark:text-blue-300';
      label = 'En Proceso';
      break;
    default:
      bgColor = 'bg-amber-100 dark:bg-amber-900/30';
      textColor = 'text-amber-800 dark:text-amber-300';
      label = 'Pendiente';
  }

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${bgColor} ${textColor}`}>
      {label}
    </span>
  );
}

export default function RequestResults() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('results');

  // Enhanced back navigation handler that invalidates doctor dashboard cache
  const handleBackNavigation = () => {
    console.log('Navigating back from doctor results - invalidating cache');
    
    // Invalidate doctor dashboard-related queries to ensure fresh data
    queryClient.invalidateQueries(['patients']);
    queryClient.invalidateQueries(['doctor-requests']);
    
    // Use navigate(-1) to go back
    navigate(-1);
  };

  // Fetch request details (exams and results) - esto incluye toda la información necesaria
  const {
    data: requestDetailsResponse,
    isLoading: detailsLoading,
    error: detailsError
  } = useQuery(
    ['requestDetails', id],
    () => requestDetailsAPI.getByRequest(id).then(res => res.data),
    {
      refetchOnWindowFocus: false,
    }
  );

  // Extraer la información de la solicitud del primer detalle
  const request = requestDetailsResponse?.data?.[0]?.solicitud || null;

  // Extraer los detalles de la respuesta
  const requestDetails = requestDetailsResponse?.data || [];

  // Verificar si hay datos y mostrar mensaje en consola
  if (requestDetailsResponse) {
    console.log('Datos recibidos correctamente');
  } else {
    console.log('No se han recibido datos de detalles');
  }

  // Efecto para mostrar un mensaje cuando los datos se cargan
  useEffect(() => {
    if (requestDetailsResponse) {
      console.log('Detalles cargados:', requestDetailsResponse);
      if (requestDetailsResponse.data && requestDetailsResponse.data.length === 0) {
        toast.info('Esta solicitud aún no tiene resultados registrados');
      }
    }
  }, [requestDetailsResponse]);

  // Generate QR code
  const {
    data: qrData,
    isLoading: qrLoading,
    error: qrError
  } = useQuery(
    ['qr', id],
    () => requestsAPI.generateQr(id).then(res => {
      console.log('QR response:', res.data);
      return res.data;
    }),
    {
      refetchOnWindowFocus: false,
      enabled: !!request, // Only fetch QR if request data is available
    }
  );

  const isLoading = detailsLoading;
  const error = detailsError;

  // Logs para depuración
  console.log('Request data:', request);
  console.log('Request details response:', requestDetailsResponse);
  console.log('Request details:', requestDetails);

  // Verificar si hay resultados disponibles
  const hasResults = Array.isArray(requestDetails) && requestDetails.length > 0;

  // Calculate overall status
  const calculateStatus = () => {
    // Verificar si requestDetails es un array
    if (!requestDetails || !Array.isArray(requestDetails) || requestDetails.length === 0) {
      return 'pendiente';
    }

    const completados = requestDetails.filter(d => d.estado === 'completado').length;
    const enProceso = requestDetails.filter(d => d.estado === 'en_proceso').length;

    if (completados === requestDetails.length) {
      return 'completado';
    } else if (enProceso > 0 || completados > 0) {
      return 'en_proceso';
    } else {
      return 'pendiente';
    }
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900 rounded-md p-4 my-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800 dark:text-red-200">Error al cargar los datos de la solicitud</h3>
            <div className="mt-2 text-sm text-red-700 dark:text-red-300">
              <p>{error.message || 'Ha ocurrido un error inesperado'}</p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const status = calculateStatus();

  return (
    <div>
      {/* Header */}
      <div className="sm:flex sm:items-center mb-6">
        <div className="sm:flex-auto">
          <div className="flex items-center">
            <button
              type="button"
              onClick={handleBackNavigation}
              className="mr-4 rounded-full p-1 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none"
            >
              <ArrowLeftIcon className="h-6 w-6" aria-hidden="true" />
            </button>
            <div>
              <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
                Resultados de Solicitud #{request.id}
              </h1>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {request.fecha && format(new Date(request.fecha), 'PPP', { locale: es })} - {request.paciente?.nombres} {request.paciente?.apellidos}
              </p>
            </div>
          </div>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex space-x-3">
          <Link
            to={`/doctor/solicitudes/${id}/imprimir`}
            className="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
          >
            <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Imprimir Solicitud
          </Link>
          {status === 'completado' && (
            <>
              <Link
                to={`/doctor/resultados/${id}/ver`}
                className="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:w-auto"
              >
                <BeakerIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                Ver Resultados
              </Link>
              <Link
                to={`/doctor/solicitudes/${id}/imprimir-resultados`}
                state={{ from: `/doctor/solicitudes/${id}/resultados` }}
                className="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
              >
                <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                Imprimir Resultados
              </Link>
            </>
          )}
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav className="-mb-px flex space-x-8" aria-label="Tabs">
          <button
            onClick={() => setActiveTab('results')}
            className={`${
              activeTab === 'results'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
          >
            Resultados
          </button>

          <button
            onClick={() => setActiveTab('qr')}
            className={`${
              activeTab === 'qr'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
          >
            Código QR
          </button>
        </nav>
      </div>

      {/* Content based on active tab */}
      {activeTab === 'results' && (
        <div>
          {/* Resumen de resultados */}
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
            <div className="px-4 py-5 sm:px-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Resultados de Exámenes
              </h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Estado general: <StatusBadge status={status} />
              </p>
            </div>
            <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
              <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                <h4 className="text-base font-medium text-gray-900 dark:text-white mb-3">
                  Resumen de Resultados
                </h4>
                {hasResults ? (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {/* Mapear los detalles */}
                    {requestDetails.map((detail) => (
                      <div
                        key={detail.id}
                        className={`border rounded-md p-4 ${
                          detail.estado === 'completado'
                            ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20'
                            : detail.estado === 'en_proceso'
                              ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20'
                              : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20'
                        }`}
                      >
                        <div className="flex justify-between items-start mb-2">
                          <h5 className="text-sm font-medium text-gray-900 dark:text-white">
                            {detail.examen?.nombre}
                          </h5>
                          <StatusBadge status={detail.estado} />
                        </div>
                        <div className="text-sm text-gray-700 dark:text-gray-300">
                          <p className="font-medium">Resultado: {detail.estado === 'completado' ? 'Disponible' : 'No disponible'}</p>
                          {detail.estado === 'completado' ? (
                            <div className="mt-2">
                              <Link
                                to={`/doctor/resultados/${request.id}/ver`}
                                className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                              >
                                <BeakerIcon className="h-4 w-4 mr-1" />
                                Ver Resultados Detallados
                              </Link>
                            </div>
                          ) : Array.isArray(detail.resultados) && detail.resultados.length > 0 ? (
                            <div className="mt-1">
                              {detail.resultados.map((resultado, index) => (
                                <p key={index} className="text-xs">
                                  <span className="font-medium">{resultado.nombre_parametro || 'Resultado'}:</span> {resultado.valor || 'N/A'} {resultado.unidad || ''}
                                  {resultado.referencia && <span className="text-gray-500 dark:text-gray-400"> (Ref: {resultado.referencia})</span>}
                                </p>
                              ))}
                            </div>
                          ) : detail.observaciones ? (
                            <p className="mt-1 text-xs">
                              <span className="font-medium">Observaciones:</span> {detail.observaciones.substring(0, 50)}{detail.observaciones && detail.observaciones.length > 50 ? '...' : ''}
                            </p>
                          ) : (
                            <p className="mt-1 text-xs italic">
                              {detail.estado === 'en_proceso'
                                ? 'Este examen está siendo procesado actualmente.'
                                : 'Este examen aún no ha sido procesado.'}
                            </p>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-gray-500 dark:text-gray-400">
                    No hay exámenes registrados para esta solicitud.
                  </p>
                )}
              </div>
            </div>
          </div>

          {/* Detalles completos de resultados */}
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
            <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
              <div>
                <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  Detalles Completos
                </h3>
                <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                  Información detallada de cada examen
                </p>
              </div>
            </div>
            <div className="border-t border-gray-200 dark:border-gray-700">
              {hasResults ? (
                <div className="divide-y divide-gray-200 dark:divide-gray-700">
                  {requestDetails.map((detail) => (
                    <div key={detail.id} className="px-4 py-5 sm:p-6">
                      <div className="flex justify-between items-start">
                        <div>
                          <h4 className="text-lg font-medium text-gray-900 dark:text-white">
                            {detail.examen?.nombre}
                          </h4>
                          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {detail.examen?.categoria?.nombre}
                          </p>
                        </div>
                        <StatusBadge status={detail.estado} />
                      </div>

                      {detail.estado === 'completado' ? (
                        <div className="mt-4">
                          <div className="bg-gray-50 dark:bg-gray-700 rounded-md p-4">
                            <div className="flex justify-between items-center mb-3">
                              <h5 className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Resultados Disponibles:
                              </h5>
                              <Link
                                to={`/doctor/resultados/${request.id}/ver`}
                                className="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20"
                              >
                                <BeakerIcon className="h-3 w-3 mr-1" />
                                Ver Detalles Completos
                              </Link>
                            </div>
                            {Array.isArray(detail.resultados) && detail.resultados.length > 0 ? (
                              <div className="space-y-2">
                                {detail.resultados.map((resultado, index) => (
                                  <div key={index} className="flex justify-between text-sm">
                                    <span className="font-medium">{resultado.nombre_parametro || 'Resultado'}:</span>
                                    <span>{resultado.valor || 'N/A'} {resultado.unidad || ''}</span>
                                    {resultado.referencia && <span className="text-gray-500 dark:text-gray-400">Ref: {resultado.referencia}</span>}
                                  </div>
                                ))}
                              </div>
                            ) : (
                              <div className="text-center py-2">
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                  Los resultados están disponibles en formato detallado
                                </p>
                                <Link
                                  to={`/doctor/resultados/${request.id}/ver`}
                                  className="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                  <BeakerIcon className="h-4 w-4 mr-1" />
                                  Ver Resultados Completos
                                </Link>
                              </div>
                            )}
                            {detail.fecha_resultado && (
                              <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Registrado el {format(new Date(detail.fecha_resultado), 'PPP p', { locale: es })}
                              </p>
                            )}
                          </div>
                        </div>
                      ) : (
                        <div className="mt-4">
                          <p className="text-sm text-gray-500 dark:text-gray-400 italic">
                            {detail.estado === 'en_proceso'
                              ? 'Este examen está siendo procesado actualmente.'
                              : 'Este examen aún no ha sido procesado.'}
                          </p>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              ) : (
                <div className="px-4 py-5 sm:p-6 text-center">
                  <p className="text-sm text-gray-500 dark:text-gray-400">
                    No hay exámenes registrados para esta solicitud.
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {activeTab === 'details' && (
        <div>
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
            <div className="px-4 py-5 sm:px-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Información de la Solicitud
              </h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Detalles completos de la solicitud de exámenes.
              </p>
            </div>
            <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-0">
              <dl className="sm:divide-y sm:divide-gray-200 sm:dark:divide-gray-700">
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Paciente
                  </dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    {request?.paciente ? `${request.paciente.nombres} ${request.paciente.apellidos}` : 'No disponible'}
                  </dd>
                </div>
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    DNI
                  </dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    {request?.paciente?.dni || 'No disponible'}
                  </dd>
                </div>
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Fecha
                  </dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    {request?.fecha ? (
                      (() => {
                        try {
                          return format(new Date(request.fecha), 'dd/MM/yyyy', { locale: es });
                        } catch (e) {
                          console.error('Error al formatear fecha:', e);
                          return request.fecha;
                        }
                      })()
                    ) : 'No disponible'}
                  </dd>
                </div>
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Hora
                  </dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    {request?.hora || 'No disponible'}
                  </dd>
                </div>
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Servicio
                  </dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    {request?.servicio?.nombre || 'No disponible'}
                  </dd>
                </div>
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Número de Recibo
                  </dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    {request?.numero_recibo || 'No disponible'}
                  </dd>
                </div>
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Estado
                  </dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    <StatusBadge status={status} />
                  </dd>
                </div>
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Exámenes
                  </dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    <ul className="border border-gray-200 dark:border-gray-700 rounded-md divide-y divide-gray-200 dark:divide-gray-700">
                      {hasResults ? (
                        // Mostrar detalles de la solicitud si están disponibles
                        requestDetails.map((detail) => (
                          <li key={detail.id} className="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                            <div className="w-0 flex-1 flex items-center">
                              <DocumentTextIcon className="flex-shrink-0 h-5 w-5 text-gray-400" aria-hidden="true" />
                              <span className="ml-2 flex-1 w-0 truncate">
                                {detail.examen?.nombre}
                              </span>
                            </div>
                            <div className="ml-4 flex-shrink-0">
                              <StatusBadge status={detail.estado} />
                            </div>
                          </li>
                        ))
                      ) : request.examenes?.length > 0 ? (
                        // Fallback: mostrar exámenes de la solicitud si no hay detalles
                        request.examenes.map((examen) => (
                          <li key={examen.id} className="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                            <div className="w-0 flex-1 flex items-center">
                              <DocumentTextIcon className="flex-shrink-0 h-5 w-5 text-gray-400" aria-hidden="true" />
                              <span className="ml-2 flex-1 w-0 truncate">
                                {examen.nombre}
                              </span>
                            </div>
                          </li>
                        ))
                      ) : (
                        // Mensaje si no hay exámenes
                        <li className="pl-3 pr-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                          No hay exámenes registrados para esta solicitud
                        </li>
                      )}
                    </ul>
                  </dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'qr' && (
        <div>
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
            <div className="px-4 py-5 sm:px-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Código QR
              </h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Escanee este código para acceder a los resultados.
              </p>
            </div>
            <div className="border-t border-gray-200 dark:border-gray-700">
              <div className="px-4 py-5 sm:p-6 flex justify-center">
                {qrLoading ? (
                  <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
                ) : qrError ? (
                  <div className="text-red-500 text-sm">
                    Error al generar el código QR
                  </div>
                ) : qrData ? (
                  <div className="text-center">
                    <img
                      src={qrData.qr_code}
                      alt="Código QR"
                      className="mx-auto h-64 w-64"
                    />
                    <p className="mt-4 text-sm text-gray-500 dark:text-gray-400">
                      Este código QR contiene información de la solicitud #{request.id}
                    </p>
                  </div>
                ) : (
                  <div className="text-gray-500 text-sm">
                    No se pudo generar el código QR
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
