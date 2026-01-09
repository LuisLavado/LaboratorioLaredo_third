import { Fragment, useState, useEffect } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { MagnifyingGlassIcon, XMarkIcon, PlusIcon } from '@heroicons/react/24/outline';
import { useQueryClient } from '@tanstack/react-query';
import { servicesAPI } from '../../services/api';
import toast from 'react-hot-toast';

export default function ServiceSearchModal({ isOpen, onClose, onSelectService }) {
  const queryClient = useQueryClient();
  const [searchTerm, setSearchTerm] = useState('');
  const [services, setServices] = useState([]);
  const [filteredServices, setFilteredServices] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isCreating, setIsCreating] = useState(false);
  
  // Cargar servicios cuando se abre el modal
  useEffect(() => {
    if (isOpen) {
      loadServices();
      setSearchTerm('');
    }
  }, [isOpen]);
  
  // Filtrar servicios cuando cambia el término de búsqueda manteniendo jerarquía
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

    // Reorganizar manteniendo jerarquía
    const organized = [];
    const parentServices = filtered.filter(service => !service.is_child);
    const childServices = filtered.filter(service => service.is_child);

    parentServices.forEach(parent => {
      organized.push(parent);
      // Agregar hijos de este padre inmediatamente después
      const children = childServices.filter(child => child.parent_id === parent.id);
      organized.push(...children);
    });

    // Agregar hijos huérfanos
    const orphanChildren = childServices.filter(child =>
      !parentServices.some(parent => parent.id === child.parent_id)
    );
    organized.push(...orphanChildren);

    setFilteredServices(organized);
  }, [searchTerm, services]);
  
  // Cargar todos los servicios con estadísticas para mostrar jerarquía
  const loadServices = async () => {
    setIsLoading(true);
    try {
      const response = await servicesAPI.getAllWithStats();
      if (response.data && response.data.servicios) {
        // Organizar servicios jerárquicamente
        const allServices = response.data.servicios;
        const organized = [];
        const parentServices = allServices.filter(service => !service.is_child);
        const childServices = allServices.filter(service => service.is_child);

        parentServices.forEach(parent => {
          organized.push(parent);
          // Agregar hijos de este padre inmediatamente después
          const children = childServices.filter(child => child.parent_id === parent.id);
          organized.push(...children);
        });

        // Agregar hijos huérfanos
        const orphanChildren = childServices.filter(child =>
          !parentServices.some(parent => parent.id === child.parent_id)
        );
        organized.push(...orphanChildren);

        setServices(organized);
        setFilteredServices(organized);
      }
    } catch (error) {
      console.error('Error al cargar servicios:', error);
      toast.error('Error al cargar los servicios');
    } finally {
      setIsLoading(false);
    }
  };
  
  // Manejar la selección de un servicio
  const handleSelectService = (service) => {
    onSelectService(service);
    onClose();
  };

  // Crear un nuevo servicio
  const handleCreateService = async () => {
    if (!searchTerm.trim()) {
      toast.error('Por favor ingrese el nombre del servicio');
      return;
    }

    setIsCreating(true);
    try {
      const response = await servicesAPI.create({ nombre: searchTerm.trim() });
      if (response.data && response.data.status) {
        const newService = response.data.servicio;

        // Agregar el nuevo servicio a la lista local
        setServices(prev => [...prev, newService]);
        setFilteredServices(prev => [...prev, newService]);

        // Invalidar la cache de servicios para que se actualice en otros componentes
        queryClient.invalidateQueries(['services']);

        toast.success('Servicio creado exitosamente');

        // Seleccionar automáticamente el nuevo servicio
        handleSelectService(newService);
      }
    } catch (error) {
      console.error('Error al crear servicio:', error);
      if (error.response?.data?.errors?.nombre) {
        toast.error('Este servicio ya existe');
      } else {
        toast.error('Error al crear el servicio');
      }
    } finally {
      setIsCreating(false);
    }
  };

  // Manejar la búsqueda al presionar Enter
  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      // Si no hay servicios filtrados y hay texto, crear nuevo servicio
      if (filteredServices.length === 0 && searchTerm.trim()) {
        handleCreateService();
      }
    }
  };
  
  return (
    <Transition.Root show={isOpen} as={Fragment}>
      <Dialog as="div" className="relative z-10" onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in duration-200"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </Transition.Child>

        <div className="fixed inset-0 z-10 overflow-y-auto">
          <div className="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enterTo="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 translate-y-0 sm:scale-100"
              leaveTo="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <Dialog.Panel className="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <div>
                  <div className="flex justify-between items-center mb-4">
                    <Dialog.Title as="h3" className="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                      Seleccionar Servicio
                    </Dialog.Title>
                    <button
                      type="button"
                      className="rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
                      onClick={onClose}
                    >
                      <span className="sr-only">Cerrar</span>
                      <XMarkIcon className="h-6 w-6" aria-hidden="true" />
                    </button>
                  </div>
                  
                  <div className="mb-4">
                    <div className="relative">
                      <input
                        type="text"
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                        placeholder="Buscar servicio o escribir uno nuevo..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        onKeyDown={handleKeyDown}
                      />
                      <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                      </div>
                    </div>
                    <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                      Presiona Enter para crear un nuevo servicio si no encuentras el que buscas
                    </p>
                  </div>
                  
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
                                  ? `hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer ${service.is_child ? 'pl-8' : ''}`
                                  : 'cursor-not-allowed opacity-60'
                              }`}
                              onClick={() => isSelectable && handleSelectService(service)}
                            >
                              <div className="flex justify-between items-center">
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
                                {service.solicitudes_count !== undefined && (
                                  <p className={`text-xs text-gray-500 dark:text-gray-400 ${service.is_child ? 'ml-4' : ''}`}>
                                    {service.solicitudes_count || 0} solicitud{(service.solicitudes_count || 0) !== 1 ? 'es' : ''}
                                  </p>
                                )}
                                </div>
                              </div>
                            </li>
                          );
                        })}
                      </ul>
                    ) : (
                      <div className="text-center py-4">
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                          No se encontraron servicios
                        </p>
                        {searchTerm.trim() && (
                          <button
                            type="button"
                            onClick={handleCreateService}
                            disabled={isCreating}
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                          >
                            {isCreating ? (
                              <>
                                <div className="animate-spin -ml-1 mr-2 h-4 w-4 border-t-2 border-b-2 border-white rounded-full"></div>
                                Creando...
                              </>
                            ) : (
                              <>
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Crear servicio "{searchTerm.trim()}"
                              </>
                            )}
                          </button>
                        )}
                      </div>
                    )}
                  </div>
                </div>
                
                <div className="mt-5 sm:mt-6">
                  <button
                    type="button"
                    className="inline-flex w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:text-sm"
                    onClick={onClose}
                  >
                    Cancelar
                  </button>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition.Root>
  );
}
