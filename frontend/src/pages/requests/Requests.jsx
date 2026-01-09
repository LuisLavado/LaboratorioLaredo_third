import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { requestsAPI, servicesAPI, requestDetailsAPI } from '../../services/api';
import { PlusIcon, MagnifyingGlassIcon, FunnelIcon, PlayIcon, PauseIcon } from '@heroicons/react/24/outline';
import Pagination from '../../components/common/Pagination';
import toast from 'react-hot-toast';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import '../../styles/table-compact.css';

export default function Requests() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedService, setSelectedService] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 50;
  const queryClient = useQueryClient();

  // Mutation para actualizar el estado de un detalle de solicitud
  const updateStatusMutation = useMutation(
    (statusData) => {
      console.log('Actualizando estado:', statusData.id, statusData.estado);
      return requestDetailsAPI.updateStatus(statusData.id, statusData.estado);
    },
    {
      onSuccess: (response) => {
        console.log('Estado actualizado con éxito:', response);
        // Invalidar consultas para refrescar los datos
        queryClient.invalidateQueries(['requests']);
        toast.success('Estado actualizado correctamente');
      },
      onError: (error) => {
        console.error('Error al actualizar estado:', error);
        toast.error('Error al actualizar el estado');
      }
    }
  );

  // Estado para almacenar los detalles de las solicitudes
  const [requestDetails, setRequestDetails] = useState({});
  const [loadingDetails, setLoadingDetails] = useState({});

  // Fetch requests with pre-calculated status (más rápido)
  const { data: requests, isLoading: requestsLoading, error: requestsError } = useQuery(
    ['requests'],
    async () => {
      // Intentar usar el endpoint optimizado primero
      try {
        const res = await requestsAPI.getAllWithStatus();
        return res.data;
      } catch (error) {
        // Si falla, usar el endpoint normal como fallback
        console.error('Error al obtener solicitudes con estado:', error);
        const res = await requestsAPI.getAll();
        return res.data;
      }
    },
    {
      refetchInterval: false, // DESACTIVADO TEMPORALMENTE
      staleTime: 10000, // Considerar datos frescos por 10 segundos
      select: (data) => {
        // Procesar los datos para asegurarnos de que cada solicitud tenga un estado_calculado
        return data.map(request => {
          // Si ya tiene estado_calculado, usarlo
          if (request.estado_calculado) {
            return request;
          }

          // Si no tiene detalles, no podemos calcular el estado, así que lo marcamos como pendiente
          if (!request.detalles || request.detalles.length === 0) {
            return {
              ...request,
              estado_calculado: 'pendiente'
            };
          }

          // Calcular el estado basado en los detalles
          let estado = 'pendiente';
          const completados = request.detalles.filter(d => d.estado === 'completado').length;
          const enProceso = request.detalles.filter(d => d.estado === 'en_proceso').length;

          if (completados === request.detalles.length) {
            estado = 'completado';
          } else if (enProceso > 0 || completados > 0) {
            estado = 'en_proceso';
          }

          return {
            ...request,
            estado_calculado: estado
          };
        });
      }
    }
  );

  // Función para cargar los detalles de una solicitud solo cuando sea necesario
  const loadRequestDetails = async (requestId) => {
    // Si ya estamos cargando o ya tenemos los detalles, no hacer nada
    if (loadingDetails[requestId] || requestDetails[requestId]) {
      return;
    }

    // Marcar como cargando
    setLoadingDetails(prev => ({ ...prev, [requestId]: true }));

    try {
      const detailsRes = await requestDetailsAPI.getByRequest(requestId);
      // Guardar los detalles
      setRequestDetails(prev => ({
        ...prev,
        [requestId]: detailsRes.data.data
      }));
    } catch (error) {
      console.error(`Error al cargar detalles para solicitud ${requestId}:`, error);
      // En caso de error, guardar un array vacío
      setRequestDetails(prev => ({
        ...prev,
        [requestId]: []
      }));
    } finally {
      // Marcar como no cargando
      setLoadingDetails(prev => ({ ...prev, [requestId]: false }));
    }
  };

  // Fetch services
  const { data: servicesResponse, isLoading: servicesLoading } = useQuery(
    ['services'],
    () => servicesAPI.getAll().then(res => res.data)
  );

  // Extract services from the response
  const services = servicesResponse?.servicios || [];

  // Mutación para actualizar el estado de una solicitud
  const updateRequestStatusMutation = useMutation(
    (statusData) => {
      console.log('Actualizando estado de solicitud:', statusData.id, statusData.estado);
      return requestsAPI.updateStatus(statusData.id, statusData.estado);
    },
    {
      onSuccess: (response, variables) => {
        console.log('Estado de solicitud actualizado con éxito:', response);

        // Invalidar la consulta para recargar los datos
        queryClient.invalidateQueries(['requests']);

        // Mostrar mensaje de éxito
        toast.success(`Estado actualizado a ${variables.estado === 'en_proceso' ? 'En proceso' : 'Pendiente'}`);
      },
      onError: (error) => {
        console.error('Error al actualizar estado de solicitud:', error);
        toast.error('Error al actualizar el estado');
      }
    }
  );

  // Función para manejar el cambio de estado de una solicitud
  const handleRequestStatusChange = (request, e) => {
    e.preventDefault(); // Evitar que se siga el enlace
    e.stopPropagation(); // Evitar que se propague el evento

    // Determinar el estado actual
    const currentStatus = getRequestStatus(request);

    // Si el estado es pendiente, cambiarlo a en_proceso
    // Si el estado es en_proceso, cambiarlo a pendiente
    // Si el estado es completado, no hacer nada
    if (currentStatus === 'completado') {
      toast.info('No se puede cambiar el estado de una solicitud completada');
      return;
    }

    const newStatus = currentStatus === 'pendiente' ? 'en_proceso' : 'pendiente';

    // Actualizar el estado localmente primero para una respuesta más rápida
    if (request.estado_calculado) {
      request.estado_calculado = newStatus;
    }

    // Enviar la actualización al servidor
    updateRequestStatusMutation.mutate({
      id: request.id,
      estado: newStatus
    });
  };

  // Determinar el estado de una solicitud
  const getRequestStatus = (request) => {
    // Si la solicitud tiene un estado pre-calculado, usarlo
    if (request.estado_calculado) {
      return request.estado_calculado;
    }

    // Si no tiene estado_calculado, devolver pendiente por defecto
    // No cargaremos los detalles automáticamente para mejorar el rendimiento
    return 'pendiente';
  };

  // Filter and sort requests based on search term, selected service, and status
  const filteredRequests = requests?.filter(request => {
    const matchesSearch =
      request.paciente?.nombres?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.paciente?.apellidos?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.paciente?.dni?.includes(searchTerm) ||
      request.numero_recibo?.includes(searchTerm);

    const matchesService = selectedService ? request.servicio_id === parseInt(selectedService) : true;

    // Si no hay filtro de estado, mostrar todas las solicitudes
    if (!statusFilter) {
      return matchesSearch && matchesService;
    }

    // Determinar el estado de la solicitud usando el estado pre-calculado
    const status = getRequestStatus(request);

    // Filtrar por estado
    return matchesSearch && matchesService && status === statusFilter;
  })
  // Sort by created_at date (most recent first)
  .sort((a, b) => {
    const dateA = new Date(a.created_at || a.fecha || 0);
    const dateB = new Date(b.created_at || b.fecha || 0);
    return dateB - dateA;
  });

  // Calcular el número total de páginas
  const totalPages = Math.ceil((filteredRequests?.length || 0) / itemsPerPage);

  // Obtener las solicitudes para la página actual
  const paginatedRequests = filteredRequests?.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  return (
    <div>
      <div className="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Solicitudes</h1>
          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Gestión de solicitudes de exámenes
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <Link
            to="/solicitudes/nueva"
            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Nueva Solicitud
          </Link>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <div className="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 mb-4">
            <div className="flex-1">
              <label htmlFor="search" className="sr-only">Buscar</label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                </div>
                <input
                  id="search"
                  name="search"
                  className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 dark:focus:placeholder-gray-500 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  placeholder="Buscar por paciente, DNI o recibo"
                  type="search"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>

            <div className="sm:w-48">
              <label htmlFor="service" className="sr-only">Servicio</label>
              <select
                id="service"
                name="service"
                className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                value={selectedService}
                onChange={(e) => setSelectedService(e.target.value)}
              >
                <option value="">Todos los servicios</option>
                {!servicesLoading && services?.map((service) => (
                  <option key={service.id} value={service.id}>
                    {service.nombre}
                  </option>
                ))}
              </select>
            </div>

            <div className="sm:w-48">
              <label htmlFor="status" className="sr-only">Estado</label>
              <select
                id="status"
                name="status"
                className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
              >
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="en_proceso">En proceso</option>
                <option value="completado">Completado</option>
              </select>
            </div>
          </div>

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
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-compact">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th
                      scope="col"
                      className="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-id"
                    >
                      ID
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-fecha"
                    >
                      Fecha
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-paciente"
                    >
                      Paciente
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-servicio"
                    >
                      Servicio
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-recibo"
                    >
                      Recibo
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-estado"
                    >
                      Estado
                    </th>
                    <th scope="col" className="relative px-3 py-3 col-acciones">
                      <span className="sr-only">Acciones</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {paginatedRequests?.length > 0 ? (
                    paginatedRequests.map((request) => {
                      // Determine request status using the new function
                      const status = getRequestStatus(request);

                      return (
                        <tr key={request.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                          <td className="px-3 py-3 text-sm font-medium text-gray-900 dark:text-white text-center col-id">
                            {request.id}
                          </td>
                          <td className="px-3 py-3 text-sm text-gray-500 dark:text-gray-300 text-center col-fecha">
                            {request.fecha ? format(new Date(request.fecha), 'dd/MM/yyyy', { locale: es }) : 'N/A'}
                          </td>
                          <td className="px-3 py-3 text-sm text-gray-500 dark:text-gray-300 col-paciente">
                            <div className="text-container">
                              <div className="font-medium text-gray-900 dark:text-white break-words">
                                {request.paciente ? `${request.paciente.nombres} ${request.paciente.apellidos}` : 'N/A'}
                              </div>
                              {request.paciente?.dni && (
                                <div className="text-xs text-gray-400 mt-1">
                                  DNI: {request.paciente.dni}
                                </div>
                              )}
                            </div>
                          </td>
                          <td className="px-3 py-3 text-sm text-gray-500 dark:text-gray-300 col-servicio">
                            <div className="text-container break-words">
                              {request.servicio?.nombre || 'N/A'}
                            </div>
                          </td>
                          <td className="px-3 py-3 text-sm text-gray-500 dark:text-gray-300 text-center col-recibo">
                            {request.numero_recibo || 'N/A'}
                          </td>
                          <td className="px-3 py-3 col-estado">
                            <div className="flex flex-col items-center space-y-1">
                              {/* Etiqueta de estado */}
                              <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                status === 'completado'
                                  ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                  : status === 'en_proceso'
                                  ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
                                  : status === 'cargando'
                                  ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'
                                  : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                              }`}>
                                {status === 'completado'
                                  ? 'Completado'
                                  : status === 'en_proceso'
                                  ? 'En proceso'
                                  : status === 'cargando'
                                  ? 'Cargando...'
                                  : 'Pendiente'}
                              </span>

                              {/* Botón para cambiar estado (solo si no está completado) */}
                              {status !== 'completado' && status !== 'cargando' && (
                                <button
                                  onClick={(e) => handleRequestStatusChange(request, e)}
                                  className="inline-flex items-center p-1 border border-transparent rounded-full text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                  title={status === 'pendiente' ? 'Marcar como En Proceso' : 'Marcar como Pendiente'}
                                  type="button"
                                >
                                  {status === 'pendiente' ? (
                                    <PlayIcon className="h-3 w-3" aria-hidden="true" />
                                  ) : (
                                    <PauseIcon className="h-3 w-3" aria-hidden="true" />
                                  )}
                                </button>
                              )}

                              {/* Indicador de carga */}
                              {status === 'cargando' && (
                                <div className="animate-spin rounded-full h-3 w-3 border-t-2 border-b-2 border-primary-500"></div>
                              )}
                            </div>
                          </td>
                          <td className="px-3 py-3 text-center text-sm font-medium col-acciones">
                            <Link
                              to={`/solicitudes/${request.id}`}
                              className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-primary-100 dark:hover:bg-primary-900"
                              title="Ver solicitud"
                            >
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                              </svg>
                            </Link>
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td colSpan="7" className="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No se encontraron solicitudes
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>

              {/* Paginación */}
              <Pagination
                currentPage={currentPage}
                totalPages={totalPages}
                totalItems={filteredRequests?.length || 0}
                itemsPerPage={itemsPerPage}
                onPageChange={setCurrentPage}
              />
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
