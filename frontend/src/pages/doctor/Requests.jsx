import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { requestsAPI } from '../../services/api';
import { PlusIcon, MagnifyingGlassIcon, EyeIcon, PrinterIcon } from '@heroicons/react/24/outline';
import Pagination from '../../components/common/Pagination';
import { format } from 'date-fns';

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

export default function DoctorRequests() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [searchTerm, setSearchTerm] = useState('');
  const statusFilter = searchParams.get('estado') || 'todos';
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 50;

  // Fetch doctor's requests with status already calculated from backend
  const { data: requestsData, isLoading, error } = useQuery(
    ['doctor-requests'],
    async () => {
      // Fetch requests with status already calculated
      const response = await requestsAPI.getDoctorRequests();
      return response.data;
    },
    {
      refetchOnWindowFocus: false,
      staleTime: 30000, // Cache for 30 seconds to reduce API calls
    }
  );

  // Use the requests data directly - the backend now calculates estado_calculado
  // Ensure requests is always an array
  const requests = Array.isArray(requestsData) ? requestsData : [];

  // Only log in development environment
  if (process.env.NODE_ENV === 'development') {
    console.log('Requests count:', requests.length);
    console.log('Status filter:', statusFilter);
  }

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
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Mis Solicitudes</h1>
          <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Lista de solicitudes de exámenes creadas por usted
          </p>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex space-x-3">
          <Link
            to="/doctor/todas-solicitudes"
            className="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
          >
            Ver Todas
          </Link>
          <Link
            to="/doctor/solicitudes/nueva"
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
          >
            <PlusIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Nueva Solicitud
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
              className={`px-3 py-2 text-sm font-medium rounded-md ${
                statusFilter === 'todos'
                  ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                  : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
              }`}
              onClick={() => handleStatusFilterChange('todos')}
            >
              Todos
            </button>
            <button
              type="button"
              className={`px-3 py-2 text-sm font-medium rounded-md ${
                statusFilter === 'pendiente'
                  ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                  : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
              }`}
              onClick={() => handleStatusFilterChange('pendiente')}
            >
              Pendientes
            </button>
            <button
              type="button"
              className={`px-3 py-2 text-sm font-medium rounded-md ${
                statusFilter === 'en_proceso'
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                  : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
              }`}
              onClick={() => handleStatusFilterChange('en_proceso')}
            >
              En Proceso
            </button>
            <button
              type="button"
              className={`px-3 py-2 text-sm font-medium rounded-md ${
                statusFilter === 'completado'
                  ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                  : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
              }`}
              onClick={() => handleStatusFilterChange('completado')}
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
                      DNI
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Servicio
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Exámenes
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Estado
                    </th>
                    <th scope="col" className="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span className="sr-only">Acciones</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                  {isLoading ? (
                    <tr>
                      <td colSpan="8" className="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                        Cargando solicitudes...
                      </td>
                    </tr>
                  ) : error ? (
                    <tr>
                      <td colSpan="8" className="px-3 py-4 text-sm text-red-500 text-center">
                        Error al cargar solicitudes
                      </td>
                    </tr>
                  ) : filteredRequests.length === 0 ? (
                    <tr>
                      <td colSpan="8" className="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
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
                          {format(new Date(request.fecha), 'dd/MM/yyyy')}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {request.paciente?.nombres} {request.paciente?.apellidos}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {request.paciente?.dni}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {request.servicio?.nombre}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {request.examenes?.length || 0}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          <StatusBadge status={request.estado_calculado || 'pendiente'} />
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
