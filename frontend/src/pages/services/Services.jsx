import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { PlusIcon, PencilIcon, TrashIcon, EyeSlashIcon } from '@heroicons/react/24/outline';
import { servicesAPI } from '../../services/api';
import Pagination from '../../components/common/Pagination';
import '../../styles/table-compact.css';

export default function Services() {
  const [services, setServices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [serviceToDelete, setServiceToDelete] = useState(null);
  const [deleteReason, setDeleteReason] = useState('');
  const itemsPerPage = 10;

  useEffect(() => {
    fetchServices();
  }, []);

  const fetchServices = async () => {
    try {
      setLoading(true);
      const response = await servicesAPI.getAll();
      if (response.data.status) {
        setServices(response.data.servicios);
      } else {
        setError('Error al cargar servicios');
      }
    } catch (err) {
      setError('Error al cargar servicios');
      console.error('Error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteService = async () => {
    if (!serviceToDelete) return;

    try {
      const response = await servicesAPI.delete(serviceToDelete.id, {
        motivo: deleteReason
      });
      
      if (response.data.status) {
        await fetchServices();
        setShowDeleteModal(false);
        setServiceToDelete(null);
        setDeleteReason('');
      } else {
        alert(response.data.message || 'Error al desactivar servicio');
      }
    } catch (err) {
      console.error('Error:', err);
      alert(err.response?.data?.message || 'Error al desactivar servicio');
    }
  };

  const openDeleteModal = (service) => {
    setServiceToDelete(service);
    setShowDeleteModal(true);
  };

  const closeDeleteModal = () => {
    setShowDeleteModal(false);
    setServiceToDelete(null);
    setDeleteReason('');
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
      <div className="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Servicios</h1>
          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Gestión de servicios del laboratorio
          </p>
        </div>
        <div className="mt-4 sm:mt-0 flex space-x-3">
          <Link
            to="/servicios/inactivos"
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
          >
            <EyeSlashIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Ver Inactivos
          </Link>
          <Link
            to="/servicios/nuevo"
            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Nuevo Servicio
          </Link>
        </div>
      </div>

      {/* Barra de búsqueda */}
      <div className="mb-6">
        <div className="max-w-md">
          <input
            type="text"
            placeholder="Buscar servicios..."
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
                  Creado
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
                      {new Date(service.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-3 py-3 text-center col-acciones">
                      <div className="flex justify-center space-x-2">
                        <Link
                          to={`/servicios/${service.id}/editar`}
                          className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-primary-100 dark:hover:bg-primary-900"
                          title="Editar servicio"
                        >
                          <PencilIcon className="w-4 h-4" />
                        </Link>
                        <button
                          onClick={() => openDeleteModal(service)}
                          className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-red-100 dark:hover:bg-red-900"
                          title="Desactivar servicio"
                        >
                          <TrashIcon className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="5" className="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron servicios
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

      {/* Modal de confirmación para desactivar */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Desactivar Servicio
              </h3>
              <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                ¿Está seguro que desea desactivar el servicio "{serviceToDelete?.nombre}"?
              </p>
              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Motivo de desactivación (opcional)
                </label>
                <textarea
                  value={deleteReason}
                  onChange={(e) => setDeleteReason(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  rows="3"
                  placeholder="Ingrese el motivo..."
                />
              </div>
              <div className="flex justify-end space-x-3">
                <button
                  onClick={closeDeleteModal}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
                >
                  Cancelar
                </button>
                <button
                  onClick={handleDeleteService}
                  className="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md"
                >
                  Desactivar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
