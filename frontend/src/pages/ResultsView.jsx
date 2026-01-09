import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { requestsAPI, requestDetailsAPI, resultValuesAPI } from '../services/api';
import { ArrowLeftIcon, PrinterIcon, ExclamationTriangleIcon, CheckCircleIcon, BeakerIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import toast from 'react-hot-toast';

// Add print styles
const printStyles = `
  @media print {
    @page {
      size: A4;
      margin: 0.75in 0.5in;
      orphans: 3;
      widows: 3;
    }

    body, html {
      font-family: Arial, sans-serif;
      background: white !important;
      color: black !important;
      margin: 0;
      padding: 0;
      overflow: hidden;
      height: auto;
    }

    .max-w-6xl {
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .space-y-8 {
      display: block !important;
    }

    .space-y-8 > * + * {
      margin-top: 0.5rem !important;
    }

    /* Contenedores de exámenes */
    .bg-white, .dark\\:bg-gray-800 {
      background: white !important;
      color: black !important;
      page-break-inside: avoid !important;
      margin-bottom: 20px !important;
      border: 1px solid #ddd !important;
      box-shadow: none !important;
      border-radius: 0 !important;
    }

    .bg-white:not(:first-child) {
      margin-top: 30px !important;
    }

    .bg-white:first-child {
      page-break-before: avoid !important;
    }

    /* Tablas */
    table {
      page-break-inside: avoid !important;
      font-size: 12px !important;
      width: 100% !important;
      border-collapse: collapse;
    }

    th, td {
      padding: 5px 8px !important;
      font-size: 11px !important;
      line-height: 1.3 !important;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }

    th {
      font-size: 10px !important;
      font-weight: bold !important;
      background: #f8f9fa !important;
      text-transform: uppercase;
    }

    /* Títulos */
    h1, h2 {
      font-size: 15px !important;
      margin: 0 !important;
      color: black !important;
    }

    h3 {
      font-size: 13px !important;
      margin: 0 !important;
      color: black !important;
    }

    /* Headers de exámenes */
    .border-b {
      padding: 8px 12px 4px 12px !important;
      margin-bottom: 8px !important;
      page-break-after: avoid !important;
      border-bottom: 1px solid #ddd !important;
    }

    /* Contenido de exámenes */
    .px-6 {
      padding: 8px 12px !important;
    }

    /* Información del paciente */
    .grid {
      gap: 8px !important;
      font-size: 11px !important;
    }

    /* Alertas */
    .bg-red-50 {
      padding: 8px !important;
      font-size: 10px !important;
      background: #fef2f2 !important;
      border: 1px solid #fecaca !important;
      page-break-inside: avoid !important;
    }

    /* Badges */
    .inline-flex {
      padding: 3px 5px !important;
      font-size: 9px !important;
    }

    .bg-red-100 {
      background: #fef2f2 !important;
      color: #dc2626 !important;
    }

    .bg-green-100 {
      background: #f0fdf4 !important;
      color: #059669 !important;
    }

    /* Iconos */
    .w-3, .h-3, .h-5, .w-5 {
      width: 10px !important;
      height: 10px !important;
    }

    /* Ocultar elementos no deseados */
    .print-hide, button, .btn {
      display: none !important;
    }

    /* Colores de texto */
    .text-gray-900, .dark\\:text-white {
      color: black !important;
    }

    .text-gray-500, .dark\\:text-gray-400 {
      color: #666 !important;
    }

    /* Bordes */
    .border-gray-200, .dark\\:border-gray-700 {
      border-color: #e5e7eb !important;
    }

    /* Fondos de secciones */
    .bg-gray-50, .dark\\:bg-gray-700 {
      background: #f8f9fa !important;
    }

    /* Overflow */
    .overflow-x-auto {
      overflow: visible !important;
    }
  }
`;

// Inject styles into document head
if (typeof document !== 'undefined') {
  const styleElement = document.createElement('style');
  styleElement.textContent = printStyles;
  document.head.appendChild(styleElement);
}

// Helper function to safely format dates
const formatSafeDate = (dateString, formatStr = 'PPP') => {
  if (!dateString) return 'N/A';
  try {
    return format(new Date(dateString), formatStr, { locale: es });
  } catch (error) {
    console.error('Error formatting date:', error);
    return dateString;
  }
};

// Component to display results for a single exam
function ExamResultsDisplay({ detalleSolicitudId, examInfo }) {
  const { data: examResultsResponse, isLoading, error } = useQuery(
    ['examResults', detalleSolicitudId],
    () => resultValuesAPI.export(detalleSolicitudId).then(res => res.data),
    {
      enabled: !!detalleSolicitudId,
      refetchOnWindowFocus: false,
    }
  );

  if (isLoading) {
    return (
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
        <div className="px-4 py-5 sm:p-6 text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary-500 mx-auto"></div>
          <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Cargando resultados de {examInfo?.nombre}...
          </p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
        <div className="px-4 py-5 sm:p-6">
          <p className="text-sm text-red-500">
            Error al cargar los resultados: {error.message}
          </p>
        </div>
      </div>
    );
  }

  if (!examResultsResponse || !examResultsResponse.valores_por_seccion || examResultsResponse.valores_por_seccion.length === 0) {
    return (
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
        <div className="px-4 py-5 sm:p-6">
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Los resultados de este examen aún no están disponibles.
          </p>
        </div>
      </div>
    );
  }

  // Check if there are any values outside normal range
  const hasAbnormalValues = examResultsResponse.valores_por_seccion.some(seccion =>
    seccion.valores.some(valor => valor.fuera_rango)
  );

  return (
    <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
      {/* Exam Header */}
      <div className="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6 px-6 pt-6">
        <div className="flex justify-between items-center">
          <div>
            <h2 className="text-xl font-bold text-gray-900 dark:text-white">
              {examResultsResponse.examen.nombre}
            </h2>
            <p className="text-sm text-gray-500 dark:text-gray-400">
              Código: {examResultsResponse.examen.codigo}
            </p>
          </div>
          <div className="text-right text-sm text-gray-500 dark:text-gray-400">
            <p><strong>FECHA:</strong> {formatSafeDate(examResultsResponse.fecha_resultado)}</p>
          </div>
        </div>
      </div>

      {/* Alert for abnormal values */}
      {hasAbnormalValues && (
        <div className="mx-6 mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <ExclamationTriangleIcon className="h-5 w-5 text-red-400" aria-hidden="true" />
            </div>
            <div className="ml-3">
              <h4 className="text-sm font-medium text-red-800 dark:text-red-200">
                Valores Fuera de Rango Detectados
              </h4>
              <div className="mt-2 text-sm text-red-700 dark:text-red-300">
                <p>Los siguientes valores están fuera del rango de referencia normal:</p>
                <ul className="list-disc list-inside mt-1">
                  {examResultsResponse.valores_por_seccion.map(seccion =>
                    seccion.valores
                      .filter(valor => valor.fuera_rango)
                      .map((valor, index) => (
                        <li key={index}>
                          <strong>{valor.campo}:</strong> {valor.valor} {valor.unidad}
                          (Referencia: {valor.valor_referencia})
                        </li>
                      ))
                  )}
                </ul>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Results Tables */}
      <div className="px-6 pb-6">
        {examResultsResponse.valores_por_seccion.map((seccion, seccionIndex) => (
          <div key={seccionIndex} className="mb-6 last:mb-0">
            {examResultsResponse.valores_por_seccion.length > 1 && (
              <div className="mb-4">
                <h4 className="text-lg font-medium text-gray-900 dark:text-white">
                  {seccion.seccion}
                </h4>
              </div>
            )}

            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Análisis
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Resultado
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Unidades
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Valor de Referencia
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Estado
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {seccion.valores.map((valor, valorIndex) => (
                    <tr key={valorIndex} className={valor.fuera_rango ? 'bg-red-50 dark:bg-red-900/20' : ''}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {valor.campo}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        <span className={valor.fuera_rango ? 'font-bold text-red-600 dark:text-red-400' : ''}>
                          {valor.valor}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {valor.unidad || '-'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {valor.valor_referencia || '-'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {valor.fuera_rango ? (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <ExclamationTriangleIcon className="w-3 h-3 mr-1" />
                            Fuera de rango
                          </span>
                        ) : (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <CheckCircleIcon className="w-3 h-3 mr-1" />
                            Normal
                          </span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}



export default function ResultsView() {
  const { id } = useParams(); // solicitud_id

  // Fetch request details
  const { data: detailsResponse, isLoading: detailsLoading } = useQuery(
    ['requestDetails', id],
    () => requestDetailsAPI.getByRequest(id).then(res => res.data)
  );

  const details = detailsResponse?.data || [];
  const request = details.length > 0 ? details[0].solicitud : null;
  const completedExams = details.filter(detail => detail.estado === 'completado');

  const handlePrint = () => {
    window.print();
  };

  if (detailsLoading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (!request) {
    return (
      <div className="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-md">
        <div className="flex">
          <div className="flex-shrink-0">
            <ExclamationTriangleIcon className="h-5 w-5 text-yellow-400" />
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
    <div className="max-w-6xl mx-auto">
      {/* Header */}
      <div className="mb-6 print-hide">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <button
              onClick={() => window.history.back()}
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </button>
            <div>
              <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
                Resultados de Laboratorio
              </h1>
              {request && (
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {request.paciente?.nombres} {request.paciente?.apellidos} - DNI: {request.paciente?.dni}
                </p>
              )}
            </div>
          </div>

          {completedExams.length > 0 && (
            <div className="flex space-x-3 print-hide">
              <button
                onClick={handlePrint}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
              >
                <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                Imprimir
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Main Content */}
      {completedExams.length > 0 ? (
        <div className="space-y-8">
          {/* Header with patient info */}
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <div className="text-center">
                <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                  LABORATORIO CLÍNICO - RESULTADOS DE ANÁLISIS
                </h2>
                {request && (
                  <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div className="text-left">
                      <p><strong>PACIENTE:</strong> {request.paciente?.nombres} {request.paciente?.apellidos}</p>
                      <p><strong>H. CLINICA:</strong> {request.paciente?.dni}</p>
                      <p><strong>EDAD:</strong> {request.paciente?.edad} años</p>
                    </div>
                    <div className="text-left">
                      <p><strong>FECHA SOLICITUD:</strong> {formatSafeDate(request.fecha, 'dd/MM/yyyy')}</p>
                      <p><strong>EXÁMENES COMPLETADOS:</strong> {completedExams.length}</p>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* All exam results */}
          {completedExams.map((detail) => (
            <ExamResultsDisplay
              key={detail.id}
              detalleSolicitudId={detail.id}
              examInfo={detail.examen}
            />
          ))}

          {/* Footer */}
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
              <p>Este reporte fue generado automáticamente por el Sistema de Laboratorio</p>
              <p>Fecha de emisión: {formatSafeDate(new Date().toISOString())}</p>
            </div>
          </div>
        </div>
      ) : (
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6 text-center">
            <BeakerIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              No hay exámenes completados
            </h3>
            <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Los resultados de esta solicitud aún no han sido registrados o están en proceso.
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
