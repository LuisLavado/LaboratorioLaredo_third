import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import {
  UserIcon,
  BeakerIcon,
  DocumentTextIcon,
  ClipboardDocumentCheckIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  ArrowUpIcon,
  ArrowDownIcon
} from '@heroicons/react/24/outline';
import { patientsAPI, examsAPI, requestsAPI, dashboardAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import SkeletonStatCard from '../../components/loading/SkeletonStatCard';
import SkeletonActivity from '../../components/loading/SkeletonActivity';
import SkeletonTable from '../../components/loading/SkeletonTable';

// Stat Card Component
function StatCard({ title, value, icon, color, secondaryText, trend, trendValue }) {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
      <div className="flex justify-between items-start">
        <div className="flex-1">
          <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{title}</h3>
          <p className="text-3xl font-bold text-gray-900 dark:text-white mt-2 mb-1">{value}</p>
          {secondaryText && (
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">{secondaryText}</p>
          )}
         
        </div>
        <div className={`p-4 rounded-lg ${color} bg-opacity-10 flex-shrink-0`}>
          {icon}
        </div>
      </div>
    </div>
  );
}

// Status Badge Component
function StatusBadge({ status }) {
  let bgColor, textColor, label;

  switch (status) {
    case 'pendiente':
      bgColor = 'bg-yellow-100';
      textColor = 'text-yellow-800';
      label = 'pendiente';
      break;
    case 'en_proceso':
      bgColor = 'bg-blue-100';
      textColor = 'text-blue-800';
      label = 'en proceso';
      break;
    case 'completado':
      bgColor = 'bg-green-100';
      textColor = 'text-green-800';
      label = 'completado';
      break;
    case 'urgente':
      bgColor = 'bg-red-100';
      textColor = 'text-red-800';
      label = 'urgente';
      break;
    case 'alta':
      bgColor = 'bg-orange-100';
      textColor = 'text-orange-800';
      label = 'alta';
      break;
    default:
      bgColor = 'bg-gray-100';
      textColor = 'text-gray-800';
      label = status;
  }

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${bgColor} ${textColor}`}>
      {label}
    </span>
  );
}

export default function NewDashboard() {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  
  const [searchTerm, setSearchTerm] = useState('');

  // Fetch dashboard stats (UNA SOLA VEZ al montar - sin polling)
  const { data: statsData, isLoading: statsLoading } = useQuery(
    ['dashboard-stats'], 
    () => dashboardAPI.getStats(),
    {
      refetchOnWindowFocus: false,  // ❌ No refetch al cambiar de tab
      staleTime: Infinity,          // ♾️ Nunca se considera stale
      refetchOnMount: false,        // ❌ No refetch al remontar
      refetchOnReconnect: false,    // ❌ No refetch al reconectar
      cacheTime: 1000 * 60 * 5,     // 5 minutos de cache
    }
  );

  // Fetch pending requests (UNA SOLA VEZ al montar - sin polling)
  const { data: pendingRequestsData, isLoading: pendingLoading } = useQuery(
    ['dashboard-pending'],
    () => dashboardAPI.getPendingRequests(),
    {
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      refetchOnMount: false,
      refetchOnReconnect: false,
      cacheTime: 1000 * 60 * 5,
    }
  );

  // Fetch recent activity (UNA SOLA VEZ al montar - sin polling)
  const { data: activityData, isLoading: activityLoading } = useQuery(
    ['dashboard-activity'],
    () => dashboardAPI.getRecentActivity(),
    {
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      refetchOnMount: false,
      refetchOnReconnect: false,
      cacheTime: 1000 * 60 * 5,
    }
  );

  // 🔥 Escuchar WebSocket para actualizar dashboard en tiempo real
  useEffect(() => {
    // Función para invalidar y recargar los datos del dashboard
    const refreshDashboard = () => {
      queryClient.invalidateQueries(['dashboard-stats']);
      queryClient.invalidateQueries(['dashboard-pending']);
      queryClient.invalidateQueries(['dashboard-activity']);
    };

    // Suscribirse al contexto de WebSocket si está disponible
    // Las notificaciones ya se manejan en WebSocketProvider
    // Aquí solo necesitamos invalidar las queries cuando llegue una nueva solicitud
    
    // Escuchar eventos del navegador personalizados (despachados por WebSocketProvider)
    const handleNewRequest = (event) => {
      refreshDashboard();
    };

    const handleRequestUpdate = (event) => {
      refreshDashboard();
    };

    window.addEventListener('solicitud-created', handleNewRequest);
    window.addEventListener('solicitud-updated', handleRequestUpdate);
    window.addEventListener('solicitud-completed', handleRequestUpdate);

    return () => {
      window.removeEventListener('solicitud-created', handleNewRequest);
      window.removeEventListener('solicitud-updated', handleRequestUpdate);
      window.removeEventListener('solicitud-completed', handleRequestUpdate);
    };
  }, [queryClient]);

  // Función para formatear fechas en español
  const formatFecha = (fechaStr) => {
    if (!fechaStr) return 'N/A';
    try {
      return format(new Date(fechaStr), 'dd MMM yyyy, HH:mm', { locale: es });
    } catch (error) {
      console.error('Error al formatear fecha:', error);
      return fechaStr;
    }
  };

  // Extraer datos de las respuestas
  // statsData ya viene procesado por TanStack Query después de la respuesta axios
  // Axios response.data = { status: true, data: {...} }
  // TanStack Query guarda eso como queryData
  // Entonces: queryData.data = { status: true, data: {...} }
  // Y necesitamos: queryData.data.data
  const stats = statsData?.data?.data || {
    total_patients: 0,
    total_exams: 0,
    total_requests: 0,
    completed_requests: 0,
    pending_exams: 0,
    pending_requests: 0,
    abnormal_results: 0,
    new_patients_this_month: 0
  };

  const pendingRequests = Array.isArray(pendingRequestsData?.data?.data) 
    ? pendingRequestsData.data.data.map(req => ({
        ...req,
        date: formatFecha(req.date || req.fecha),
        status: req.estado_calculado || 'pendiente'
      }))
    : [];

  const recentActivity = Array.isArray(activityData?.data?.data)
    ? activityData.data.data.map(activity => ({
        ...activity,
        time: formatFecha(activity.time)
      }))
    : [];

  // Filter pending requests based on search term
  const filteredRequests = pendingRequests.filter(
    request =>
      request.patient.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.service.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="w-full">
      {/* Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6 lg:gap-8 mb-8">
        {statsLoading ? (
          <>
            <SkeletonStatCard />
            <SkeletonStatCard />
            <SkeletonStatCard />
            <SkeletonStatCard />
          </>
        ) : (
          <>
            <StatCard
              title="Total Pacientes"
              value={stats.total_patients?.toLocaleString() || '0'}
              icon={<UserIcon className="h-6 w-6 text-blue-500" />}
              color="bg-blue-500"
              secondaryText={`Nuevos este mes: ${stats.new_patients_this_month || 0}`}
              trend="up"
              trendValue="8.5%"
            />
            <StatCard
              title="Exámenes"
              value={stats.total_exams?.toLocaleString() || '0'}
              icon={<BeakerIcon className="h-6 w-6 text-purple-500" />}
              color="bg-purple-500"
              secondaryText={`Pendientes: ${stats.pending_exams || 0}`}
              trend="up"
              trendValue="12.3%"
            />
            <StatCard
              title="Solicitudes"
              value={stats.total_requests?.toLocaleString() || '0'}
              icon={<DocumentTextIcon className="h-6 w-6 text-green-500" />}
              color="bg-green-500"
              secondaryText={`Pendientes: ${stats.pending_requests || 0}`}
              trend="up"
              trendValue="5.7%"
            />
            <StatCard
              title="Resultados"
              value={stats.completed_requests?.toLocaleString() || '0'}
              icon={<ClipboardDocumentCheckIcon className="h-6 w-6 text-yellow-500" />}
              color="bg-yellow-500"
          
              trend="down"
              trendValue="3.2%"
            />
          </>
        )}
      </div>

      {/* Main Content */}
      <div className="grid grid-cols-1 lg:grid-cols-5 gap-6 lg:gap-8">
        {/* Solicitudes Pendientes (60%) */}
        <div className="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
          <div className="p-6 sm:p-8">
            <h2 className="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-6">Solicitudes Pendientes</h2>

            {/* Barra de búsqueda */}
            <div className="relative mb-6">
              <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                type="text"
                className="block w-full pl-11 pr-4 py-3 border border-gray-300 rounded-lg leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                placeholder="Buscar solicitudes..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>

            {/* Tabla Desktop / Cards Mobile */}
            {pendingLoading ? (
              <SkeletonTable />
            ) : filteredRequests.length > 0 ? (
              <>
                {/* Vista Desktop - Tabla (oculta en móvil) */}
                <div className="hidden md:block overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th scope="col" className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Paciente
                        </th>
                        <th scope="col" className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Servicio
                        </th>
                        <th scope="col" className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Fecha Solicitada
                        </th>
                        <th scope="col" className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Estado
                        </th>
                      </tr>
                    </thead>
                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                      {filteredRequests.map((request) => (
                        <tr key={request.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                          <td className="px-6 py-5 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            <Link to={`/solicitudes/${request.id}`} className="hover:text-primary-600 transition-colors">
                              {request.patient}
                            </Link>
                          </td>
                          <td className="px-6 py-5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {request.service}
                          </td>
                          <td className="px-6 py-5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {request.date}
                          </td>
                          <td className="px-6 py-5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <StatusBadge status={request.status} />
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {/* Vista Mobile - Cards (oculta en desktop) */}
                <div className="md:hidden space-y-4">
                  {filteredRequests.map((request) => (
                    <Link 
                      key={request.id}
                      to={`/solicitudes/${request.id}`}
                      className="block bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow"
                    >
                      <div className="flex justify-between items-start mb-3">
                        <div className="flex-1">
                          <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                            {request.patient}
                          </h3>
                          <p className="text-xs text-gray-500 dark:text-gray-400">
                            {request.service}
                          </p>
                        </div>
                        <StatusBadge status={request.status} />
                      </div>
                      <div className="flex items-center text-xs text-gray-500 dark:text-gray-400">
                        <svg className="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {request.date}
                      </div>
                    </Link>
                  ))}
                </div>
              </>
            ) : (
              <div className="text-center py-16">
                <svg className="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 className="mt-4 text-base font-medium text-gray-900 dark:text-white">No hay solicitudes pendientes</h3>
                <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  {searchTerm ? 'Intenta con otro término de búsqueda' : 'Las nuevas solicitudes aparecerán aquí'}
                </p>
              </div>
            )}
          </div>
        </div>

        {/* Actividad Reciente (40%) */}
        <div className="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
          <div className="p-6 sm:p-8">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">Actividad Reciente</h2>
              <button className="text-gray-400 hover:text-gray-500 transition-colors">
                <FunnelIcon className="h-5 w-5" aria-hidden="true" />
              </button>
            </div>

            <div className="space-y-6 sm:space-y-8">
              {activityLoading ? (
                <SkeletonActivity />
              ) : recentActivity.length > 0 ? (
                recentActivity.map((activity, index) => (
                  <div key={activity.id} className="relative">
                    <div className="flex space-x-3">
                      <div className="flex flex-col items-center flex-shrink-0">
                        <div className={`h-8 w-8 rounded-full flex items-center justify-center ${
                          activity.status === 'completado' ? 'bg-green-100 text-green-600' :
                          activity.status === 'en_proceso' ? 'bg-blue-100 text-blue-600' :
                          'bg-yellow-100 text-yellow-600'
                        }`}>
                          {activity.type === 'request' && <DocumentTextIcon className="h-4 w-4" />}
                          {activity.type === 'update' && <ClipboardDocumentCheckIcon className="h-4 w-4" />}
                          {activity.type === 'complete' && <ClipboardDocumentCheckIcon className="h-4 w-4" />}
                        </div>
                        {index !== recentActivity.length - 1 && (
                          <div className="h-full w-0.5 bg-gray-200 dark:bg-gray-700 mt-2"></div>
                        )}
                      </div>
                      <div className="min-w-0 flex-1 pb-4">
                        <div>
                          <div className="text-sm font-medium text-gray-900 dark:text-white break-words">
                            {activity.title}
                          </div>
                          <p className="mt-0.5 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {activity.time}
                          </p>
                        </div>
                        <div className="mt-2 text-xs sm:text-sm text-gray-700 dark:text-gray-300">
                          <div className="flex items-center flex-wrap gap-2">
                            <StatusBadge status={activity.status} />
                            <span className="text-xs truncate">{activity.user}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                ))
              ) : (
                <div className="text-center py-12">
                  <svg className="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <h3 className="mt-4 text-base font-medium text-gray-900 dark:text-white">No hay actividad reciente</h3>
                  <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Las actualizaciones aparecerán aquí
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
