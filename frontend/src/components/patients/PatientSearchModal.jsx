import { Fragment, useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { patientsAPI, dniAPI } from '../../services/api';
import toast from 'react-hot-toast';

export default function PatientSearchModal({ isOpen, onClose, onSelectPatient }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [isSearching, setIsSearching] = useState(false);

  // Reset search when modal opens
  useEffect(() => {
    if (isOpen) {
      setSearchTerm('');
      setSearchResults([]);
    }
  }, [isOpen]);

  // Handle search
  const handleSearch = async () => {
    if (!searchTerm) return;

    setIsSearching(true);
    try {
      // Try to search by DNI first if it's a number with 8 digits
      if (searchTerm.length === 8 && /^\d+$/.test(searchTerm)) {
        try {
          // First check if patient exists in database
          const patientResponse = await patientsAPI.searchByDNI(searchTerm);
          if (patientResponse.data.paciente) {
            setSearchResults([patientResponse.data.paciente]);
          } else {
            // If not found in database, try to get from DNI API
            const dniResponse = await dniAPI.consult(searchTerm);

            if (dniResponse.data.success) {
              // Create a temporary patient object with DNI data
              const personData = dniResponse.data.data;
              const tempPatient = {
                id: 'new',
                dni: searchTerm,
                nombres: personData.nombres,
                apellidos: personData.apellidos,
                fecha_nacimiento: personData.fecha_nacimiento,
                sexo: personData.sexo,
                isNew: true // Flag to indicate this is a new patient
              };

              setSearchResults([tempPatient]);
              toast.success('Datos obtenidos de RENIEC. Seleccione para crear paciente.');
            } else {
              toast.error('No se encontraron datos para este DNI');
              performRegularSearch();
            }
          }
        } catch (error) {
          console.error('Error searching by DNI:', error);
          // Fallback to regular search
          performRegularSearch();
        }
      } else {
        // Regular search by name or other criteria
        performRegularSearch();
      }
    } catch (error) {
      console.error('Error searching patients:', error);
      toast.error('Error al buscar pacientes');
    } finally {
      setIsSearching(false);
    }
  };

  // Helper function for regular search
  const performRegularSearch = async () => {
    try {
      const allPatientsResponse = await patientsAPI.getAll();
      const allPatients = allPatientsResponse.data.pacientes || [];

      const filteredPatients = allPatients.filter(patient =>
        patient.nombres?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        patient.apellidos?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        patient.dni?.includes(searchTerm)
      );

      setSearchResults(filteredPatients);

      if (filteredPatients.length === 0) {
        toast.info('No se encontraron pacientes con ese criterio de búsqueda');
      }
    } catch (error) {
      console.error('Error in regular search:', error);
      toast.error('Error al buscar pacientes');
    }
  };

  // Handle patient selection
  const handleSelectPatient = (patient) => {
    onSelectPatient(patient);
    onClose();
  };

  // Handle search on Enter key
  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleSearch();
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
                      Buscar Paciente
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
                    <div className="flex">
                      <div className="relative flex-grow">
                        <input
                          type="text"
                          className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                          placeholder="Buscar por DNI, nombre o apellido"
                          value={searchTerm}
                          onChange={(e) => setSearchTerm(e.target.value)}
                          onKeyDown={handleKeyDown}
                        />
                        <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                          {isSearching ? (
                            <div className="animate-spin h-5 w-5 border-t-2 border-b-2 border-primary-500 rounded-full"></div>
                          ) : (
                            <MagnifyingGlassIcon
                              className="h-5 w-5 text-gray-400 cursor-pointer"
                              onClick={handleSearch}
                            />
                          )}
                        </div>
                      </div>
                      <button
                        type="button"
                        onClick={handleSearch}
                        disabled={isSearching}
                        className="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        Buscar
                      </button>
                    </div>
                    <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                      Ingrese DNI para búsqueda exacta o nombre/apellido para búsqueda general
                    </p>
                  </div>

                  <div className="mt-4 max-h-80 overflow-y-auto">
                    {searchResults.length > 0 ? (
                      <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                        {searchResults.map((patient) => (
                          <li
                            key={patient.id}
                            className="px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                            onClick={() => handleSelectPatient(patient)}
                          >
                            <div className="flex justify-between">
                              <div>
                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                  {patient.nombres} {patient.apellidos}
                                  {patient.isNew && (
                                    <span className="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                      Nuevo
                                    </span>
                                  )}
                                </p>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                  DNI: {patient.dni}
                                </p>
                              </div>
                              <p className="text-sm text-gray-500 dark:text-gray-400">
                                {patient.historia_clinica ? `HC: ${patient.historia_clinica}` : ''}
                              </p>
                            </div>
                          </li>
                        ))}
                      </ul>
                    ) : (
                      <div className="text-center py-4">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          {isSearching ? 'Buscando...' : 'No hay resultados para mostrar'}
                        </p>
                      </div>
                    )}
                  </div>
                </div>

                <div className="mt-5 sm:mt-6 flex justify-between">
                  <button
                    type="button"
                    className="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-700 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    onClick={onClose}
                  >
                    Cancelar
                  </button>
                  <Link
                    to="/pacientes/nuevo"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    onClick={onClose}
                  >
                    Crear Nuevo Paciente
                  </Link>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition.Root>
  );
}
