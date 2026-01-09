import React, { useState, useEffect, useCallback } from 'react';
import { useForm } from 'react-hook-form';
import { ExclamationTriangleIcon, CheckCircleIcon, CloudArrowUpIcon } from '@heroicons/react/24/outline';

const DynamicResultForm = ({ detalleSolicitudId, onSubmit, onCancel }) => {
  const [camposPorSeccion, setCamposPorSeccion] = useState({});
  const [loading, setLoading] = useState(true);
  const [validaciones, setValidaciones] = useState({});
  const [examen, setExamen] = useState(null);
  const [guardandoCampos, setGuardandoCampos] = useState({}); // Para mostrar estado de guardado por campo
  const [camposGuardados, setCamposGuardados] = useState({}); // Para trackear campos guardados

  const { register, handleSubmit, watch, setValue, formState: { errors } } = useForm();

  useEffect(() => {
    if (detalleSolicitudId) {
      fetchCamposExamen();
      cargarValoresExistentes();
    }
  }, [detalleSolicitudId]);

  const fetchCamposExamen = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/valores-resultado?detalle_solicitud_id=${detalleSolicitudId}`);
      const data = await response.json();
      
      if (data.detalle) {
        setExamen(data.detalle.examen);
        
        // Organizar campos por sección
        const campos = data.detalle.examen.todos_los_campos || data.detalle.examen.campos || [];
        const agrupados = campos.reduce((acc, campo) => {
          const seccion = campo.seccion || 'General';
          if (!acc[seccion]) {
            acc[seccion] = [];
          }
          acc[seccion].push(campo);
          return acc;
        }, {});
        
        setCamposPorSeccion(agrupados);
        
        // Cargar valores existentes
        if (data.detalle.valores_resultado) {
          data.detalle.valores_resultado.forEach(valor => {
            setValue(`campo_${valor.campo_examen_id}`, valor.valor);
            setValue(`observaciones_${valor.campo_examen_id}`, valor.observaciones);
          });
        }
      }
    } catch (error) {
      console.error('Error fetching exam fields:', error);
    } finally {
      setLoading(false);
    }
  };

  // Cargar valores existentes del backend
  const cargarValoresExistentes = async () => {
    try {
      const response = await fetch(`/api/valores-resultado/detalle/${detalleSolicitudId}`);
      if (response.ok) {
        const valoresExistentes = await response.json();

        // Cargar valores en el formulario
        valoresExistentes.forEach(valor => {
          setValue(`campo_${valor.campo_examen_id}`, valor.valor);
          if (valor.observaciones) {
            setValue(`observaciones_${valor.campo_examen_id}`, valor.observaciones);
          }
          // Marcar como guardado
          setCamposGuardados(prev => ({
            ...prev,
            [valor.campo_examen_id]: true
          }));
        });
      }
    } catch (error) {
      console.error('Error cargando valores existentes:', error);
    }
  };

  // Validar un campo específico (solo cuando sale del campo)
  const validateField = async (campoId, valor) => {
    if (!valor) return;

    try {
      const response = await fetch('/api/valores-resultado/validar', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          valores: [{ campo_examen_id: campoId, valor }]
        })
      });

      const validacionesData = await response.json();
      if (validacionesData.length > 0) {
        setValidaciones(prev => ({
          ...prev,
          [campoId]: validacionesData[0]
        }));
      }
    } catch (error) {
      console.error('Error validating field:', error);
    }
  };

  // Guardar un campo individual
  const guardarCampo = useCallback(async (campoId, valor, observaciones = null) => {
    if (!valor) return;

    setGuardandoCampos(prev => ({ ...prev, [campoId]: true }));

    try {
      const response = await fetch('/api/valores-resultado/campo', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          detalle_solicitud_id: detalleSolicitudId,
          campo_examen_id: parseInt(campoId),
          valor,
          observaciones
        })
      });

      if (response.ok) {
        setCamposGuardados(prev => ({ ...prev, [campoId]: true }));
        console.log(`Campo ${campoId} guardado exitosamente`);
      } else {
        console.error('Error guardando campo:', await response.text());
      }
    } catch (error) {
      console.error('Error guardando campo:', error);
    } finally {
      setGuardandoCampos(prev => ({ ...prev, [campoId]: false }));
    }
  }, [detalleSolicitudId]);

  const handleFormSubmit = (data) => {
    const valores = [];
    
    Object.keys(data).forEach(key => {
      if (key.startsWith('campo_')) {
        const campoId = key.replace('campo_', '');
        const valor = data[key];
        const observaciones = data[`observaciones_${campoId}`];
        
        if (valor) {
          valores.push({
            campo_examen_id: parseInt(campoId),
            valor,
            observaciones
          });
        }
      }
    });

    onSubmit({
      detalle_solicitud_id: detalleSolicitudId,
      valores
    });
  };

  const renderCampo = (campo) => {
    const validacion = validaciones[campo.id];
    const isOutOfRange = validacion && !validacion.en_rango;
    const isGuardando = guardandoCampos[campo.id];
    const isGuardado = camposGuardados[campo.id];

    const handleBlur = (e) => {
      const valor = e.target.value;
      if (valor) {
        // Validar cuando sale del campo
        validateField(campo.id, valor);
        // Guardar automáticamente
        const observaciones = document.querySelector(`[name="observaciones_${campo.id}"]`)?.value;
        guardarCampo(campo.id, valor, observaciones);
      }
    };

    switch (campo.tipo) {
      case 'number':
        return (
          <div className="relative">
            <input
              type="number"
              step="any"
              {...register(`campo_${campo.id}`, {
                required: campo.requerido ? 'Este campo es requerido' : false
              })}
              onBlur={handleBlur}
              className={`w-full px-3 py-2 pr-10 border rounded-md focus:outline-none focus:ring-2 ${
                isOutOfRange
                  ? 'border-red-300 focus:ring-red-500'
                  : isGuardado
                  ? 'border-green-300 focus:ring-green-500'
                  : 'border-gray-300 focus:ring-blue-500'
              }`}
              placeholder={`Ingrese valor${campo.unidad ? ` (${campo.unidad})` : ''}`}
            />
            <div className="absolute right-2 top-2 flex items-center space-x-1">
              {isGuardando && (
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              )}
              {isGuardado && !isGuardando && (
                <CloudArrowUpIcon className="w-4 h-4 text-green-500" title="Guardado" />
              )}
              {isOutOfRange && (
                <ExclamationTriangleIcon className="w-4 h-4 text-red-500" title="Fuera de rango" />
              )}
              {validacion && validacion.en_rango && (
                <CheckCircleIcon className="w-4 h-4 text-green-500" title="En rango normal" />
              )}
            </div>
          </div>
        );

      case 'select':
        return (
          <div className="relative">
            <select
              {...register(`campo_${campo.id}`, {
                required: campo.requerido ? 'Este campo es requerido' : false
              })}
              onBlur={handleBlur}
              className={`w-full px-3 py-2 pr-10 border rounded-md focus:outline-none focus:ring-2 ${
                isGuardado
                  ? 'border-green-300 focus:ring-green-500'
                  : 'border-gray-300 focus:ring-blue-500'
              }`}
            >
              <option value="">Seleccionar...</option>
              {campo.opciones?.map((opcion, index) => (
                <option key={index} value={opcion}>
                  {opcion}
                </option>
              ))}
            </select>
            <div className="absolute right-2 top-2 flex items-center space-x-1">
              {isGuardando && (
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              )}
              {isGuardado && !isGuardando && (
                <CloudArrowUpIcon className="w-4 h-4 text-green-500" title="Guardado" />
              )}
            </div>
          </div>
        );

      case 'boolean':
        const handleBooleanChange = (e) => {
          const valor = e.target.value;
          if (valor) {
            // Validar cuando se selecciona una opción
            validateField(campo.id, valor);
            // Guardar automáticamente
            const observaciones = document.querySelector(`[name="observaciones_${campo.id}"]`)?.value;
            guardarCampo(campo.id, valor, observaciones);
          }
        };

        return (
          <div className={`flex space-x-4 p-3 border rounded-md ${
            isGuardado
              ? 'border-green-300 bg-green-50'
              : 'border-gray-300'
          }`}>
            <label className="flex items-center">
              <input
                type="radio"
                value="true"
                {...register(`campo_${campo.id}`, {
                  required: campo.requerido ? 'Este campo es requerido' : false
                })}
                onChange={handleBooleanChange}
                className="mr-2"
              />
              Sí
            </label>
            <label className="flex items-center">
              <input
                type="radio"
                value="false"
                {...register(`campo_${campo.id}`, {
                  required: campo.requerido ? 'Este campo es requerido' : false
                })}
                onChange={handleBooleanChange}
                className="mr-2"
              />
              No
            </label>
            <div className="ml-auto flex items-center space-x-1">
              {isGuardando && (
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              )}
              {isGuardado && !isGuardando && (
                <CloudArrowUpIcon className="w-4 h-4 text-green-500" title="Guardado" />
              )}
            </div>
          </div>
        );

      case 'textarea':
        return (
          <div className="relative">
            <textarea
              {...register(`campo_${campo.id}`, {
                required: campo.requerido ? 'Este campo es requerido' : false
              })}
              onBlur={handleBlur}
              rows={3}
              className={`w-full px-3 py-2 pr-10 border rounded-md focus:outline-none focus:ring-2 ${
                isGuardado
                  ? 'border-green-300 focus:ring-green-500'
                  : 'border-gray-300 focus:ring-blue-500'
              }`}
              placeholder="Ingrese observaciones..."
            />
            <div className="absolute right-2 top-2 flex items-center space-x-1">
              {isGuardando && (
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              )}
              {isGuardado && !isGuardando && (
                <CloudArrowUpIcon className="w-4 h-4 text-green-500" title="Guardado" />
              )}
            </div>
          </div>
        );

      default:
        return (
          <div className="relative">
            <input
              type="text"
              {...register(`campo_${campo.id}`, {
                required: campo.requerido ? 'Este campo es requerido' : false
              })}
              onBlur={handleBlur}
              className={`w-full px-3 py-2 pr-10 border rounded-md focus:outline-none focus:ring-2 ${
                isGuardado
                  ? 'border-green-300 focus:ring-green-500'
                  : 'border-gray-300 focus:ring-blue-500'
              }`}
              placeholder="Ingrese valor"
            />
            <div className="absolute right-2 top-2 flex items-center space-x-1">
              {isGuardando && (
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              )}
              {isGuardado && !isGuardando && (
                <CloudArrowUpIcon className="w-4 h-4 text-green-500" title="Guardado" />
              )}
            </div>
          </div>
        );
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-lg">
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900">
          Registrar Resultados: {examen?.nombre}
        </h2>
        {examen?.instrucciones_muestra && (
          <p className="text-sm text-gray-600 mt-2">
            <strong>Instrucciones de muestra:</strong> {examen.instrucciones_muestra}
          </p>
        )}
        {examen?.metodo_analisis && (
          <p className="text-sm text-gray-600">
            <strong>Método:</strong> {examen.metodo_analisis}
          </p>
        )}

        {/* Información sobre auto-guardado */}
        <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
          <div className="flex items-center">
            <CloudArrowUpIcon className="w-5 h-5 text-blue-600 mr-2" />
            <p className="text-sm text-blue-800">
              <strong>Auto-guardado activado:</strong> Los valores se guardan automáticamente al salir de cada campo.
              Los campos guardados se marcan con <CloudArrowUpIcon className="w-4 h-4 inline text-green-500" />.
            </p>
          </div>
        </div>
      </div>

      <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-8">
        {Object.entries(camposPorSeccion).map(([seccion, campos]) => (
          <div key={seccion} className="border border-gray-200 rounded-lg p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
              {seccion}
            </h3>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {campos.map(campo => {
                const validacion = validaciones[campo.id];
                const isOutOfRange = validacion && !validacion.en_rango;
                
                return (
                  <div key={campo.id} className="space-y-2">
                    <label className="block text-sm font-medium text-gray-700">
                      {campo.nombre}
                      {campo.requerido && <span className="text-red-500 ml-1">*</span>}
                      {campo.unidad && (
                        <span className="text-gray-500 ml-1">({campo.unidad})</span>
                      )}
                    </label>
                    
                    {renderCampo(campo)}
                    
                    {campo.valor_referencia && (
                      <p className="text-xs text-gray-500">
                        Valor de referencia: {campo.valor_referencia}
                      </p>
                    )}
                    
                    {isOutOfRange && (
                      <p className="text-xs text-red-600 flex items-center">
                        <ExclamationTriangleIcon className="w-4 h-4 mr-1" />
                        Valor fuera del rango de referencia
                      </p>
                    )}
                    
                    {errors[`campo_${campo.id}`] && (
                      <p className="text-red-500 text-xs">
                        {errors[`campo_${campo.id}`].message}
                      </p>
                    )}
                    
                    {/* Campo de observaciones adicionales */}
                    <div className="mt-2">
                      <label className="block text-xs text-gray-600 mb-1">
                        Observaciones
                      </label>
                      <input
                        type="text"
                        {...register(`observaciones_${campo.id}`)}
                        className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="Observaciones adicionales..."
                      />
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        ))}

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
            Guardar Resultados
          </button>
        </div>
      </form>
    </div>
  );
};

export default DynamicResultForm;
