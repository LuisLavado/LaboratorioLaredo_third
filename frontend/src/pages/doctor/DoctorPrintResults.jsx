import { useState, useEffect, useRef } from 'react';
import { useParams, useSearchParams, Link, useNavigate, useLocation } from 'react-router-dom';
import { ArrowLeftIcon, PrinterIcon, DocumentArrowDownIcon, EyeIcon, CalendarIcon, UserIcon, BeakerIcon, ClipboardDocumentListIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { requestDetailsAPI, servicesAPI, requestsAPI, resultValuesAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';

export default function DoctorPrintResults() {
  const { id } = useParams(); // solicitud_id
  const [searchParams] = useSearchParams();
  const examId = searchParams.get('examen');
  const navigate = useNavigate();
  const location = useLocation();
  const printRef = useRef();
  const { user } = useAuth();

  // Estado para almacenar la ruta de retorno
  const [returnPath, setReturnPath] = useState('/doctor/resultados');

  const [request, setRequest] = useState(null);
  const [details, setDetails] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [doctorName, setDoctorName] = useState('');
  const [detailsWithResults, setDetailsWithResults] = useState([]);

  // Determinar la ruta de retorno basada en el state de location o referrer
  useEffect(() => {
    // Si venimos de una página específica (pasada en el state)
    if (location.state && location.state.from) {
      console.log('Return path from state:', location.state.from);
      setReturnPath(location.state.from);
    }
    // Si no hay state, intentar determinar basado en la URL actual
    else {
      // Si venimos de la vista de resultados de una solicitud específica
      if (location.pathname.includes('/solicitudes/')) {
        setReturnPath(`/doctor/solicitudes/${id}`);
      }
      // Si venimos de la lista de resultados
      else if (document.referrer.includes('/doctor/resultados')) {
        setReturnPath(`/doctor/solicitudes/${id}`);
      }
      console.log('Determined return path:', returnPath);
    }
  }, [location, id]);

  // Configurar el nombre del doctor
  useEffect(() => {
    if (user) {
      // Determinar el nombre del doctor basado en la estructura del objeto user
      if (user.nombre && user.apellido) {
        setDoctorName(`${user.nombre} ${user.apellido}`);
      } else if (user.name) {
        setDoctorName(user.name);
      } else if (user.nombres && user.apellidos) {
        setDoctorName(`${user.nombres} ${user.apellidos}`);
      } else {
        setDoctorName('Médico');
      }
    }
  }, [user]);

  // Fetch data
  useEffect(() => {
    const fetchData = async () => {
      try {
        setIsLoading(true);
        console.log('Fetching data for request ID:', id);

        // Fetch details directly
        const detailsResponse = await requestDetailsAPI.getByRequest(id);
        console.log('Details response:', detailsResponse);

        // Extract the details data
        let detailsData = [];

        // Handle the specific structure we know we're getting
        if (detailsResponse.data && detailsResponse.data.success && Array.isArray(detailsResponse.data.data)) {
          detailsData = detailsResponse.data.data;
        } else if (detailsResponse.data && Array.isArray(detailsResponse.data)) {
          detailsData = detailsResponse.data;
        } else if (detailsResponse.data && Array.isArray(detailsResponse.data.data)) {
          detailsData = detailsResponse.data.data;
        } else if (detailsResponse.data && typeof detailsResponse.data === 'object') {
          // If it's an object with data property that's not an array, try to convert
          detailsData = Object.values(detailsResponse.data);
        }

        console.log('Processed details data:', detailsData);

        // Filter to only include completed exams
        const completedDetails = detailsData.filter(detail => detail.estado === 'completado');
        console.log('Completed details:', completedDetails);

        // Get request data from the first detail's solicitud property
        const requestData = completedDetails.length > 0 ? completedDetails[0].solicitud : null;
        console.log('Request data from detail:', requestData);

        setRequest(requestData);
        setDetails(completedDetails);

        // Cargar resultados dinámicos para cada detalle completado
        const detailsWithDynamicResults = await Promise.all(
          completedDetails.map(async (detail) => {
            try {
              // Intentar cargar resultados dinámicos
              const dynamicResultsResponse = await resultValuesAPI.export(detail.id);
              console.log('Dynamic results for detail', detail.id, ':', dynamicResultsResponse.data);

              if (dynamicResultsResponse.data && dynamicResultsResponse.data.valores_por_seccion) {
                // Convertir resultados dinámicos al formato esperado
                const dynamicResults = [];
                dynamicResultsResponse.data.valores_por_seccion.forEach(seccion => {
                  seccion.valores.forEach(valor => {
                    dynamicResults.push({
                      nombre_parametro: valor.campo,
                      valor: valor.valor,
                      unidad: valor.unidad || '',
                      referencia: valor.valor_referencia || '',
                      observaciones: valor.observaciones || ''
                    });
                  });
                });

                return {
                  ...detail,
                  resultados: detail.resultados && detail.resultados.length > 0 ? detail.resultados : dynamicResults,
                  hasDynamicResults: dynamicResults.length > 0
                };
              }
            } catch (error) {
              console.log('No dynamic results found for detail', detail.id, ':', error.message);
            }

            return detail;
          })
        );

        setDetailsWithResults(detailsWithDynamicResults);
        console.log('Details with results loaded:', detailsWithDynamicResults);

      } catch (err) {
        console.error('Error fetching data:', err);
        setError(err.message || 'Error al cargar los datos');
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [id, examId]);

  // Handle print
  const handlePrint = () => {
    // Debug information
    console.log('Print triggered with data:');
    console.log('Request:', request);
    console.log('Details:', details);

    // Reference to content to print
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
      alert('Por favor, permita las ventanas emergentes para imprimir');
      return;
    }

    // Create HTML content for printing
    let htmlContent = '';

    // Create document header
    htmlContent += '<html><head>';
    htmlContent += '<title>Resultados de Exámenes - Solicitud #' + id + '</title>';
    htmlContent += '<style>';
    htmlContent += `
      @page {
        size: A4;
        margin: 1cm;
      }
      body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        color: #333;
        font-size: 12px;
      }
      .print-container {
        width: 100%;
        max-width: 800px;
        margin: 0 auto;
        padding: 10px;
      }
      .header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #333;
      }
      .header h2 {
        margin: 8px 0;
        font-size: 18px;
        font-weight: bold;
        text-transform: uppercase;
        color: #333;
      }
      .header p {
        margin: 5px 0;
        font-size: 12px;
        color: #555;
      }
      .patient-info {
        margin-bottom: 25px;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #f5f5f5;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }
      .patient-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
      }
      .patient-info-item {
        margin-bottom: 6px;
        font-size: 13px;
      }
      .patient-info-label {
        font-weight: bold;
        display: inline-block;
        min-width: 80px;
        color: #444;
      }
      .exam-result {
        margin-bottom: 20px;
        page-break-inside: avoid;
      }
      .exam-header {
        font-weight: bold;
        padding: 8px 12px;
        background-color: #e0e0e0;
        border-radius: 4px 4px 0 0;
        border: 1px solid #ccc;
        font-size: 14px;
        margin-top: 15px;
        color: #333;
      }
      .result-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #ddd;
        font-size: 12px;
        margin-bottom: 15px;
      }
      .result-table th, .result-table td {
        padding: 8px;
        text-align: left;
        border: 1px solid #ddd;
      }
      .result-table th {
        background-color: #f5f5f5;
        font-weight: bold;
        font-size: 11px;
        text-transform: uppercase;
      }
      .result-value {
        font-weight: bold;
      }
      .result-table tr:nth-child(even) {
        background-color: #f9f9f9;
      }
      .signature-section {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
        padding-top: 20px;
        border-top: 1px dashed #ccc;
      }
      .signature {
        text-align: center;
        width: 45%;
      }
      .signature-line {
        margin-top: 60px;
        border-top: 1px solid #000;
        padding-top: 5px;
        font-weight: bold;
        font-size: 12px;
      }
      .footer-container {
        position: fixed;
        bottom: 0;
        width: 100%;
        text-align: center;
        font-size: 10px;
        padding: 5px 0;
        border-top: 1px solid #ddd;
      }
      .page-break {
        page-break-after: always;
      }
      .qr-code {
        text-align: right;
        margin-bottom: 10px;
      }
      .qr-code img {
        width: 80px; /* Smaller QR code */
        height: 80px;
      }
      @media print {
        .no-print {
          display: none;
        }
      }
    `;
    htmlContent += '</style>';
    htmlContent += '</head>';

    // Document body
    htmlContent += '<body>';

    // Fixed footer
    htmlContent += '<div class="footer-container">';
    htmlContent += '<p>Este documento es un informe oficial de resultados de laboratorio - Página <span class="pageNumber"></span> de <span class="totalPages"></span></p>';
    htmlContent += '</div>';

    // Main container
    htmlContent += '<div class="print-container">';

    // Enhanced main header
    htmlContent += '<div class="header">';
    htmlContent += '<div class="logo"><h2>LABORATORIO CLÍNICO LAREDO</h2></div>';
    htmlContent += '<h2>INFORME DE RESULTADOS DE LABORATORIO</h2>';
    htmlContent += '<p><strong>Solicitud #' + (request?.id || 'N/A') + '</strong> • ' + formatDate(request?.fecha) + '</p>';
    htmlContent += '<p style="margin-top: 10px; font-size: 11px; color: #666;">Documento generado el ' + formatDate(new Date().toISOString()) + ' a las ' + format(new Date(), 'HH:mm:ss') + '</p>';
    htmlContent += '</div>';

    // QR code if available
    if (request?.qr_code) {
      htmlContent += '<div class="qr-code">';
      htmlContent += '<img src="' + request.qr_code + '" alt="QR Code" />';
      htmlContent += '</div>';
    }

    // Enhanced patient information
    htmlContent += '<div class="patient-info">';
    htmlContent += '<h3 style="margin-bottom: 15px; color: #333; font-size: 14px; font-weight: bold;">INFORMACIÓN DEL PACIENTE</h3>';
    htmlContent += '<div class="patient-info-grid">';

    // Patient details with better formatting
    htmlContent += '<div class="patient-info-item"><span class="patient-info-label">Nombre Completo:</span> ' + (request?.paciente?.nombres || 'N/A') + ' ' + (request?.paciente?.apellidos || '') + '</div>';
    htmlContent += '<div class="patient-info-item"><span class="patient-info-label">DNI:</span> ' + (request?.paciente?.dni || 'No especificado') + '</div>';

    // Handle gender display correctly
    let genero = 'No especificado';
    if (request?.paciente?.sexo) {
      const sexo = request.paciente.sexo.toLowerCase();
      if (sexo === 'm' || sexo === 'masculino') {
        genero = 'Masculino';
      } else if (sexo === 'f' || sexo === 'femenino') {
        genero = 'Femenino';
      } else {
        genero = request.paciente.sexo;
      }
    }
    htmlContent += '<div class="patient-info-item"><span class="patient-info-label">Género:</span> ' + genero + '</div>';
    htmlContent += '<div class="patient-info-item"><span class="patient-info-label">Edad:</span> ' + (request?.paciente?.edad ? request.paciente.edad + ' años' : 'No especificada') + '</div>';
    htmlContent += '<div class="patient-info-item"><span class="patient-info-label">Fecha de Solicitud:</span> ' + formatDate(request?.fecha) + '</div>';
    htmlContent += '<div class="patient-info-item"><span class="patient-info-label">Médico Solicitante:</span> ' + (doctorName || 'No especificado') + '</div>';

    htmlContent += '</div>';
    htmlContent += '</div>';

    // Exam results - usar detailsWithResults si está disponible
    const detailsToUse = detailsWithResults.length > 0 ? detailsWithResults : details;
    detailsToUse.forEach((detail, index) => {
      htmlContent += '<div class="exam-result">';
      htmlContent += '<div class="exam-header">' + (detail.examen?.nombre || 'Examen sin nombre') + ' (' + (detail.examen?.codigo || 'N/A') + ')</div>';

      // Check if there are results and how they are structured
      console.log('Processing results for detail:', detail);

      // Get results directly from the array with more robust handling
      let resultadosArray = [];

      if (Array.isArray(detail.resultados)) {
        resultadosArray = detail.resultados;
      } else if (detail.resultados && typeof detail.resultados === 'object') {
        // If it's an object, try to convert to array
        resultadosArray = Object.values(detail.resultados);
      }

      console.log('Processed results for detail ' + detail.id + ':', resultadosArray);

      if (resultadosArray.length > 0) {
        htmlContent += '<table class="result-table">';
        htmlContent += '<thead><tr><th>Parámetro</th><th>Resultado</th><th>Unidad</th><th>Valor de Referencia</th><th>Estado</th></tr></thead>';
        htmlContent += '<tbody>';

        resultadosArray.forEach(resultado => {
          const fueraRango = resultado.fuera_rango || false;
          const rowClass = fueraRango ? ' style="background-color: #fef2f2;"' : '';
          const valueClass = fueraRango ? ' style="color: #dc2626; font-weight: bold;"' : ' class="result-value"';

          htmlContent += '<tr' + rowClass + '>';
          htmlContent += '<td>' + (resultado.nombre_parametro || resultado.nombre || 'Resultado') + '</td>';
          htmlContent += '<td' + valueClass + '>' + resultado.valor + '</td>';
          htmlContent += '<td>' + (resultado.unidad || '-') + '</td>';
          htmlContent += '<td>' + (resultado.referencia || resultado.valor_referencia || '-') + '</td>';
          htmlContent += '<td>';
          if (fueraRango) {
            htmlContent += '<span style="background-color: #fecaca; color: #991b1b; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold;">FUERA DE RANGO</span>';
          } else {
            htmlContent += '<span style="background-color: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold;">NORMAL</span>';
          }
          htmlContent += '</td>';
          htmlContent += '</tr>';
        });

        htmlContent += '</tbody>';
        htmlContent += '</table>';

        // Add exam observations if available
        if (detail.observaciones) {
          htmlContent += '<div style="margin-top: 10px; padding: 10px; background-color: #f9fafb; border-left: 4px solid #3b82f6; border-radius: 4px;">';
          htmlContent += '<h5 style="margin: 0 0 5px 0; font-size: 12px; font-weight: bold; color: #1f2937;">Observaciones del Examen:</h5>';
          htmlContent += '<p style="margin: 0; font-size: 11px; color: #4b5563;">' + detail.observaciones + '</p>';
          htmlContent += '</div>';
        }
      } else {
        htmlContent += '<div style="text-align: center; padding: 20px; color: #6b7280;">';
        htmlContent += '<p>No hay resultados disponibles para este examen.</p>';
        htmlContent += '</div>';
      }

      htmlContent += '</div>';

      // Insert page break every 4 exams except the last
      if (index < detailsToUse.length - 1 && (index + 1) % 4 === 0) {
        htmlContent += '<div class="page-break"></div>';
      }
    });

    // Signature section
    htmlContent += '<div class="signature-section">';

    // Find registrar information in the details
    let registrador = null;
    if (detailsToUse.length > 0 && detailsToUse[0].registrador) {
      registrador = detailsToUse[0].registrador;
      console.log('Using registrar from details:', registrador);
    }

    // Left signature (Laboratory)
    htmlContent += '<div class="signature">';
    if (registrador) {
      htmlContent += '<div class="signature-line">';
      // Check if registrador has nombre/apellido or nombres/apellidos
      if (registrador.nombre && registrador.apellido) {
        htmlContent += registrador.nombre + ' ' + registrador.apellido;
      } else if (registrador.nombres && registrador.apellidos) {
        htmlContent += registrador.nombres + ' ' + registrador.apellidos;
      } else {
        htmlContent += 'Técnico de Laboratorio';
      }
      htmlContent += '</div>';
      htmlContent += '<p>Laboratorio</p>';
    } else {
      htmlContent += '<div class="signature-line">____________________</div>';
      htmlContent += '<p>Laboratorio</p>';
    }
    htmlContent += '</div>';

    // Right signature (Doctor)
    htmlContent += '<div class="signature">';
    htmlContent += '<div class="signature-line">' + (doctorName || 'Médico') + '</div>';
    htmlContent += '<p>Médico</p>';
    htmlContent += '</div>';

    htmlContent += '</div>'; // End signature section

    htmlContent += '</div>'; // End print-container

    // Script for page numbering and auto-print
    htmlContent += '<script>';
    htmlContent += 'window.onload = function() {';
    htmlContent += '  (function() {';
    htmlContent += '    var style = document.createElement("style");';
    htmlContent += '    style.innerHTML = "@media print {" +';
    htmlContent += '      "  .pageNumber:before {" +';
    htmlContent += '      "    content: counter(page);" +';
    htmlContent += '      "  }" +';
    htmlContent += '      "  .totalPages:before {" +';
    htmlContent += '      "    content: counter(pages);" +';
    htmlContent += '      "  }" +';
    htmlContent += '      "}";';
    htmlContent += '    document.head.appendChild(style);';
    htmlContent += '    setTimeout(function() {';
    htmlContent += '      window.print();';
    htmlContent += '      setTimeout(function() {';
    htmlContent += '        window.close();';
    htmlContent += '      }, 1000);';
    htmlContent += '    }, 500);';
    htmlContent += '  })();';
    htmlContent += '};';
    htmlContent += '</script>';

    htmlContent += '</body></html>';

    // Write HTML content to the print window
    printWindow.document.write(htmlContent);
    printWindow.document.close();
  };

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
              Error al cargar los resultados
            </h3>
            <div className="mt-2 text-sm text-red-700 dark:text-red-300">
              <p>{error}</p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!request || (details.length === 0 && detailsWithResults.length === 0)) {
    return (
      <div className="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4">
        <div className="flex">
          <div className="ml-3">
            <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">
              No hay resultados disponibles
            </h3>
            <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
              <p>
                No se encontraron resultados para esta solicitud o los exámenes aún no han sido completados.
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Enhanced Header */}
      <div className="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center">
              <button
                onClick={() => navigate(returnPath)}
                className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
              >
                <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
              </button>
              <div>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                  Resultados de Laboratorio
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Solicitud #{request?.id} • {formatDate(request?.fecha)}
                </p>
              </div>
            </div>

            <div className="flex items-center space-x-3">
              <Link
                to={`/doctor/resultados/${id}/ver`}
                className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
              >
                <EyeIcon className="-ml-1 mr-2 h-4 w-4" aria-hidden="true" />
                Vista Detallada
              </Link>

              <button
                type="button"
                onClick={handlePrint}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
              >
                <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                Imprimir
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {/* Document Header */}
        <div ref={printRef} className="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
          <div className="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-8 text-center">
            <div className="flex justify-center mb-4">
              <div className="bg-white rounded-full p-3">
                <BeakerIcon className="h-8 w-8 text-primary-600" />
              </div>
            </div>
            <h2 className="text-2xl font-bold text-white mb-2">
              LABORATORIO CLÍNICO LAREDO
            </h2>
            <h3 className="text-xl font-semibold text-primary-100 mb-1">
              INFORME DE RESULTADOS
            </h3>
            <p className="text-primary-200">
              Solicitud #{request?.id || 'N/A'} • {formatDate(request?.fecha)}
            </p>
          </div>

          {/* Patient Information Card */}
          <div className="px-6 py-6 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div className="flex items-center mb-4">
              <UserIcon className="h-5 w-5 text-primary-600 mr-2" />
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                Información del Paciente
              </h3>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="space-y-3">
                <div>
                  <label className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Nombre Completo
                  </label>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {request?.paciente?.nombres || 'N/A'} {request?.paciente?.apellidos || ''}
                  </p>
                </div>
                <div>
                  <label className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    DNI
                  </label>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {request?.paciente?.dni || 'No especificado'}
                  </p>
                </div>
              </div>

              <div className="space-y-3">
                <div>
                  <label className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Género
                  </label>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {(() => {
                      if (!request?.paciente?.sexo) return 'No especificado';
                      const sexo = request.paciente.sexo.toLowerCase();
                      if (sexo === 'm' || sexo === 'masculino') return 'Masculino';
                      if (sexo === 'f' || sexo === 'femenino') return 'Femenino';
                      return request.paciente.sexo;
                    })()}
                  </p>
                </div>
                <div>
                  <label className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Edad
                  </label>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {request?.paciente?.edad ? `${request.paciente.edad} años` : 'No especificada'}
                  </p>
                </div>
              </div>

              <div className="space-y-3">
                <div>
                  <label className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Fecha de Solicitud
                  </label>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {formatDate(request?.fecha)}
                  </p>
                </div>
                <div>
                  <label className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Médico Solicitante
                  </label>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {doctorName || 'No especificado'}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Results Section */}
          <div className="px-6 py-6">
            <div className="flex items-center mb-6">
              <ClipboardDocumentListIcon className="h-5 w-5 text-primary-600 mr-2" />
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                Resultados de Exámenes
              </h3>
              <div className="ml-auto">
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                  {(detailsWithResults.length > 0 ? detailsWithResults : details).length} examen(es) completado(s)
                </span>
              </div>
            </div>

            <div className="space-y-8">
              {console.log('Rendering details in preview:', detailsWithResults.length > 0 ? detailsWithResults : details)}
              {(detailsWithResults.length > 0 ? detailsWithResults : details).map((detail, examIndex) => (
                <div key={detail.id} className="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                  {/* Exam Header */}
                  <div className="bg-gray-100 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="text-lg font-semibold text-gray-900 dark:text-white">
                          {detail.examen?.nombre || 'Examen sin nombre'}
                        </h4>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          Código: {detail.examen?.codigo || 'N/A'} •
                          Categoría: {detail.examen?.categoria?.nombre || 'No especificada'}
                        </p>
                      </div>
                      <div className="text-right">
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                          Completado
                        </span>
                        {detail.fecha_resultado && (
                          <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {formatDate(detail.fecha_resultado)}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Results Content */}
                  <div className="bg-white dark:bg-gray-800">
                    {(() => {
                      // Get results directly from the array with more robust handling
                      let resultadosArray = [];

                      if (Array.isArray(detail.resultados)) {
                        resultadosArray = detail.resultados;
                      } else if (detail.resultados && typeof detail.resultados === 'object') {
                        // If it's an object, try to convert to array
                        resultadosArray = Object.values(detail.resultados);
                      }

                      if (resultadosArray.length > 0) {
                        return (
                          <div className="overflow-hidden">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                              <thead className="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                  <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Parámetro
                                  </th>
                                  <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Resultado
                                  </th>
                                  <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Unidad
                                  </th>
                                  <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Valor de Referencia
                                  </th>
                                  <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Estado
                                  </th>
                                </tr>
                              </thead>
                              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                {resultadosArray.map((resultado, index) => {
                                  // Determinar si está fuera de rango (simulado por ahora)
                                  const fueraRango = resultado.fuera_rango || false;

                                  return (
                                    <tr key={index} className={fueraRango ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700'}>
                                      <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {resultado.nombre_parametro || resultado.nombre || 'Resultado'}
                                      </td>
                                      <td className={`px-6 py-4 text-sm font-bold ${fueraRango ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}`}>
                                        {resultado.valor}
                                      </td>
                                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {resultado.unidad || '-'}
                                      </td>
                                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {resultado.referencia || resultado.valor_referencia || '-'}
                                      </td>
                                      <td className="px-6 py-4">
                                        {fueraRango ? (
                                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Fuera de rango
                                          </span>
                                        ) : (
                                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Normal
                                          </span>
                                        )}
                                      </td>
                                    </tr>
                                  );
                                })}
                              </tbody>
                            </table>

                            {/* Observaciones adicionales */}
                            {detail.observaciones && (
                              <div className="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                                <h5 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                  Observaciones del Examen:
                                </h5>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                  {detail.observaciones}
                                </p>
                              </div>
                            )}
                          </div>
                        );
                      } else {
                        return (
                          <div className="px-6 py-8 text-center">
                            <BeakerIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                              No hay resultados disponibles para este examen.
                            </p>
                          </div>
                        );
                      }
                    })()}
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Professional Footer */}
          <div className="bg-gray-50 dark:bg-gray-700 px-6 py-6 border-t border-gray-200 dark:border-gray-600">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Signatures Section */}
              <div>
                <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-4">
                  Firmas y Validaciones
                </h4>
                <div className="space-y-4">
                  <div className="border-t border-gray-300 dark:border-gray-600 pt-2">
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Técnico de Laboratorio</p>
                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                      {(() => {
                        const detailsToUse = detailsWithResults.length > 0 ? detailsWithResults : details;
                        if (detailsToUse.length > 0 && detailsToUse[0].registrador) {
                          const registrador = detailsToUse[0].registrador;
                          if (registrador.nombre && registrador.apellido) {
                            return `${registrador.nombre} ${registrador.apellido}`;
                          } else if (registrador.nombres && registrador.apellidos) {
                            return `${registrador.nombres} ${registrador.apellidos}`;
                          }
                        }
                        return 'Técnico de Laboratorio';
                      })()}
                    </p>
                  </div>
                  <div className="border-t border-gray-300 dark:border-gray-600 pt-2">
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Médico Solicitante</p>
                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                      {doctorName || 'Médico'}
                    </p>
                  </div>
                </div>
              </div>

              {/* Document Info */}
              <div>
                <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-4">
                  Información del Documento
                </h4>
                <div className="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                  <div className="flex justify-between">
                    <span>Fecha de emisión:</span>
                    <span>{formatDate(new Date().toISOString())}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Hora de emisión:</span>
                    <span>{format(new Date(), 'HH:mm:ss')}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Número de solicitud:</span>
                    <span>#{request?.id}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Total de exámenes:</span>
                    <span>{(detailsWithResults.length > 0 ? detailsWithResults : details).length}</span>
                  </div>
                </div>

                <div className="mt-4 pt-4 border-t border-gray-300 dark:border-gray-600">
                  <p className="text-xs text-gray-500 dark:text-gray-400 text-center">
                    Este documento es un informe oficial de resultados de laboratorio.
                    <br />
                    Laboratorio Clínico Laredo - Todos los derechos reservados.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Action Buttons for Screen View */}
        <div className="mt-8 flex justify-center space-x-4 print:hidden">
          <button
            type="button"
            onClick={() => navigate(returnPath)}
            className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
          >
            <ArrowLeftIcon className="-ml-1 mr-2 h-4 w-4" />
            Volver
          </button>

          <Link
            to={`/doctor/resultados/${id}/ver`}
            className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
          >
            <EyeIcon className="-ml-1 mr-2 h-4 w-4" />
            Vista Detallada
          </Link>

          <button
            type="button"
            onClick={handlePrint}
            className="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
          >
            <PrinterIcon className="-ml-1 mr-2 h-5 w-5" />
            Imprimir Resultados
          </button>
        </div>
      </div>
    </div>
  );
}
