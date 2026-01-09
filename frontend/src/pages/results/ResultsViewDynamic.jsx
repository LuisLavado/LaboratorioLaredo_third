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
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import ExportButtons from '../../components/results/ExportButtons';

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

// Simple print styles - added via useEffect in component

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
    <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6 exam-card">
      {/* Exam Header */}
      <div className="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6 px-6 pt-6 exam-header">
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
            <p><strong>FECHA:</strong> {format(new Date(examResultsResponse.fecha_resultado), 'dd/MM/yyyy')}</p>
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
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 results-table">
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

export default function ResultsViewDynamic() {
  const { id } = useParams(); // solicitud_id

  // Add print styles
  React.useEffect(() => {
    const printStyles = document.createElement('style');
    printStyles.id = 'results-print-styles';
    printStyles.textContent = `
      @media print {
        .print-hide { display: none !important; }
        body { font-size: 12px !important; color: black !important; background: white !important; }
        .results-content { margin: 0 !important; padding: 20px !important; }
        .patient-info, .exam-card { page-break-inside: avoid; margin-bottom: 20px !important; border: 1px solid #ddd !important; }
        .exam-header { background: #f8f9fa !important; page-break-after: avoid; }
        .results-table { page-break-inside: avoid; border-collapse: collapse !important; }
        .results-table th, .results-table td { font-size: 10px !important; padding: 4px !important; border: 1px solid #ddd !important; }
        h1, h2, h3 { page-break-after: avoid; color: black !important; }
      }
    `;

    if (!document.getElementById('results-print-styles')) {
      document.head.appendChild(printStyles);
    }

    return () => {
      const existingStyles = document.getElementById('results-print-styles');
      if (existingStyles) {
        existingStyles.remove();
      }
    };
  }, []);

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
            <Link
              to="/resultados"
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
            <div className="print-hide">
              <ExportButtons
                solicitudId={id}
                detalles={details}
                paciente={request?.paciente}
                variant="compact"
                size="md"
                className="flex space-x-3"
              />
            </div>
          )}
        </div>
      </div>

      {/* Main Content */}
      {completedExams.length > 0 ? (
        <div className="space-y-8 results-content">
          {/* Header with patient info */}
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg patient-info">
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
                      <p><strong>FECHA SOLICITUD:</strong> {format(new Date(request.fecha), 'dd/MM/yyyy')}</p>
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
              <p>Fecha de emisión: {format(new Date(), 'dd/MM/yyyy')}</p>
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
