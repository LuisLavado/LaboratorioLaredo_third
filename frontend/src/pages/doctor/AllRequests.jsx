import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { requestsAPI } from '../../services/api';
import { MagnifyingGlassIcon, EyeIcon, PrinterIcon } from '@heroicons/react/24/outline';
import Pagination from '../../components/common/Pagination';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

export default function AllRequests() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [searchTerm, setSearchTerm] = useState('');
  const statusFilter = searchParams.get('estado') || 'todos';
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 50;

  // Fetch all requests with status already calculated
  const { data: requestsData, isLoading, error } = useQuery(
    ['all-requests'],
    () => requestsAPI.getAllWithStatus().then(res => res.data),
    {
      refetchOnWindowFocus: false,
      staleTime: 30000, // Cache for 30 seconds to reduce API calls
    }
  );

  // Use the requests data directly - the backend now calculates estado_calculado
  const requests = requestsData || [];

  // Filter requests based on search term and status
  const filteredRequests = requests.filter(request => {
    const matchesSearch =
      (request.paciente?.nombres?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.paciente?.apellidos?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.paciente?.dni?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.id.toString().includes(searchTerm.toLowerCase()));

    const matchesStatus =
      statusFilter === 'todos' ||
      request.estado_calculado === statusFilter;

    return matchesSearch && matchesStatus;
  });

  // Calcular el número total de páginas
  const totalPages = Math.ceil(filteredRequests.length / itemsPerPage);

  // Obtener las solicitudes para la página actual
  const paginatedRequests = filteredRequests.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  // Handle status filter change
  const handleStatusFilterChange = (status) => {
    setSearchParams({ estado: status });
  };

  return (
    <div>
      <div className="sm:flex sm:items-center mb-6">
        <div className="sm:flex-auto">
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Todas las Solicitudes</h1>
          <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Lista de todas las solicitudes de exámenes en el sistema
          </p>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <Link
            to="/doctor/solicitudes"
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
          >
            Ver Mis Solicitudes
          </Link>
        </div>
      </div>

      {/* Search and filters */}
      <div className="mb-6 grid grid-cols-1 gap-y-4 sm:grid-cols-4 sm:gap-x-6">
        <div className="sm:col-span-2">
          <div className="relative rounded-md shadow-sm">
            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
            </div>
            <input
              type="text"
              className="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white pl-10 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              placeholder="Buscar por paciente o DNI"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
        </div>
        <div className="sm:col-span-2">
          <div className="flex space-x-2">
            <button
              type="button"
              onClick={() => handleStatusFilterChange('todos')}
              className={`inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ${
                statusFilter === 'todos'
                  ? 'bg-primary-600 text-white dark:bg-primary-700'
                  : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
              }`}
            >
              Todos
            </button>
            <button
              type="button"
              onClick={() => handleStatusFilterChange('pendiente')}
              className={`inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ${
                statusFilter === 'pendiente'
                  ? 'bg-primary-600 text-white dark:bg-primary-700'
                  : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
              }`}
            >
              Pendientes
            </button>
            <button
              type="button"
              onClick={() => handleStatusFilterChange('en_proceso')}
              className={`inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ${
                statusFilter === 'en_proceso'
                  ? 'bg-primary-600 text-white dark:bg-primary-700'
                  : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
              }`}
            >
              En Proceso
            </button>
            <button
              type="button"
              onClick={() => handleStatusFilterChange('completado')}
              className={`inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ${
                statusFilter === 'completado'
                  ? 'bg-primary-600 text-white dark:bg-primary-700'
                  : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
              }`}
            >
              Completados
            </button>
          </div>
        </div>
      </div>

      {/* Requests list */}
      <div className="mt-8 flex flex-col">
        <div className="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table className="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-800">
                  <tr>
                    <th scope="col" className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">
                      ID
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Fecha
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Paciente
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Servicio
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Doctor
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Estado
                    </th>
                    <th scope="col" className="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span className="sr-only">Acciones</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                  {isLoading ? (
                    <tr>
                      <td colSpan="7" className="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        <div className="flex justify-center">
                          <div className="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-primary-500"></div>
                        </div>
                      </td>
                    </tr>
                  ) : error ? (
                    <tr>
                      <td colSpan="7" className="px-3 py-4 text-center text-sm text-red-500">
                        Error al cargar solicitudes: {error.message}
                      </td>
                    </tr>
                  ) : filteredRequests.length === 0 ? (
                    <tr>
                      <td colSpan="7" className="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No se encontraron solicitudes
                      </td>
                    </tr>
                  ) : (
                    paginatedRequests.map((request) => (
                      <tr key={request.id}>
                        <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-6">
                          {request.id}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {request.fecha ? format(new Date(request.fecha), 'dd/MM/yyyy', { locale: es }) : 'N/A'}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {request.paciente ? `${request.paciente.nombres} ${request.paciente.apellidos}` : 'N/A'}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {request.servicio?.nombre || 'N/A'}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {request.user ? `${request.user.nombre || ''} ${request.user.apellido || ''}` : 'N/A'}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm">
                          <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                            request.estado_calculado === 'completado'
                              ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                              : request.estado_calculado === 'en_proceso'
                              ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                          }`}>
                            {request.estado_calculado === 'completado'
                              ? 'Completado'
                              : request.estado_calculado === 'en_proceso'
                              ? 'En proceso'
                              : 'Pendiente'}
                          </span>
                        </td>
                        <td className="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                          <Link
                            to={`/doctor/solicitudes/${request.id}`}
                            className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 mr-4"
                          >
                            <EyeIcon className="h-5 w-5" aria-hidden="true" />
                            <span className="sr-only">Ver</span>
                          </Link>
                          <Link
                            to={`/doctor/solicitudes/${request.id}/imprimir`}
                            className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                          >
                            <PrinterIcon className="h-5 w-5" aria-hidden="true" />
                            <span className="sr-only">Imprimir</span>
                          </Link>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>

              {/* Paginación */}
              <Pagination
                currentPage={currentPage}
                totalPages={totalPages}
                totalItems={filteredRequests.length}
                itemsPerPage={itemsPerPage}
                onPageChange={setCurrentPage}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
