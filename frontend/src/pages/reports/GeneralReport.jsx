import React from 'react';
import { 
  ChartBarIcon, 
  UserGroupIcon, 
  BeakerIcon, 
  ClipboardDocumentListIcon 
} from '@heroicons/react/24/outline';
import ReportCharts from '../../components/charts/ReportCharts';

export default function GeneralReport({
  data,
  reportType,
  dateRange,
  reportsLoading,
  reportsError
}) {
  // Debug logs
  console.log('GeneralReport - Props recibidas:', {
    data,
    reportType,
    reportsLoading,
    reportsError
  });

  console.log('GeneralReport - Valores específicos:', {
    totalRequests: data?.totalRequests,
    totalPatients: data?.totalPatients,
    totalExams: data?.totalExams,
    examStats: data?.examStats,
    examStatsLength: data?.examStats?.length
  });

  if (reportsLoading) {
    return (
      <div className="flex justify-center items-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <span className="ml-2 text-gray-600 dark:text-gray-400">Cargando datos...</span>
      </div>
    );
  }

  if (reportsError) {
    return (
      <div className="bg-red-100 dark:bg-red-900/30 p-4 rounded-lg text-red-700 dark:text-red-300">
        Error al cargar los datos: {reportsError}
      </div>
    );
  }

  // Verificar si hay datos válidos - simplificado para debugging
  if (!data) {
    console.log('GeneralReport - No hay datos (data es null/undefined)');
    return (
      <div className="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-lg text-yellow-700 dark:text-yellow-300">
        No hay datos disponibles para el período indicado.
      </div>
    );
  }

  console.log('GeneralReport - Datos recibidos, renderizando componente...');

  return (
    <div className="space-y-6">
      {/* Gráficos con datos procesados */}
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
            Gráficos Estadísticos Generales
          </h3>
          <ReportCharts
            reportType={reportType}
            startDate={dateRange && dateRange[0] ? dateRange[0].startDate : null}
            endDate={dateRange && dateRange[1] ? dateRange[1].endDate : null}
            reportsData={{ data: data }}
          />
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Resumen */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Resumen
            </h3>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
              <div className="bg-primary-50 dark:bg-primary-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-md p-3">
                      <ClipboardDocumentListIcon className="h-6 w-6 text-primary-600 dark:text-primary-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Solicitudes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data?.totalRequests ?? 'N/A'}
                            <span className="text-xs text-gray-500 ml-2">
                              (Debug: {JSON.stringify(data?.totalRequests)})
                            </span>
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>

              <div className="bg-blue-50 dark:bg-blue-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-blue-100 dark:bg-blue-800 rounded-md p-3">
                      <UserGroupIcon className="h-6 w-6 text-blue-600 dark:text-blue-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Pacientes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data?.totalPatients ?? 'N/A'}
                            <span className="text-xs text-gray-500 ml-2">
                              (Debug: {JSON.stringify(data?.totalPatients)})
                            </span>
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>

              <div className="bg-green-50 dark:bg-green-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-green-100 dark:bg-green-800 rounded-md p-3">
                      <BeakerIcon className="h-6 w-6 text-green-600 dark:text-green-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Exámenes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data?.totalExams ?? 'N/A'}
                            <span className="text-xs text-gray-500 ml-2">
                              (Debug: {JSON.stringify(data?.totalExams)})
                            </span>
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Tabla de exámenes más solicitados */}
        {data.examStats && data.examStats.length > 0 && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                Exámenes más solicitados
              </h3>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead className="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Examen
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Cantidad
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Porcentaje
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    {data.examStats.slice(0, 10).map((exam, index) => (
                      <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                          {exam.name || exam.nombre}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {exam.count}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {exam.percentage ? `${exam.percentage}%` : '-'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
