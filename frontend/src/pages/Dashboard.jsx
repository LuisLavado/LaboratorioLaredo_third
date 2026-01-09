import { useState, useEffect } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import {
  UserIcon,
  BeakerIcon,
  DocumentTextIcon,
  ClipboardDocumentCheckIcon,
  BuildingOfficeIcon,
  CalendarIcon
} from '@heroicons/react/24/outline';
import { patientsAPI, examsAPI, requestsAPI, servicesAPI } from '../services/api';
import { useAuth } from '../contexts/AuthContext';
import NotificationStatus from '../components/notifications/NotificationStatus';
import useNavigationCache from '../hooks/useNavigationCache';
import toast from 'react-hot-toast';

// Dashboard card component
function DashboardCard({ title, value, icon, color, to }) {
  return (
    <Link
      to={to}
      className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg transition-all hover:shadow-md"
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
          <div className="font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
            Ver todos
          </div>
        </div>
      </div>
    </Link>
  );
}



export default function Dashboard() {
  const { user, token, isDoctor, isLabTechnician } = useAuth();
  const queryClient = useQueryClient();

  // Add navigation cache invalidation for dashboard-specific queries
  useNavigationCache([
    ['requests'],
    ['pending-requests'], 
    ['patients'],
    ['exams'],
    ['services']
  ]);

  console.log('Dashboard - Usuario actual:', user);
  console.log('Dashboard - ¿Es doctor?', isDoctor());
  console.log('Dashboard - ¿Es técnico de laboratorio?', isLabTechnician());

  // Redirect doctors to their specific dashboard
  if (isDoctor()) {
    console.log('Redirigiendo doctor al dashboard específico');
    return <Navigate to="/doctor" replace />;
  }

  // Efecto para invalidar la consulta de solicitudes periódicamente
  useEffect(() => {
    // Crear un intervalo para invalidar la consulta de solicitudes cada 5 segundos
    // DESACTIVADO TEMPORALMENTE
    /*
    const interval = setInterval(() => {
      console.log('Invalidando consulta de solicitudes...');
      queryClient.invalidateQueries(['requests']);

      // Mostrar un mensaje toast cada 30 segundos (cada 6 actualizaciones)
      const now = new Date();
      if (now.getSeconds() % 30 === 0) {
        toast.success('Actualizando datos automáticamente...', {
          duration: 2000,
          position: 'bottom-right',
          icon: '🔄'
        });
      }
    }, 5000);
    */
    const interval = null; // DESACTIVADO

    // Limpiar el intervalo al desmontar el componente
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [queryClient]);

  // Verificar que el usuario sea técnico de laboratorio
  if (!isLabTechnician()) {
    console.error('Usuario no es técnico de laboratorio:', user);

    // Si el usuario tiene un rol pero no es laboratorio ni doctor, asumimos que es laboratorio
    if (user && user.role && user.role !== 'doctor' && user.role !== 'laboratorio') {
      console.log('Corrigiendo rol del usuario a laboratorio');
      // Actualizar el usuario en localStorage
      const correctedUser = { ...user, role: 'laboratorio' };
      localStorage.setItem('user', JSON.stringify(correctedUser));
      // Recargar la página para aplicar los cambios
      window.location.reload();
      return null;
    }

    // No redirigimos aquí, dejamos que el RoleProtectedRoute se encargue
  }

  const [stats, setStats] = useState({
    patients: 0,
    exams: 0,
    requests: 0,
    completedRequests: 0,
    pendingRequests: 0,
    inProcessRequests: 0,
    services: 0,
    activeExams: 0,
    inactiveExams: 0
  });

  const [recentRequests, setRecentRequests] = useState([]);
  const [pendingGeneralRequests, setPendingGeneralRequests] = useState([]);

  // Fetch patients count (excluding deleted)
  const { data: patientsData } = useQuery(['patients'], () =>
    patientsAPI.getAll().then(res => res.data)
  );

  // Fetch exams
  const { data: examsData } = useQuery(['exams'], () =>
    examsAPI.getAll({ all: true }).then(res => res.data)
  );

  // Fetch requests with status - con actualizaciones automáticas más frecuentes
  const { data: requestsData, isLoading: requestsLoading } = useQuery(
    ['requests'],
    () => requestsAPI.getAllWithStatus().then(res => res.data),
    {
      refetchInterval: false, // DESACTIVADO TEMPORALMENTE
      refetchOnWindowFocus: true,
      staleTime: 2000, // Consider data stale after 2 seconds (reduced for back navigation)
      retry: 3, // Retry 3 times if the request fails
      retryDelay: 1000, // Wait 1 second between retries
      refetchOnMount: 'always', // Always refetch when component mounts
      refetchOnReconnect: true, // Refetch when reconnecting
    }
  );

  // Fetch pending requests (not limited to today)
  const { data: pendingRequestsData, isLoading: pendingRequestsLoading } = useQuery(
    ['pending-requests'],
    async () => {
      console.log('Ejecutando consulta de solicitudes pendientes generales');
      try {
        const res = await requestsAPI.getPendingRequests({ not_today: 'true' });
        console.log('Datos de solicitudes pendientes generales recibidos:', res.data);
        return res.data;
      } catch (error) {
        console.error('Error al obtener solicitudes pendientes generales:', error);
        toast.error('Error al cargar solicitudes pendientes generales');
        return [];
      }
    },
    {
      refetchInterval: false, // DESACTIVADO TEMPORALMENTE
      refetchOnWindowFocus: true,
      staleTime: 2000, // Consider data stale after 2 seconds (reduced for back navigation)
      retry: 3,
      retryDelay: 1000,
      refetchOnMount: 'always', // Always refetch when component mounts
      refetchOnReconnect: true,
    }
  );

  // Fetch services
  const { data: servicesData } = useQuery(['services'], () =>
    servicesAPI.getAll().then(res => res.data)
  );

  // Process pending general requests
  useEffect(() => {
    console.log('Efecto de procesamiento de solicitudes pendientes generales ejecutado');
    console.log('pendingRequestsData:', pendingRequestsData);

    if (pendingRequestsData) {
      try {
        // Verificar que pendingRequestsData sea un array válido
        if (!Array.isArray(pendingRequestsData)) {
          console.error('Error: pendingRequestsData no es un array', pendingRequestsData);
          setPendingGeneralRequests([]);
          return;
        }

        console.log('Cantidad de solicitudes pendientes recibidas:', pendingRequestsData.length);

        // Filtrar solicitudes inválidas (sin fecha o sin ID)
        const validRequests = pendingRequestsData.filter(req => {
          const isValid = req && req.id && (req.created_at || req.fecha) && req.estado_calculado === 'pendiente';
          if (!isValid) {
            console.log('Solicitud inválida:', req);
          }
          return isValid;
        });

        console.log('Solicitudes válidas después de filtrar:', validRequests.length);

        if (validRequests.length === 0) {
          console.warn('No hay solicitudes pendientes generales para mostrar');
          setPendingGeneralRequests([]);
          return;
        }

        // Ordenar por fecha de creación (más antiguas primero para mostrar las que llevan más tiempo pendientes)
        const sortedRequests = [...validRequests].sort((a, b) => {
          const dateA = new Date(a.created_at || a.fecha);
          const dateB = new Date(b.created_at || b.fecha);
          return dateA - dateB;
        });

        console.log('Solicitudes ordenadas por fecha (más antiguas primero):',
          sortedRequests.map(r => ({ id: r.id, fecha: r.created_at || r.fecha }))
        );

        // Tomar hasta 10 solicitudes y mapearlas al formato requerido
        const pendingGeneral = sortedRequests.slice(0, 10).map(req => {
          // Verificar que los datos del paciente existan
          const pacienteNombre = req.paciente
            ? `${req.paciente.nombres || ''} ${req.paciente.apellidos || ''}`.trim()
            : 'Paciente sin nombre';

          // Contar exámenes de manera segura
          const examenesCount = req.examenes?.length || req.detalles?.length || 0;

          // Formatear fecha de manera segura
          let formattedDate;
          try {
            const date = new Date(req.created_at || req.fecha);
            formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
          } catch (error) {
            console.error('Error al formatear fecha:', error);
            formattedDate = 'Fecha desconocida';
          }

          // Calcular días pendientes
          let daysPending = 0;
          try {
            const createdDate = new Date(req.created_at || req.fecha);
            const today = new Date();
            const diffTime = Math.abs(today - createdDate);
            daysPending = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
          } catch (error) {
            console.error('Error al calcular días pendientes:', error);
          }

          return {
            id: req.id,
            title: `Solicitud #${req.id} - ${pacienteNombre}`,
            description: `${examenesCount} exámenes solicitados`,
            date: formattedDate,
            daysPending: daysPending,
            service: req.servicio?.nombre || 'Sin servicio',
            to: `/solicitudes/${req.id}`
          };
        });

        console.log('Solicitudes pendientes generales procesadas:', pendingGeneral);
        setPendingGeneralRequests(pendingGeneral);
      } catch (error) {
        console.error('Error al procesar solicitudes pendientes generales:', error);
        setPendingGeneralRequests([]);
      }
    } else {
      console.log('No hay datos de solicitudes pendientes generales');
    }
  }, [pendingRequestsData]);

  // Update stats when data is loaded
  useEffect(() => {
    if (patientsData && examsData && requestsData && servicesData) {
      const patients = patientsData.pacientes || [];
      // Asegurarse de acceder correctamente a los exámenes
      const exams = examsData.examenes || examsData || [];
      const requests = requestsData || [];
      const services = servicesData.servicios || [];

      // Calcular estadísticas
      const completedRequests = requests.filter(req => req.estado_calculado === 'completado').length || 0;
      const pendingRequests = requests.filter(req => req.estado_calculado === 'pendiente').length || 0;
      const inProcessRequests = requests.filter(req => req.estado_calculado === 'en_proceso').length || 0;
      const activeExams = exams.filter(exam => exam.activo === true).length || 0;
      const inactiveExams = exams.filter(exam => exam.activo === false).length || 0;

      setStats({
        patients: patients.length || 0,
        exams: exams.length || 0,
        requests: requests.length || 0,
        completedRequests,
        pendingRequests,
        inProcessRequests,
        services: services.length || 0,
        activeExams,
        inactiveExams
      });

      // No necesitamos preparar datos para gráficos ya que no estamos usando Chart.js

      // Set recent requests con manejo mejorado de errores
      try {
        // Verificar que requests sea un array válido
        if (!Array.isArray(requests)) {
          console.error('Error: requests no es un array', requests);
          return;
        }

        // Filtrar solicitudes inválidas (sin fecha o sin ID)
        const validRequests = requests.filter(req =>
          req && req.id && (req.created_at || req.fecha)
        );

        if (validRequests.length === 0) {
          console.warn('No hay solicitudes válidas para mostrar');
          setRecentRequests([]);
          return;
        }

        // Ordenar por fecha de creación (más recientes primero)
        const sortedRequests = [...validRequests].sort((a, b) => {
          const dateA = new Date(a.created_at || a.fecha);
          const dateB = new Date(b.created_at || b.fecha);
          return dateB - dateA;
        });

        // Tomar las 5 más recientes y mapearlas al formato requerido
        const recent = sortedRequests.slice(0, 5).map(req => {
          // Verificar que los datos del paciente existan
          const pacienteNombre = req.paciente
            ? `${req.paciente.nombres || ''} ${req.paciente.apellidos || ''}`.trim()
            : 'Paciente sin nombre';

          // Contar exámenes de manera segura
          const examenesCount = req.examenes?.length || req.detalles?.length || 0;

          // Formatear fecha de manera segura
          let formattedDate;
          try {
            formattedDate = new Date(req.created_at || req.fecha).toLocaleDateString();
          } catch (error) {
            console.error('Error al formatear fecha:', error);
            formattedDate = 'Fecha desconocida';
          }

          return {
            title: `Solicitud #${req.id} - ${pacienteNombre}`,
            description: `${examenesCount} exámenes solicitados`,
            status: req.estado_calculado || 'pendiente',
            date: formattedDate,
            to: `/solicitudes/${req.id}`
          };
        });

        console.log('Solicitudes recientes procesadas:', recent.length);
        setRecentRequests(recent);
      } catch (error) {
        console.error('Error al procesar solicitudes recientes:', error);
        // En caso de error, mostrar un array vacío para evitar que la UI se rompa
        setRecentRequests([]);
      }
    }
  }, [patientsData, examsData, requestsData, servicesData]);

  return (
    <div>
      <div className="mb-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard</h1>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Bienvenido al sistema de laboratorio clínico
            </p>
            <p className="text-sm text-gray-500 dark:text-gray-400">
              Usuario: {user?.name || 'No disponible'} | Rol: {user?.role || 'No disponible'}
            </p>
          </div>
          <button
            onClick={() => {
              console.log('Usuario actual:', user);
              console.log('Token:', token);
              console.log('LocalStorage user:', JSON.parse(localStorage.getItem('user') || '{}'));
              alert('Información de depuración enviada a la consola');
            }}
            className="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs"
          >
            Debug
          </button>
        </div>
      </div>

      {/* Estado de notificaciones */}
      <NotificationStatus />

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <DashboardCard
          title="Pacientes"
          value={stats.patients}
          icon={<UserIcon className="h-6 w-6 text-white" aria-hidden="true" />}
          color="bg-blue-500"
          to="/pacientes"
        />
        <DashboardCard
          title="Exámenes"
          value={stats.exams}
          icon={<BeakerIcon className="h-6 w-6 text-white" aria-hidden="true" />}
          color="bg-green-500"
          to="/examenes"
        />
        <DashboardCard
          title="Solicitudes"
          value={stats.requests}
          icon={<DocumentTextIcon className="h-6 w-6 text-white" aria-hidden="true" />}
          color="bg-purple-500"
          to="/solicitudes"
        />
        <DashboardCard
          title="Resultados Completados"
          value={stats.completedRequests}
          icon={<ClipboardDocumentCheckIcon className="h-6 w-6 text-white" aria-hidden="true" />}
          color="bg-yellow-500"
          to="/resultados"
        />
      </div>

      {/* Estadísticas adicionales */}
      <div className="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <DashboardCard
          title="Servicios"
          value={stats.services}
          icon={<BuildingOfficeIcon className="h-6 w-6 text-white" aria-hidden="true" />}
          color="bg-indigo-500"
          to="/servicios"
        />
        <DashboardCard
          title="Exámenes Activos"
          value={stats.activeExams}
          icon={<BeakerIcon className="h-6 w-6 text-white" aria-hidden="true" />}
          color="bg-emerald-500"
          to="/examenes"
        />
        <DashboardCard
          title="Solicitudes Pendientes"
          value={stats.pendingRequests}
          icon={<CalendarIcon className="h-6 w-6 text-white" aria-hidden="true" />}
          color="bg-amber-500"
          to="/solicitudes"
        />
        <DashboardCard
          title="Solicitudes En Proceso"
          value={stats.inProcessRequests}
          icon={<DocumentTextIcon className="h-6 w-6 text-white" aria-hidden="true" />}
          color="bg-sky-500"
          to="/solicitudes"
        />
      </div>

      {/* Estadísticas y actividad reciente */}
      <div className="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Estado de Solicitudes
            </h3>
            <div className="grid grid-cols-3 gap-4 mb-6">
              <div className="bg-amber-100 dark:bg-amber-900/30 p-4 rounded-lg text-center">
                <div className="text-amber-600 dark:text-amber-400 text-2xl font-bold">{stats.pendingRequests}</div>
                <div className="text-gray-600 dark:text-gray-400 text-sm">Pendientes</div>
              </div>
              <div className="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-lg text-center">
                <div className="text-blue-600 dark:text-blue-400 text-2xl font-bold">{stats.inProcessRequests}</div>
                <div className="text-gray-600 dark:text-gray-400 text-sm">En Proceso</div>
              </div>
              <div className="bg-green-100 dark:bg-green-900/30 p-4 rounded-lg text-center">
                <div className="text-green-600 dark:text-green-400 text-2xl font-bold">{stats.completedRequests}</div>
                <div className="text-gray-600 dark:text-gray-400 text-sm">Completados</div>
              </div>
            </div>
          </div>
          <div className="bg-gray-50 dark:bg-gray-700 px-4 py-4 sm:px-6">
            <div className="space-y-4">
              <div>
                <div className="flex justify-between items-center">
                  <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Solicitudes completadas</div>
                  <div className="text-sm font-medium text-gray-900 dark:text-white">
                    {stats.completedRequests} de {stats.requests}
                  </div>
                </div>
                <div className="mt-2 relative pt-1">
                  <div className="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-600">
                    <div
                      style={{ width: `${stats.requests ? (stats.completedRequests / stats.requests) * 100 : 0}%` }}
                      className="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary-500"
                    ></div>
                  </div>
                </div>
              </div>

              <div className="pt-2">
                <Link
                  to="/reportes"
                  className="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700"
                >
                  Ver reportes detallados →
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
          <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div className="flex items-center">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">Solicitudes Recientes</h3>
              {requestsLoading && (
                <div className="ml-2 animate-spin rounded-full h-4 w-4 border-t-2 border-b-2 border-primary-500"></div>
              )}
            </div>
            <div className="text-xs text-gray-500 dark:text-gray-400">
              Actualización automática cada 10 segundos
            </div>
          </div>
          <ul className="divide-y divide-gray-200 dark:divide-gray-700">
            {requestsLoading ? (
              <li className="px-4 py-4 sm:px-6 text-center">
                <div className="flex justify-center">
                  <div className="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-primary-500"></div>
                </div>
                <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">Cargando solicitudes recientes...</p>
              </li>
            ) : recentRequests.length === 0 ? (
              <li className="px-4 py-4 sm:px-6 text-center">
                <p className="text-sm text-gray-500 dark:text-gray-400">No hay solicitudes recientes</p>
              </li>
            ) : (
              recentRequests.map((item, index) => (
                <li key={index}>
                  <Link to={item.to} className="block hover:bg-gray-50 dark:hover:bg-gray-700">
                    <div className="px-4 py-4 sm:px-6">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-primary-600 dark:text-primary-400 truncate">
                          {item.title}
                        </p>
                        <div className="ml-2 flex-shrink-0 flex">
                          <p className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            ${item.status === 'completado' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                              item.status === 'en_proceso' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'}`}>
                            {item.status === 'completado' ? 'Completado' :
                             item.status === 'en_proceso' ? 'En proceso' : 'Pendiente'}
                          </p>
                        </div>
                      </div>
                      <div className="mt-2 sm:flex sm:justify-between">
                        <div className="sm:flex">
                          <p className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            {item.description}
                          </p>
                        </div>
                        <div className="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:mt-0">
                          <p>
                            {item.date}
                          </p>
                        </div>
                      </div>
                    </div>
                  </Link>
                </li>
              ))
            )}
          </ul>
        </div>
      </div>

      {/* Solicitudes pendientes generales (no del día actual) */}
      <div className="mt-8 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
        <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
          <div className="flex items-center">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">Solicitudes Pendientes Generales</h3>
            {pendingRequestsLoading && (
              <div className="ml-2 animate-spin rounded-full h-4 w-4 border-t-2 border-b-2 border-amber-500"></div>
            )}
          </div>
          <div className="text-xs text-gray-500 dark:text-gray-400">
            Incluye solicitudes pendientes de días anteriores
          </div>
        </div>
        <ul className="divide-y divide-gray-200 dark:divide-gray-700">
          {pendingRequestsLoading ? (
            <li className="px-4 py-4 sm:px-6 text-center">
              <div className="flex justify-center">
                <div className="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-amber-500"></div>
              </div>
              <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">Cargando solicitudes pendientes...</p>
            </li>
          ) : pendingGeneralRequests.length === 0 ? (
            <li className="px-4 py-4 sm:px-6 text-center">
              <p className="text-sm text-gray-500 dark:text-gray-400">No hay solicitudes pendientes de días anteriores</p>
            </li>
          ) : (
            pendingGeneralRequests.map((item) => (
              <li key={item.id}>
                <Link to={item.to} className="block hover:bg-gray-50 dark:hover:bg-gray-700">
                  <div className="px-4 py-4 sm:px-6">
                    <div className="flex items-center justify-between">
                      <p className="text-sm font-medium text-amber-600 dark:text-amber-400 truncate">
                        {item.title}
                      </p>
                      <div className="ml-2 flex-shrink-0 flex">
                        <p className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                          ${item.daysPending > 2 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                           item.daysPending > 1 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' :
                           'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'}`}>
                          {item.daysPending > 1 ? `${item.daysPending} días pendiente` : 'Pendiente'}
                        </p>
                      </div>
                    </div>
                    <div className="mt-2 sm:flex sm:justify-between">
                      <div className="sm:flex">
                        <p className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                          {item.description}
                        </p>
                      </div>
                      <div className="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:mt-0">
                        <p className="mr-2">
                          {item.service}
                        </p>
                        <p>
                          {item.date}
                        </p>
                      </div>
                    </div>
                  </div>
                </Link>
              </li>
            ))
          )}
        </ul>
        <div className="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6">
          <div className="text-sm">
            <Link
              to="/solicitudes"
              className="font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700"
            >
              Ver todas las solicitudes →
            </Link>
          </div>
        </div>
      </div>

      {/* Estado de exámenes */}
      <div className="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
            Estado de Exámenes
          </h3>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-emerald-100 dark:bg-emerald-900/30 p-4 rounded-lg text-center">
                <div className="text-emerald-600 dark:text-emerald-400 text-2xl font-bold">{stats.activeExams}</div>
                <div className="text-gray-600 dark:text-gray-400 text-sm">Activos</div>
              </div>
              <div className="bg-red-100 dark:bg-red-900/30 p-4 rounded-lg text-center">
                <div className="text-red-600 dark:text-red-400 text-2xl font-bold">{stats.inactiveExams}</div>
                <div className="text-gray-600 dark:text-gray-400 text-sm">Inactivos</div>
              </div>
            </div>
            <div className="flex flex-col justify-center">
              <div className="space-y-4">
                <div>
                  <div className="flex justify-between items-center">
                    <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Exámenes activos</div>
                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                      {stats.activeExams} de {stats.exams}
                    </div>
                  </div>
                  <div className="mt-2 relative pt-1">
                    <div className="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-600">
                      <div
                        style={{ width: `${stats.exams ? (stats.activeExams / stats.exams) * 100 : 0}%` }}
                        className="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-emerald-500"
                      ></div>
                    </div>
                  </div>
                </div>
                <div className="pt-4">
                  <Link
                    to="/examenes"
                    className="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700"
                  >
                    Administrar exámenes →
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
