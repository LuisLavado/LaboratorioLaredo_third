import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import { servicesAPI } from '../../services/api';

export default function EditService() {
  const navigate = useNavigate();
  const { id } = useParams();
  const [formData, setFormData] = useState({
    nombre: '',
    parent_id: ''
  });
  const [parentServices, setParentServices] = useState([]);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    fetchService();
    fetchParentServices();
  }, [id]);

  const fetchService = async () => {
    try {
      const response = await servicesAPI.getById(id);
      if (response.data.status) {
        const service = response.data.servicio;
        setFormData({
          nombre: service.nombre,
          parent_id: service.parent_id || ''
        });
      } else {
        navigate('/servicios', {
          state: { error: 'Servicio no encontrado' }
        });
      }
    } catch (err) {
      console.error('Error al cargar servicio:', err);
      navigate('/servicios', {
        state: { error: 'Error al cargar servicio' }
      });
    } finally {
      setInitialLoading(false);
    }
  };

  const fetchParentServices = async () => {
    try {
      const response = await servicesAPI.getAll();
      if (response.data.status) {
        // Filtrar servicios padre y excluir el servicio actual para evitar referencias circulares
        const parentOnly = response.data.servicios.filter(service => 
          !service.parent_id && service.id !== parseInt(id)
        );
        setParentServices(parentOnly);
      }
    } catch (err) {
      console.error('Error al cargar servicios padre:', err);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value === '' ? null : value
    }));
    
    // Limpiar error del campo cuando el usuario empiece a escribir
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      const response = await servicesAPI.update(id, {
        nombre: formData.nombre.trim(),
        parent_id: formData.parent_id || null
      });

      if (response.data.status) {
        navigate('/servicios', {
          state: { message: 'Servicio actualizado correctamente' }
        });
      } else {
        if (response.data.errors) {
          setErrors(response.data.errors);
        } else {
          setErrors({ general: response.data.message || 'Error al actualizar servicio' });
        }
      }
    } catch (err) {
      console.error('Error:', err);
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      } else {
        setErrors({ 
          general: err.response?.data?.message || 'Error al actualizar servicio' 
        });
      }
    } finally {
      setLoading(false);
    }
  };

  if (initialLoading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate('/servicios')}
          className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          <ArrowLeftIcon className="w-4 h-4 mr-1" />
          Volver a Servicios
        </button>
      </div>

      <div className="max-w-2xl">
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
          <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h1 className="text-xl font-semibold text-gray-900 dark:text-white">
              Editar Servicio
            </h1>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Modificar la información del servicio
            </p>
          </div>

          <form onSubmit={handleSubmit} className="px-6 py-4 space-y-6">
            {errors.general && (
              <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
                <div className="text-sm text-red-700 dark:text-red-300">
                  {errors.general}
                </div>
              </div>
            )}

            <div>
              <label htmlFor="nombre" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Nombre del Servicio *
              </label>
              <input
                type="text"
                id="nombre"
                name="nombre"
                value={formData.nombre}
                onChange={handleInputChange}
                className={`mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white ${
                  errors.nombre 
                    ? 'border-red-300 dark:border-red-600' 
                    : 'border-gray-300 dark:border-gray-600'
                }`}
                placeholder="Ingrese el nombre del servicio"
                required
              />
              {errors.nombre && (
                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                  {Array.isArray(errors.nombre) ? errors.nombre[0] : errors.nombre}
                </p>
              )}
            </div>

            <div>
              <label htmlFor="parent_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Servicio Padre (opcional)
              </label>
              <select
                id="parent_id"
                name="parent_id"
                value={formData.parent_id}
                onChange={handleInputChange}
                className={`mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white ${
                  errors.parent_id 
                    ? 'border-red-300 dark:border-red-600' 
                    : 'border-gray-300 dark:border-gray-600'
                }`}
              >
                <option value="">Sin servicio padre</option>
                {parentServices.map((service) => (
                  <option key={service.id} value={service.id}>
                    {service.nombre}
                  </option>
                ))}
              </select>
              {errors.parent_id && (
                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                  {Array.isArray(errors.parent_id) ? errors.parent_id[0] : errors.parent_id}
                </p>
              )}
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Si selecciona un servicio padre, este servicio será un sub-servicio
              </p>
            </div>

            <div className="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
              <button
                type="button"
                onClick={() => navigate('/servicios')}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
              >
                Cancelar
              </button>
              <button
                type="submit"
                disabled={loading}
                className="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {loading ? (
                  <div className="flex items-center">
                    <div className="animate-spin rounded-full h-4 w-4 border-t-2 border-b-2 border-white mr-2"></div>
                    Actualizando...
                  </div>
                ) : (
                  'Actualizar Servicio'
                )}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
