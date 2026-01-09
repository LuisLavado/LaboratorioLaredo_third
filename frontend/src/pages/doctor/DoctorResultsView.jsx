import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { resultValuesAPI, requestDetailsAPI } from '../../services/api';
import {
  ArrowLeftIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  PrinterIcon,
  BeakerIcon
} from '@heroicons/react/24/outline';
import { format, isValid, parseISO } from 'date-fns';

// Estilos CSS para impresión
const printStyles = `
  @media print {
    /* Ocultar navegación y elementos no deseados */
    nav, .navbar, .sidebar, .print-hide,
    [class*="nav"], [class*="header"], [class*="menu"],
    .bg-gray-900, .bg-primary-600, .text-primary-600,
    button:not(.print-show), .btn:not(.print-show) {
      display: none !important;
    }

    /* Ocultar el header de la página */
    .max-w-6xl > .mb-6:first-child {
      display: none !important;
    }

    /* Configuración de página sin scroll */
    @page {
      size: A4;
      margin: 0.5in;
    }

    /* Asegurar que el contenido principal se vea bien */
    body, html {
      background: white !important;
      color: black !important;
      overflow: hidden !important;
      height: auto !important;
    }

    .dark\\:bg-gray-800, .dark\\:text-white {
      background: white !important;
      color: black !important;
    }

    .dark\\:border-gray-700 {
      border-color: #e5e7eb !important;
    }

    /* Ajustar márgenes para impresión */
    .max-w-6xl {
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    /* Espaciado más compacto */
    .space-y-8 > * + * {
      margin-top: 0.5rem !important;
    }

    /* Hacer las tablas más compactas */
    table {
      page-break-inside: avoid;
      font-size: 12px !important;
      width: 100% !important;
    }

    th, td {
      padding: 5px 8px !important;
      font-size: 11px !important;
      line-height: 1.3 !important;
    }

    th {
      font-size: 10px !important;
      font-weight: bold !important;
    }

    /* Headers de exámenes más compactos */
    .border-b.border-gray-200.dark\\:border-gray-700.pb-4.mb-6.px-6.pt-6 {
      padding: 8px 12px 4px 12px !important;
      margin-bottom: 8px !important;
    }

    /* Contenido de exámenes más compacto */
    .px-6.pb-6.space-y-6 {
      padding: 8px 12px !important;
    }

    /* Títulos más pequeños */
    h2 {
      font-size: 15px !important;
      margin: 0 !important;
    }

    h3 {
      font-size: 13px !important;
      margin: 0 !important;
    }

    /* Información del paciente más compacta */
    .grid.grid-cols-1.md\\:grid-cols-2.gap-4.text-sm {
      gap: 8px !important;
      font-size: 11px !important;
    }

    /* Eliminar sombras y bordes innecesarios */
    .shadow, .sm\\:rounded-lg {
      box-shadow: none !important;
      border-radius: 0 !important;
    }

    /* Contenedores más compactos */
    .bg-white.dark\\:bg-gray-800.shadow.overflow-hidden.sm\\:rounded-lg.mb-6 {
      margin-bottom: 8px !important;
      border: 1px solid #ddd !important;
    }

    /* Alertas más compactas */
    .p-4.bg-red-50 {
      padding: 8px !important;
      font-size: 10px !important;
    }

    /* Badges más pequeños */
    .inline-flex.items-center.px-2\\.5.py-0\\.5 {
      padding: 3px 5px !important;
      font-size: 9px !important;
    }

    /* Iconos más pequeños */
    .w-3.h-3, .h-5.w-5 {
      width: 10px !important;
      height: 10px !important;
    }

    /* Overflow hidden para evitar scroll */
    .overflow-x-auto {
      overflow: visible !important;
    }

    /* Ajustar el contenedor principal */
    .space-y-8 {
      display: block !important;
    }

    /* Control de saltos de página para exámenes */
    .bg-white.dark\\:bg-gray-800.shadow.overflow-hidden.sm\\:rounded-lg.mb-6 {
      page-break-inside: avoid !important;
      page-break-before: auto !important;
      page-break-after: auto !important;
      margin-bottom: 20px !important;
      border: 1px solid #ddd !important;
    }

    /* Asegurar que cada examen completo esté en una página */
    .bg-white.dark\\:bg-gray-800.shadow.overflow-hidden.sm\\:rounded-lg.mb-6:not(:first-child) {
      margin-top: 20px !important;
    }

    /* Espaciado entre páginas */
    @page {
      size: A4;
      margin: 0.75in 0.5in;
      orphans: 3;
      widows: 3;
    }

    /* Evitar cortes en tablas */
    table, .border.border-gray-200.dark\\:border-gray-700.rounded-lg.overflow-hidden {
      page-break-inside: avoid !important;
    }

    /* Evitar cortes en headers de exámenes */
    .border-b.border-gray-200.dark\\:border-gray-700.pb-4.mb-6.px-6.pt-6 {
      page-break-after: avoid !important;
    }

    /* Evitar cortes en alertas de valores fuera de rango */
    .p-4.bg-red-50.dark\\:bg-red-900\\/20.border.border-red-200.dark\\:border-red-800.rounded-md {
      page-break-inside: avoid !important;
    }

    /* Espaciado adicional para separar exámenes */
    .space-y-8 > .bg-white:not(:first-child) {
      margin-top: 30px !important;
    }

    /* Asegurar que el header del paciente esté en la primera página */
    .space-y-8 > .bg-white:first-child {
      page-break-before: avoid !important;
    }
  }
`;

// Inyectar estilos en el head
if (typeof document !== 'undefined') {
  const styleElement = document.createElement('style');
  styleElement.textContent = printStyles;
  document.head.appendChild(styleElement);
}

// Helper function to safely format dates
const formatSafeDate = (dateString, formatString = 'dd/MM/yyyy HH:mm') => {
  if (!dateString) return 'N/A';

  try {
    const date = typeof dateString === 'string' ? parseISO(dateString) : new Date(dateString);
    if (isValid(date)) {
      return format(date, formatString);
    }
    return 'N/A';
  } catch (error) {
    console.warn('Error formatting date:', dateString, error);
    return 'N/A';
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
        <div className="px-4 py-5 sm:p-6 text-center">
          <ExclamationTriangleIcon className="h-8 w-8 text-red-400 mx-auto mb-2" />
          <p className="text-sm text-red-500">
            Error al cargar resultados de {examInfo?.nombre}: {error.message}
          </p>
        </div>
      </div>
    );
  }

  if (!examResultsResponse || !examResultsResponse.valores_por_seccion || examResultsResponse.valores_por_seccion.length === 0) {
    return (
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
        <div className="px-4 py-5 sm:p-6 text-center">
          <BeakerIcon className="h-8 w-8 text-gray-400 mx-auto mb-2" />
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Los resultados de {examInfo?.nombre} aún no están disponibles.
          </p>
        </div>
      </div>
    );
  }

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

      {/* Results by Section */}
      <div className="px-6 pb-6 space-y-6">
        {examResultsResponse.valores_por_seccion.map((seccion, index) => (
          <div key={index} className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            {examResultsResponse.valores_por_seccion.length > 1 && (
              <div className="bg-gray-50 dark:bg-gray-700 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                  {seccion.seccion}
                </h3>
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

        {/* Summary of out-of-range values for this exam */}
        {examResultsResponse.valores_por_seccion.some(seccion =>
          seccion.valores.some(valor => valor.fuera_rango)
        ) && (
          <div className="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
            <div className="flex">
              <ExclamationTriangleIcon className="h-5 w-5 text-red-400" />
              <div className="ml-3">
                <h4 className="text-sm font-medium text-red-800 dark:text-red-200">
                  Valores Fuera de Rango en {examResultsResponse.examen.nombre}
                </h4>
                <div className="mt-2 text-sm text-red-700 dark:text-red-300">
                  <ul className="list-disc list-inside">
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
      </div>
    </div>
  );
}

export default function DoctorResultsView() {
  const { id } = useParams(); // solicitud_id

  // Fetch request details
  const { data: detailsResponse, isLoading: detailsLoading } = useQuery(
    ['requestDetails', id],
    () => requestDetailsAPI.getByRequest(id).then(res => res.data)
  );

  const details = detailsResponse?.data || [];
  const request = details.length > 0 ? details[0].solicitud : null;
  const completedExams = details.filter(detail => detail.estado === 'completado');





  if (detailsLoading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (!request) {
    return (
      <div className="text-center py-8">
        <ExclamationTriangleIcon className="h-12 w-12 text-yellow-400 mx-auto mb-4" />
        <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
          Solicitud no encontrada
        </h3>
        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
          La solicitud que busca no existe o no tiene permisos para verla
        </p>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto">
      {/* Header */}
      <div className="mb-6 print-hide">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <Link
              to={`/doctor/solicitudes/${id}`}
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </Link>
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
            <div className="flex justify-end print-hide">
              <Link
                to={`/doctor/solicitudes/${id}/imprimir-resultados`}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
              >
                <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                Imprimir Resultados
              </Link>
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
              Esta solicitud aún no tiene exámenes con resultados disponibles
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
