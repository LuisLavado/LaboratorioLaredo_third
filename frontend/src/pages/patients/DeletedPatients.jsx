import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { patientsAPI } from '../../services/api';
import { ArrowLeftIcon, ArrowPathIcon, TrashIcon } from '@heroicons/react/24/outline';
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

export default function DeletedPatients() {
  const [searchTerm, setSearchTerm] = useState('');
  const queryClient = useQueryClient();
  const [isRestoring, setIsRestoring] = useState({});
  const [isDeleting, setIsDeleting] = useState({});

  // Fetch deleted patients
  const { data, isLoading, error, refetch } = useQuery(
    ['deleted-patients'],
    async () => {
      try {
        const res = await patientsAPI.getTrashed();
        console.log('Respuesta de pacientes eliminados:', res);
        return res.data;
      } catch (error) {
        console.error('Error al obtener pacientes eliminados:', error);
        // Devolver un objeto con un array vacío para evitar errores
        return { pacientes: [] };
      }
    },
    {
      refetchOnWindowFocus: true,
      staleTime: 0, // Considerar los datos obsoletos inmediatamente
      cacheTime: 0, // No almacenar en caché
      retry: 1, // Intentar una vez más si falla
    }
  );

  // Extract patients from the response
  const patients = data?.pacientes || [];

  // Restore patient mutation
  const restorePatientMutation = useMutation(
    (id) => patientsAPI.restore(id),
    {
      onSuccess: (response) => {
        console.log('Respuesta de restauración:', response);

        if (response.data?.success === false) {
          toast.error(response.data?.message || 'Error al restaurar paciente');
        } else {
          toast.success(response.data?.message || 'Paciente restaurado con éxito');
          queryClient.invalidateQueries(['patients']);
        }

        queryClient.invalidateQueries(['deleted-patients']);
        setIsRestoring({});
        refetch(); // Recargar la lista de pacientes eliminados
      },
      onError: (error) => {
        console.error('Error restoring patient:', error);
        toast.error(error.response?.data?.message || 'Error al restaurar paciente');
        setIsRestoring({});
      }
    }
  );

  // Force delete patient mutation
  const forceDeletePatientMutation = useMutation(
    (id) => patientsAPI.forceDelete(id),
    {
      onSuccess: (response) => {
        console.log('Respuesta de eliminación permanente:', response);

        if (response.data?.success === false) {
          toast.error(response.data?.message || 'Error al eliminar permanentemente el paciente');
        } else {
          toast.success(response.data?.message || 'Paciente eliminado permanentemente');
        }

        queryClient.invalidateQueries(['deleted-patients']);
        setIsDeleting({});
        refetch(); // Recargar la lista de pacientes eliminados
      },
      onError: (error) => {
        console.error('Error force deleting patient:', error);
        toast.error(error.response?.data?.message || 'Error al eliminar permanentemente el paciente');
        setIsDeleting({});
      }
    }
  );

  // Handle restore
  const handleRestore = (id) => {
    console.log('Restaurando paciente con ID:', id);
    setIsRestoring(prev => ({ ...prev, [id]: true }));
    restorePatientMutation.mutate(id);
  };

  // Handle force delete
  const handleForceDelete = (id) => {
    if (window.confirm('¿Está seguro de eliminar permanentemente este paciente? Esta acción no se puede deshacer.')) {
      console.log('Eliminando permanentemente paciente con ID:', id);
      setIsDeleting(prev => ({ ...prev, [id]: true }));
      forceDeletePatientMutation.mutate(id);
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
  }).slice(0, 50); // Limitar a 50 registros

  return (
    <div>
      <div className="mb-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <Link
              to="/pacientes"
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </Link>
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
              Pacientes Eliminados
            </h1>
          </div>
          <button
            onClick={() => {
              refetch();
              toast.success('Lista de pacientes eliminados actualizada');
            }}
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <ArrowPathIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Actualizar
          </button>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <div className="max-w-lg w-full lg:max-w-xs mb-4">
            <label htmlFor="search" className="sr-only">Buscar</label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                </svg>
              </div>
              <input
                id="search"
                name="search"
                className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 dark:focus:placeholder-gray-500 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm h-10"
                placeholder="Buscar por nombre, apellido o DNI"
                type="search"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>

          {isLoading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
            </div>
          ) : error ? (
            <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
              <div className="flex">
                <div className="ml-3">
                  <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
                    Error al cargar pacientes eliminados
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
                      Fecha de Eliminación
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
                      Acciones
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {filteredPatients.length > 0 ? (
                    filteredPatients.map((patient) => (
                      <tr key={patient.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                          {patient.dni}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {patient.nombres} {patient.apellidos}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {patient.deleted_at ? (
                            (() => {
                              try {
                                return new Date(patient.deleted_at).toLocaleString();
                              } catch (error) {
                                console.error('Error al formatear fecha de eliminación:', error);
                                return patient.deleted_at;
                              }
                            })()
                          ) : 'N/A'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {calculateAge(patient.fecha_nacimiento)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                          <div className="flex space-x-3">
                            <button
                              onClick={() => handleRestore(patient.id)}
                              disabled={isRestoring[patient.id]}
                              className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                              {isRestoring[patient.id] ? 'Restaurando...' : 'Restaurar'}
                            </button>
                            <button
                              onClick={() => handleForceDelete(patient.id)}
                              disabled={isDeleting[patient.id]}
                              className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                              {isDeleting[patient.id] ? 'Eliminando...' : 'Eliminar permanentemente'}
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="4" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        <div className="py-8">
                          <p className="text-center text-gray-500 dark:text-gray-400 mb-4">
                            No se encontraron pacientes eliminados
                          </p>
                          <p className="text-center text-gray-500 dark:text-gray-400">
                            Cuando elimine pacientes, aparecerán aquí y podrá restaurarlos si es necesario.
                          </p>
                        </div>
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
