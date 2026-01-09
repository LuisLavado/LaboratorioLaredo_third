import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { requestsAPI } from '../../services/api';
import { MagnifyingGlassIcon, EyeIcon, CalendarIcon, PrinterIcon } from '@heroicons/react/24/outline';
import Pagination from '../../components/common/Pagination';
import { format, subDays, startOfWeek, startOfMonth, isAfter, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

export default function DoctorResults() {
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [dateFilter, setDateFilter] = useState('all'); // 'all', 'today', 'week', 'month', 'custom'
  const [customDateRange, setCustomDateRange] = useState({
    startDate: format(subDays(new Date(), 30), 'yyyy-MM-dd'),
    endDate: format(new Date(), 'yyyy-MM-dd')
  });
  const itemsPerPage = 50;

  // Helper function to check if a date is within the selected range
  const isDateInRange = (dateStr) => {
    if (!dateStr) return false;
    if (dateFilter === 'all') return true;

    try {
      const date = parseISO(dateStr);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      switch (dateFilter) {
        case 'today':
          return format(date, 'yyyy-MM-dd') === format(today, 'yyyy-MM-dd');
        case 'yesterday':
          const yesterday = subDays(today, 1);
          return format(date, 'yyyy-MM-dd') === format(yesterday, 'yyyy-MM-dd');
        case 'week':
          const weekStart = startOfWeek(today, { locale: es });
          return isAfter(date, weekStart) || format(date, 'yyyy-MM-dd') === format(weekStart, 'yyyy-MM-dd');
        case 'month':
          const monthStart = startOfMonth(today);
          return isAfter(date, monthStart) || format(date, 'yyyy-MM-dd') === format(monthStart, 'yyyy-MM-dd');
        case 'custom':
          const startDate = parseISO(customDateRange.startDate);
          const endDate = parseISO(customDateRange.endDate);
          // Add one day to endDate to include the end date in the range
          endDate.setDate(endDate.getDate() + 1);
          return (isAfter(date, startDate) || format(date, 'yyyy-MM-dd') === format(startDate, 'yyyy-MM-dd')) &&
                 (isAfter(endDate, date) || format(date, 'yyyy-MM-dd') === format(endDate, 'yyyy-MM-dd'));
        default:
          return true;
      }
    } catch (error) {
      console.error('Error checking date range:', error);
      return false;
    }
  };

  // Fetch requests data
  const { data: requestsData, isLoading, error } = useQuery(
    ['doctor-requests-with-results'],
    async () => {
      const response = await requestsAPI.getDoctorRequests();
      return response.data;
    },
    {
      refetchOnWindowFocus: false,
      staleTime: 30000, // 30 seconds
    }
  );

  // Use the requests data directly - the backend now calculates estado_calculado
  const requests = requestsData || [];

  // Filter requests to only show those with results (completed)
  const filteredRequests = requests.filter(request => {
    const matchesSearch =
      (request.paciente?.nombres?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.paciente?.apellidos?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.paciente?.dni?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.id.toString().includes(searchTerm.toLowerCase()));

    // Only show requests with results (completed)
    const hasResults = request.estado_calculado === 'completado';

    // Check if the request date is within the selected range
    const matchesDateRange = isDateInRange(request.created_at || request.fecha);

    return matchesSearch && hasResults && matchesDateRange;
  });

  // Calcular el número total de páginas
  const totalPages = Math.ceil(filteredRequests.length / itemsPerPage);

  // Obtener las solicitudes para la página actual
  const paginatedRequests = filteredRequests.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  // Format date for display
  const formatFecha = (dateString) => {
    if (!dateString) return 'N/A';
    try {
      return format(new Date(dateString), 'dd/MM/yyyy', { locale: es });
    } catch (error) {
      console.error('Error formatting date:', error);
      return 'Fecha inválida';
    }
  };

  return (
    <div>
      <div className="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Resultados</h1>
          <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Lista de solicitudes con resultados disponibles
          </p>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <div className="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
            <div className="w-full sm:w-auto relative rounded-md shadow-sm">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                type="text"
                className="focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md h-10"
                placeholder="Buscar por paciente o DNI"
                value={searchTerm}
                onChange={(e) => {
                  setSearchTerm(e.target.value);
                  setCurrentPage(1); // Reset to first page on search
                }}
              />
            </div>

            {/* Filtro de fechas */}
            <div className="w-full sm:w-auto flex flex-col sm:flex-row gap-2 items-center">
              <div className="flex items-center">
                <CalendarIcon className="h-5 w-5 text-gray-400 mr-2" aria-hidden="true" />
                <select
                  className="focus:ring-primary-500 focus:border-primary-500 h-10 py-0 pl-2 pr-7 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm rounded-md"
                  value={dateFilter}
                  onChange={(e) => {
                    setDateFilter(e.target.value);
                    setCurrentPage(1); // Reset to first page on filter change
                  }}
                >
                  <option value="all">Todas las fechas</option>
                  <option value="today">Hoy</option>
                  <option value="yesterday">Ayer</option>
                  <option value="week">Esta semana</option>
                  <option value="month">Este mes</option>
                  <option value="custom">Personalizado</option>
                </select>
              </div>

              {/* Rango de fechas personalizado */}
              {dateFilter === 'custom' && (
                <div className="flex flex-col sm:flex-row gap-2">
                  <input
                    type="date"
                    className="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md h-10"
                    value={customDateRange.startDate}
                    onChange={(e) => {
                      setCustomDateRange({...customDateRange, startDate: e.target.value});
                      setCurrentPage(1);
                    }}
                  />
                  <span className="text-gray-500 dark:text-gray-400 self-center">hasta</span>
                  <input
                    type="date"
                    className="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md h-10"
                    value={customDateRange.endDate}
                    onChange={(e) => {
                      setCustomDateRange({...customDateRange, endDate: e.target.value});
                      setCurrentPage(1);
                    }}
                  />
                </div>
              )}
            </div>
          </div>

          <div className="mt-4 flex flex-col">
            <div className="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
              <div className="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div className="shadow overflow-hidden border-b border-gray-200 dark:border-gray-700 sm:rounded-lg">
                  <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          ID
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Paciente
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          DNI
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Fecha
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Exámenes
                        </th>
                        <th scope="col" className="relative px-6 py-3">
                          <span className="sr-only">Acciones</span>
                        </th>
                      </tr>
                    </thead>
                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                      {isLoading ? (
                        <tr>
                          <td colSpan="6" className="px-6 py-4 text-center">
                            <div className="flex justify-center">
                              <div className="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-primary-500"></div>
                            </div>
                          </td>
                        </tr>
                      ) : error ? (
                        <tr>
                          <td colSpan="6" className="px-6 py-4 text-center text-sm text-red-500">
                            Error al cargar resultados: {error.message}
                          </td>
                        </tr>
                      ) : filteredRequests.length === 0 ? (
                        <tr>
                          <td colSpan="6" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            No se encontraron resultados
                          </td>
                        </tr>
                      ) : (
                        paginatedRequests.map((request) => (
                          <tr key={request.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                              {request.id}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                              {request.paciente ? `${request.paciente.nombres} ${request.paciente.apellidos}` : 'N/A'}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                              {request.paciente?.dni || 'N/A'}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                              {formatFecha(request.created_at || request.fecha)}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                              {request.examenes?.length || 0}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                              <div className="flex justify-end space-x-3">
                                <Link
                                  to={`/doctor/resultados/${request.id}/ver`}
                                  className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                  title="Ver resultados"
                                >
                                  <EyeIcon className="h-5 w-5" aria-hidden="true" />
                                </Link>
                                <Link
                                  to={`/doctor/solicitudes/${request.id}/imprimir-resultados`}
                                  state={{ from: '/doctor/resultados' }}
                                  className="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                  title="Imprimir resultados"
                                >
                                  <PrinterIcon className="h-5 w-5" aria-hidden="true" />
                                </Link>
                              </div>
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
      </div>
    </div>
  );
}
