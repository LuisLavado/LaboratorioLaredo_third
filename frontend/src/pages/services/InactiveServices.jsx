import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeftIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import { servicesAPI } from '../../services/api';
import Pagination from '../../components/common/Pagination';
import '../../styles/table-compact.css';

export default function InactiveServices() {
  const [services, setServices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [showActivateModal, setShowActivateModal] = useState(false);
  const [serviceToActivate, setServiceToActivate] = useState(null);
  const itemsPerPage = 10;

  useEffect(() => {
    fetchInactiveServices();
  }, []);

  const fetchInactiveServices = async () => {
    try {
      setLoading(true);
      const response = await servicesAPI.getInactive();
      if (response.data.status) {
        setServices(response.data.servicios);
      } else {
        setError('Error al cargar servicios inactivos');
      }
    } catch (err) {
      setError('Error al cargar servicios inactivos');
      console.error('Error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleActivateService = async () => {
    if (!serviceToActivate) return;

    try {
      const response = await servicesAPI.activate(serviceToActivate.id);
      
      if (response.data.status) {
        await fetchInactiveServices();
        setShowActivateModal(false);
        setServiceToActivate(null);
      } else {
        alert(response.data.message || 'Error al activar servicio');
      }
    } catch (err) {
      console.error('Error:', err);
      alert(err.response?.data?.message || 'Error al activar servicio');
    }
  };

  const openActivateModal = (service) => {
    setServiceToActivate(service);
    setShowActivateModal(true);
  };

  const closeActivateModal = () => {
    setShowActivateModal(false);
    setServiceToActivate(null);
  };

  // Filtrar servicios
  const filteredServices = services.filter(service =>
    service.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (service.parent?.nombre && service.parent.nombre.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  // Paginación
  const totalPages = Math.ceil(filteredServices.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedServices = filteredServices.slice(startIndex, startIndex + itemsPerPage);

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <Link
          to="/servicios"
          className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          <ArrowLeftIcon className="w-4 h-4 mr-1" />
          Volver a Servicios
        </Link>
      </div>

      <div className="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Servicios Inactivos</h1>
          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Servicios que han sido desactivados
          </p>
        </div>
      </div>

      {/* Barra de búsqueda */}
      <div className="mb-6">
        <div className="max-w-md">
          <input
            type="text"
            placeholder="Buscar servicios inactivos..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
          />
        </div>
      </div>

      {error ? (
        <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
          <div className="text-sm text-red-700 dark:text-red-300">{error}</div>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-compact">
            <thead className="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-id">
                  ID
                </th>
                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-servicio">
                  Nombre
                </th>
                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-servicio">
                  Servicio Padre
                </th>
                <th className="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-fecha">
                  Desactivado
                </th>
                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-servicio">
                  Motivo
                </th>
                <th className="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider col-acciones">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              {paginatedServices.length > 0 ? (
                paginatedServices.map((service) => (
                  <tr key={service.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-3 py-3 text-sm font-medium text-gray-900 dark:text-white text-center col-id">
                      {service.id}
                    </td>
                    <td className="px-3 py-3 text-sm text-gray-500 dark:text-gray-300 col-servicio">
                      <div className="text-container break-words">
                        <div className={`font-medium ${service.parent_id ? 'ml-4' : ''} text-gray-900 dark:text-white`}>
                          {service.parent_id && '└─ '}
                          {service.nombre}
                        </div>
                      </div>
                    </td>
                    <td className="px-3 py-3 text-sm text-gray-500 dark:text-gray-300 col-servicio">
                      <div className="text-container break-words">
                        {service.parent?.nombre || '-'}
                      </div>
                    </td>
                    <td className="px-3 py-3 text-sm text-gray-500 dark:text-gray-300 text-center col-fecha">
                      {service.fecha_desactivacion 
                        ? new Date(service.fecha_desactivacion).toLocaleDateString()
                        : '-'
                      }
                    </td>
                    <td className="px-3 py-3 text-sm text-gray-500 dark:text-gray-300 col-servicio">
                      <div className="text-container break-words">
                        {service.motivo_desactivacion || 'Sin motivo especificado'}
                      </div>
                    </td>
                    <td className="px-3 py-3 text-center col-acciones">
                      <button
                        onClick={() => openActivateModal(service)}
                        className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-green-100 dark:hover:bg-green-900"
                        title="Activar servicio"
                      >
                        <ArrowPathIcon className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="6" className="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron servicios inactivos
                  </td>
                </tr>
              )}
            </tbody>
          </table>

          {/* Paginación */}
          <Pagination
            currentPage={currentPage}
            totalPages={totalPages}
            totalItems={filteredServices.length}
            itemsPerPage={itemsPerPage}
            onPageChange={setCurrentPage}
          />
        </div>
      )}

      {/* Modal de confirmación para activar */}
      {showActivateModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Activar Servicio
              </h3>
              <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                ¿Está seguro que desea activar el servicio "{serviceToActivate?.nombre}"?
              </p>
              {serviceToActivate?.motivo_desactivacion && (
                <div className="mb-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-md">
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    <strong>Motivo de desactivación:</strong> {serviceToActivate.motivo_desactivacion}
                  </p>
                </div>
              )}
              <div className="flex justify-end space-x-3">
                <button
                  onClick={closeActivateModal}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
                >
                  Cancelar
                </button>
                <button
                  onClick={handleActivateService}
                  className="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md"
                >
                  Activar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
