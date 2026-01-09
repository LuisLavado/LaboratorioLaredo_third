import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { requestsAPI, requestDetailsAPI } from '../../services/api';
import { MagnifyingGlassIcon, DocumentTextIcon, PlayIcon, PauseIcon, BeakerIcon, PencilIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import DynamicSystemNotice from '../../components/DynamicSystemNotice';
import Pagination from '../../components/common/Pagination';

export default function Results() {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [dateFilter, setDateFilter] = useState('all'); // 'all', 'today', 'week', 'month'

  // Estados para paginación
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(25);

  // Obtener el queryClient para invalidar consultas manualmente
  const queryClient = useQueryClient();

  // Fetch requests with more frequent updates
  const { data: requests, isLoading: requestsLoading, error: requestsError } = useQuery(
    ['requests'],
    () => requestsAPI.getAll().then(res => res.data),
    {
      refetchInterval: false, // DESACTIVADO TEMPORALMENTE
      refetchOnWindowFocus: true,
      staleTime: 3000, // Consider data stale after 3 seconds
    }
  );

  // Fetch request details with more frequent updates
  const { data: requestDetailsResponse, isLoading: detailsLoading } = useQuery(
    ['requestDetails'],
    () => requestDetailsAPI.getAll().then(res => res.data),
    {
      refetchInterval: false, // DESACTIVADO TEMPORALMENTE
      refetchOnWindowFocus: true,
      staleTime: 3000, // Consider data stale after 3 seconds
    }
  );

  // Efecto para invalidar consultas periódicamente
  // DESACTIVADO TEMPORALMENTE
  /*
  useEffect(() => {
    const interval = setInterval(() => {
      queryClient.invalidateQueries(['requests']);
      queryClient.invalidateQueries(['requestDetails']);
    }, 10000); // Invalidate every 10 seconds

    return () => clearInterval(interval);
  }, [queryClient]);
  */

  // Extract request details from the response
  const requestDetails = requestDetailsResponse?.data || [];

  // Helper function to check if a date is within the specified range
  const isDateInRange = (dateStr, range) => {
    if (range === 'all') return true;

    const date = new Date(dateStr);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const weekStart = new Date(today);
    weekStart.setDate(weekStart.getDate() - weekStart.getDay());

    const monthStart = new Date(today);
    monthStart.setDate(1);

    switch (range) {
      case 'today':
        return date >= today;
      case 'yesterday':
        return date >= yesterday && date < today;
      case 'week':
        return date >= weekStart;
      case 'month':
        return date >= monthStart;
      default:
        return true;
    }
  };

  // Filter and sort requests based on search term, status, and date
  const filteredRequests = requests?.filter(request => {
    const matchesSearch =
      request.paciente?.nombres?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.paciente?.apellidos?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.paciente?.dni?.includes(searchTerm) ||
      request.numero_recibo?.includes(searchTerm);

    // Filter by status
    let matchesStatus = true;
    if (statusFilter) {
      const details = requestDetails?.filter(d => d.solicitud_id === request.id) || [];

      if (statusFilter === 'completado') {
        matchesStatus = details.length > 0 && details.every(d => d.estado === 'completado');
      } else if (statusFilter === 'pendiente') {
        matchesStatus = details.length > 0 && details.some(d => d.estado === 'pendiente');
      } else if (statusFilter === 'en_proceso') {
        matchesStatus = details.length > 0 &&
          details.some(d => d.estado === 'en_proceso') ||
          (details.some(d => d.estado === 'completado') && details.some(d => d.estado === 'pendiente'));
      }
    }

    // Filter by date
    const matchesDate = isDateInRange(request.created_at, dateFilter);

    return matchesSearch && matchesStatus && matchesDate;
  })
  // Sort by created_at date (most recent first)
  .sort((a, b) => {
    const dateA = new Date(a.created_at || a.fecha || 0);
    const dateB = new Date(b.created_at || b.fecha || 0);
    return dateB - dateA;
  }) || [];

  // Cálculos de paginación
  const totalItems = filteredRequests.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const paginatedRequests = filteredRequests.slice(startIndex, endIndex);

  // Funciones de paginación
  const handlePageChange = (page) => {
    setCurrentPage(page);
  };

  const handleItemsPerPageChange = (newItemsPerPage) => {
    setItemsPerPage(newItemsPerPage);
    setCurrentPage(1); // Reset to first page when changing items per page
  };

  // Reset to first page when filters change
  useEffect(() => {
    setCurrentPage(1);
  }, [searchTerm, statusFilter, dateFilter]);

  return (
    <div>
      <div className="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Resultados</h1>
          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Gestión de resultados de exámenes
          </p>
        </div>
      </div>

      {/* Dynamic System Notice */}
      <DynamicSystemNotice />

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          {/* Resumen de resultados */}
          {!requestsLoading && !detailsLoading && totalItems > 0 && (
            <div className="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
              <div className="flex items-center justify-between text-sm">
                <div className="text-blue-800 dark:text-blue-200">
                  <span className="font-medium">Total de solicitudes:</span> {totalItems}
                  {searchTerm && (
                    <span className="ml-2">
                      • <span className="font-medium">Filtrado por:</span> "{searchTerm}"
                    </span>
                  )}
                  {statusFilter && (
                    <span className="ml-2">
                      • <span className="font-medium">Estado:</span> {
                        statusFilter === 'completado' ? 'Completado' :
                        statusFilter === 'en_proceso' ? 'En proceso' :
                        statusFilter === 'pendiente' ? 'Pendiente' : statusFilter
                      }
                    </span>
                  )}
                  {dateFilter !== 'all' && (
                    <span className="ml-2">
                      • <span className="font-medium">Fecha:</span> {
                        dateFilter === 'today' ? 'Hoy' :
                        dateFilter === 'yesterday' ? 'Ayer' :
                        dateFilter === 'week' ? 'Esta semana' :
                        dateFilter === 'month' ? 'Este mes' : dateFilter
                      }
                    </span>
                  )}
                </div>
                <div className="text-blue-600 dark:text-blue-400 font-medium">
                  Página {currentPage} de {totalPages}
                </div>
              </div>
            </div>
          )}

          {/* Filtros */}
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

            <div className="sm:w-48">
              <label htmlFor="date" className="sr-only">Fecha</label>
              <select
                id="date"
                name="date"
                className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
              >
                <option value="all">Todas las fechas</option>
                <option value="today">Hoy</option>
                <option value="yesterday">Ayer</option>
                <option value="week">Esta semana</option>
                <option value="month">Este mes</option>
              </select>
            </div>
          </div>

          {requestsLoading || detailsLoading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
            </div>
          ) : requestsError ? (
            <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
              <div className="flex">
                <div className="ml-3">
                  <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
                    Error al cargar resultados
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
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Solicitud
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Fecha
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Paciente
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Exámenes
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Estado
                    </th>
                    <th scope="col" className="relative px-6 py-3">
                      <span className="sr-only">Acciones</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {paginatedRequests?.length > 0 ? (
                    paginatedRequests.map((request) => {
                      const details = requestDetails?.filter(d => d.solicitud_id === request.id) || [];
                      const completedCount = details.filter(d => d.estado === 'completado').length;
                      const totalCount = details.length;

                      // Determine request status
                      let status = 'pendiente';
                      if (totalCount > 0 && completedCount === totalCount) {
                        status = 'completado';
                      } else if (completedCount > 0) {
                        status = 'en_proceso';
                      }

                      return (
                        <tr key={request.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            #{request.id}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {request.fecha ? format(new Date(request.fecha), 'dd/MM/yyyy', { locale: es }) : 'N/A'}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {request.paciente ? `${request.paciente.nombres} ${request.paciente.apellidos}` : 'N/A'}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {completedCount} / {totalCount}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                              status === 'completado'
                                ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                : status === 'en_proceso'
                                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
                                : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            }`}>
                              {status === 'completado' ? 'Completado' : status === 'en_proceso' ? 'En proceso' : 'Pendiente'}
                            </span>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div className="flex justify-end space-x-2">
                              {/* Dropdown for registration options */}
                              <div className="relative inline-block text-left">
                                <div className="flex space-x-1">
                                  <Link
                                    to={`/resultados/${request.id}`}
                                    className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 shadow-sm"
                                    title={status === 'completado' ? "Editar Resultados (Sistema Dinámico)" : "Registrar Resultados (Sistema Dinámico)"}
                                  >
                                    {status === 'completado' ? (
                                      <PencilIcon className="h-4 w-4 mr-1" />
                                    ) : (
                                      <BeakerIcon className="h-4 w-4 mr-1" />
                                    )}
                                    {status === 'completado' ? 'Editar' : 'Registrar'}
                                  </Link>

                                </div>
                              </div>

                              {status === 'completado' && (
                                <>
                                  <div className="flex space-x-1">
                                    <Link
                                      to={`/resultados/${request.id}/ver`}
                                      className="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                      title="Ver Resultados"
                                    >
                                      <BeakerIcon className="h-3 w-3 mr-1" />
                                      Ver
                                    </Link>

                                  </div>
                         
                                </>
                              )}
                            </div>
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td colSpan="6" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        {totalItems > 0 ?
                          `No hay resultados en la página ${currentPage}. Total de resultados: ${totalItems}` :
                          'No se encontraron resultados'
                        }
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          )}

          {/* Paginación */}
          {totalItems > 0 && (
            <Pagination
              currentPage={currentPage}
              totalPages={totalPages}
              totalItems={totalItems}
              itemsPerPage={itemsPerPage}
              onPageChange={handlePageChange}
              onItemsPerPageChange={handleItemsPerPageChange}
            />
          )}
        </div>
      </div>
    </div>
  );
}
