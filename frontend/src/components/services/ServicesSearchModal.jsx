import React, { useState, useEffect } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { XMarkIcon, CheckIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { servicesAPI } from '../../services/api';
import toast from 'react-hot-toast';

export default function ServicesSearchModal({ isOpen, onClose, onSelectServices, selectedServiceIds = [] }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [services, setServices] = useState([]);
  const [filteredServices, setFilteredServices] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [selectedServices, setSelectedServices] = useState([]);
  
  // Cargar servicios cuando se abre el modal
  useEffect(() => {
    if (isOpen) {
      loadServices();
      setSearchTerm('');
    }
  }, [isOpen]);
  
  // Inicializar servicios seleccionados
  useEffect(() => {
    if (services.length > 0 && selectedServiceIds.length > 0) {
      const selected = services.filter(service => selectedServiceIds.includes(service.id));
      setSelectedServices(selected);
    } else {
      setSelectedServices([]);
    }
  }, [services, selectedServiceIds]);
  
  // Organizar servicios jerárquicamente y filtrar basado en el término de búsqueda
  useEffect(() => {
    if (services.length === 0) {
      setFilteredServices([]);
      return;
    }

    let filtered = services;

    // Aplicar filtro de búsqueda si hay término
    if (searchTerm.trim()) {
      filtered = services.filter(service =>
        service.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (service.full_name && service.full_name.toLowerCase().includes(searchTerm.toLowerCase()))
      );
    }

    // Organizar servicios: primero padres, luego sus hijos inmediatamente después
    const organized = [];
    const parentServices = filtered.filter(service => !service.is_child);
    const childServices = filtered.filter(service => service.is_child);

    parentServices.forEach(parent => {
      organized.push(parent);
      // Agregar hijos de este padre inmediatamente después
      const children = childServices.filter(child => child.parent_id === parent.id);
      organized.push(...children);
    });

    // Agregar hijos huérfanos (si el padre no está en la lista filtrada)
    const orphanChildren = childServices.filter(child =>
      !parentServices.some(parent => parent.id === child.parent_id)
    );
    organized.push(...orphanChildren);

    setFilteredServices(organized);
  }, [searchTerm, services]);
  
  // Cargar todos los servicios con estadísticas
  const loadServices = async () => {
    setIsLoading(true);
    try {
      console.log('Cargando servicios con estadísticas...');
      const response = await servicesAPI.getAllWithStats();
      console.log('Respuesta de servicios:', response);

      if (response.data && response.data.servicios) {
        // Los servicios ya vienen ordenados por cantidad de solicitudes (más primero)
        const allServices = response.data.servicios;
        console.log('Servicios encontrados:', allServices.length);
        setServices(allServices);
        setFilteredServices(allServices);
      } else {
        console.log('No se encontraron servicios en la respuesta');
        toast.error('No se encontraron servicios');
      }
    } catch (error) {
      console.error('Error al cargar servicios:', error);
      toast.error('Error al cargar los servicios: ' + (error.message || 'Error desconocido'));
    } finally {
      setIsLoading(false);
    }
  };
  
  // Manejar la selección de un servicio
  const handleToggleService = (service) => {
    setSelectedServices(prevSelected => {
      const isSelected = prevSelected.some(s => s.id === service.id);
      
      if (isSelected) {
        return prevSelected.filter(s => s.id !== service.id);
      } else {
        return [...prevSelected, service];
      }
    });
  };
  
  // Confirmar selección de servicios
  const handleConfirm = () => {
    onSelectServices(selectedServices);
    onClose();
  };
  
  // Manejar la búsqueda al presionar Enter
  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
    }
  };
  
  // Verificar si un servicio está seleccionado
  const isServiceSelected = (serviceId) => {
    return selectedServices.some(service => service.id === serviceId);
  };

  return (
    <Transition appear show={isOpen} as={Fragment}>
      <Dialog as="div" className="relative z-50" onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in duration-200"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-black bg-opacity-25" />
        </Transition.Child>

        <div className="fixed inset-0 overflow-y-auto">
          <div className="flex min-h-full items-center justify-center p-4 text-center">
            <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 scale-95"
              enterTo="opacity-100 scale-100"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 scale-100"
              leaveTo="opacity-0 scale-95"
            >
              <Dialog.Panel className="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all">
                <div className="flex justify-between items-center mb-4">
                  <div>
                    <Dialog.Title
                      as="h3"
                      className="text-lg font-medium leading-6 text-gray-900 dark:text-white"
                    >
                      Seleccionar Servicios
                    </Dialog.Title>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      {isLoading ? (
                        'Cargando servicios...'
                      ) : (
                        `${services.length} servicio${services.length !== 1 ? 's' : ''} disponible${services.length !== 1 ? 's' : ''}`
                      )}
                    </p>
                  </div>
                  <button
                    type="button"
                    className="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    onClick={onClose}
                  >
                    <span className="sr-only">Cerrar</span>
                    <XMarkIcon className="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>

                <div className="mt-2">
                  {/* Buscador */}
                  <div className="relative mb-4">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                    </div>
                    <input
                      type="text"
                      className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                      placeholder="Buscar servicios..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      onKeyDown={handleKeyDown}
                    />
                  </div>
                  
                  {selectedServices.length > 0 && (
                    <div className="mb-4">
                      <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Servicios seleccionados ({selectedServices.length})
                      </h4>
                      <div className="flex flex-wrap gap-2">
                        {selectedServices.map(service => (
                          <span
                            key={service.id}
                            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100"
                          >
                            {service.full_name || service.nombre}
                            <button
                              type="button"
                              className="ml-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full text-primary-400 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200"
                              onClick={() => handleToggleService(service)}
                            >
                              <span className="sr-only">Eliminar</span>
                              <XMarkIcon className="h-3 w-3" aria-hidden="true" />
                            </button>
                          </span>
                        ))}
                      </div>
                    </div>
                  )}
                  
                  {/* Información de resultados de búsqueda */}
                  {searchTerm && !isLoading && (
                    <div className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                      {filteredServices.length > 0 ? (
                        `Mostrando ${filteredServices.length} de ${services.length} servicios`
                      ) : (
                        `No se encontraron servicios que coincidan con "${searchTerm}"`
                      )}
                    </div>
                  )}

                  <div className="mt-4 max-h-80 overflow-y-auto">
                    {isLoading ? (
                      <div className="flex justify-center py-4">
                        <div className="animate-spin h-8 w-8 border-t-2 border-b-2 border-primary-500 rounded-full"></div>
                      </div>
                    ) : filteredServices.length > 0 ? (
                      <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                        {filteredServices.map((service) => {
                          // Determinar si el servicio es seleccionable
                          const isSelectable = service.is_child || (!service.is_child && service.children_count === 0);

                          return (
                            <li
                              key={service.id}
                              className={`px-4 py-4 ${
                                isSelectable
                                  ? `hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer ${
                                      isServiceSelected(service.id) ? 'bg-primary-50 dark:bg-primary-900/20' : ''
                                    } ${service.is_child ? 'pl-8' : ''}`
                                  : 'cursor-not-allowed opacity-60'
                              }`}
                              onClick={() => isSelectable && handleToggleService(service)}
                            >
                              <div className="flex items-center">
                                {isSelectable ? (
                                  <input
                                    type="checkbox"
                                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded mr-3"
                                    checked={isServiceSelected(service.id)}
                                    onChange={() => handleToggleService(service)}
                                    onClick={(e) => e.stopPropagation()}
                                  />
                                ) : (
                                  <div className="h-4 w-4 mr-3"></div>
                                )}
                                <div className="flex-1">
                                  <div className="flex items-center">
                                    {service.is_child && (
                                      <span className="text-gray-400 mr-2">└─</span>
                                    )}
                                    <p className={`text-sm font-medium ${
                                      isSelectable
                                        ? 'text-gray-700 dark:text-gray-300'
                                        : 'text-gray-900 dark:text-white font-semibold'
                                    }`}>
                                      {service.nombre}
                                    </p>
                                    {!service.is_child && service.children_count > 0 && (
                                      <span className="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                        (Seleccione un sub-servicio)
                                      </span>
                                    )}
                                  </div>
                                {service.is_child && (
                                  <p className="text-xs text-gray-500 dark:text-gray-400 ml-4">
                                    {(() => {
                                      // Buscar el servicio padre para mostrar su nombre
                                      const parent = services.find(s => s.id === service.parent_id);
                                      return parent ? `Parte de ${parent.nombre}` : 'Sub-servicio';
                                    })()}
                                  </p>
                                )}
                                  <p className={`text-xs text-gray-500 dark:text-gray-400 ${service.is_child ? 'ml-4' : ''}`}>
                                    {service.solicitudes_count || 0} solicitud{(service.solicitudes_count || 0) !== 1 ? 'es' : ''}
                                  </p>
                                </div>
                              </div>
                            </li>
                          );
                        })}
                      </ul>
                    ) : (
                      <div className="text-center py-4">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          No se encontraron servicios
                        </p>
                      </div>
                    )}
                  </div>
                </div>

                {/* Información de selección y botón seleccionar todos */}
                <div className="mt-4 flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-4">
                  <div className="text-sm text-gray-500 dark:text-gray-400">
                    {selectedServices.length} servicio{selectedServices.length !== 1 ? 's' : ''} seleccionado{selectedServices.length !== 1 ? 's' : ''}
                  </div>
                  <button
                    type="button"
                    className="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                    onClick={() => {
                      // Servicios seleccionables: hijos + padres sin sub-servicios
                      const selectableServices = filteredServices.filter(service =>
                        service.is_child || (!service.is_child && service.children_count === 0)
                      );
                      if (selectedServices.length === selectableServices.length) {
                        // Deseleccionar todos
                        setSelectedServices([]);
                      } else {
                        // Seleccionar todos los servicios seleccionables filtrados
                        setSelectedServices(selectableServices);
                      }
                    }}
                  >
                    {(() => {
                      const selectableServices = filteredServices.filter(service =>
                        service.is_child || (!service.is_child && service.children_count === 0)
                      );
                      return selectedServices.length === selectableServices.length ? 'Deseleccionar todos' : 'Seleccionar todos';
                    })()}
                  </button>
                </div>

                <div className="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    className="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    onClick={onClose}
                  >
                    Cancelar
                  </button>
                  <button
                    type="button"
                    className="inline-flex justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    onClick={handleConfirm}
                  >
                    Confirmar ({selectedServices.length})
                  </button>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition>
  );
}
