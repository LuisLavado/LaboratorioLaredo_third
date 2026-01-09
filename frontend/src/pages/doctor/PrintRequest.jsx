import { useState, useEffect, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { requestsAPI } from '../../services/api';
import { ArrowLeftIcon, PrinterIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import toast from 'react-hot-toast';
import { useAuth } from '../../contexts/AuthContext';

export default function PrintRequest() {
  const { id } = useParams();
  const { user } = useAuth();
  const printRef = useRef();
  const [qrCode, setQrCode] = useState(null);

  // Estado para almacenar los datos de la solicitud
  const [request, setRequest] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Efecto para cargar los datos
  useEffect(() => {
    const fetchData = async () => {
      try {
        setIsLoading(true);

        // Intentar obtener los datos a través del QR primero (que incluye todos los datos)
        const qrResponse = await requestsAPI.generateQr(id);
        console.log('QR response:', qrResponse.data);

        if (qrResponse.data.qr_code) {
          setQrCode(qrResponse.data.qr_code);
        }

        // Si la respuesta del QR incluye los datos de la solicitud, usarlos
        if (qrResponse.data.solicitud && qrResponse.data.solicitud.paciente) {
          console.log('Usando datos de solicitud desde QR');
          setRequest(qrResponse.data.solicitud);
          setIsLoading(false);
          return;
        }

        // Si no, intentar obtener los datos directamente
        console.log('Obteniendo datos de solicitud directamente');
        const requestResponse = await requestsAPI.getById(id);

        if (requestResponse.data) {
          setRequest(requestResponse.data);
        } else {
          throw new Error('No se pudieron cargar los datos de la solicitud');
        }

        setIsLoading(false);
      } catch (err) {
        console.error('Error al cargar datos:', err);
        setError(err.message || 'Error al cargar la solicitud');
        setIsLoading(false);
        toast.error('Error al cargar los datos de la solicitud');
      }
    };

    if (id) {
      fetchData();
    }
  }, [id]);

  // Handle print
  const handlePrint = () => {
    if (printRef.current) {
      const printContents = printRef.current.innerHTML;

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
              @page {
                size: A4;
                margin: 0.5cm;
              }
              body {
                font-family: Arial, sans-serif;
                line-height: 1.2;
                color: #111827;
                margin: 0;
                padding: 0;
                background-color: white;
                font-size: 8pt;
              }
              .print-container {
                width: 100%;
                max-width: 21cm;
                margin: 0 auto;
                padding: 0.3cm;
                box-sizing: border-box;
              }
              .header {
                text-align: center;
                margin-bottom: 6px;
                padding-bottom: 3px;
                border-bottom: 1px solid #e5e7eb;
              }
              h2 {
                font-size: 12pt;
                font-weight: bold;
                margin-bottom: 2px;
                margin-top: 0;
              }
              h3 {
                font-size: 10pt;
                font-weight: 600;
                margin-bottom: 2px;
                margin-top: 5px;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 2px;
              }
              p {
                margin-bottom: 1px;
                margin-top: 0;
              }
              .mb-6 {
                margin-bottom: 6px;
              }
              .grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 5px;
              }
              table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 5px;
              }
              th, td {
                padding: 2px 3px;
                text-align: left;
                border-bottom: 1px solid #e5e7eb;
                font-size: 7pt;
              }
              th {
                font-size: 6pt;
                font-weight: 500;
                text-transform: uppercase;
                color: #6b7280;
                background-color: #f9fafb;
              }
              .text-center {
                text-align: center;
              }
              .flex {
                display: flex;
              }
              .justify-between {
                justify-content: space-between;
              }
              .border-t {
                border-top: 1px solid #e5e7eb;
              }
              .mt-10 {
                margin-top: 10px;
              }
              .pt-6 {
                padding-top: 6px;
              }
              .mt-16 {
                margin-top: 15px;
              }
              .mt-2 {
                margin-top: 1px;
              }
              .mt-8 {
                margin-top: 6px;
              }
              .text-xs {
                font-size: 6pt;
              }
              .text-sm {
                font-size: 7pt;
              }
              .text-lg {
                font-size: 10pt;
              }
              .text-xl {
                font-size: 12pt;
              }
              .text-gray-500 {
                color: #6b7280;
              }
              .w-40 {
                width: 60px;
              }
              .mx-auto {
                margin-left: auto;
                margin-right: auto;
              }
              .section {
                margin-bottom: 6px;
                border: 1px solid #e5e7eb;
                border-radius: 3px;
                padding: 5px;
              }
              .section-title {
                font-size: 10pt;
                font-weight: bold;
                margin-bottom: 3px;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 2px;
              }
              .info-grid {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 3px;
              }
              .label {
                font-weight: bold;
              }
              .value {
                margin-bottom: 1px;
              }
              .exam-list {
                list-style: none;
                padding: 0;
                margin: 0;
              }
              .exam-item {
                padding: 2px 0;
                border-bottom: 1px solid #e5e7eb;
              }
              .exam-item:last-child {
                border-bottom: none;
              }
              .status {
                display: inline-block;
                padding: 1px 3px;
                border-radius: 9999px;
                font-size: 6pt;
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
                margin-top: 3px;
              }
              .qr-code img {
                max-width: 15px;
                height: auto;
              }
              @media print {
                body {
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                }
                .print-container {
                  width: 100%;
                  max-width: none;
                  padding: 0.2cm;
                }
                .section {
                  page-break-inside: avoid;
                }
                tr {
                  page-break-inside: avoid;
                }
                img[alt="QR Code"] {
                  max-width: 1cm !important;
                  max-height: 1cm !important;
                }
              }
            </style>
          </head>
          <body>
            <div class="print-container">
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
            <button
              onClick={() => window.location.reload()}
              className="mt-3 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              Reintentar
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Verificar si tenemos los datos necesarios
  if (!request || !request.paciente) {
    return (
      <div className="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-md">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">Datos incompletos</h3>
            <p className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
              No se pudieron cargar todos los datos necesarios para la impresión. Por favor, intente nuevamente.
            </p>
            <button
              onClick={() => window.location.reload()}
              className="mt-3 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
            >
              Reintentar
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="sm:flex sm:items-center mb-6">
        <div className="sm:flex-auto">
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Imprimir Solicitud</h1>
          <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Imprimir comprobante de solicitud de exámenes
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

      {/* Printable content */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg" ref={printRef}>
        <div className="print-container p-6">
          <div className="text-center mb-6">
            <h2 className="text-xl font-bold">LABORATORIO CLÍNICO</h2>
            <p className="text-sm">Solicitud de Exámenes</p>
          </div>

          <div className="flex justify-between mb-6">
            <div>
              <p className="text-sm"><strong>Solicitud #:</strong> {request.id}</p>
              <p className="text-sm"><strong>Fecha:</strong> {request.fecha ? format(new Date(request.fecha), 'dd/MM/yyyy') : 'No especificada'}</p>
              <p className="text-sm"><strong>Hora:</strong> {request.hora ? (
                (() => {
                  try {
                    // Extraer solo la parte de la hora si es un formato ISO completo
                    let horaLimpia;
                    if (request.hora.includes('T')) {
                      // Es un formato ISO completo como "2025-04-30T18:58:00.000000Z"
                      const partes = request.hora.split('T');
                      if (partes.length > 1) {
                        // Tomar solo la parte de la hora y quitar los segundos/milisegundos
                        horaLimpia = partes[1].substring(0, 5);
                      } else {
                        horaLimpia = request.hora;
                      }
                    } else {
                      // Es un formato simple como "18:58"
                      horaLimpia = request.hora.substring(0, 5);
                    }
                    return horaLimpia;
                  } catch (e) {
                    console.error('Error al formatear hora:', e);
                    // Intentar extraer solo la hora si es posible
                    if (typeof request.hora === 'string' && request.hora.includes(':')) {
                      return request.hora.split(':').slice(0, 2).join(':');
                    }
                    return request.hora; // Devolver la hora sin formatear como último recurso
                  }
                })()
              ) : 'No especificada'}</p>
            </div>
            <div>
              <p className="text-sm"><strong>Recibo #:</strong> {request.numero_recibo}</p>
              <p className="text-sm"><strong>Servicio:</strong> {request.servicio?.nombre}</p>
              <p className="text-sm">
                <strong>Tipo:</strong> {' '}
                {request.sis ? 'SIS' : request.rdr ? 'RDR' : request.exon ? 'Exonerado' : 'Normal'}
              </p>
            </div>
          </div>

          <div className="mb-6">
            <h3 className="text-lg font-semibold border-b pb-2 mb-2">Datos del Paciente</h3>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm"><strong>Nombres:</strong> {request.paciente?.nombres}</p>
                <p className="text-sm"><strong>Apellidos:</strong> {request.paciente?.apellidos}</p>
              </div>
              <div>
                <p className="text-sm"><strong>Sexo:</strong> {request.paciente?.sexo === 'masculino' ? 'Masculino' : 'Femenino'}</p>
              </div>
            </div>
          </div>

          <div className="mb-6">
            <h3 className="text-lg font-semibold border-b pb-2 mb-2">Exámenes Solicitados</h3>
            <table className="min-w-full divide-y divide-gray-300">
              <thead>
                <tr>
                  <th scope="col" className="py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                  <th scope="col" className="py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Examen</th>
                  <th scope="col" className="py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {request.examenes?.map((exam) => (
                  <tr key={exam.id}>
                    <td className="py-2 text-sm text-gray-900">{exam.codigo}</td>
                    <td className="py-2 text-sm text-gray-900">{exam.nombre}</td>
                    <td className="py-2 text-sm text-gray-900">{exam.categoria?.nombre}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="flex justify-between mb-6">
            <div>
              <h3 className="text-lg font-semibold mb-2">Médico Solicitante</h3>
              <p className="text-sm"><strong>Nombre:</strong> {user?.name || `${user?.nombre || ''} ${user?.apellido || ''}`}</p>
              <p className="text-sm"><strong>Especialidad:</strong> {user?.especialidad}</p>
            </div>
            <div className="text-center">
              {qrCode && (
                <div>
                  <img src={qrCode} alt="QR Code" className="h-10 w-10 mx-auto" />
                  <p className="text-xs mt-0" style={{fontSize: '6px'}}>Escanee para ver resultados</p>
                </div>
              )}
            </div>
          </div>

          <div className="mt-10 pt-6 border-t">
            <div className="flex justify-center">
              <div className="text-center">
                <div className="border-t border-gray-300 w-40 mx-auto mt-16"></div>
                <p className="text-sm mt-2">Firma del Médico</p>
              </div>
            </div>
          </div>

          <div className="text-center text-xs text-gray-500 mt-8">
            <p>Este documento es un comprobante de solicitud de exámenes.</p>
            <p>Fecha de impresión: {format(new Date(), 'dd/MM/yyyy HH:mm', { locale: es })}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
