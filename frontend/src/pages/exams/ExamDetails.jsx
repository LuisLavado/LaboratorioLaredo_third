import { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { examsAPI } from '../../services/api';
import {
  ArrowLeftIcon,
  PencilIcon,
  TrashIcon,
  ExclamationTriangleIcon,
  XMarkIcon,
  CheckCircleIcon,
  BeakerIcon,
  DocumentTextIcon,
  ClipboardDocumentListIcon,
  TagIcon,
  CalendarIcon,
  InformationCircleIcon,
  CubeIcon,
  LinkIcon,
  EyeIcon,
  ChevronRightIcon
} from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export default function ExamDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [isDeleting, setIsDeleting] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);

  // Fetch exam data
  const { data, isLoading, error, refetch } = useQuery(
    ['exam', id],
    () => examsAPI.getById(id).then(res => res.data),
    {
      refetchOnWindowFocus: false,
    }
  );

  // Extract exam from the response
  const exam = data?.examen;

  // Delete o activate exam mutation según el estado actual
  const toggleExamStatusMutation = useMutation(
    () => exam.activo ? examsAPI.delete(id) : examsAPI.activate(id),
    {
      onSuccess: (data) => {
        toast.success(exam.activo ? 'Examen desactivado con éxito' : 'Examen activado con éxito');
        
        // Recargar completamente la aplicación para refrescar todos los datos
        setTimeout(() => {
          if (exam.activo) {
            // Si se desactivó, ir a la página de desactivados
            window.location.href = '/examenes/desactivados';
          } else {
            // Si se activó, ir a la página principal de exámenes
            window.location.href = '/examenes';
          }
        }, 800); // Retraso para permitir que se complete la mutación
      },
      onError: (error) => {
        console.error(`Error ${exam.activo ? 'desactivando' : 'activando'} examen:`, error);
        toast.error(error.response?.data?.message || `Error al ${exam.activo ? 'desactivar' : 'activar'} examen`);
        setIsDeleting(false);
        setShowDeleteModal(false);
      }
    }
  );

  // Handle delete/activate
  const handleDelete = () => {
    setShowDeleteModal(true);
  };

  // Confirm delete/activate
  const confirmDelete = () => {
    setIsDeleting(true);
    
    // Guardar el estado actual antes de la mutación
    const wasActive = exam.activo;
    
    toggleExamStatusMutation.mutate(null, {
      onSuccess: () => {
        toast.success(exam.activo ? 'Examen desactivado con éxito' : 'Examen activado con éxito');
        
        // Si estaba inactivo (ahora activado), simplemente recargar la página completa
        if (!wasActive) {
          // Solo recargar la página actual - la forma más directa
          window.location.href = '/examenes';
        } else {
          // Si se desactivó, recargar la página completa para la vista de desactivados
          window.location.href = '/examenes/desactivados';
        }
      }
    });
  };

  // Close modal
  const closeModal = () => {
    setShowDeleteModal(false);
  };

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
              Error al cargar examen
            </h3>
            <div className="mt-2 text-sm text-red-700 dark:text-red-300">
              <p>
                {error.message || 'Ha ocurrido un error. Por favor intente nuevamente.'}
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!exam) {
    return (
      <div className="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4">
        <div className="flex">
          <div className="ml-3">
            <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">
              Examen no encontrado
            </h3>
            <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
              <p>
                No se encontró el examen solicitado.
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
              to="/examenes"
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </Link>
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
              Detalles del Examen
            </h1>
          </div>
          <div className="flex space-x-3">
            <Link
              to={`/examenes/${id}/editar`}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <PencilIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
              Editar
            </Link>
            <button
              type="button"
              onClick={handleDelete}
              disabled={isDeleting}
              className={`inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white ${
                exam.activo 
                ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' 
                : 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
              } focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed`}
            >
              {exam.activo 
                ? <TrashIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                : <CheckCircleIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
              }
              {isDeleting 
                ? (exam.activo ? 'Desactivando...' : 'Activando...') 
                : (exam.activo ? 'Desactivar' : 'Activar')
              }
            </button>
          </div>
        </div>
      </div>

      {/* Estadísticas y Resumen */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {/* Tipo de Examen */}
        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <BeakerIcon className="h-6 w-6 text-gray-400" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Tipo de Examen
                  </dt>
                  <dd className="text-lg font-medium text-gray-900 dark:text-white">
                    {exam.tipo === 'compuesto' ? 'Compuesto' :
                     exam.tipo === 'hibrido' ? 'Híbrido' :
                     'Simple'}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        {/* Campos Totales */}
        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <ClipboardDocumentListIcon className="h-6 w-6 text-gray-400" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Campos Totales
                  </dt>
                  <dd className="text-lg font-medium text-gray-900 dark:text-white">
                    {(exam.campos?.length || 0) +
                     (exam.examenes_hijos?.reduce((acc, hijo) => acc + (hijo.campos?.length || 0), 0) || 0)}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        {/* Exámenes Hijos */}
        {(exam.tipo === 'compuesto' || exam.tipo === 'hibrido') && (
          <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <LinkIcon className="h-6 w-6 text-gray-400" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Exámenes Incluidos
                    </dt>
                    <dd className="text-lg font-medium text-gray-900 dark:text-white">
                      {exam.examenes_hijos?.length || 0}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Estado */}
        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <CheckCircleIcon className={`h-6 w-6 ${exam.activo ? 'text-green-400' : 'text-red-400'}`} />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Estado
                  </dt>
                  <dd className={`text-lg font-medium ${exam.activo ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>
                    {exam.activo ? 'Activo' : 'Inactivo'}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
          <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Información del Examen
          </h3>
          <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Detalles y propiedades del examen.
          </p>
        </div>
        <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-0">
          <dl className="sm:divide-y sm:divide-gray-200 sm:dark:divide-gray-700">
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Código
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {exam.codigo}
              </dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Nombre
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {exam.nombre}
              </dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Categoría
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {exam.categoria?.nombre || 'Sin categoría'}
              </dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Tipo
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                <span className={`px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
                  exam.tipo === 'compuesto'
                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'
                    : exam.tipo === 'hibrido'
                    ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                }`}>
                  {exam.tipo === 'compuesto' ? '🔗 Compuesto' :
                   exam.tipo === 'hibrido' ? '🧬 Híbrido' :
                   '🧪 Simple'}
                </span>
                {exam.es_perfil && (
                  <span className="ml-2 px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    📋 Perfil
                  </span>
                )}
              </dd>
            </div>
            {exam.instrucciones_muestra && (
              <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Instrucciones de Muestra
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {exam.instrucciones_muestra}
                </dd>
              </div>
            )}
            {exam.metodo_analisis && (
              <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Método de Análisis
                </dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                  {exam.metodo_analisis}
                </dd>
              </div>
            )}
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Estado
              </dt>
              <dd className="mt-1 text-sm sm:mt-0 sm:col-span-2">
                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                  exam.activo
                    ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                    : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
                }`}>
                  {exam.activo ? 'Activo' : 'Inactivo'}
                </span>
              </dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Fecha de creación
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {exam.created_at ? new Date(exam.created_at).toLocaleString() : 'No disponible'}
              </dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Última actualización
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {exam.updated_at ? new Date(exam.updated_at).toLocaleString() : 'No disponible'}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      {/* Campos del examen (para exámenes simples e híbridos) */}
      {(exam.tipo === 'simple' || exam.tipo === 'hibrido') && exam.campos && exam.campos.length > 0 && (
        <div className="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  {exam.tipo === 'hibrido' ? 'Campos Propios del Examen' : 'Campos del Examen'}
                </h3>
                <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                  {exam.tipo === 'hibrido'
                    ? 'Campos específicos de este examen (no incluye campos de exámenes hijos).'
                    : 'Campos que se registrarán en los resultados.'
                  }
                </p>
              </div>
              <div className="flex items-center space-x-2">
                <span className="bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 px-3 py-1 rounded-full text-sm font-medium">
                  {exam.campos.length} campo{exam.campos.length !== 1 ? 's' : ''}
                </span>
                <span className="bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100 px-3 py-1 rounded-full text-sm font-medium">
                  {Object.keys(
                    exam.campos_por_seccion ||
                    exam.campos.reduce((acc, campo) => {
                      const seccion = campo.seccion || 'General';
                      if (!acc[seccion]) acc[seccion] = [];
                      acc[seccion].push(campo);
                      return acc;
                    }, {})
                  ).length} sección{Object.keys(
                    exam.campos_por_seccion ||
                    exam.campos.reduce((acc, campo) => {
                      const seccion = campo.seccion || 'General';
                      if (!acc[seccion]) acc[seccion] = [];
                      acc[seccion].push(campo);
                      return acc;
                    }, {})
                  ).length !== 1 ? 'es' : ''}
                </span>
              </div>
            </div>
          </div>
          <div className="border-t border-gray-200 dark:border-gray-700">
            {Object.entries(
              exam.campos_por_seccion ||
              exam.campos.reduce((acc, campo) => {
                const seccion = campo.seccion || 'General';
                if (!acc[seccion]) acc[seccion] = [];
                acc[seccion].push(campo);
                return acc;
              }, {})
            ).map(([seccion, campos]) => (
              <div key={seccion} className="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                <div className="px-4 py-5 sm:px-6">
                  <div className="flex items-center justify-between mb-4">
                    <h4 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                      <TagIcon className="h-5 w-5 mr-2 text-gray-400" />
                      {seccion}
                    </h4>
                    <span className="bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100 px-2 py-1 rounded-full text-xs font-medium">
                      {campos.length} campo{campos.length !== 1 ? 's' : ''}
                    </span>
                  </div>
                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {campos.map((campo, index) => (
                      <div key={index} className="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="flex justify-between items-start mb-3">
                          <h5 className="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                            <DocumentTextIcon className="h-4 w-4 mr-1 text-gray-400" />
                            {campo.nombre}
                          </h5>
                          <div className="flex space-x-1">
                            <span className={`px-2 py-1 text-xs rounded-full font-medium ${
                              campo.requerido
                                ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
                                : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                            }`}>
                              {campo.requerido ? 'Requerido' : 'Opcional'}
                            </span>
                            <span className={`px-2 py-1 text-xs rounded-full font-medium ${
                              campo.tipo === 'number' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' :
                              campo.tipo === 'select' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' :
                              campo.tipo === 'textarea' ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' :
                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
                            }`}>
                              {campo.tipo}
                            </span>
                          </div>
                        </div>
                        <div className="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                          {campo.unidad && (
                            <div className="flex items-center">
                              <span className="font-medium text-gray-500 dark:text-gray-400 w-16">Unidad:</span>
                              <span className="bg-white dark:bg-gray-800 px-2 py-1 rounded border text-gray-900 dark:text-white">
                                {campo.unidad}
                              </span>
                            </div>
                          )}
                          {campo.valor_referencia && (
                            <div className="flex items-start">
                              <span className="font-medium text-gray-500 dark:text-gray-400 w-16 mt-1">Referencia:</span>
                              <span className="bg-white dark:bg-gray-800 px-2 py-1 rounded border text-gray-900 dark:text-white flex-1">
                                {campo.valor_referencia}
                              </span>
                            </div>
                          )}
                          {campo.opciones && Array.isArray(campo.opciones) && campo.opciones.length > 0 && (
                            <div className="flex items-start">
                              <span className="font-medium text-gray-500 dark:text-gray-400 w-16 mt-1">Opciones:</span>
                              <div className="flex-1">
                                <div className="flex flex-wrap gap-1">
                                  {campo.opciones.slice(0, 3).map((opcion, idx) => (
                                    <span key={idx} className="bg-white dark:bg-gray-800 px-2 py-1 rounded border text-xs text-gray-900 dark:text-white">
                                      {opcion}
                                    </span>
                                  ))}
                                  {campo.opciones.length > 3 && (
                                    <span className="text-gray-500 dark:text-gray-400 text-xs">
                                      +{campo.opciones.length - 3} más
                                    </span>
                                  )}
                                </div>
                              </div>
                            </div>
                          )}
                          {campo.descripcion && (
                            <div className="flex items-start">
                              <span className="font-medium text-gray-500 dark:text-gray-400 w-16 mt-1">Descripción:</span>
                              <span className="text-gray-600 dark:text-gray-300 flex-1">
                                {campo.descripcion}
                              </span>
                            </div>
                          )}
                          <div className="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                            <span className="text-gray-500 dark:text-gray-400">Orden: {campo.orden + 1}</span>
                            {campo.activo !== undefined && (
                              <span className={`px-2 py-1 text-xs rounded-full ${
                                campo.activo
                                  ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                  : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
                              }`}>
                                {campo.activo ? 'Activo' : 'Inactivo'}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Exámenes incluidos (para exámenes compuestos e híbridos) */}
      {(exam.tipo === 'compuesto' || exam.tipo === 'hibrido') && exam.examenes_hijos && exam.examenes_hijos.length > 0 && (
        <div className="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white flex items-center">
                  <LinkIcon className="h-5 w-5 mr-2 text-gray-400" />
                  Exámenes Incluidos
                </h3>
                <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                  {exam.tipo === 'hibrido'
                    ? 'Exámenes independientes que se incluyen además de los campos propios.'
                    : 'Exámenes que forman parte de este perfil.'
                  }
                </p>
              </div>
              <div className="flex items-center space-x-2">
                <span className="bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 px-3 py-1 rounded-full text-sm font-medium">
                  {exam.examenes_hijos.length} examen{exam.examenes_hijos.length !== 1 ? 'es' : ''}
                </span>
                <span className="bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100 px-3 py-1 rounded-full text-sm font-medium">
                  {exam.examenes_hijos.reduce((acc, hijo) => acc + (hijo.campos?.length || 0), 0)} campo{exam.examenes_hijos.reduce((acc, hijo) => acc + (hijo.campos?.length || 0), 0) !== 1 ? 's' : ''} total
                </span>
              </div>
            </div>
          </div>
          <div className="border-t border-gray-200 dark:border-gray-700">
            <div className="px-4 py-5 sm:px-6">
              <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {exam.examenes_hijos.map((examenHijo, index) => (
                  <div key={index} className="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-5 hover:shadow-lg transition-shadow">
                    <div className="flex justify-between items-start mb-4">
                      <div className="flex-1">
                        <h5 className="text-base font-semibold text-gray-900 dark:text-white flex items-center mb-1">
                          <BeakerIcon className="h-4 w-4 mr-2 text-gray-400" />
                          {examenHijo.nombre}
                        </h5>
                        <p className="text-xs text-gray-500 dark:text-gray-400 font-mono">
                          {examenHijo.codigo}
                        </p>
                      </div>
                      <div className="flex flex-col items-end space-y-1">
                        <span className="bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 px-2 py-1 text-xs rounded-full font-medium">
                          #{examenHijo.pivot?.orden || index + 1}
                        </span>
                        <span className={`px-2 py-1 text-xs rounded-full font-medium ${
                          examenHijo.tipo === 'compuesto'
                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'
                            : examenHijo.tipo === 'hibrido'
                            ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                        }`}>
                          {examenHijo.tipo}
                        </span>
                      </div>
                    </div>

                    <div className="space-y-3">
                      <div className="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-600 rounded">
                        <span className="text-xs font-medium text-gray-600 dark:text-gray-300">Categoría:</span>
                        <span className="text-xs text-gray-900 dark:text-white font-medium">
                          {examenHijo.categoria?.nombre || 'Sin categoría'}
                        </span>
                      </div>

                      {examenHijo.campos && (
                        <div className="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-600 rounded">
                          <span className="text-xs font-medium text-gray-600 dark:text-gray-300">Campos:</span>
                          <span className="text-xs text-gray-900 dark:text-white font-medium">
                            {examenHijo.campos.length} campo{examenHijo.campos.length !== 1 ? 's' : ''}
                          </span>
                        </div>
                      )}

                      {examenHijo.instrucciones_muestra && (
                        <div className="p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                          <span className="text-xs font-medium text-yellow-800 dark:text-yellow-200">Instrucciones:</span>
                          <p className="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                            {examenHijo.instrucciones_muestra.length > 50
                              ? `${examenHijo.instrucciones_muestra.substring(0, 50)}...`
                              : examenHijo.instrucciones_muestra
                            }
                          </p>
                        </div>
                      )}

                      {examenHijo.metodo_analisis && (
                        <div className="p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                          <span className="text-xs font-medium text-blue-800 dark:text-blue-200">Método:</span>
                          <p className="text-xs text-blue-700 dark:text-blue-300 mt-1">
                            {examenHijo.metodo_analisis}
                          </p>
                        </div>
                      )}
                    </div>

                    <div className="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
                      <Link
                        to={`/examenes/${examenHijo.id}`}
                        className="inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
                      >
                        <EyeIcon className="h-3 w-3 mr-1" />
                        Ver detalles
                        <ChevronRightIcon className="h-3 w-3 ml-1" />
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Información Técnica y Metadatos */}
      <div className="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
          <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white flex items-center">
            <InformationCircleIcon className="h-5 w-5 mr-2 text-gray-400" />
            Información Técnica
          </h3>
          <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Metadatos y configuración técnica del examen.
          </p>
        </div>
        <div className="border-t border-gray-200 dark:border-gray-700">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            {/* Configuración */}
            <div className="space-y-4">
              <h4 className="text-md font-semibold text-gray-900 dark:text-white flex items-center">
                <CubeIcon className="h-4 w-4 mr-2 text-gray-400" />
                Configuración
              </h4>
              <div className="space-y-3">
                <div className="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span className="text-sm font-medium text-gray-600 dark:text-gray-300">ID del Examen:</span>
                  <span className="text-sm text-gray-900 dark:text-white font-mono bg-white dark:bg-gray-800 px-2 py-1 rounded border">
                    #{exam.id}
                  </span>
                </div>
                <div className="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span className="text-sm font-medium text-gray-600 dark:text-gray-300">Es Perfil:</span>
                  <span className={`text-sm px-2 py-1 rounded-full font-medium ${
                    exam.es_perfil
                      ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                      : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                  }`}>
                    {exam.es_perfil ? 'Sí' : 'No'}
                  </span>
                </div>
                {exam.categoria && (
                  <div className="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span className="text-sm font-medium text-gray-600 dark:text-gray-300">ID Categoría:</span>
                    <span className="text-sm text-gray-900 dark:text-white font-mono bg-white dark:bg-gray-800 px-2 py-1 rounded border">
                      #{exam.categoria_id}
                    </span>
                  </div>
                )}
              </div>
            </div>

            {/* Fechas y Auditoría */}
            <div className="space-y-4">
              <h4 className="text-md font-semibold text-gray-900 dark:text-white flex items-center">
                <CalendarIcon className="h-4 w-4 mr-2 text-gray-400" />
                Auditoría
              </h4>
              <div className="space-y-3">
                <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span className="text-sm font-medium text-gray-600 dark:text-gray-300 block mb-1">Fecha de Creación:</span>
                  <span className="text-sm text-gray-900 dark:text-white">
                    {exam.created_at ? new Date(exam.created_at).toLocaleString('es-ES', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    }) : 'No disponible'}
                  </span>
                </div>
                <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span className="text-sm font-medium text-gray-600 dark:text-gray-300 block mb-1">Última Actualización:</span>
                  <span className="text-sm text-gray-900 dark:text-white">
                    {exam.updated_at ? new Date(exam.updated_at).toLocaleString('es-ES', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    }) : 'No disponible'}
                  </span>
                </div>
                {exam.created_at && exam.updated_at && (
                  <div className="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <span className="text-sm font-medium text-blue-800 dark:text-blue-200 block mb-1">Tiempo desde creación:</span>
                    <span className="text-sm text-blue-700 dark:text-blue-300">
                      {Math.floor((new Date() - new Date(exam.created_at)) / (1000 * 60 * 60 * 24))} días
                    </span>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Resumen de Estructura */}
          <div className="border-t border-gray-200 dark:border-gray-700 p-6">
            <h4 className="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
              <ClipboardDocumentListIcon className="h-4 w-4 mr-2 text-gray-400" />
              Resumen de Estructura
            </h4>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {exam.campos?.length || 0}
                </div>
                <div className="text-sm text-blue-800 dark:text-blue-200">
                  Campos Propios
                </div>
              </div>
              <div className="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                  {exam.examenes_hijos?.length || 0}
                </div>
                <div className="text-sm text-green-800 dark:text-green-200">
                  Exámenes Hijos
                </div>
              </div>
              <div className="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                  {Object.keys(
                    exam.campos_por_seccion ||
                    (exam.campos || []).reduce((acc, campo) => {
                      const seccion = campo.seccion || 'General';
                      if (!acc[seccion]) acc[seccion] = [];
                      acc[seccion].push(campo);
                      return acc;
                    }, {})
                  ).length}
                </div>
                <div className="text-sm text-purple-800 dark:text-purple-200">
                  Secciones
                </div>
              </div>
              <div className="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <div className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                  {(exam.campos?.length || 0) +
                   (exam.examenes_hijos?.reduce((acc, hijo) => acc + (hijo.campos?.length || 0), 0) || 0)}
                </div>
                <div className="text-sm text-yellow-800 dark:text-yellow-200">
                  Total Campos
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Modal de confirmación para desactivar/activar */}
      {showDeleteModal && (
        <div className="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
              <div className="sm:flex sm:items-start">
                <div className={`mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full ${
                  exam.activo 
                    ? 'bg-red-100 dark:bg-red-900' 
                    : 'bg-green-100 dark:bg-green-900'
                } sm:mx-0 sm:h-10 sm:w-10`}>
                  {exam.activo ? (
                    <ExclamationTriangleIcon className="h-6 w-6 text-red-600 dark:text-red-400" aria-hidden="true" />
                  ) : (
                    <CheckCircleIcon className="h-6 w-6 text-green-600 dark:text-green-400" aria-hidden="true" />
                  )}
                </div>
                <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                    ¿Está seguro de {exam.activo ? 'desactivar' : 'activar'} este examen?
                  </h3>
                  <div className="mt-2">
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      {exam.activo 
                        ? 'El examen no aparecerá en nuevas solicitudes de exámenes una vez desactivado.' 
                        : 'El examen volverá a estar disponible para nuevas solicitudes de exámenes.'}
                    </p>
                  </div>
                </div>
              </div>
              <div className="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  disabled={isDeleting}
                  className={`w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm ${
                    exam.activo 
                      ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' 
                      : 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
                  }`}
                  onClick={confirmDelete}
                >
                  <span className="flex items-center">
                    {!exam.activo && <CheckCircleIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />}
                    {exam.activo ? 'Desactivar' : 'Activar'}
                  </span>
                </button>
                <button
                  type="button"
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                  onClick={closeModal}
                >
                  Cancelar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
