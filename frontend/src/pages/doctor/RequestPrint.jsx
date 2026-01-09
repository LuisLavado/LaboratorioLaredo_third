import { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { requestsAPI, requestDetailsAPI } from '../../services/api';
import { ArrowLeftIcon, PrinterIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import toast from 'react-hot-toast';

// Status badge component
function StatusBadge({ status }) {
  let bgColor, textColor, label;

  switch (status) {
    case 'completado':
      bgColor = 'bg-green-100';
      textColor = 'text-green-800';
      label = 'Completado';
      break;
    case 'en_proceso':
      bgColor = 'bg-blue-100';
      textColor = 'text-blue-800';
      label = 'En Proceso';
      break;
    default:
      bgColor = 'bg-amber-100';
      textColor = 'text-amber-800';
      label = 'Pendiente';
  }

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${bgColor} ${textColor}`}>
      {label}
    </span>
  );
}

export default function RequestPrint() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const printRef = useRef();

  // Enhanced back navigation handler that invalidates doctor dashboard cache
  const handleBackNavigation = () => {
    console.log('Navigating back from doctor print - invalidating cache');
    
    // Invalidate doctor dashboard-related queries to ensure fresh data
    queryClient.invalidateQueries(['patients']);
    queryClient.invalidateQueries(['doctor-requests']);
    
    // Use navigate(-1) to go back
    navigate(-1);
  };
  const [request, setRequest] = useState(null);
  const [details, setDetails] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [qrCode, setQrCode] = useState(null);

  // Fetch data
  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // Fetch request
        const requestResponse = await requestsAPI.getById(id);
        const requestData = requestResponse.data;

        // Fetch details
        const detailsResponse = await requestDetailsAPI.getByRequest(id);
        const detailsData = detailsResponse.data?.data || [];

        // Fetch QR code
        try {
          const qrResponse = await requestsAPI.generateQr(id);
          setQrCode(qrResponse.data.qr_code);
        } catch (qrError) {
          console.error('Error fetching QR code:', qrError);
          // Continue without QR code
        }

        setRequest(requestData);
        setDetails(detailsData);
        setIsLoading(false);
      } catch (err) {
        console.error('Error fetching data:', err);
        setError(err.message || 'Error al cargar los datos');
        setIsLoading(false);
      }
    };

    fetchData();
  }, [id]);

  // Handle print
  const handlePrint = () => {
    if (printRef.current) {
      const printContents = printRef.current.innerHTML;
      const originalContents = document.body.innerHTML;

      // Create a new window with only the print content
      const printWindow = window.open('', '_blank');

      if (!printWindow) {
        toast.error('Por favor, permita las ventanas emergentes para imprimir');
        return;
      }

      printWindow.document.write(`
        <html>
          <head>
            <title>Solicitud #${id} - Impresión</title>
            <style>
              body {
                font-family: Arial, sans-serif;
                line-height: 1.5;
                color: #111827;
              }
              .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
              }
              .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 10px;
              }
              .section {
                margin-bottom: 20px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 15px;
              }
              .section-title {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 10px;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 5px;
              }
              .grid {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 8px;
              }
              .label {
                font-weight: bold;
              }
              .value {
                margin-bottom: 5px;
              }
              .exam-list {
                list-style: none;
                padding: 0;
              }
              .exam-item {
                padding: 8px 0;
                border-bottom: 1px solid #e5e7eb;
              }
              .exam-item:last-child {
                border-bottom: none;
              }
              .status {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 9999px;
                font-size: 12px;
                font-weight: 500;
              }
              .status-pending {
                background-color: #fef3c7;
                color: #92400e;
              }
              .status-processing {
                background-color: #dbeafe;
                color: #1e40af;
              }
              .status-completed {
                background-color: #d1fae5;
                color: #065f46;
              }
              .qr-code {
                text-align: center;
                margin-top: 20px;
              }
              .qr-code img {
                max-width: 150px;
                height: auto;
              }
              @media print {
                body {
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                }
              }
            </style>
          </head>
          <body>
            <div class="container">
              ${printContents}
            </div>
            <script>
              window.onload = function() {
                window.print();
                window.setTimeout(function() {
                  window.close();
                }, 500);
              };
            </script>
          </body>
        </html>
      `);

      printWindow.document.close();
    }
  };

  // Calculate overall status
  const calculateStatus = () => {
    if (!details || details.length === 0) return 'pendiente';

    const completados = details.filter(d => d.estado === 'completado').length;
    const enProceso = details.filter(d => d.estado === 'en_proceso').length;

    if (completados === details.length) {
      return 'completado';
    } else if (enProceso > 0 || completados > 0) {
      return 'en_proceso';
    } else {
      return 'pendiente';
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
            <p className="mt-2 text-sm text-red-700 dark:text-red-300">{error}</p>
          </div>
        </div>
      </div>
    );
  }

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

  const status = calculateStatus();

  return (
    <div>
      {/* Header */}
      <div className="sm:flex sm:items-center mb-6">
        <div className="sm:flex-auto">
          <div className="flex items-center">
            <button
              type="button"
              onClick={handleBackNavigation}
              className="mr-4 rounded-full p-1 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none"
            >
              <ArrowLeftIcon className="h-6 w-6" aria-hidden="true" />
            </button>
            <div>
              <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
                Imprimir Solicitud #{request.id}
              </h1>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Vista previa para impresión
              </p>
            </div>
          </div>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <button
            type="button"
            onClick={handlePrint}
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
          >
            <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Imprimir
          </button>
        </div>
      </div>

      {/* Print Preview */}
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
        <div ref={printRef}>
          <div className="header">
            <h2 className="text-xl font-bold text-center">Laboratorio Clínico Laredo</h2>
            <p className="text-sm text-center">Solicitud de Exámenes #{request.id}</p>
            <p className="text-sm text-center">
              Fecha: {request.fecha && format(new Date(request.fecha), 'PPP', { locale: es })}
            </p>
          </div>

          <div className="section">
            <div className="section-title">Información del Paciente</div>
            <div className="grid">
              <div className="label">Nombre:</div>
              <div className="value">{request.paciente?.nombres} {request.paciente?.apellidos}</div>

              <div className="label">Sexo:</div>
              <div className="value">{request.paciente?.sexo === 'masculino' ? 'Masculino' : 'Femenino'}</div>
            </div>
          </div>

          <div className="section">
            <div className="section-title">Detalles de la Solicitud</div>
            <div className="grid">
              <div className="label">Fecha:</div>
              <div className="value">{request.fecha && format(new Date(request.fecha), 'PPP', { locale: es })}</div>

              <div className="label">Hora:</div>
              <div className="value">{request.hora || 'No especificada'}</div>

              <div className="label">Servicio:</div>
              <div className="value">{request.servicio?.nombre || 'No especificado'}</div>

              <div className="label">Número de Recibo:</div>
              <div className="value">{request.numero_recibo || 'No especificado'}</div>

              <div className="label">Estado:</div>
              <div className="value">
                <span className={`status ${
                  status === 'completado' ? 'status-completed' :
                  status === 'en_proceso' ? 'status-processing' :
                  'status-pending'
                }`}>
                  {status === 'completado' ? 'Completado' :
                   status === 'en_proceso' ? 'En Proceso' :
                   'Pendiente'}
                </span>
              </div>
            </div>
          </div>

          <div className="section">
            <div className="section-title">Exámenes Solicitados</div>
            <ul className="exam-list">
              {details && details.length > 0 ? (
                details.map((detail) => (
                  <li key={detail.id} className="exam-item">
                    <div className="flex justify-between">
                      <div>
                        <div className="font-medium">{detail.examen?.nombre}</div>
                        <div className="text-sm text-gray-500">
                          {detail.examen?.categoria?.nombre || 'Sin categoría'}
                        </div>
                      </div>
                      <span className={`status ${
                        detail.estado === 'completado' ? 'status-completed' :
                        detail.estado === 'en_proceso' ? 'status-processing' :
                        'status-pending'
                      }`}>
                        {detail.estado === 'completado' ? 'Completado' :
                         detail.estado === 'en_proceso' ? 'En Proceso' :
                         'Pendiente'}
                      </span>
                    </div>
                  </li>
                ))
              ) : request.examenes && request.examenes.length > 0 ? (
                request.examenes.map((examen) => (
                  <li key={examen.id} className="exam-item">
                    <div className="flex justify-between">
                      <div>
                        <div className="font-medium">{examen.nombre}</div>
                        <div className="text-sm text-gray-500">
                          {examen.categoria?.nombre || 'Sin categoría'}
                        </div>
                      </div>
                      <span className="status status-pending">Pendiente</span>
                    </div>
                  </li>
                ))
              ) : (
                <li className="exam-item text-center text-gray-500">
                  No hay exámenes registrados para esta solicitud
                </li>
              )}
            </ul>
          </div>

          {qrCode && (
            <div className="qr-code">
              <img src={qrCode} alt="Código QR" style={{ maxWidth: '80px', maxHeight: '80px' }} />
              <p className="text-xs text-gray-500">
                Escanee para ver resultados
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
