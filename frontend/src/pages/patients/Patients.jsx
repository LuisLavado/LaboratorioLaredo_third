import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { patientsAPI, dniAPI } from '../../services/api';
import { PlusIcon, MagnifyingGlassIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import Pagination from '../../components/common/Pagination';
import toast from 'react-hot-toast';

// Función para calcular la edad a partir de la fecha de nacimiento
const calculateAge = (birthDate) => {
  if (!birthDate) return 'N/A';

  try {
    const today = new Date();
    let birth;

    // Verificar si la fecha ya está en formato YYYY-MM-DD
    const dateParts = birthDate.split('-');
    if (dateParts.length === 3) {
      // Crear fecha con partes individuales para evitar problemas de zona horaria
      birth = new Date(parseInt(dateParts[0]), parseInt(dateParts[1]) - 1, parseInt(dateParts[2]));
    } else {
      // Intentar parsear la fecha normalmente
      birth = new Date(birthDate);
    }

    // Verificar si es una fecha válida
    if (isNaN(birth.getTime())) return 'N/A';

    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    // Si aún no ha pasado el cumpleaños este año, restar un año
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }

    return age >= 0 ? age.toString() : 'N/A';
  } catch (error) {
    console.error('Error al calcular edad:', error);
    return 'N/A';
  }
};

export default function Patients() {
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [isSearching, setIsSearching] = useState(false);
  const [searchResult, setSearchResult] = useState(null);
  const itemsPerPage = 50;

  const { data, isLoading, error, refetch } = useQuery(
    ['patients'],
    () => patientsAPI.getAll().then(res => res.data),
    {
      refetchOnWindowFocus: true,
      staleTime: 0, // Considerar los datos obsoletos inmediatamente
      cacheTime: 0, // No almacenar en caché
    }
  );

  // Extract all patients from the response
  const allPatients = data?.pacientes || [];

  // Filter out deleted patients (double check in frontend)
  const patients = allPatients.filter(patient => !patient.deleted_at);

  // Count deleted patients
  const deletedCount = allPatients.length - patients.length;

  console.log('Total patients:', allPatients.length);
  console.log('Active patients:', patients.length);
  console.log('Deleted patients:', deletedCount);

  // Handle search by DNI
  const handleSearchByDNI = async (dni) => {
    if (!dni || !dni.trim() || dni.length !== 8 || !/^\d+$/.test(dni)) {
      return;
    }

    setIsSearching(true);
    try {
      // First check if patient exists in our database
      const localResult = await patientsAPI.searchByDNI(dni);
      if (localResult.data.paciente) {
        setSearchResult({
          exists: true,
          data: localResult.data.paciente
        });
        toast.success('Paciente encontrado en la base de datos');
        return;
      }
    } catch (error) {
      // If not found in our database, search in external API
      try {
        const externalResult = await dniAPI.consult(dni);
        if (externalResult.data.success) {
          setSearchResult({
            exists: false,
            data: {
              dni: dni,
              nombres: externalResult.data.data.nombres,
              apellidos: `${externalResult.data.data.apellidos}`,
              fecha_nacimiento: externalResult.data.data.fecha_nacimiento,
              sexo: externalResult.data.data.sexo
            }
          });
          toast.success('Paciente encontrado en RENIEC');
        } else {
          setSearchResult(null);
          toast.error('No se encontró información para este DNI');
        }
      } catch (externalError) {
        console.error('Error searching DNI:', externalError);
        setSearchResult(null);
        toast.error('Error al buscar el DNI');
      }
    } finally {
      setIsSearching(false);
    }
  };

  // Handle search input change
  const handleSearchChange = (e) => {
    const value = e.target.value;
    setSearchTerm(value);
    setSearchResult(null);

    // If input is 8 digits, try to search by DNI
    if (value.length === 8 && /^\d+$/.test(value)) {
      handleSearchByDNI(value);
    }
  };

  // Filter patients based on search term
  const filteredPatients = patients.filter(patient => {
    // Convertir a minúsculas solo si el valor existe
    const nombres = patient.nombres ? patient.nombres.toLowerCase() : '';
    const apellidos = patient.apellidos ? patient.apellidos.toLowerCase() : '';
    const dni = patient.dni || '';
    const searchTermLower = searchTerm.toLowerCase();

    return nombres.includes(searchTermLower) ||
           apellidos.includes(searchTermLower) ||
           dni.includes(searchTerm);
  });

  // Calcular el número total de páginas
  const totalPages = Math.ceil(filteredPatients.length / itemsPerPage);

  // Obtener los pacientes para la página actual
  const paginatedPatients = filteredPatients.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  return (
    <div>
      <div className="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Pacientes</h1>
          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Gestión de pacientes del laboratorio
            {deletedCount > 0 && (
              <span className="ml-2 text-red-500">
                (Hay {deletedCount} paciente{deletedCount !== 1 ? 's' : ''} eliminado{deletedCount !== 1 ? 's' : ''} que no se muestran aquí)
              </span>
            )}
          </p>
        </div>
        <div className="mt-4 sm:mt-0 flex space-x-3">
          <button
            onClick={() => {
              refetch();
              toast.success('Lista de pacientes actualizada');
            }}
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <ArrowPathIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Actualizar
          </button>
          <Link
            to="/pacientes/eliminados"
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            Ver Eliminados
          </Link>
          <Link
            to="/pacientes/nuevo"
            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Nuevo Paciente
          </Link>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <div className="max-w-lg w-full lg:max-w-xs mb-4">
            <label htmlFor="search" className="sr-only">Buscar</label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                id="search"
                name="search"
                className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 dark:focus:placeholder-gray-500 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                placeholder="Buscar por nombre, apellido o DNI (8 dígitos para búsqueda en RENIEC)"
                type="search"
                value={searchTerm}
                onChange={handleSearchChange}
              />
              {isSearching && (
                <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                  <div className="animate-spin rounded-full h-5 w-5 border-t-2 border-b-2 border-primary-500"></div>
                </div>
              )}
            </div>
          </div>

          {searchResult && (
            <div className="mb-6 p-4 border rounded-md border-gray-200 dark:border-gray-700">
              <h3 className="font-medium text-gray-900 dark:text-white">
                {searchResult.exists ? 'Paciente encontrado en la base de datos' : 'Paciente encontrado en RENIEC'}
              </h3>
              <div className="mt-2 grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400">DNI</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">{searchResult.data.dni}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400">Nombres</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">{searchResult.data.nombres}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400">Apellidos</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">{searchResult.data.apellidos}</p>
                </div>
              </div>
              <div className="mt-4">
                {searchResult.exists ? (
                  <Link
                    to={`/pacientes/${searchResult.data.id}`}
                    className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  >
                    Ver Paciente
                  </Link>
                ) : (
                  <Link
                    to="/pacientes/nuevo"
                    state={{ patientData: searchResult.data }}
                    className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  >
                    Registrar Paciente
                  </Link>
                )}
              </div>
            </div>
          )}

          {isLoading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
            </div>
          ) : error ? (
            <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
              <div className="flex">
                <div className="ml-3">
                  <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
                    Error al cargar pacientes
                  </h3>
                  <div className="mt-2 text-sm text-red-700 dark:text-red-300">
                    <p>
                      {error.message || 'Ha ocurrido un error. Por favor intente nuevamente.'}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      DNI
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Nombre
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Historia Clínica
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Edad
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      Sexo
                    </th>
                    <th scope="col" className="relative px-6 py-3">
                      <span className="sr-only">Acciones</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {paginatedPatients?.length > 0 ? (
                    paginatedPatients.map((patient) => (
                      <tr key={patient.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                          {patient.dni}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {patient.nombres} {patient.apellidos}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {patient.historia_clinica || 'N/A'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {calculateAge(patient.fecha_nacimiento)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {patient.sexo === 'masculino' ? 'Masculino' : patient.sexo === 'femenino' ? 'Femenino' : 'N/A'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <div className="flex justify-end space-x-3">
                            <Link
                              to={`/pacientes/${patient.id}`}
                              className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                            >
                              Ver
                            </Link>
                            <Link
                              to={`/pacientes/${patient.id}/editar`}
                              className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                              Editar
                            </Link>
                          </div>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="6" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No se encontraron pacientes
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>

              {/* Paginación */}
              <Pagination
                currentPage={currentPage}
                totalPages={totalPages}
                totalItems={filteredPatients.length}
                itemsPerPage={itemsPerPage}
                onPageChange={setCurrentPage}
              />
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
