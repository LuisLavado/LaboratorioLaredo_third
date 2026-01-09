import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import {
  UserIcon,
  DocumentTextIcon,
  ClipboardDocumentCheckIcon,
  CalendarIcon,
  ClockIcon,
  ArrowPathIcon
} from '@heroicons/react/24/outline';
import { dashboardAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import SkeletonCard from '../../components/loading/SkeletonCard';
import SkeletonActivity from '../../components/loading/SkeletonActivity';

// Dashboard card component
function DashboardCard({ title, value, icon, color, to }) {
  return (
    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg transition-all hover:shadow-md">
      <Link
        to={to}
        className="block"
      >
        <div className="p-5">
          <div className="flex items-center">
            <div className={`flex-shrink-0 rounded-md p-3 ${color}`}>
              {icon}
            </div>
            <div className="ml-5 w-0 flex-1">
              <dl>
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{title}</dt>
                <dd>
                  <div className="text-lg font-medium text-gray-900 dark:text-white">{value}</div>
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div className={`bg-gray-50 dark:bg-gray-700 px-5 py-3`}>
          <div className="text-sm">
            <span className="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
              Ver detalles
            </span>
          </div>
        </div>
      </Link>
    </div>
  );
}

// Recent activity component
function RecentActivity({ title, items }) {
  return (
    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
      <div className="px-4 py-5 sm:p-6">
        <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
          {title}
        </h3>
        <div className="flow-root">
          <ul className="-mb-8">
            {items.length === 0 ? (
              <li className="py-4">
                <div className="text-center text-gray-500 dark:text-gray-400">
                  No hay actividad reciente
                </div>
              </li>
            ) : (
              items.map((item, itemIdx) => (
                <li key={itemIdx}>
                  <div className="relative pb-8">
                    {itemIdx !== items.length - 1 ? (
                      <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                    ) : null}
                    <div className="relative flex space-x-3">
                      <div>
                        <span className={`h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800 ${
                          item.status === 'completado' ? 'bg-green-500' :
                          item.status === 'en_proceso' ? 'bg-blue-500' : 'bg-amber-500'
                        }`}>
                          <DocumentTextIcon className="h-5 w-5 text-white" aria-hidden="true" />
                        </span>
                      </div>
                      <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                        <div>
                          <p className="text-sm text-gray-900 dark:text-white">
                            <Link to={item.to} className="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                              {item.title}
                            </Link>
                          </p>
                          <p className="text-sm text-gray-500 dark:text-gray-400">{item.description}</p>
                        </div>
                        <div className="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                          <time dateTime={item.date}>{item.date}</time>
                        </div>
                      </div>
                    </div>
                  </div>
                </li>
              ))
            )}
          </ul>
        </div>
      </div>
    </div>
  );
}

export default function DoctorDashboard() {
  const { user } = useAuth();
  const queryClient = useQueryClient();

  // Fetch doctor stats (optimizado - sin polling)
  const { data: statsData, isLoading: statsLoading } = useQuery(
    ['doctor-dashboard-stats'],
    () => dashboardAPI.getDoctorStats(),
    {
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      refetchOnMount: false,
      refetchOnReconnect: false,
      cacheTime: 1000 * 60 * 5,
    }
  );

  // Fetch recent requests (optimizado - sin polling)
  const { data: recentRequestsData, isLoading: recentLoading } = useQuery(
    ['doctor-recent-requests'],
    () => dashboardAPI.getDoctorRecentRequests(),
    {
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      refetchOnMount: false,
      refetchOnReconnect: false,
      cacheTime: 1000 * 60 * 5,
    }
  );

  // WebSocket listeners para actualizar en tiempo real
  useEffect(() => {
    const refreshDashboard = () => {
      queryClient.invalidateQueries(['doctor-dashboard-stats']);
      queryClient.invalidateQueries(['doctor-recent-requests']);
    };

    window.addEventListener('solicitud-created', refreshDashboard);
    window.addEventListener('solicitud-updated', refreshDashboard);
    window.addEventListener('solicitud-completed', refreshDashboard);

    return () => {
      window.removeEventListener('solicitud-created', refreshDashboard);
      window.removeEventListener('solicitud-updated', refreshDashboard);
      window.removeEventListener('solicitud-completed', refreshDashboard);
    };
  }, [queryClient]);

  // Extraer datos
  const stats = statsData?.data?.data || {
    total_patients: 0,
    total_requests: 0,
    completed_requests: 0,
    pending_requests: 0,
    in_process_requests: 0,
  };

  const recentRequests = Array.isArray(recentRequestsData?.data?.data)
    ? recentRequestsData.data.data.map(req => ({
        title: `Solicitud #${req.id} - ${req.patient}`,
        description: `${req.exams_count} exámenes solicitados`,
        status: req.status,
        date: new Date(req.date).toLocaleDateString(),
        to: `/doctor/solicitudes/${req.id}`
      }))
    : [];

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard de Doctor</h1>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Bienvenido, Dr. {user?.nombre || ''}
        </p>
        <p className="text-sm text-gray-500 dark:text-gray-400">
          {user?.especialidad ? `Especialidad: ${user?.especialidad}` : ''}
          {user?.colegiatura ? ` | Colegiatura: ${user?.colegiatura}` : ''}
        </p>
      </div>

      {/* Stats Cards con Skeleton Loading */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {statsLoading ? (
          <>
            <SkeletonCard />
            <SkeletonCard />
            <SkeletonCard />
            <SkeletonCard />
          </>
        ) : (
          <>
            <DashboardCard
              title="Pacientes"
              value={stats.total_patients}
              icon={<UserIcon className="h-6 w-6 text-white" aria-hidden="true" />}
              color="bg-blue-500"
              to="/doctor/pacientes"
            />
            <DashboardCard
              title="Solicitudes"
              value={stats.total_requests}
              icon={<DocumentTextIcon className="h-6 w-6 text-white" aria-hidden="true" />}
              color="bg-purple-500"
              to="/doctor/solicitudes"
            />
            <DashboardCard
              title="Resultados Completados"
              value={stats.completed_requests}
              icon={<ClipboardDocumentCheckIcon className="h-6 w-6 text-white" aria-hidden="true" />}
              color="bg-green-500"
              to="/doctor/resultados"
            />
            <DashboardCard
              title="Solicitudes Pendientes"
              value={stats.pending_requests}
              icon={<CalendarIcon className="h-6 w-6 text-white" aria-hidden="true" />}
              color="bg-amber-500"
              to="/doctor/solicitudes?estado=pendiente"
            />
          </>
        )}
      </div>

      {/* Estadísticas y actividad reciente */}
      <div className="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
        {statsLoading ? (
          <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg animate-pulse">
            <div className="px-4 py-5 sm:p-6">
              <div className="h-6 bg-gray-300 dark:bg-gray-700 rounded w-1/3 mb-4"></div>
              <div className="grid grid-cols-3 gap-4 mb-6">
                <div className="bg-gray-200 dark:bg-gray-700 p-4 rounded-lg">
                  <div className="h-8 bg-gray-300 dark:bg-gray-600 rounded mb-2"></div>
                  <div className="h-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                </div>
                <div className="bg-gray-200 dark:bg-gray-700 p-4 rounded-lg">
                  <div className="h-8 bg-gray-300 dark:bg-gray-600 rounded mb-2"></div>
                  <div className="h-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                </div>
                <div className="bg-gray-200 dark:bg-gray-700 p-4 rounded-lg">
                  <div className="h-8 bg-gray-300 dark:bg-gray-600 rounded mb-2"></div>
                  <div className="h-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                </div>
              </div>
            </div>
          </div>
        ) : (
          <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                Estado de Solicitudes
              </h3>
              <div className="grid grid-cols-3 gap-4 mb-6">
                <div className="bg-amber-100 dark:bg-amber-900/30 p-4 rounded-lg text-center">
                  <div className="text-amber-600 dark:text-amber-400 text-2xl font-bold">{stats.pending_requests}</div>
                  <div className="text-gray-600 dark:text-gray-400 text-sm">Pendientes</div>
                </div>
                <div className="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-lg text-center">
                  <div className="text-blue-600 dark:text-blue-400 text-2xl font-bold">{stats.in_process_requests}</div>
                  <div className="text-gray-600 dark:text-gray-400 text-sm">En Proceso</div>
                </div>
                <div className="bg-green-100 dark:bg-green-900/30 p-4 rounded-lg text-center">
                  <div className="text-green-600 dark:text-green-400 text-2xl font-bold">{stats.completed_requests}</div>
                  <div className="text-gray-600 dark:text-gray-400 text-sm">Completados</div>
                </div>
              </div>

              {/* Progreso de solicitudes */}
              <div className="mt-4 mb-6">
                <div className="flex justify-between items-center mb-2">
                  <div className="text-sm font-medium text-gray-700 dark:text-gray-300">Progreso de solicitudes</div>
                  <div className="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {stats.completed_requests} de {stats.total_requests} completadas
                  </div>
                </div>
                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                  <div
                    className="bg-primary-600 h-2.5 rounded-full transition-all duration-300"
                    style={{ width: `${stats.total_requests > 0 ? (stats.completed_requests / stats.total_requests) * 100 : 0}%` }}
                  ></div>
                </div>
              </div>

              <div className="mt-6 flex space-x-4">
                <Link
                  to="/doctor/solicitudes/nueva"
                  className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  <ClockIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                  Nueva Solicitud
                </Link>
                <Link
                  to="/doctor/todas-solicitudes"
                  className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  <ArrowPathIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                  Ver Todas
                </Link>
              </div>
            </div>
          </div>
        )}

        {recentLoading ? (
          <SkeletonActivity />
        ) : (
          <RecentActivity
            title="Solicitudes Recientes"
            items={recentRequests}
          />
        )}
      </div>
    </div>
  );
}
