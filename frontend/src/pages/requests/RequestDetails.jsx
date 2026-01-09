import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { requestsAPI, requestDetailsAPI, patientsAPI, servicesAPI } from '../../services/api';
import api from '../../services/api';
import { ArrowLeftIcon, DocumentTextIcon, BeakerIcon, UserIcon, ClockIcon, BuildingOfficeIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

export default function RequestDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('details');
  const [request, setRequest] = useState(null);
  const [details, setDetails] = useState([]);
  const [user, setUser] = useState(null);
  const [service, setService] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch data
  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // Usar la función del servicio API en lugar de fetch directo
        const detailsResponse = await requestDetailsAPI.getByRequest(id);
        const detailsData = detailsResponse.data;
        console.log('Details API Response:', detailsData);

        if (detailsData && detailsData.data && detailsData.data.length > 0) {
          // Extraer la información de la solicitud del primer detalle
          const solicitudInfo = detailsData.data[0].solicitud;
          console.log('Solicitud extraída de detalles:', solicitudInfo);

          // Establecer la solicitud
          setRequest(solicitudInfo);

          // Establecer los detalles
          setDetails(detailsData.data);

          // La información del usuario ya viene incluida en solicitudInfo.user
          if (solicitudInfo.user) {
            setUser(solicitudInfo.user);
          }

          // La información del servicio ya viene incluida en solicitudInfo.servicio
          if (solicitudInfo.servicio) {
            setService(solicitudInfo.servicio);
          }
        } else {
          // Si no hay detalles, intentar obtener la solicitud directamente
          try {
            // Usar requestsAPI en lugar de fetch directo
            const requestResponse = await requestsAPI.getById(id);
            const requestData = requestResponse.data;
            console.log('Request API Response:', requestData);

            if (requestData) {
              setRequest(requestData);
            } else {
              setError('No se encontró la solicitud');
            }
          } catch (requestError) {
            console.error('Error fetching request:', requestError);
            throw requestError;
          }
        }
      } catch (err) {
        console.error('Error fetching data:', err);
        setError(err.message || 'Error al cargar los datos');
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [id]);

  // Format date
  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
      return format(new Date(dateString), 'dd MMMM yyyy', { locale: es });
    } catch (error) {
      console.error('Error formatting date:', error);
      return dateString;
    }
  };

  // Format time
  const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    return timeString.substring(0, 5); // Extract HH:MM
  };

  // Get status badge color
  const getStatusColor = (status) => {
    switch (status) {
      case 'pendiente':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
      case 'en_proceso':
        return 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100';
      case 'completado':
        return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100';
    }
  };

  // Format status text
  const formatStatus = (status) => {
    switch (status) {
      case 'pendiente':
        return 'Pendiente';
      case 'en_proceso':
        return 'En proceso';
      case 'completado':
        return 'Completado';
      default:
        return status;
    }
  };

  // Get attention type
  const getAttentionType = () => {
    if (!request) return 'Normal';
    if (request.rdr) return 'RDR';
    if (request.sis) return 'SIS';
    if (request.exon) return 'Exonerado';
    return 'Normal';
  };

  if (isLoading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
        <div className="flex">
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
              Error al cargar los detalles de la solicitud
            </h3>
            <div className="mt-2 text-sm text-red-700 dark:text-red-300">
              <p>{error}</p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!request) {
    return (
      <div className="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4">
        <div className="flex">
          <div className="ml-3">
            <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">
              Solicitud no encontrada
            </h3>
            <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
              <p>
                La solicitud que está buscando no existe o ha sido eliminada.
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <button
              type="button"
              onClick={() => navigate('/solicitudes')}
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </button>
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
              Solicitud #{request.id}
            </h1>
          </div>
          <div className="flex space-x-3">
            <Link
              to={`/resultados/${id}`}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              Ver Resultados
            </Link>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav className="-mb-px flex space-x-8" aria-label="Tabs">
          <button
            onClick={() => setActiveTab('details')}
            className={`${
              activeTab === 'details'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
          >
            Detalles de la Solicitud
          </button>
          <button
            onClick={() => setActiveTab('exams')}
            className={`${
              activeTab === 'exams'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
          >
            Exámenes ({details.length})
          </button>
        </nav>
      </div>

      {/* Content */}
      {activeTab === 'details' ? (
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Información de la Solicitud
              </h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Detalles y estado de la solicitud
              </p>
            </div>
            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(request.estado || 'pendiente')}`}>
              {formatStatus(request.estado || 'pendiente')}
            </span>
          </div>
          <div className="border-t border-gray-200 dark:border-gray-700">
            <dl>
              <div className="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-300 flex items-center">
                  <ClockIcon className="h-5 w-5 mr-2 text-gray-400 dark:text-gray-500" />
                  Fecha y Hora
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {request.fecha ? formatDate(request.fecha) : 'N/A'} a las {request.hora ? formatTime(request.hora) : 'N/A'}
                </dd>
              </div>
              <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-300 flex items-center">
                  <UserIcon className="h-5 w-5 mr-2 text-gray-400 dark:text-gray-500" />
                  Paciente
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  <div>
                    {request.paciente ? (
                      <>
                        <p className="font-medium">{request.paciente.nombres} {request.paciente.apellidos}</p>
                        <p className="text-gray-500 dark:text-gray-400">DNI: {request.paciente.dni}</p>
                        <p className="text-gray-500 dark:text-gray-400">Historia Clínica: {request.paciente.historia_clinica || 'N/A'}</p>
                      </>
                    ) : (
                      <p className="text-gray-500 dark:text-gray-400">ID del paciente: {request.paciente_id || 'No disponible'}</p>
                    )}
                  </div>
                </dd>
              </div>
              <div className="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-300 flex items-center">
                  <BuildingOfficeIcon className="h-5 w-5 mr-2 text-gray-400 dark:text-gray-500" />
                  Servicio
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {service ? service.nombre : `ID del servicio: ${request.servicio_id || 'No disponible'}`}
                </dd>
              </div>
              <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-300 flex items-center">
                  <DocumentTextIcon className="h-5 w-5 mr-2 text-gray-400 dark:text-gray-500" />
                  Número de Recibo
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {request.numero_recibo || 'No registrado'}
                </dd>
              </div>
              <div className="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                  Tipo de Atención
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {getAttentionType()}
                </dd>
              </div>
              <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                  Registrado por
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {user ? (user.name || `${user.nombre || ''} ${user.apellido || ''}`) : `ID del usuario: ${request.user_id || 'No disponible'}`}
                </dd>
              </div>
              <div className="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-300">
                  Fecha de Registro
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {formatDate(request.created_at)}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      ) : (
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              Exámenes Solicitados
            </h3>
            <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
              Lista de exámenes incluidos en esta solicitud
            </p>
          </div>
          <div className="border-t border-gray-200 dark:border-gray-700">
            {details && details.length > 0 ? (
              <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                {details.map((detail) => (
                  <li key={detail.id} className="px-4 py-4">
                    <div className="flex items-center justify-between">
                      <div className="flex items-start">
                        <BeakerIcon className="h-5 w-5 mr-3 text-gray-400 dark:text-gray-500 mt-0.5" />
                        <div>
                          <p className="text-sm font-medium text-gray-900 dark:text-white">
                            {detail.examen ? `${detail.examen.codigo} - ${detail.examen.nombre}` : `Examen ID: ${detail.examen_id}`}
                          </p>
                          {detail.examen?.categoria?.nombre && (
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                              Categoría: {detail.examen.categoria.nombre}
                            </p>
                          )}
                        </div>
                      </div>
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(detail.estado)}`}>
                        {formatStatus(detail.estado)}
                      </span>
                    </div>
                    {detail.observaciones && (
                      <div className="mt-2 ml-8">
                        <p className="text-xs font-medium text-gray-500 dark:text-gray-400">Observaciones:</p>
                        <p className="text-sm text-gray-700 dark:text-gray-300">{detail.observaciones}</p>
                      </div>
                    )}
                  </li>
                ))}
              </ul>
            ) : (
              <div className="px-4 py-5 text-center text-sm text-gray-500 dark:text-gray-400">
                No hay exámenes registrados para esta solicitud
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
