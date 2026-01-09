import React, { useState, useEffect } from 'react';
import { 
  ExclamationTriangleIcon, 
  CheckCircleIcon, 
  DocumentArrowDownIcon,
  PrinterIcon 
} from '@heroicons/react/24/outline';

const ResultDisplay = ({ detalleSolicitudId, showActions = true }) => {
  const [resultado, setResultado] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (detalleSolicitudId) {
      fetchResultado();
    }
  }, [detalleSolicitudId]);

  const fetchResultado = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/valores-resultado/exportar?detalle_solicitud_id=${detalleSolicitudId}`);
      
      if (!response.ok) {
        throw new Error('Error al cargar los resultados');
      }
      
      const data = await response.json();
      setResultado(data);
    } catch (error) {
      console.error('Error fetching results:', error);
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleExportPDF = async () => {
    try {
      const response = await fetch(`/api/reportes/pdf?detalle_solicitud_id=${detalleSolicitudId}`);
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `resultado_${resultado.paciente.dni}_${resultado.examen.codigo}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error('Error exporting PDF:', error);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-md p-4">
        <div className="flex">
          <ExclamationTriangleIcon className="h-5 w-5 text-red-400" />
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800">Error</h3>
            <p className="text-sm text-red-700 mt-1">{error}</p>
          </div>
        </div>
      </div>
    );
  }

  if (!resultado) {
    return (
      <div className="text-center py-8">
        <p className="text-gray-500">No se encontraron resultados</p>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto bg-white">
      {/* Header con información del paciente */}
      <div className="border-b border-gray-200 pb-6 mb-6">
        <div className="flex justify-between items-start">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              LABORATORIO CLÍNICO - RESULTADOS DE ANÁLISIS
            </h1>
            <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
              <div>
                <p><strong>PACIENTE:</strong> {resultado.paciente.nombres} {resultado.paciente.apellidos}</p>
                <p><strong>H. CLINICA:</strong> {resultado.paciente.dni}</p>
                <p><strong>EDAD:</strong> {resultado.paciente.edad} años</p>
              </div>
              <div>
                <p><strong>EXAMEN:</strong> {resultado.examen.nombre}</p>
                <p><strong>CÓDIGO:</strong> {resultado.examen.codigo}</p>
                <p><strong>FECHA:</strong> {new Date(resultado.fecha_resultado).toLocaleDateString()}</p>
              </div>
            </div>
          </div>
          
          {showActions && (
            <div className="flex space-x-2">
              <button
                onClick={handlePrint}
                className="flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
              >
                <PrinterIcon className="w-4 h-4 mr-1" />
                Imprimir
              </button>
              <button
                onClick={handleExportPDF}
                className="flex items-center px-3 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700"
              >
                <DocumentArrowDownIcon className="w-4 h-4 mr-1" />
                Exportar PDF
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Resultados por sección */}
      <div className="space-y-8">
        {resultado.valores_por_seccion.map((seccion, index) => (
          <div key={index} className="border border-gray-200 rounded-lg overflow-hidden">
            <div className="bg-gray-50 px-6 py-3 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">
                {seccion.seccion}
              </h2>
            </div>
            
            <div className="p-6">
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Análisis
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Resultado
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Unidades
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Valor de Referencia
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Estado
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {seccion.valores.map((valor, valorIndex) => (
                      <tr key={valorIndex} className={valor.fuera_rango ? 'bg-red-50' : ''}>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                          {valor.campo}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          <span className={valor.fuera_rango ? 'font-bold text-red-600' : ''}>
                            {valor.valor}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {valor.unidad || '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {valor.valor_referencia || '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                          {valor.fuera_rango ? (
                            <div className="flex items-center text-red-600">
                              <ExclamationTriangleIcon className="w-4 h-4 mr-1" />
                              Fuera de rango
                            </div>
                          ) : (
                            <div className="flex items-center text-green-600">
                              <CheckCircleIcon className="w-4 h-4 mr-1" />
                              Normal
                            </div>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              
              {/* Observaciones de la sección */}
              {seccion.valores.some(v => v.observaciones) && (
                <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                  <h4 className="text-sm font-medium text-yellow-800 mb-2">Observaciones:</h4>
                  <div className="space-y-1">
                    {seccion.valores
                      .filter(v => v.observaciones)
                      .map((valor, obsIndex) => (
                        <p key={obsIndex} className="text-sm text-yellow-700">
                          <strong>{valor.campo}:</strong> {valor.observaciones}
                        </p>
                      ))}
                  </div>
                </div>
              )}
            </div>
          </div>
        ))}
      </div>

      {/* Resumen de valores fuera de rango */}
      {resultado.valores_por_seccion.some(seccion => 
        seccion.valores.some(valor => valor.fuera_rango)
      ) && (
        <div className="mt-8 p-4 bg-red-50 border border-red-200 rounded-md">
          <div className="flex">
            <ExclamationTriangleIcon className="h-5 w-5 text-red-400" />
            <div className="ml-3">
              <h3 className="text-sm font-medium text-red-800">
                Valores Fuera de Rango Detectados
              </h3>
              <div className="mt-2 text-sm text-red-700">
                <p>Los siguientes valores están fuera del rango de referencia normal:</p>
                <ul className="list-disc list-inside mt-1">
                  {resultado.valores_por_seccion.map(seccion =>
                    seccion.valores
                      .filter(valor => valor.fuera_rango)
                      .map((valor, index) => (
                        <li key={index}>
                          {valor.campo}: {valor.valor} {valor.unidad} 
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

      {/* Footer */}
      <div className="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
        <p>Este reporte fue generado automáticamente por el Sistema de Laboratorio</p>
        <p>Fecha de emisión: {new Date().toLocaleString()}</p>
      </div>
    </div>
  );
};

export default ResultDisplay;
