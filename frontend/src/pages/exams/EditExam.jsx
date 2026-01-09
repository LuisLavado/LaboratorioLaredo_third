import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useForm, useFieldArray } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { examsAPI, categoriesAPI, examFieldsAPI } from '../../services/api';
import {
  ArrowLeftIcon,
  PlusIcon,
  TrashIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  BeakerIcon
} from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export default function EditExam() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [examType, setExamType] = useState('simple');
  const [secciones, setSecciones] = useState({});

  const { register, control, handleSubmit, reset, watch, setValue, formState: { errors } } = useForm({
    defaultValues: {
      codigo: '',
      nombre: '',
      categoria_id: '',
      tipo: 'simple',
      instrucciones_muestra: '',
      metodo_analisis: '',
      activo: true,
      campos: [],
      examenes_hijos: []
    }
  });

  const { fields: camposFields, append: appendCampo, remove: removeCampo, move: moveCampo, replace: replaceCampos } = useFieldArray({
    control,
    name: 'campos'
  });

  const watchedType = watch('tipo');

  // Fetch exam data
  const { data: examData, isLoading: examLoading, error: examError } = useQuery(
    ['exam', id],
    () => examsAPI.getById(id).then(res => res.data),
    {
      staleTime: 0, // Always refetch
      cacheTime: 0, // Don't cache
      refetchOnWindowFocus: false
    }
  );

  // Fetch categories
  const { data: categoriesResponse, isLoading: categoriesLoading } = useQuery(
    ['categories'],
    () => categoriesAPI.getAll().then(res => res.data)
  );

  // Fetch simple exams (excluding profiles) for composite and hybrid exams
  const { data: simpleExamsResponse } = useQuery(
    ['simple-exams-sin-perfiles'],
    () => examsAPI.getSimplesSinPerfiles().then(res => res.data),
    {
      enabled: examType === 'compuesto' || examType === 'hibrido'
    }
  );

  // Extract data from responses
  const categories = categoriesResponse?.categorias || [];
  const simpleExams = simpleExamsResponse?.examenes?.data || simpleExamsResponse?.examenes || [];

  // Update exam mutation
  const updateExamMutation = useMutation(
    (examData) => examsAPI.update(id, examData),
    {
      onSuccess: () => {
        toast.success('Examen actualizado con éxito');
        queryClient.invalidateQueries(['exam', id]);
        queryClient.invalidateQueries(['exams']);
        setIsSubmitting(false);
        navigate('/examenes');
      },
      onError: (error) => {
        console.error('Error updating exam:', error);
        toast.error(error.response?.data?.message || 'Error al actualizar examen');
        setIsSubmitting(false);
      }
    }
  );

  // Delete exam mutation
  const deleteExamMutation = useMutation(
    () => examsAPI.delete(id),
    {
      onSuccess: () => {
        toast.success('Examen eliminado con éxito');
        queryClient.invalidateQueries(['exams']);
        navigate('/examenes');
      },
      onError: (error) => {
        console.error('Error deleting exam:', error);
        toast.error(error.response?.data?.message || 'Error al eliminar examen');
      }
    }
  );

  // Effects
  useEffect(() => {
    setExamType(watchedType);
  }, [watchedType]);

  // Main effect to load exam data when available
  useEffect(() => {
    if (examData?.examen) {
      console.log('Loading exam data:', examData.examen);
      const exam = examData.examen;

      setExamType(exam.tipo || 'simple');

      // Prepare campos data - ensure it's an array with proper structure
      let camposData = [];
      if (exam.campos && Array.isArray(exam.campos)) {
        camposData = exam.campos.map(campo => ({
          id: campo.id || Date.now() + Math.random(), // Ensure unique ID for react-hook-form
          nombre: campo.nombre || '',
          tipo: campo.tipo || 'text',
          unidad: campo.unidad || '',
          valor_referencia: campo.valor_referencia || '',
          seccion: campo.seccion || '',
          requerido: Boolean(campo.requerido),
          orden: campo.orden || 0,
          descripcion: campo.descripcion || ''
        }));
      }

      // Prepare examenes_hijos data
      let examenesHijosData = [];
      if (exam.examenes_hijos && Array.isArray(exam.examenes_hijos)) {
        examenesHijosData = exam.examenes_hijos.map(e => e.id);
      }

      console.log('Setting form values:', {
        codigo: exam.codigo || '',
        nombre: exam.nombre || '',
        categoria_id: exam.categoria_id ? exam.categoria_id.toString() : '',
        tipo: exam.tipo || 'simple',
        instrucciones_muestra: exam.instrucciones_muestra || '',
        metodo_analisis: exam.metodo_analisis || '',
        activo: exam.activo === undefined ? true : Boolean(exam.activo),
        campos: camposData,
        examenes_hijos: examenesHijosData
      });

      // Set individual form values
      setValue('codigo', exam.codigo || '');
      setValue('nombre', exam.nombre || '');
      setValue('categoria_id', exam.categoria_id ? exam.categoria_id.toString() : '');
      setValue('tipo', exam.tipo || 'simple');
      setValue('es_perfil', Boolean(exam.es_perfil));
      setValue('instrucciones_muestra', exam.instrucciones_muestra || '');
      setValue('metodo_analisis', exam.metodo_analisis || '');
      setValue('activo', exam.activo === undefined ? true : Boolean(exam.activo));
      setValue('examenes_hijos', examenesHijosData);

      // Replace campos array separately to trigger useFieldArray update
      replaceCampos(camposData);
    }
  }, [examData, setValue, replaceCampos]);

  // Field management functions
  const addCampo = () => {
    appendCampo({
      nombre: '',
      tipo: 'text',
      unidad: '',
      valor_referencia: '',
      opciones: [],
      requerido: true,
      orden: camposFields.length,
      seccion: '',
      descripcion: ''
    });
  };

  const moveCampoUp = (index) => {
    if (index > 0) {
      moveCampo(index, index - 1);
    }
  };

  const moveCampoDown = (index) => {
    if (index < camposFields.length - 1) {
      moveCampo(index, index + 1);
    }
  };

  // Función para actualizar el título de una sección
  const updateSeccionTitulo = (seccionOriginal, nuevoTitulo) => {
    setSecciones(prev => ({
      ...prev,
      [seccionOriginal]: nuevoTitulo
    }));
  };

  // Función para obtener el título de una sección (editado o original)
  const getSeccionTitulo = (seccion) => {
    return secciones[seccion] || seccion || 'SIN SECCIÓN';
  };

  // Handle form submission
  const onSubmit = (data) => {
    // Update field order
    if (data.campos) {
      data.campos = data.campos.map((campo, index) => ({
        ...campo,
        orden: index
      }));
    }

    // Convert categoria_id to number if it's a string
    if (data.categoria_id && typeof data.categoria_id === 'string') {
      data.categoria_id = parseInt(data.categoria_id);
    }

    setIsSubmitting(true);
    updateExamMutation.mutate(data);
  };

  // Handle delete
  const handleDelete = () => {
    if (window.confirm('¿Está seguro de eliminar este examen? Esta acción no se puede deshacer.')) {
      deleteExamMutation.mutate();
    }
  };

  if (examLoading || categoriesLoading) {
    return (
      <div className="flex flex-col items-center justify-center py-8">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
        <p className="mt-4 text-sm text-gray-500 dark:text-gray-400">
          Cargando datos del examen...
        </p>
      </div>
    );
  }

  // Show loading state if we have exam data but form is not populated yet
  const isFormReady = examData?.examen && watch('codigo');

  if (examData?.examen && !isFormReady) {
    return (
      <div className="flex flex-col items-center justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary-500"></div>
        <p className="mt-4 text-sm text-gray-500 dark:text-gray-400">
          Preparando formulario...
        </p>
      </div>
    );
  }

  // Debug information
  console.log('Current form state:', {
    examType,
    camposFields: camposFields.length,
    examData: examData?.examen?.codigo,
    hasExamData: !!examData?.examen,
    examCamposLength: examData?.examen?.campos?.length || 0,
    formValues: {
      codigo: watch('codigo'),
      nombre: watch('nombre'),
      categoria_id: watch('categoria_id'),
      examenes_hijos: watch('examenes_hijos')
    }
  });

  if (examError) {
    return (
      <div className="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
        <div className="flex">
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
              Error al cargar examen
            </h3>
            <div className="mt-2 text-sm text-red-700 dark:text-red-300">
              <p>
                {examError.message || 'Ha ocurrido un error. Por favor intente nuevamente.'}
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
              to={`/examenes/${id}`}
              className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
            </Link>
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
              Editar Examen
            </h1>
          </div>
          <button
            type="button"
            onClick={handleDelete}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          >
            Eliminar Examen
          </button>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            {/* Información básica del examen */}
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-3">
                <label htmlFor="codigo" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Código *
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="codigo"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3"
                    {...register('codigo', {
                      required: 'El código es requerido',
                      maxLength: {
                        value: 20,
                        message: 'El código no puede tener más de 20 caracteres'
                      }
                    })}
                  />
                  {errors.codigo && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.codigo.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="nombre" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Nombre *
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="nombre"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3"
                    {...register('nombre', {
                      required: 'El nombre es requerido',
                      maxLength: {
                        value: 255,
                        message: 'El nombre no puede tener más de 255 caracteres'
                      }
                    })}
                  />
                  {errors.nombre && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.nombre.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="categoria_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Categoría *
                </label>
                <div className="mt-1">
                  <select
                    id="categoria_id"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3"
                    {...register('categoria_id', {
                      required: 'La categoría es requerida'
                    })}
                  >
                    <option value="">Seleccione una categoría</option>
                    {categories.map((category) => (
                      <option key={category.id} value={category.id}>
                        {category.nombre}
                      </option>
                    ))}
                  </select>
                  {errors.categoria_id && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.categoria_id.message}</p>
                  )}
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="tipo" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Tipo de Examen *
                </label>
                <div className="mt-1">
                  <select
                    id="tipo"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3"
                    {...register('tipo', {
                      required: 'El tipo es requerido'
                    })}
                  >
                    <option value="simple">Simple</option>
                    <option value="compuesto">Compuesto</option>
                    <option value="hibrido">Híbrido (Campos + Exámenes)</option>
                  </select>
                  {errors.tipo && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.tipo.message}</p>
                  )}
                </div>

                {/* Checkbox para marcar como perfil */}
                {(examType === 'compuesto' || examType === 'hibrido') && (
                  <div className="flex items-center mt-3">
                    <input
                      id="es_perfil"
                      type="checkbox"
                      className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-700 rounded"
                      {...register('es_perfil')}
                    />
                    <label htmlFor="es_perfil" className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                      Es un perfil (no puede incluir otros perfiles)
                    </label>
                  </div>
                )}
              </div>

              <div className="sm:col-span-6">
                <div className="flex items-center">
                  <input
                    id="activo"
                    type="checkbox"
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-700 rounded"
                    {...register('activo')}
                  />
                  <label htmlFor="activo" className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Activo
                  </label>
                </div>
              </div>
            </div>

            {/* Información adicional */}
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
              <div>
                <label htmlFor="instrucciones_muestra" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Instrucciones de Muestra
                </label>
                <div className="mt-1">
                  <textarea
                    id="instrucciones_muestra"
                    rows={3}
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white bg-white text-gray-900 rounded-md px-3 py-2"
                    placeholder="Ej: Ayuno de 12 horas, primera orina de la mañana..."
                    {...register('instrucciones_muestra')}
                  />
                </div>
              </div>

              <div>
                <label htmlFor="metodo_analisis" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Método de Análisis
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    id="metodo_analisis"
                    className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white bg-white text-gray-900 rounded-md h-10 px-3"
                    placeholder="Ej: Enzimático colorimétrico, Citometría de flujo..."
                    {...register('metodo_analisis')}
                  />
                </div>
              </div>
            </div>

            {/* Campos dinámicos para exámenes simples e híbridos */}
            {(examType === 'simple' || examType === 'hibrido') && (
              <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div className="flex justify-between items-center mb-4">
                  <div>
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                      Campos del Examen
                    </h3>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      {camposFields.length} campo(s) configurado(s)
                      {examData?.examen?.campos && (
                        <span className="ml-2 text-xs">
                          (Original: {examData.examen.campos.length})
                        </span>
                      )}
                      <br />
                      <span className="text-xs">
                        Los campos se organizan por sección. Puedes editar los títulos de las secciones.
                      </span>
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={addCampo}
                    className="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  >
                    <PlusIcon className="w-4 h-4 mr-1" />
                    Agregar Campo
                  </button>
                </div>

                {camposFields.length === 0 ? (
                  <div className="text-center py-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                    <BeakerIcon className="mx-auto h-12 w-12 text-gray-400" />
                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                      No hay campos configurados
                    </h3>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Agrega campos para definir qué datos se registrarán en los resultados.
                    </p>
                    <div className="mt-6">
                      <button
                        type="button"
                        onClick={addCampo}
                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                      >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Agregar Primer Campo
                      </button>
                    </div>
                  </div>
                ) : (
                  <CamposPorSeccion
                    camposFields={camposFields}
                    register={register}
                    errors={errors}
                    onRemove={removeCampo}
                    onMoveUp={moveCampoUp}
                    onMoveDown={moveCampoDown}
                    secciones={secciones}
                    onUpdateSeccionTitulo={updateSeccionTitulo}
                    getSeccionTitulo={getSeccionTitulo}
                  />
                )}
              </div>
            )}

            {/* Selección de exámenes hijos para exámenes compuestos e híbridos */}
            {(examType === 'compuesto' || examType === 'hibrido') && (
              <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div className="flex justify-between items-center mb-4">
                  <div>
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                      Exámenes Incluidos
                    </h3>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      {watch('examenes_hijos')?.length || 0} examen(es) seleccionado(s)
                      {examData?.examen?.examenes_hijos && (
                        <span className="ml-2 text-xs">
                          (Original: {examData.examen.examenes_hijos.length})
                        </span>
                      )}
                    </p>
                  </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                  {simpleExams.map(availableExam => {
                    const isSelected = watch('examenes_hijos')?.includes(availableExam.id) || false;
                    return (
                      <label key={availableExam.id} className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          value={availableExam.id}
                          checked={isSelected}
                          onChange={(e) => {
                            const currentValues = watch('examenes_hijos') || [];
                            if (e.target.checked) {
                              setValue('examenes_hijos', [...currentValues, availableExam.id]);
                            } else {
                              setValue('examenes_hijos', currentValues.filter(id => id !== availableExam.id));
                            }
                          }}
                          className="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                        />
                        <span className="text-sm text-gray-900 dark:text-gray-300">
                          {availableExam.nombre}
                        </span>
                      </label>
                    );
                  })}
                </div>
              </div>
            )}

            {/* Botones de acción */}
            <div className="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
              <Link
                to={`/examenes/${id}`}
                className="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-700 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                disabled={isSubmitting}
                className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? 'Guardando...' : 'Guardar Cambios'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}

// Componente para formulario de campo
const CampoForm = ({
  index,
  register,
  errors,
  onRemove,
  onMoveUp,
  onMoveDown,
  canMoveUp,
  canMoveDown
}) => {
  const tiposCampo = [
    { value: 'text', label: 'Texto' },
    { value: 'number', label: 'Número' },
    { value: 'select', label: 'Selección' },
    { value: 'boolean', label: 'Sí/No' },
    { value: 'textarea', label: 'Texto largo' }
  ];

  return (
    <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
      <div className="flex justify-between items-start mb-3">
        <h4 className="font-medium text-gray-900 dark:text-white">Campo {index + 1}</h4>
        <div className="flex space-x-1">
          <button
            type="button"
            onClick={onMoveUp}
            disabled={!canMoveUp}
            className="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-50"
          >
            <ArrowUpIcon className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={onMoveDown}
            disabled={!canMoveDown}
            className="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-50"
          >
            <ArrowDownIcon className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={onRemove}
            className="p-1 text-red-400 hover:text-red-600"
          >
            <TrashIcon className="w-4 h-4" />
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
            Nombre *
          </label>
          <input
            type="text"
            {...register(`campos.${index}.nombre`, { required: 'Nombre requerido' })}
            className="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="Ej: Hemoglobina, Glucosa..."
          />
          {errors.campos?.[index]?.nombre && (
            <p className="text-xs text-red-500 mt-1">{errors.campos[index].nombre.message}</p>
          )}
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
            Tipo *
          </label>
          <select
            {...register(`campos.${index}.tipo`)}
            className="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:outline-none focus:ring-1 focus:ring-primary-500"
          >
            {tiposCampo.map(tipo => (
              <option key={tipo.value} value={tipo.value}>
                {tipo.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
            Unidad
          </label>
          <input
            type="text"
            {...register(`campos.${index}.unidad`)}
            className="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="mg/dL, %, etc."
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
            Valor de Referencia
          </label>
          <input
            type="text"
            {...register(`campos.${index}.valor_referencia`)}
            className="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="10-50, >5, Normal"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
            Sección
          </label>
          <input
            type="text"
            {...register(`campos.${index}.seccion`)}
            className="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="SERIE ROJA, QUÍMICA, etc."
          />
        </div>

        <div className="flex items-center">
          <label className="flex items-center text-xs">
            <input
              type="checkbox"
              {...register(`campos.${index}.requerido`)}
              className="mr-1 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
            />
            Requerido
          </label>
        </div>
      </div>
    </div>
  );
};

// Componente para mostrar campos agrupados por sección con títulos editables
const CamposPorSeccion = ({
  camposFields,
  register,
  errors,
  onRemove,
  onMoveUp,
  onMoveDown,
  secciones,
  onUpdateSeccionTitulo,
  getSeccionTitulo
}) => {
  // Agrupar campos por sección
  const camposPorSeccion = camposFields.reduce((acc, campo, index) => {
    const seccion = campo.seccion || 'SIN_SECCION';
    if (!acc[seccion]) {
      acc[seccion] = [];
    }
    acc[seccion].push({ ...campo, originalIndex: index });
    return acc;
  }, {});

  return (
    <div className="space-y-6">
      {Object.entries(camposPorSeccion).map(([seccion, campos]) => (
        <div key={seccion} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
          {/* Título de sección editable */}
          <div className="mb-4">
            <div className="flex items-center space-x-2">
              <input
                type="text"
                value={getSeccionTitulo(seccion)}
                onChange={(e) => onUpdateSeccionTitulo(seccion, e.target.value)}
                className="text-lg font-semibold bg-transparent border-b-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:outline-none focus:border-primary-500 px-2 py-1"
                placeholder="Nombre de la sección"
              />
              <span className="text-sm text-gray-500 dark:text-gray-400">
                ({campos.length} campo{campos.length !== 1 ? 's' : ''})
              </span>
            </div>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Haz clic en el título para editarlo
            </p>
          </div>

          {/* Campos de la sección */}
          <div className="space-y-3">
            {campos.map((campo) => (
              <CampoForm
                key={campo.id}
                index={campo.originalIndex}
                register={register}
                errors={errors}
                onRemove={() => onRemove(campo.originalIndex)}
                onMoveUp={() => onMoveUp(campo.originalIndex)}
                onMoveDown={() => onMoveDown(campo.originalIndex)}
                canMoveUp={campo.originalIndex > 0}
                canMoveDown={campo.originalIndex < camposFields.length - 1}
              />
            ))}
          </div>
        </div>
      ))}
    </div>
  );
};
