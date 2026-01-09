import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { requestsAPI, requestDetailsAPI } from '../../services/api';
import { ArrowLeftIcon, PrinterIcon, DocumentTextIcon, BeakerIcon, UserIcon, BuildingOfficeIcon, QrCodeIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import toast from 'react-hot-toast';

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

export default function RequestDetail() {
  const { id } = useParams();
  const [qrCode, setQrCode] = useState(null);
  const [showQr, setShowQr] = useState(false);
  const [request, setRequest] = useState(null);
  const [details, setDetails] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch data
  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // Fetch details
        const detailsResponse = await requestDetailsAPI.getByRequest(id);
        const detailsData = detailsResponse.data;
        console.log('Details API Response:', detailsData);

        if (detailsData && detailsData.data && detailsData.data.length > 0) {
          // Extract request info from the first detail
          const requestInfo = detailsData.data[0].solicitud;
          console.log('Solicitud extraída de detalles:', requestInfo);

          // Set request and details
          setRequest(requestInfo);
          setDetails(detailsData.data);
        } else {
          // If no details, try to get the request directly
          const requestResponse = await requestsAPI.getById(id);
          const requestData = requestResponse.data;
          console.log('Request API Response:', requestData);

          if (requestData) {
            setRequest(requestData);
          } else {
            setError('No se encontró la solicitud');
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

  // Generate QR code
  const handleGenerateQr = async () => {
    try {
      const response = await requestsAPI.generateQr(id);
      setQrCode(response.data.qr_code);
      setShowQr(true);
    } catch (error) {
      console.error('Error generating QR code:', error);
      toast.error('Error al generar el código QR');
    }
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 dark:bg-red-900/30 p-4 rounded-md">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800 dark:text-red-200">Error al cargar la solicitud</h3>
          </div>
        </div>
      </div>
    );
  }

  // Verificar si tenemos datos para mostrar
  if (!request) {
    return (
      <div className="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-md">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">Solicitud no encontrada</h3>
            <p className="mt-1 text-sm text-yellow-700 dark:text-yellow-300">La solicitud que está buscando no existe o ha sido eliminada.</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="sm:flex sm:items-center mb-6">
        <div className="sm:flex-auto">
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Detalle de Solicitud</h1>
          <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Información detallada de la solicitud #{request.id}
          </p>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none space-x-3">
          <Link
            to="/doctor/solicitudes"
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 sm:w-auto"
          >
            <ArrowLeftIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Volver
          </Link>
          {/* Botón de QR Code */}
          <button
            type="button"
            onClick={handleGenerateQr}
            className="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
          >
            <QrCodeIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Generar QR
          </button>
          <Link
            to={`/doctor/solicitudes/${id}/imprimir`}
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
          >
            <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Imprimir
          </Link>

        </div>
      </div>

      {/* Sección de Resultados si hay exámenes completados */}
      {details.length > 0 && details.some(detail => detail.estado === 'completado') && (
        <div className="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  📋 Resultados Disponibles
                </h3>
                <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                  {details.filter(detail => detail.estado === 'completado').length} examen(es) completado(s) y listo(s) para revisar. Use el botón "Imprimir" del header para generar PDF.
                </p>
              </div>
              <div className="flex justify-center">
                <Link
                  to={`/doctor/resultados/${id}/ver`}
                  className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200 shadow-lg"
                >
                  <BeakerIcon className="-ml-1 mr-3 h-6 w-6" />
                  Ver Resultados Detallados
                </Link>
              </div>
            </div>
          </div>

          <div className="px-4 py-5 sm:p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {details.filter(detail => detail.estado === 'completado').map((detail) => (
                <div
                  key={detail.id}
                  className="border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20 rounded-lg p-4"
                >
                  <div className="flex items-center justify-between mb-2">
                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                      {detail.examen?.nombre}
                    </h4>
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                      Completado
                    </span>
                  </div>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    Código: {detail.examen?.codigo}
                  </p>
                  {detail.fecha_resultado && (
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      Completado: {format(new Date(detail.fecha_resultado), 'dd/MM/yyyy HH:mm')}
                    </p>
                  )}
                </div>
              ))}
            </div>

            {/* Resumen de estado */}
            <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div>
                  <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                    {details.filter(detail => detail.estado === 'completado').length}
                  </div>
                  <div className="text-sm text-gray-500 dark:text-gray-400">Completados</div>
                </div>
                <div>
                  <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {details.filter(detail => detail.estado === 'en_proceso').length}
                  </div>
                  <div className="text-sm text-gray-500 dark:text-gray-400">En Proceso</div>
                </div>
                <div>
                  <div className="text-2xl font-bold text-amber-600 dark:text-amber-400">
                    {details.filter(detail => detail.estado === 'pendiente').length}
                  </div>
                  <div className="text-sm text-gray-500 dark:text-gray-400">Pendientes</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* QR Code Modal */}
      {showQr && qrCode && (
        <div className="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onClick={() => setShowQr(false)}></div>
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full sm:p-6">
              <div>
                <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900">
                  <QrCodeIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-300" aria-hidden="true" />
                </div>
                <div className="mt-3 text-center sm:mt-5">
                  <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                    Código QR de la Solicitud
                  </h3>
                  <div className="mt-4">
                    <img src={qrCode} alt="QR Code" className="h-64 w-64 mx-auto" />
                  </div>
                  <div className="mt-4">
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      Este código QR contiene la información de la solicitud y puede ser escaneado por el personal de laboratorio.
                    </p>
                  </div>
                </div>
              </div>
              <div className="mt-5 sm:mt-6">
                <button
                  type="button"
                  className="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:text-sm"
                  onClick={() => setShowQr(false)}
                >
                  Cerrar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
          <div>
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              Solicitud #{request.id}
            </h3>
            <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
              Creada el {request.created_at ? format(new Date(request.created_at), 'dd/MM/yyyy') : (request.fecha ? format(new Date(request.fecha), 'dd/MM/yyyy') : 'fecha no disponible')}
            </p>
          </div>
          <StatusBadge status={request.estado_calculado || 'pendiente'} />
        </div>
        <div className="border-t border-gray-200 dark:border-gray-700">
          <dl>
            <div className="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Solicitud</dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {request.fecha ? (() => {
                  try {
                    return format(new Date(request.fecha), 'dd/MM/yyyy');
                  } catch (e) {
                    console.error('Error al formatear fecha:', e);
                    return 'Fecha no disponible';
                  }
                })() : 'Fecha no disponible'}
                {request.hora ? (() => {
                  try {
                    // Asegurarse de que la hora tenga el formato correcto (HH:mm:ss)
                    const horaFormateada = request.hora.length <= 5 ? `${request.hora}:00` : request.hora;
                    return ` a las ${format(new Date(`2000-01-01T${horaFormateada}`), 'HH:mm')}`;
                  } catch (e) {
                    console.error('Error al formatear hora:', e);
                    return '';
                  }
                })() : ''}
              </dd>
            </div>
            <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Número de Recibo</dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{request.numero_recibo}</dd>
            </div>
            <div className="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Servicio</dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{request.servicio?.nombre}</dd>
            </div>
            <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Atención</dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {request.sis ? 'SIS' : request.rdr ? 'RDR' : request.exon ? 'Exonerado' : 'Normal'}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Patient Information */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 flex items-center">
            <UserIcon className="h-6 w-6 text-gray-400 mr-2" aria-hidden="true" />
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              Información del Paciente
            </h3>
          </div>
          <div className="border-t border-gray-200 dark:border-gray-700">
            <dl>
              <div className="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre Completo</dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {request.paciente?.nombres} {request.paciente?.apellidos}
                </dd>
              </div>
              <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">DNI</dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{request.paciente?.dni}</dd>
              </div>
              <div className="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Historia Clínica</dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{request.paciente?.historia_clinica || 'No registrada'}</dd>
              </div>
              <div className="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Edad</dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{request.paciente?.edad} años</dd>
              </div>
              <div className="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Sexo</dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {request.paciente?.sexo === 'masculino' ? 'Masculino' : 'Femenino'}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        {/* Exams Information */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 flex items-center">
            <BeakerIcon className="h-6 w-6 text-gray-400 mr-2" aria-hidden="true" />
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              Exámenes Solicitados
            </h3>
          </div>
          <div className="border-t border-gray-200 dark:border-gray-700">
            <div className="px-4 py-5 sm:px-6">
              <div className="flow-root">
                <ul className="-mb-8">
                  {details && details.length > 0 ? (
                    details.map((detail, idx) => (
                      <li key={detail.id}>
                        <div className="relative pb-8">
                          {idx !== details.length - 1 ? (
                            <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                          ) : null}
                          <div className="relative flex space-x-3">
                            <div>
                              <span className="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                <DocumentTextIcon className="h-5 w-5 text-primary-600 dark:text-primary-300" aria-hidden="true" />
                              </span>
                            </div>
                            <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                              <div>
                                <p className="text-sm text-gray-900 dark:text-white font-medium">
                                  {detail.examen?.nombre || `Examen ID: ${detail.examen_id}`}
                                </p>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                  Código: {detail.examen?.codigo || 'N/A'} | Categoría: {detail.examen?.categoria?.nombre || 'Sin categoría'}
                                </p>
                              </div>
                              <div className="text-right text-sm whitespace-nowrap">
                                <StatusBadge status={detail.estado || 'pendiente'} />
                              </div>
                            </div>
                          </div>
                        </div>
                      </li>
                    ))
                  ) : request.examenes?.map((exam, examIdx) => (
                    <li key={exam.id}>
                      <div className="relative pb-8">
                        {examIdx !== request.examenes.length - 1 ? (
                          <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                        ) : null}
                        <div className="relative flex space-x-3">
                          <div>
                            <span className="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                              <DocumentTextIcon className="h-5 w-5 text-primary-600 dark:text-primary-300" aria-hidden="true" />
                            </span>
                          </div>
                          <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                            <div>
                              <p className="text-sm text-gray-900 dark:text-white font-medium">
                                {exam.nombre}
                              </p>
                              <p className="text-sm text-gray-500 dark:text-gray-400">
                                Código: {exam.codigo} | Categoría: {exam.categoria?.nombre || 'Sin categoría'}
                              </p>
                            </div>
                            <div className="text-right text-sm whitespace-nowrap">
                              <StatusBadge status={'pendiente'} />
                            </div>
                          </div>
                        </div>
                      </div>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Eliminamos la sección de resultados y solo dejamos un botón para ver resultados */}
    </div>
  );
}
