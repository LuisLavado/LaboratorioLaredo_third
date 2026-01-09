import React, { useState, useEffect } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { PlusIcon, TrashIcon, ArrowUpIcon, ArrowDownIcon } from '@heroicons/react/24/outline';

const ExamFormWithFields = ({ exam = null, onSubmit, onCancel, categories = [] }) => {
  const [examType, setExamType] = useState(exam?.tipo || 'simple');
  const [availableExams, setAvailableExams] = useState([]);

  const { register, control, handleSubmit, watch, setValue, formState: { errors } } = useForm({
    defaultValues: {
      codigo: exam?.codigo || '',
      nombre: exam?.nombre || '',
      categoria_id: exam?.categoria_id || '',
      tipo: exam?.tipo || 'simple',
      instrucciones_muestra: exam?.instrucciones_muestra || '',
      metodo_analisis: exam?.metodo_analisis || '',
      activo: exam?.activo ?? true,
      campos: exam?.campos || [],
      examenes_hijos: exam?.examenes_hijos?.map(e => e.id) || []
    }
  });

  const { fields: camposFields, append: appendCampo, remove: removeCampo, move: moveCampo } = useFieldArray({
    control,
    name: 'campos'
  });

  const watchedType = watch('tipo');

  useEffect(() => {
    setExamType(watchedType);
  }, [watchedType]);

  useEffect(() => {
    // Cargar exámenes disponibles para exámenes compuestos
    if (examType === 'compuesto') {
      fetchAvailableExams();
    }
  }, [examType]);

  const fetchAvailableExams = async () => {
    try {
      const response = await fetch('/api/examenes?tipo=simple&all=true');
      const data = await response.json();
      setAvailableExams(data.examenes?.data || data.examenes || []);
    } catch (error) {
      console.error('Error fetching exams:', error);
    }
  };

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

  const handleFormSubmit = (data) => {
    // Actualizar orden de campos
    data.campos = data.campos.map((campo, index) => ({
      ...campo,
      orden: index
    }));

    onSubmit(data);
  };

  const tiposCampo = [
    { value: 'text', label: 'Texto' },
    { value: 'number', label: 'Número' },
    { value: 'select', label: 'Selección' },
    { value: 'boolean', label: 'Sí/No' },
    { value: 'textarea', label: 'Texto largo' }
  ];

  return (
    <div className="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-lg">
      <h2 className="text-2xl font-bold mb-6">
        {exam ? 'Editar Examen' : 'Crear Nuevo Examen'}
      </h2>

      <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">
        {/* Información básica del examen */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Código *
            </label>
            <input
              type="text"
              {...register('codigo', { required: 'El código es requerido' })}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            {errors.codigo && (
              <p className="text-red-500 text-sm mt-1">{errors.codigo.message}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Nombre *
            </label>
            <input
              type="text"
              {...register('nombre', { required: 'El nombre es requerido' })}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            {errors.nombre && (
              <p className="text-red-500 text-sm mt-1">{errors.nombre.message}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Categoría *
            </label>
            <select
              {...register('categoria_id', { required: 'La categoría es requerida' })}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Seleccionar categoría</option>
              {categories.map(category => (
                <option key={category.id} value={category.id}>
                  {category.nombre}
                </option>
              ))}
            </select>
            {errors.categoria_id && (
              <p className="text-red-500 text-sm mt-1">{errors.categoria_id.message}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Tipo de Examen *
            </label>
            <select
              {...register('tipo', { required: 'El tipo es requerido' })}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="simple">Simple</option>
              <option value="compuesto">Compuesto</option>
            </select>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Instrucciones de Muestra
            </label>
            <textarea
              {...register('instrucciones_muestra')}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Ej: Ayuno de 12 horas, primera orina de la mañana..."
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Método de Análisis
            </label>
            <input
              type="text"
              {...register('metodo_analisis')}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Ej: Enzimático colorimétrico, Citometría de flujo..."
            />
          </div>
        </div>

        {/* Campos dinámicos para exámenes simples */}
        {examType === 'simple' && (
          <div className="border-t pt-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold">Campos del Examen</h3>
              <button
                type="button"
                onClick={addCampo}
                className="flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
              >
                <PlusIcon className="w-4 h-4 mr-1" />
                Agregar Campo
              </button>
            </div>

            <div className="space-y-4">
              {camposFields.map((campo, index) => (
                <CampoForm
                  key={campo.id}
                  index={index}
                  register={register}
                  errors={errors}
                  onRemove={() => removeCampo(index)}
                  onMoveUp={() => moveCampoUp(index)}
                  onMoveDown={() => moveCampoDown(index)}
                  canMoveUp={index > 0}
                  canMoveDown={index < camposFields.length - 1}
                  tiposCampo={tiposCampo}
                />
              ))}
            </div>
          </div>
        )}

        {/* Selección de exámenes hijos para exámenes compuestos */}
        {examType === 'compuesto' && (
          <div className="border-t pt-6">
            <h3 className="text-lg font-semibold mb-4">Exámenes Incluidos</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
              {availableExams.map(availableExam => (
                <label key={availableExam.id} className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    value={availableExam.id}
                    {...register('examenes_hijos')}
                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <span className="text-sm">{availableExam.nombre}</span>
                </label>
              ))}
            </div>
          </div>
        )}

        {/* Botones de acción */}
        <div className="flex justify-end space-x-3 pt-6 border-t">
          <button
            type="button"
            onClick={onCancel}
            className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            type="submit"
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            {exam ? 'Actualizar' : 'Crear'} Examen
          </button>
        </div>
      </form>
    </div>
  );
};

// Componente para cada campo individual
const CampoForm = ({ 
  index, 
  register, 
  errors, 
  onRemove, 
  onMoveUp, 
  onMoveDown, 
  canMoveUp, 
  canMoveDown, 
  tiposCampo 
}) => {
  return (
    <div className="border border-gray-200 rounded-lg p-4 bg-gray-50">
      <div className="flex justify-between items-start mb-3">
        <h4 className="font-medium text-gray-900">Campo {index + 1}</h4>
        <div className="flex space-x-1">
          <button
            type="button"
            onClick={onMoveUp}
            disabled={!canMoveUp}
            className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
          >
            <ArrowUpIcon className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={onMoveDown}
            disabled={!canMoveDown}
            className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
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
          <label className="block text-xs font-medium text-gray-700 mb-1">
            Nombre *
          </label>
          <input
            type="text"
            {...register(`campos.${index}.nombre`, { required: 'Nombre requerido' })}
            className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 mb-1">
            Tipo *
          </label>
          <select
            {...register(`campos.${index}.tipo`)}
            className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            {tiposCampo.map(tipo => (
              <option key={tipo.value} value={tipo.value}>
                {tipo.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 mb-1">
            Unidad
          </label>
          <input
            type="text"
            {...register(`campos.${index}.unidad`)}
            className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="mg/dL, %, etc."
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 mb-1">
            Valor de Referencia
          </label>
          <input
            type="text"
            {...register(`campos.${index}.valor_referencia`)}
            className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="10-50, >5, Normal"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 mb-1">
            Sección
          </label>
          <input
            type="text"
            {...register(`campos.${index}.seccion`)}
            className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="SERIE ROJA, QUÍMICA, etc."
          />
        </div>

        <div className="flex items-center">
          <label className="flex items-center text-xs">
            <input
              type="checkbox"
              {...register(`campos.${index}.requerido`)}
              className="mr-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            Requerido
          </label>
        </div>
      </div>
    </div>
  );
};

export default ExamFormWithFields;
