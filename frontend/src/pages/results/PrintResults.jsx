import { useState, useEffect, useRef } from 'react';
import { useParams, useSearchParams, Link } from 'react-router-dom';
import { ArrowLeftIcon, PrinterIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { requestDetailsAPI, servicesAPI, requestsAPI } from '../../services/api';

export default function PrintResults() {
  const { id } = useParams(); // solicitud_id
  const [searchParams] = useSearchParams();
  const examId = searchParams.get('examen');
  const printRef = useRef();

  const [request, setRequest] = useState(null);
  const [details, setDetails] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch data
  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // Obtener la información completa de la solicitud
        const requestResponse = await requestsAPI.getById(id);
        const requestData = requestResponse.data;
        console.log('Request API Response:', requestData);

        // Usar la función del servicio API para obtener detalles
        const detailsResponse = await requestDetailsAPI.getByRequest(id);
        const detailsData = detailsResponse.data;
        console.log('Details API Response:', detailsData);

        // Verificar la estructura de los datos
        if (detailsData && detailsData.success && Array.isArray(detailsData.data)) {
          console.log('Detalles encontrados:', detailsData.data.length);

          // Establecer la solicitud con los datos completos
          // Si la solicitud está incluida en los detalles, usarla desde ahí
          if (detailsData.data.length > 0 && detailsData.data[0].solicitud) {
            const solicitudFromDetails = detailsData.data[0].solicitud;
            console.log('Usando solicitud desde detalles:', solicitudFromDetails);
            setRequest(solicitudFromDetails);
          } else {
            // De lo contrario, usar la respuesta directa de la API
            setRequest(requestData);
          }

          // Filtrar los detalles si se especificó un examen
          if (examId) {
            setDetails(detailsData.data.filter(detail => detail.id === parseInt(examId)));
          } else {
            // Solo mostrar exámenes completados
            const completedDetails = detailsData.data.filter(detail => detail.estado === 'completado');
            console.log('Detalles completados:', completedDetails.length);
            setDetails(completedDetails);
          }

          // Obtener información del servicio si no está incluida
          const currentRequest = detailsData.data[0]?.solicitud || requestData;
          if (currentRequest.servicio_id && !currentRequest.servicio) {
            try {
              // Usar servicesAPI en lugar de fetch directo
              const serviceResponse = await servicesAPI.getById(currentRequest.servicio_id);
              const serviceData = serviceResponse.data;
              console.log('Service API Response:', serviceData);
              if (serviceData && serviceData.servicio) {
                // Actualizar la solicitud con la información del servicio
                setRequest(prev => ({
                  ...prev,
                  servicio: serviceData.servicio
                }));
              }
            } catch (serviceError) {
              console.error('Error fetching service:', serviceError);
            }
          }
        } else {
          setError('No se encontraron detalles para esta solicitud');
        }
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
    // Referencia al contenido para imprimir
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
      alert('Por favor, permita las ventanas emergentes para imprimir');
      return;
    }

    // Crear el contenido HTML para la impresión
    let htmlContent = '';

    // Crear el encabezado del documento
    htmlContent += '<html><head>';
    htmlContent += '<title>Resultados de Exámenes - Solicitud #' + id + '</title>';

    // Estilos CSS
    htmlContent += '<style>';
    htmlContent += '@page { size: A4; margin: 0.5cm; margin-top: 1.2cm; margin-bottom: 1.2cm; }';
    htmlContent += 'body { font-family: Arial, sans-serif; line-height: 1.2; color: #333; margin: 0; padding: 0; background-color: white; font-size: 8pt; }';
    htmlContent += '.print-container { width: 100%; max-width: 21cm; margin: 0 auto; padding: 0.3cm; box-sizing: border-box; }';
    htmlContent += '.header { text-align: center; margin-bottom: 6px; border-bottom: 1px solid #ddd; padding-bottom: 3px; }';
    htmlContent += '.header h2 { font-size: 12pt; margin: 0 0 2px 0; }';
    htmlContent += '.header p { font-size: 8pt; margin: 0; }';

    // Encabezado y pie de página fijos
    htmlContent += '.header-container { position: fixed; top: 0; left: 0; right: 0; height: 0.8cm; text-align: center; border-bottom: 1px solid #ddd; background-color: white; z-index: 1000; font-size: 7pt; padding-top: 2px; }';
    htmlContent += '.footer-container { position: fixed; bottom: 0; left: 0; right: 0; height: 0.6cm; text-align: center; border-top: 1px solid #ddd; background-color: white; font-size: 6pt; color: #777; padding-top: 2px; z-index: 1000; }';

    // Información del paciente
    htmlContent += '.patient-info { margin-bottom: 6px; display: grid; grid-template-columns: 1fr 1fr; gap: 3px; border: 1px solid #ddd; border-radius: 3px; padding: 5px; }';
    htmlContent += '.patient-info div { margin-bottom: 2px; }';
    htmlContent += '.patient-info-title { font-size: 9pt; font-weight: bold; margin-bottom: 3px; border-bottom: 1px solid #eee; padding-bottom: 2px; grid-column: 1 / -1; }';
    htmlContent += '.info-label { font-weight: bold; }';
    htmlContent += '.patient-info p { margin: 1px 0; font-size: 7pt; }';

    // Resultados de exámenes
    htmlContent += '.exam-result { margin-bottom: 8px; border: 1px solid #ddd; padding: 5px; border-radius: 3px; page-break-inside: auto; }';
    htmlContent += '.exam-header, .observations, .result-date, .signature-section { page-break-inside: avoid; }';
    htmlContent += '.page-break { page-break-after: always; }';
    htmlContent += '.exam-header { font-weight: bold; font-size: 9pt; margin-bottom: 3px; border-bottom: 1px solid #eee; padding-bottom: 2px; }';

    // Tablas de resultados
    htmlContent += '.result-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; table-layout: fixed; }';
    htmlContent += '.result-table th, .result-table td { border: 1px solid #ddd; padding: 2px 3px; text-align: left; overflow-wrap: break-word; word-wrap: break-word; }';
    htmlContent += '.result-table th { background-color: #f8f8f8; font-weight: bold; font-size: 7pt; }';
    htmlContent += '.result-table td { font-size: 7pt; }';
    htmlContent += '.result-table th:nth-child(1), .result-table td:nth-child(1) { width: 30%; }';
    htmlContent += '.result-table th:nth-child(2), .result-table td:nth-child(2) { width: 20%; }';
    htmlContent += '.result-table th:nth-child(3), .result-table td:nth-child(3) { width: 15%; }';
    htmlContent += '.result-table th:nth-child(4), .result-table td:nth-child(4) { width: 35%; }';
    htmlContent += '.result-value { font-weight: bold; }';

    // Observaciones y otros elementos
    htmlContent += '.observations { font-style: italic; margin-top: 3px; padding: 3px; background-color: #f9f9f9; border-radius: 2px; font-size: 7pt; }';
    htmlContent += '.observations-title { font-weight: bold; margin-bottom: 1px; }';
    htmlContent += '.observations p { margin: 1px 0; }';
    htmlContent += '.result-date { font-size: 6pt; color: #666; text-align: right; margin-top: 3px; }';
    htmlContent += '.signature-section { margin-top: 10px; display: flex; justify-content: space-between; }';
    htmlContent += '.signature-line { width: 80px; border-top: 1px solid #000; margin-top: 10px; text-align: center; }';
    htmlContent += '.signature-line p { margin: 1px 0; font-size: 6pt; }';
    htmlContent += '.footer { margin-top: 5px; text-align: center; font-size: 6pt; color: #777; border-top: 1px solid #ddd; padding-top: 2px; }';
    htmlContent += '.logo { text-align: center; margin-bottom: 2px; }';
    htmlContent += '.logo img { max-height: 25px; max-width: 100px; }';

    // Estilos para impresión
    htmlContent += '@media print {';
    htmlContent += '  body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }';
    htmlContent += '  .print-container { width: 100%; max-width: none; padding: 0; }';
    htmlContent += '  .exam-result { page-break-inside: auto; }';
    htmlContent += '  .result-table { page-break-inside: auto; }';
    htmlContent += '  tr { page-break-inside: avoid; }';
    htmlContent += '}';
    htmlContent += '</style>';
    htmlContent += '</head>';

    // Cuerpo del documento
    htmlContent += '<body>';

    // Encabezado fijo


    // Pie de página fijo
    htmlContent += '<div class="footer-container">';
    htmlContent += '<p>Este documento es un informe oficial de resultados de laboratorio - Página <span class="pageNumber"></span> de <span class="totalPages"></span></p>';
    htmlContent += '</div>';

    // Contenedor principal
    htmlContent += '<div class="print-container">';

    // Encabezado principal
    htmlContent += '<div class="header">';
    htmlContent += '<div class="logo"><h2>LABORATORIO CLÍNICO</h2></div>';
    htmlContent += '<h2>INFORME DE RESULTADOS</h2>';
    htmlContent += '<p>Solicitud #' + id + ' - ' + formatDate(request?.fecha) + '</p>';
    htmlContent += '</div>';

    // Información del paciente
    htmlContent += '<div class="patient-info">';
    htmlContent += '<div class="patient-info-title">Información del Paciente</div>';
    htmlContent += '<div>';
    htmlContent += '<p><span class="info-label">Paciente:</span> ' + (request?.paciente ? request.paciente.nombres + ' ' + request.paciente.apellidos : 'N/A') + '</p>';
    htmlContent += '</div>';
    htmlContent += '<div>';
    htmlContent += '<p><span class="info-label">Sexo:</span> ' + (request?.paciente?.sexo === 'masculino' ? 'Masculino' : 'Femenino') + '</p>';
    htmlContent += '<p><span class="info-label">Servicio:</span> ' + (request?.servicio?.nombre || 'N/A') + '</p>';
    htmlContent += '</div>';
    htmlContent += '</div>';

    // Resultados de exámenes
    details.forEach((detail, index) => {
      htmlContent += '<div class="exam-result">';
      htmlContent += '<div class="exam-header">' + (detail.examen?.nombre || 'Examen sin nombre') + ' (' + (detail.examen?.codigo || 'N/A') + ')</div>';

      // Verificar si hay resultados y cómo están estructurados
      console.log('Procesando resultados para detalle:', detail);

      // Obtener los resultados directamente del array
      let resultadosArray = Array.isArray(detail.resultados) ? detail.resultados : [];

      console.log('Resultados procesados:', resultadosArray);

      if (resultadosArray.length > 0) {
        htmlContent += '<table class="result-table">';
        htmlContent += '<thead><tr><th>Parámetro</th><th>Resultado</th><th>Unidad</th><th>Valores de Referencia</th></tr></thead>';
        htmlContent += '<tbody>';

        resultadosArray.forEach(resultado => {
          htmlContent += '<tr>';
          htmlContent += '<td>' + (resultado.nombre_parametro || resultado.nombre || 'Resultado') + '</td>';
          htmlContent += '<td class="result-value">' + resultado.valor + '</td>';
          htmlContent += '<td>' + (resultado.unidad || '-') + '</td>';
          htmlContent += '<td>' + (resultado.referencia || '-') + '</td>';
          htmlContent += '</tr>';
        });

        htmlContent += '</tbody></table>';

        if (detail.observaciones) {
          htmlContent += '<div class="observations">';
          htmlContent += '<div class="observations-title">Observaciones:</div>';
          htmlContent += '<p>' + detail.observaciones + '</p>';
          htmlContent += '</div>';
        }

        htmlContent += '<div class="result-date">';
        htmlContent += 'Fecha de resultado: ' + (detail.fecha_resultado ? formatDate(detail.fecha_resultado) : 'N/A');
        htmlContent += '</div>';
      } else if (detail.estado === 'completado') {
        htmlContent += '<p>Este examen está marcado como completado, pero no se encontraron resultados detallados.</p>';

        if (detail.observaciones) {
          htmlContent += '<div class="observations">';
          htmlContent += '<div class="observations-title">Observaciones:</div>';
          htmlContent += '<p>' + detail.observaciones + '</p>';
          htmlContent += '</div>';
        }
      } else {
        htmlContent += '<p>No hay resultados detallados disponibles para este examen.</p>';
      }

      htmlContent += '</div>';

      // Insertar salto de página cada 4 exámenes excepto el último
      if (index < details.length - 1 && (index + 1) % 4 === 0) {
        htmlContent += '<div class="page-break"></div>';
      }
    });

    // Sección de firmas
    htmlContent += '<div class="signature-section">';

    // Verificar el rol del usuario que creó la solicitud
    console.log('Datos del usuario para firma:', request.user);

    // Buscar información del registrador en los detalles
    let registrador = null;
    if (details.length > 0 && details[0].registrador) {
      registrador = details[0].registrador;
      console.log('Usando registrador de detalles:', registrador);
    }

    if (registrador && registrador.role === 'laboratorio') {
      htmlContent += '<div><div class="signature-line"><p>' + (registrador.nombre || '') + ' ' + (registrador.apellido || '') + '</p>';
      htmlContent += '<p>Encargado de Laboratorio</p></div></div>';
    } else if (request.user && request.user.role === 'laboratorio') {
      htmlContent += '<div><div class="signature-line"><p>' + (request.user.nombre || '') + ' ' + (request.user.apellido || '') + '</p>';
      htmlContent += '<p>Encargado de Laboratorio</p></div></div>';
    } else if (registrador && registrador.role === 'doctor') {
      htmlContent += '<div><div class="signature-line"><p>' + (registrador.nombre || '') + ' ' + (registrador.apellido || '') + '</p>';
      htmlContent += '<p>Doctor</p>';
      if (registrador.colegiatura) {
        htmlContent += '<p>CMP: ' + registrador.colegiatura + '</p>';
      }
      htmlContent += '</div></div>';
    } else if (request.user && request.user.role === 'doctor') {
      htmlContent += '<div><div class="signature-line"><p>' + (request.user.nombre || '') + ' ' + (request.user.apellido || '') + '</p>';
      htmlContent += '<p>Doctor</p>';
      if (request.user.colegiatura) {
        htmlContent += '<p>CMP: ' + request.user.colegiatura + '</p>';
      }
      htmlContent += '</div></div>';
    } else {
      // Si no hay información del usuario, mostrar datos genéricos con una nota
      htmlContent += '<div><div class="signature-line"><p>Responsable del Laboratorio</p>';
      htmlContent += '<p style="font-size: 5pt; color: #999;">Documento generado automáticamente</p></div></div>';
    }

    htmlContent += '<div><div class="signature-line"><p>Sello</p></div></div>';
    htmlContent += '</div>';

    // Pie de página principal
    htmlContent += '<div class="footer">';
    htmlContent += '<p>Este documento es un informe oficial de resultados de laboratorio.</p>';
    htmlContent += '<p>Fecha de impresión: ' + format(new Date(), 'dd/MM/yyyy HH:mm', { locale: es }) + '</p>';
    htmlContent += '</div>';

    htmlContent += '</div>'; // Cierre del contenedor principal

    // Script para numeración de páginas y autoimpresión
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

    // Escribir el contenido HTML en la ventana de impresión
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

  // Función para depurar el estado
  useEffect(() => {
    console.log('Estado actual de la solicitud:', request);
    console.log('Estado actual de los detalles:', details);
  }, [request, details]);

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

  if (!request || details.length === 0) {
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
    <div>
      <div className="mb-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <Link
              to={`/resultados/${id}`}
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </Link>
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
              Resultados de Exámenes
            </h1>
          </div>
          <button
            type="button"
            onClick={handlePrint}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Imprimir
          </button>
        </div>
      </div>

      <div ref={printRef} className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6 text-center">
          <h2 className="text-xl font-bold text-gray-900 dark:text-white">
            RESULTADOS DE LABORATORIO
          </h2>
          <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Solicitud #{request.id} - {formatDate(request.fecha)}
          </p>
        </div>

        <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-0">
          <dl className="sm:divide-y sm:divide-gray-200 sm:dark:divide-gray-700">
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Paciente
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {request?.paciente ? `${request.paciente.nombres} ${request.paciente.apellidos}` : 'N/A'}
              </dd>
            </div>

            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Servicio
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {request?.servicio?.nombre || 'N/A'}
              </dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Fecha de Solicitud
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {formatDate(request.fecha)}
              </dd>
            </div>
          </dl>
        </div>

        <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
            Resultados de Exámenes
          </h3>

          <div className="space-y-6">
            {details.map((detail) => (
              <div key={detail.id} className="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                <h4 className="text-md font-medium text-gray-900 dark:text-white mb-2">
                  {detail.examen?.nombre || 'Examen sin nombre'} ({detail.examen?.codigo || 'N/A'})
                </h4>

                {/* Verificar si hay resultados y mostrarlos */}
                {console.log('Detalle completo:', detail)}
                {console.log('Resultados del detalle:', detail.resultados)}
                {(() => {
                  // Obtener los resultados directamente del array
                  let resultadosArray = Array.isArray(detail.resultados) ? detail.resultados : [];

                  console.log('Resultados procesados para vista previa:', resultadosArray);

                  if (resultadosArray.length > 0) {
                    return (
                      <>
                        <div className="mt-3">
                          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                              <thead className="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Parámetro
                                  </th>
                                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Resultado
                                  </th>
                                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Unidad
                                  </th>
                                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Valores de Referencia
                                  </th>
                                </tr>
                              </thead>
                              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                {resultadosArray.map((resultado, index) => {
                                  console.log('Mostrando resultado:', resultado);
                                  return (
                                    <tr key={index}>
                                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {resultado.nombre_parametro || resultado.nombre || 'Resultado'}
                                      </td>
                                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {resultado.valor}
                                      </td>
                                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {resultado.unidad || '-'}
                                      </td>
                                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {resultado.referencia || '-'}
                                      </td>
                                    </tr>
                                  );
                                })}
                              </tbody>
                            </table>
                          </div>
                        </div>

                        {detail.observaciones && (
                          <div className="mt-4">
                            <h5 className="text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones:</h5>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{detail.observaciones}</p>
                          </div>
                        )}
                      </>
                    );
                  } else if (detail.estado === 'completado') {
                    return (
                      <div className="mt-3">
                        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-4">
                          <p className="text-sm text-gray-900 dark:text-white">
                            Este examen está marcado como completado, pero no se encontraron resultados detallados.
                          </p>
                        </div>
                      </div>
                    );
                  } else {
                    return (
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        No hay resultados detallados disponibles para este examen.
                      </p>
                    );
                  }
                })()}

                <div className="mt-3 text-sm text-gray-500 dark:text-gray-400">
                  Fecha de resultado: {detail.fecha_resultado ? formatDate(detail.fecha_resultado) : 'N/A'}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
