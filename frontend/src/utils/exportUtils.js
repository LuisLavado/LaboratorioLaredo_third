/**
 * Utilidades para exportación de resultados
 */

// Configuración base para las APIs
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';

/**
 * Exportar resultados de una solicitud completa a PDF
 * @param {number} solicitudId - ID de la solicitud
 * @returns {Promise<void>}
 */
export const exportarSolicitudPDF = async (solicitudId) => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      throw new Error('No se encontró token de autenticación. Por favor, inicie sesión nuevamente.');
    }

    const response = await fetch(`${API_BASE_URL}/resultados/solicitud/${solicitudId}/pdf`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/pdf',
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      let errorMessage = 'Error al exportar PDF';
      try {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          const errorData = await response.json();
          errorMessage = errorData.message || errorMessage;
        } else {
          const errorText = await response.text();
          errorMessage = errorText || `Error ${response.status}: ${response.statusText}`;
        }
      } catch (parseError) {
        errorMessage = `Error ${response.status}: ${response.statusText}`;
      }
      throw new Error(errorMessage);
    }

    // Obtener el blob del PDF
    const blob = await response.blob();
    
    // Crear URL temporal para descarga
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    
    // Generar nombre de archivo
    const timestamp = new Date().toISOString().split('T')[0];
    a.download = `resultados_laboratorio_solicitud_${solicitudId}_${timestamp}.pdf`;
    
    // Ejecutar descarga
    document.body.appendChild(a);
    a.click();
    
    // Limpiar
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    
    return { success: true, message: 'PDF exportado correctamente' };
  } catch (error) {
    console.error('Error exportando PDF de solicitud:', error);
    throw error;
  }
};

/**
 * Exportar resultado de un examen específico a PDF
 * @param {number} detalleSolicitudId - ID del detalle de solicitud
 * @returns {Promise<void>}
 */
export const exportarDetallePDF = async (detalleSolicitudId) => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      throw new Error('No se encontró token de autenticación. Por favor, inicie sesión nuevamente.');
    }

    const response = await fetch(`${API_BASE_URL}/resultados/detalle/${detalleSolicitudId}/pdf`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/pdf',
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      let errorMessage = 'Error al exportar PDF';
      try {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          const errorData = await response.json();
          errorMessage = errorData.message || errorMessage;
        } else {
          const errorText = await response.text();
          errorMessage = errorText || `Error ${response.status}: ${response.statusText}`;
        }
      } catch (parseError) {
        errorMessage = `Error ${response.status}: ${response.statusText}`;
      }
      throw new Error(errorMessage);
    }

    // Obtener el blob del PDF
    const blob = await response.blob();
    
    // Crear URL temporal para descarga
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    
    // Generar nombre de archivo
    const timestamp = new Date().toISOString().split('T')[0];
    a.download = `resultado_examen_${detalleSolicitudId}_${timestamp}.pdf`;
    
    // Ejecutar descarga
    document.body.appendChild(a);
    a.click();
    
    // Limpiar
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    
    return { success: true, message: 'PDF exportado correctamente' };
  } catch (error) {
    console.error('Error exportando PDF de detalle:', error);
    throw error;
  }
};

/**
 * Función simple y efectiva para imprimir
 */
export const imprimirResultados = () => {
  try {
    // Simplemente usar window.print() que es más confiable
    window.print();
    return { success: true, message: 'Impresión iniciada correctamente' };
  } catch (error) {
    console.error('Error al imprimir:', error);
    throw error;
  }
};

/**
 * Validar si hay resultados disponibles para exportar
 * @param {Array} detalles - Array de detalles de solicitud
 * @returns {Object} - Información sobre disponibilidad de resultados
 */
export const validarResultadosDisponibles = (detalles) => {
  if (!detalles || !Array.isArray(detalles)) {
    return {
      hayResultados: false,
      completados: 0,
      total: 0,
      mensaje: 'No hay detalles disponibles'
    };
  }
  
  const completados = detalles.filter(detalle => detalle.estado === 'completado');
  const total = detalles.length;
  
  return {
    hayResultados: completados.length > 0,
    completados: completados.length,
    total: total,
    mensaje: completados.length > 0 
      ? `${completados.length} de ${total} exámenes completados`
      : 'No hay exámenes completados para exportar'
  };
};

/**
 * Formatear nombre de archivo para descarga
 * @param {string} tipo - Tipo de archivo ('solicitud' o 'detalle')
 * @param {string|number} id - ID del elemento
 * @param {string} dni - DNI del paciente (opcional)
 * @returns {string} - Nombre de archivo formateado
 */
export const formatearNombreArchivo = (tipo, id, dni = null) => {
  const timestamp = new Date().toISOString().split('T')[0];
  const dniPart = dni ? `_${dni}` : '';
  
  switch (tipo) {
    case 'solicitud':
      return `resultados_laboratorio_solicitud_${id}${dniPart}_${timestamp}.pdf`;
    case 'detalle':
      return `resultado_examen_${id}${dniPart}_${timestamp}.pdf`;
    default:
      return `documento_laboratorio_${id}_${timestamp}.pdf`;
  }
};

export default {
  exportarSolicitudPDF,
  exportarDetallePDF,
  imprimirResultados,
  validarResultadosDisponibles,
  formatearNombreArchivo
};
