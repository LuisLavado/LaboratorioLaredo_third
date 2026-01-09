import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { patientsAPI, dniAPI } from '../../services/api';
import { PlusIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import Pagination from '../../components/common/Pagination';
import toast from 'react-hot-toast';

// Función para calcular la edad a partir de la fecha de nacimiento
const calculateAge = (birthDate) => {
  if (!birthDate) return null;

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
    if (isNaN(birth.getTime())) return null;

    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    // Si aún no ha pasado el cumpleaños este año, restar un año
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }

    return age >= 0 ? age : null;
  } catch (error) {
    console.error('Error al calcular edad:', error);
    return null;
  }
};

export default function DoctorPatients() {
  const [searchTerm, setSearchTerm] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const [searchResult, setSearchResult] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 50;

  // Fetch patients
  const { data: patientsData, isLoading, error } = useQuery(
    ['patients'],
    () => patientsAPI.getAll().then(res => {
      // Log para depuración
      console.log('Datos de pacientes cargados:', res.data);
      return res.data;
    }),
    {
      refetchOnWindowFocus: false,
    }
  );

  const patients = patientsData?.pacientes || [];

  // Log para verificar los datos de cada paciente
  useEffect(() => {
    if (patients.length > 0) {
      console.log('Pacientes con datos de edad:', patients.map(p => ({
        id: p.id,
        nombre: `${p.nombres} ${p.apellidos}`,
        edad: p.edad,
        fecha_nacimiento: p.fecha_nacimiento
      })));
    }
  }, [patients]);

  // Filter patients based on search term
  const filteredPatients = patients.filter(patient => {
    const fullName = `${patient.nombres} ${patient.apellidos}`.toLowerCase();
    const dni = patient.dni?.toLowerCase() || '';
    const searchTermLower = searchTerm.toLowerCase();

    return fullName.includes(searchTermLower) || dni.includes(searchTermLower);
  });

  // Calcular el número total de páginas
  const totalPages = Math.ceil(filteredPatients.length / itemsPerPage);

  // Obtener los pacientes para la página actual
  const paginatedPatients = filteredPatients.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

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

  // Handle search input changes
  const handleSearchChange = (e) => {
    const value = e.target.value;
    setSearchTerm(value);

    // If input is 8 digits, try to search by DNI
    if (value.length === 8 && /^\d+$/.test(value)) {
      handleSearchByDNI(value);
    } else {
      // Clear search result if not a valid DNI
      setSearchResult(null);
    }
  };

  return (
    <div>
      <div className="sm:flex sm:items-center mb-6">
        <div className="sm:flex-auto">
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Pacientes</h1>
          <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Lista de todos los pacientes registrados en el sistema
          </p>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <Link
            to="/doctor/pacientes/nuevo"
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
          >
            <PlusIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Nuevo Paciente
          </Link>
        </div>
      </div>

      {/* Search by name/dni */}
      <div className="mb-6">
        <div className="relative rounded-md shadow-sm">
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
          </div>
          <input
            type="text"
            className="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white pl-10 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            placeholder="Buscar por nombre o DNI (8 dígitos para búsqueda en RENIEC)"
            value={searchTerm}
            onChange={handleSearchChange}
          />
          {isSearching && (
            <div className="absolute inset-y-0 right-0 flex items-center pr-3">
              <div className="animate-spin h-5 w-5 border-t-2 border-b-2 border-primary-500 rounded-full"></div>
            </div>
          )}
        </div>
        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
          Ingrese un DNI de 8 dígitos para buscar automáticamente en RENIEC
        </p>
      </div>

      {/* Search results */}
      <div className="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        <h2 className="text-lg font-medium text-gray-900 dark:text-white mb-3">Resultados de búsqueda</h2>

        {searchResult && (
          <div className="mt-4 p-4 border rounded-md border-gray-200 dark:border-gray-700">
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
                  to={`/doctor/pacientes/${searchResult.data.id}`}
                  className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  Ver Paciente
                </Link>
              ) : (
                <Link
                  to="/doctor/pacientes/nuevo"
                  state={{ patientData: searchResult.data }}
                  className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  Registrar Paciente
                </Link>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Patients list */}
      <div className="mt-8 flex flex-col">
        <div className="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table className="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-800">
                  <tr>
                    <th scope="col" className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">
                      Código
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      DNI
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Nombres
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Apellidos
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Edad
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Sexo
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                      Historia Clínica
                    </th>
                    <th scope="col" className="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span className="sr-only">Acciones</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                  {isLoading ? (
                    <tr>
                      <td colSpan="8" className="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                        Cargando pacientes...
                      </td>
                    </tr>
                  ) : error ? (
                    <tr>
                      <td colSpan="8" className="px-3 py-4 text-sm text-red-500 text-center">
                        Error al cargar pacientes
                      </td>
                    </tr>
                  ) : filteredPatients.length === 0 ? (
                    <tr>
                      <td colSpan="8" className="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                        No se encontraron pacientes
                      </td>
                    </tr>
                  ) : (
                    paginatedPatients.map((patient) => (
                      <tr key={patient.id}>
                        <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-6">
                          {patient.codigo}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {patient.dni}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {patient.nombres}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {patient.apellidos}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {(() => {
                            // Primero intentamos usar la edad proporcionada por el backend
                            if (patient.edad !== null && patient.edad !== undefined) {
                              return patient.edad;
                            }

                            // Si no hay edad pero hay fecha de nacimiento, calculamos la edad
                            if (patient.fecha_nacimiento) {
                              const calculatedAge = calculateAge(patient.fecha_nacimiento);
                              return calculatedAge !== null ? calculatedAge : 'N/A';
                            }

                            // Si no hay ni edad ni fecha de nacimiento
                            return 'N/A';
                          })()}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {patient.sexo === 'masculino' ? 'Masculino' : 'Femenino'}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                          {patient.historia_clinica || 'No registrada'}
                        </td>
                        <td className="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                          <Link
                            to={`/doctor/pacientes/${patient.id}`}
                            className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 mr-4"
                          >
                            Ver
                          </Link>
                          <Link
                            to={`/doctor/solicitudes/nueva?paciente=${patient.id}`}
                            className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                          >
                            Nueva Solicitud
                          </Link>
                        </td>
                      </tr>
                    ))
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
          </div>
        </div>
      </div>
    </div>
  );
}
