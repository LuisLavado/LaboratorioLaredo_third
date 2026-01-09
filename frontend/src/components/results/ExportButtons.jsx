import React, { useState } from 'react';
import { 
  PrinterIcon, 
  DocumentArrowDownIcon, 
  ExclamationTriangleIcon,
  CheckCircleIcon 
} from '@heroicons/react/24/outline';
import { 
  exportarSolicitudPDF, 
  exportarDetallePDF, 
  imprimirResultados, 
  validarResultadosDisponibles 
} from '../../utils/exportUtils';

/**
 * Componente para botones de exportación e impresión de resultados
 */
const ExportButtons = ({ 
  solicitudId = null,
  detalleSolicitudId = null,
  detalles = [],
  paciente = null,
  className = "",
  size = "md",
  showLabels = true,
  variant = "default" // "default", "compact", "minimal"
}) => {
  const [loading, setLoading] = useState({
    print: false,
    pdfSolicitud: false,
    pdfDetalle: false
  });
  const [message, setMessage] = useState(null);

  // Validar disponibilidad de resultados
  const resultadosInfo = validarResultadosDisponibles(detalles);

  // Configuración de estilos según el tamaño
  const sizeClasses = {
    sm: "px-2 py-1 text-xs",
    md: "px-3 py-2 text-sm",
    lg: "px-4 py-3 text-base"
  };

  const iconSizes = {
    sm: "h-3 w-3",
    md: "h-4 w-4",
    lg: "h-4 w-4"
  };

  // Mostrar mensaje temporal
  const showMessage = (msg, type = 'success') => {
    setMessage({ text: msg, type });
    setTimeout(() => setMessage(null), 3000);
  };

  // Manejar impresión
  const handlePrint = async () => {
    setLoading(prev => ({ ...prev, print: true }));
    try {
      await imprimirResultados('.results-content');
      showMessage('Impresión iniciada correctamente');
    } catch (error) {
      showMessage(error.message || 'Error al imprimir', 'error');
    } finally {
      setLoading(prev => ({ ...prev, print: false }));
    }
  };

  // Manejar exportación de solicitud completa
  const handleExportSolicitud = async () => {
    if (!solicitudId) {
      showMessage('ID de solicitud no disponible', 'error');
      return;
    }

    if (!resultadosInfo.hayResultados) {
      showMessage(resultadosInfo.mensaje, 'warning');
      return;
    }

    setLoading(prev => ({ ...prev, pdfSolicitud: true }));
    try {
      await exportarSolicitudPDF(solicitudId);
      showMessage(`PDF exportado: ${resultadosInfo.completados} exámenes incluidos`);
    } catch (error) {
      showMessage(error.message || 'Error al exportar PDF', 'error');
    } finally {
      setLoading(prev => ({ ...prev, pdfSolicitud: false }));
    }
  };

  // Manejar exportación de detalle específico
  const handleExportDetalle = async () => {
    if (!detalleSolicitudId) {
      showMessage('ID de detalle no disponible', 'error');
      return;
    }

    setLoading(prev => ({ ...prev, pdfDetalle: true }));
    try {
      await exportarDetallePDF(detalleSolicitudId);
      showMessage('PDF del examen exportado correctamente');
    } catch (error) {
      showMessage(error.message || 'Error al exportar PDF', 'error');
    } finally {
      setLoading(prev => ({ ...prev, pdfDetalle: false }));
    }
  };

  // Renderizar según variante
  if (variant === "minimal") {
    return (
      <div className={`flex space-x-1 ${className}`}>
        <button
          onClick={handlePrint}
          disabled={loading.print}
          className="p-1 text-gray-500 hover:text-gray-700 disabled:opacity-50"
          title="Imprimir"
        >
          <PrinterIcon className="h-3 w-3" />
        </button>

        {solicitudId && (
          <button
            onClick={handleExportSolicitud}
            disabled={loading.pdfSolicitud || !resultadosInfo.hayResultados}
            className="p-1 text-blue-500 hover:text-blue-700 disabled:opacity-50"
            title="Exportar PDF completo"
          >
            <DocumentArrowDownIcon className="h-3 w-3" />
          </button>
        )}
      </div>
    );
  }

  if (variant === "compact") {
    return (
      <div className={`flex space-x-2 ${className}`}>
        <button
          onClick={handlePrint}
          disabled={loading.print}
          className={`inline-flex items-center ${sizeClasses[size]} border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed`}
        >
          <PrinterIcon className={`h-4 w-4 ${showLabels ? 'mr-1' : ''}`} />
          {showLabels && "Imprimir"}
        </button>

        {solicitudId && (
          <button
            onClick={handleExportSolicitud}
            disabled={loading.pdfSolicitud || !resultadosInfo.hayResultados}
            className={`inline-flex items-center ${sizeClasses[size]} border border-transparent rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed`}
          >
            <DocumentArrowDownIcon className={`h-4 w-4 ${showLabels ? 'mr-1' : ''}`} />
            {showLabels && "PDF"}
          </button>
        )}
      </div>
    );
  }

  // Variante por defecto (completa)
  return (
    <div className={`space-y-3 ${className}`}>
      {/* Mensaje de estado */}
      {message && (
        <div className={`p-3 rounded-md text-sm ${
          message.type === 'error' 
            ? 'bg-red-50 text-red-700 border border-red-200' 
            : message.type === 'warning'
            ? 'bg-yellow-50 text-yellow-700 border border-yellow-200'
            : 'bg-green-50 text-green-700 border border-green-200'
        }`}>
          <div className="flex items-center">
            {message.type === 'error' && <ExclamationTriangleIcon className="h-3 w-3 mr-2" />}
            {message.type === 'success' && <CheckCircleIcon className="h-3 w-3 mr-2" />}
            {message.type === 'warning' && <ExclamationTriangleIcon className="h-3 w-3 mr-2" />}
            {message.text}
          </div>
        </div>
      )}

      {/* Información de resultados disponibles */}
      {detalles.length > 0 && (
        <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
          <div className="flex items-center text-sm text-blue-700">
            <CheckCircleIcon className="h-3 w-3 mr-2" />
            {resultadosInfo.mensaje}
          </div>
        </div>
      )}

      {/* Botones de acción */}
      <div className="flex flex-wrap gap-3">
        {/* Botón de impresión */}
        <button
          onClick={handlePrint}
          disabled={loading.print}
          className={`inline-flex items-center ${sizeClasses[size]} border border-gray-300 rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed`}
        >
          <PrinterIcon className={`h-4 w-4 ${showLabels ? 'mr-2' : ''}`} />
          {loading.print ? 'Imprimiendo...' : (showLabels ? 'Imprimir Resultados' : '')}
        </button>

        {/* Botón de exportación completa */}
        {solicitudId && (
          <button
            onClick={handleExportSolicitud}
            disabled={loading.pdfSolicitud || !resultadosInfo.hayResultados}
            className={`inline-flex items-center ${sizeClasses[size]} border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed`}
          >
            <DocumentArrowDownIcon className={`h-4 w-4 ${showLabels ? 'mr-2' : ''}`} />
            {loading.pdfSolicitud ? 'Exportando...' : (showLabels ? 'Exportar PDF Completo' : '')}
          </button>
        )}

        {/* Botón de exportación individual */}
        {detalleSolicitudId && (
          <button
            onClick={handleExportDetalle}
            disabled={loading.pdfDetalle}
            className={`inline-flex items-center ${sizeClasses[size]} border border-transparent rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed`}
          >
            <DocumentArrowDownIcon className={`h-4 w-4 ${showLabels ? 'mr-2' : ''}`} />
            {loading.pdfDetalle ? 'Exportando...' : (showLabels ? 'Exportar Este Examen' : '')}
          </button>
        )}
      </div>

      {/* Información adicional */}
      {paciente && (
        <div className="text-xs text-gray-500 mt-2">
          <p>Paciente: {paciente.nombres} {paciente.apellidos} - DNI: {paciente.dni}</p>
        </div>
      )}
    </div>
  );
};

export default ExportButtons;
