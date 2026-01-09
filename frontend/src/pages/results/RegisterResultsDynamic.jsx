import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { 
  requestDetailsAPI, 
  resultValuesAPI, 
  examsAPI 
} from '../../services/api';
import {
  ArrowLeftIcon,
  CheckCircleIcon,
  PlayIcon,
  PauseIcon,
  ExclamationTriangleIcon,
  BeakerIcon,
  CloudArrowUpIcon
} from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';
import { format } from 'date-fns';
import ValueDebugger from '../../components/debug/ValueDebugger';

export default function RegisterResultsDynamic() {
  const { id } = useParams(); // solicitud_id
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [activeExam, setActiveExam] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [examFields, setExamFields] = useState({});
  const [validations, setValidations] = useState({});

  const { register, handleSubmit, reset, watch, setValue, formState: { errors } } = useForm();

  // Fetch request details
  const { data: detailsResponse, isLoading: detailsLoading } = useQuery(
    ['requestDetails', id],
    () => requestDetailsAPI.getByRequest(id).then(res => res.data),
    {
      onSuccess: (data) => {
        if (data?.data?.length > 0) {
          // Auto-select first pending exam
          const firstPending = data.data.find(detail => detail.estado !== 'completado');
          if (firstPending) {
            handleSelectExam(firstPending);
          }
        }
      }
    }
  );

  // Extract data
  const details = detailsResponse?.data || [];
  const request = details.length > 0 ? details[0].solicitud : null;

  // Estados para auto-guardado
  const [guardandoCampos, setGuardandoCampos] = useState({});
  const [camposGuardados, setCamposGuardados] = useState({});

  // Estados para optimización de rendimiento
  const [pendingUpdates, setPendingUpdates] = useState(new Map());
  const [lastSaveTime, setLastSaveTime] = useState(new Map());
  const [batchSaveTimeout, setBatchSaveTimeout] = useState(null);

  // Validar un campo específico (solo cuando sale del campo)
  const validateField = async (campoId, valor) => {
    if (!valor) return;

    // No validar campos por defecto
    if (campoId.toString().startsWith('default_')) {
      return;
    }

    // Limpiar y validar el valor antes de enviarlo
    const valorLimpio = String(valor).trim();

    // Detectar valores problemáticos
    if (valorLimpio === '-1' || valorLimpio === '' || valorLimpio === 'undefined' || valorLimpio === 'null') {
      // Mostrar advertencia al usuario
      toast.warning(`Valor problemático detectado: "${valorLimpio}". Por favor, verifique el valor ingresado.`);
      return;
    }

    try {
      const response = await resultValuesAPI.validate({
        valores: [{ campo_examen_id: parseInt(campoId), valor: valorLimpio }]
      });

      if (response.data.length > 0) {
        const validacion = response.data[0];

        // Verificar si hay errores en la validación
        if (validacion.error) {
          console.error('Error en validación del servidor:', validacion.error);
          toast.error(`Error al validar campo: ${validacion.error}`);
          return;
        }

        setValidations(prev => ({
          ...prev,
          [campoId]: validacion
        }));
      }
    } catch (error) {
      console.error('Error validating field:', error);
      toast.error('Error al validar el campo. Por favor, intente nuevamente.');
    }
  };

  // Función optimizada para guardar campos con debounce y batch
  const guardarCampoOptimizado = useCallback((campoId, valor, observaciones = null) => {
    if (!valor) {
      return;
    }

    // Limpiar y validar el valor antes de guardarlo
    const valorLimpio = String(valor).trim();

    // Prevenir guardado de valores problemáticos
    if (valorLimpio === '-1' || valorLimpio === '' || valorLimpio === 'undefined' || valorLimpio === 'null') {
      toast.error(`No se puede guardar el valor "${valorLimpio}". Por favor, ingrese un valor válido.`);
      return;
    }

    // Marcar como pendiente de guardado
    setPendingUpdates(prev => {
      const newMap = new Map(prev);
      newMap.set(campoId, { valor: valorLimpio, observaciones, timestamp: Date.now() });
      return newMap;
    });

    // Marcar como guardando
    setGuardandoCampos(prev => ({ ...prev, [campoId]: true }));

    // Limpiar timeout anterior si existe
    if (batchSaveTimeout) {
      clearTimeout(batchSaveTimeout);
    }

    // Crear nuevo timeout para batch save
    const newTimeout = setTimeout(() => {
      procesarCamposPendientes();
    }, 800); // Esperar 800ms antes de guardar

    setBatchSaveTimeout(newTimeout);
  }, [batchSaveTimeout, activeExam]);

  // Procesar todos los campos pendientes en batch - OPTIMIZADO
  const procesarCamposPendientes = useCallback(async () => {
    if (!activeExam || pendingUpdates.size === 0) return;

    const camposParaGuardar = Array.from(pendingUpdates.entries());
    setPendingUpdates(new Map()); // Limpiar pendientes

    try {
      // Separar campos normales de campos simples
      const camposNormales = [];
      const camposSimples = [];

      camposParaGuardar.forEach(([campoId, data]) => {
        if (campoId.toString().startsWith('default_')) {
          camposSimples.push({ campoId, data });
        } else {
          camposNormales.push({
            campo_examen_id: parseInt(campoId),
            valor: data.valor,
            observaciones: data.observaciones,
            campoId // Para tracking
          });
        }
      });

      let successful = 0;
      let failed = 0;

      // Procesar campos normales en batch si hay alguno
      if (camposNormales.length > 0) {
        try {
          const response = await resultValuesAPI.storeBatch({
            detalle_solicitud_id: activeExam.id,
            campos: camposNormales.map(c => ({
              campo_examen_id: c.campo_examen_id,
              valor: c.valor,
              observaciones: c.observaciones
            }))
          });

          if (response.data.status) {
            successful += response.data.campos_guardados || camposNormales.length;

            // Marcar todos como guardados
            camposNormales.forEach(campo => {
              setCamposGuardados(prev => ({ ...prev, [campo.campoId]: true }));
              setGuardandoCampos(prev => ({ ...prev, [campo.campoId]: false }));
            });
          } else {
            failed += camposNormales.length;
          }
        } catch (error) {
          console.error('❌ Error en batch save:', error);
          failed += camposNormales.length;

          // Marcar como no guardando
          camposNormales.forEach(campo => {
            setGuardandoCampos(prev => ({ ...prev, [campo.campoId]: false }));
          });
        }
      }

      // Procesar campos simples individualmente (son pocos)
      if (camposSimples.length > 0) {
        const promisesSimples = camposSimples.map(async ({ campoId, data }) => {
          try {
            const response = await resultValuesAPI.storeSimple({
              detalle_solicitud_id: activeExam.id,
              valor: data.valor,
              observaciones: data.observaciones
            });

            if (response.data.status) {
              setCamposGuardados(prev => ({ ...prev, [campoId]: true }));
              return { success: true };
            } else {
              throw new Error(response.data.message || 'Error desconocido');
            }
          } catch (error) {
            console.error(`❌ Error guardando campo simple ${campoId}:`, error);
            return { success: false };
          } finally {
            setGuardandoCampos(prev => ({ ...prev, [campoId]: false }));
          }
        });

        const resultsSimples = await Promise.allSettled(promisesSimples);
        successful += resultsSimples.filter(r => r.status === 'fulfilled' && r.value.success).length;
        failed += resultsSimples.filter(r => r.status === 'rejected' || !r.value.success).length;
      }

      // Mostrar resultados
      if (successful > 0) {
        toast.success(`${successful} campo${successful > 1 ? 's' : ''} guardado${successful > 1 ? 's' : ''}`);

        // Solo invalidar queries una vez al final del batch
        queryClient.invalidateQueries(['requestDetails', id]);
      }

      if (failed > 0) {
        toast.error(`Error al guardar ${failed} campo${failed > 1 ? 's' : ''}`);
      }

    } catch (error) {
      console.error('❌ Error en batch save:', error);
      toast.error('Error al guardar los campos');

      // Limpiar estados de guardando
      camposParaGuardar.forEach(([campoId]) => {
        setGuardandoCampos(prev => ({ ...prev, [campoId]: false }));
      });
    }
  }, [activeExam, pendingUpdates, queryClient, id]);

  // Función legacy para compatibilidad (ahora usa la optimizada)
  const guardarCampo = useCallback((campoId, valor, observaciones = null) => {
    guardarCampoOptimizado(campoId, valor, observaciones);
  }, [guardarCampoOptimizado]);

  // Cleanup al desmontar el componente
  useEffect(() => {
    return () => {
      if (batchSaveTimeout) {
        clearTimeout(batchSaveTimeout);
      }
      // Procesar campos pendientes antes de desmontar
      if (pendingUpdates.size > 0) {
        procesarCamposPendientes();
      }
    };
  }, [batchSaveTimeout, pendingUpdates, procesarCamposPendientes]);

  // Update status mutation
  const updateStatusMutation = useMutation(
    (statusData) => requestDetailsAPI.updateStatus(statusData.id, statusData.estado),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['requestDetails', id]);
        toast.success('Estado actualizado correctamente');
      },
      onError: (error) => {
        console.error('Error updating status:', error);
        toast.error('Error al actualizar el estado');
      }
    }
  );

  // Register results mutation
  const registerResultMutation = useMutation(
    (resultData) => resultValuesAPI.create(resultData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['requestDetails', id]);
        toast.success('Resultados registrados con éxito');
        setActiveExam(null);
        reset();
        setIsSubmitting(false);
        setValidations({});
      },
      onError: (error) => {
        console.error('Error registering results:', error);
        toast.error(error.response?.data?.message || 'Error al registrar resultados');
        setIsSubmitting(false);
      }
    }
  );



  // Handle exam selection
  const handleSelectExam = useCallback(async (exam) => {
    // Evitar recarga si es el mismo examen activo
    if (activeExam && activeExam.id === exam.id) {
      return;
    }
    
    setActiveExam(exam);
    setValidations({});
    setCamposGuardados({});
    setGuardandoCampos({});

    try {
      // Get exam fields template
      const response = await resultValuesAPI.getTemplate(exam.examen_id);
      const { examen, campos_por_seccion } = response.data;

      let isDefaultField = false;
      let examFieldsData;

      // Si el examen no tiene campos definidos, crear un campo simple por defecto
      if (!campos_por_seccion || Object.keys(campos_por_seccion).length === 0) {
        isDefaultField = true;

        const campoDefault = {
          id: `default_${exam.examen_id}`,
          nombre: 'Resultado',
          tipo: 'text',
          unidad: null,
          valor_referencia: 'Según criterio médico',
          requerido: true,
          orden: 1,
          seccion: 'RESULTADO'
        };

        const camposPorDefecto = {
          'RESULTADO': [campoDefault]
        };

        examFieldsData = {
          examen,
          campos_por_seccion: camposPorDefecto,
          isDefaultField: true
        };

        toast.success(`Examen "${examen.nombre}" configurado con campo por defecto`);
      } else {
        isDefaultField = false;
        
        examFieldsData = {
          examen,
          campos_por_seccion,
          isDefaultField: false
        };
      }

      // Establecer los campos del examen
      setExamFields(prev => ({
        ...prev,
        [exam.id]: examFieldsData
      }));

      // Ahora cargar valores existentes usando la información correcta
      await loadExistingValues(exam, isDefaultField);

    } catch (error) {
      console.error('❌ Error loading exam template:', error);
      toast.error('Error al cargar la plantilla del examen');
    }
  }, [activeExam, queryClient, id, reset]);

  // Función optimizada para cargar valores existentes sin invalidaciones innecesarias
  const loadExistingValues = useCallback(async (exam, isDefaultField) => {
    try {
      // Reset form with existing values
      const formData = {};
      const camposConDatos = {};

      if (isDefaultField) {
        // Para exámenes sin campos, usar los datos ya cargados en lugar de recargar
        // Buscar en los detalles ya cargados
        const detalleConResultado = details.find(d => d.id === exam.id);
        if (detalleConResultado && detalleConResultado.resultado) {
          const campoKey = `campo_default_${exam.examen_id}`;
          const obsKey = `observaciones_default_${exam.examen_id}`;

          formData[campoKey] = detalleConResultado.resultado;
          if (detalleConResultado.observaciones) {
            formData[obsKey] = detalleConResultado.observaciones;
          }

          // Marcar como guardado
          camposConDatos[`default_${exam.examen_id}`] = true;
        }
      } else {
        // Para exámenes con campos dinámicos, cargar solo los valores específicos
        const valuesResponse = await resultValuesAPI.getByDetail(exam.id);
        const existingValues = valuesResponse.data || [];

        existingValues.forEach(valor => {
          const campoKey = `campo_${valor.campo_examen_id}`;
          const obsKey = `observaciones_${valor.campo_examen_id}`;

          formData[campoKey] = valor.valor;
          if (valor.observaciones) {
            formData[obsKey] = valor.observaciones;
          }
          // Marcar como guardado
          camposConDatos[valor.campo_examen_id] = true;
        });
      }

      reset(formData);
      setCamposGuardados(camposConDatos);
    } catch (error) {
      console.error('❌ Error loading existing values:', error);
      reset();
      setCamposGuardados({});
    }
  }, [details, reset]);

  // Handle status change
  const handleStatusChange = (exam, e) => {
    e.stopPropagation();
    e.preventDefault();

    const newStatus = exam.estado === 'pendiente' ? 'en_proceso' : 'pendiente';
    updateStatusMutation.mutate({
      id: exam.id,
      estado: newStatus
    });
  };

  // Handle form submission
  const onSubmit = (data) => {
    if (!activeExam) {
      toast.error('Debe seleccionar un examen');
      return;
    }

    setIsSubmitting(true);

    // Verificar si es un examen con campos por defecto
    const isDefaultField = examFields[activeExam.id]?.isDefaultField;

    if (isDefaultField) {
      // Para exámenes sin campos definidos, usar endpoint simple
      const campoDefaultKey = Object.keys(data).find(key => key.startsWith('campo_default_'));
      if (!campoDefaultKey) {
        toast.error('Debe ingresar un valor');
        setIsSubmitting(false);
        return;
      }

      const valor = data[campoDefaultKey];
      const observaciones = data[`observaciones_default_${activeExam.examen_id}`];

      if (!valor) {
        toast.error('Debe ingresar un valor');
        setIsSubmitting(false);
        return;
      }

      // Usar endpoint simple
      resultValuesAPI.storeSimple({
        detalle_solicitud_id: activeExam.id,
        valor,
        observaciones
      }).then(response => {
        if (response.data.status) {
          toast.success('Resultado guardado correctamente');
          queryClient.invalidateQueries(['requestDetails', id]);
          // Cerrar el formulario (como si se diera a cancelar)
          setActiveExam(null);
        } else {
          toast.error('Error al guardar el resultado');
        }
      }).catch(error => {
        console.error('Error:', error);
        toast.error('Error al guardar el resultado');
      }).finally(() => {
        setIsSubmitting(false);
      });

      return;
    }

    // Para exámenes con campos normales
    const valores = [];
    Object.keys(data).forEach(key => {
      if (key.startsWith('campo_') && !key.startsWith('campo_default_')) {
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

    if (valores.length === 0) {
      toast.error('Debe ingresar al menos un valor');
      setIsSubmitting(false);
      return;
    }

    const resultData = {
      detalle_solicitud_id: activeExam.id,
      valores
    };

    registerResultMutation.mutate(resultData);
  };

  // Render field input based on type
  const renderFieldInput = (campo, sectionIndex, fieldIndex) => {
    const fieldName = `campo_${campo.id}`;
    const observacionesName = `observaciones_${campo.id}`;
    const validacion = validations[campo.id];
    const isOutOfRange = validacion && !validacion.en_rango;
    const isGuardando = guardandoCampos[campo.id];
    const isGuardado = camposGuardados[campo.id];

    const handleBlur = (e) => {
      const valor = e.target.value;
      if (valor) {
        // Validar cuando sale del campo
        validateField(campo.id, valor);
        // Guardar automáticamente
        const observaciones = document.querySelector(`[name="${observacionesName}"]`)?.value;
        guardarCampo(campo.id, valor, observaciones);
      }
    };

    switch (campo.tipo) {
      case 'number':
        const currentValue = watch(fieldName);
        return (
          <div className="space-y-2">
            <ValueDebugger
              value={currentValue}
              fieldName={fieldName}
              onChange={(newValue) => setValue(fieldName, newValue)}
            />
            <div className="relative">
              <input
                type="number"
                step="any"
                {...register(fieldName, {
                  required: campo.requerido ? 'Este campo es requerido' : false
                })}
                onBlur={handleBlur}
                className={`w-full px-3 py-2 pr-12 border rounded-md focus:outline-none focus:ring-2 ${
                  isOutOfRange
                    ? 'border-red-300 focus:ring-red-500'
                    : isGuardado
                    ? 'border-green-300 focus:ring-green-500'
                    : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500'
                } dark:bg-gray-700 dark:text-white`}
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
            <input
              type="text"
              {...register(observacionesName)}
              onBlur={handleBlur}
              className={`w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring-1 ${
                isGuardado
                  ? 'border-green-300 focus:ring-green-500'
                  : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500'
              } dark:bg-gray-700 dark:text-white`}
              placeholder="Observaciones..."
            />
          </div>
        );



      case 'boolean':
        const handleBooleanChange = (e) => {
          const valor = e.target.value;
          const observaciones = document.querySelector(`[name="${observacionesName}"]`)?.value;
          guardarCampo(campo.id, valor, observaciones);
        };

        return (
          <div className="space-y-2">
            <div className={`flex space-x-4 p-3 border rounded-md ${
              isGuardado
                ? 'border-green-300 bg-green-50 dark:bg-green-900/20'
                : 'border-gray-300 dark:border-gray-600'
            }`}>
              <label className="flex items-center">
                <input
                  type="radio"
                  value="true"
                  {...register(fieldName, {
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
                  {...register(fieldName, {
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
            <input
              type="text"
              {...register(observacionesName)}
              onBlur={handleBlur}
              className={`w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring-1 ${
                isGuardado
                  ? 'border-green-300 focus:ring-green-500'
                  : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500'
              } dark:bg-gray-700 dark:text-white`}
              placeholder="Observaciones..."
            />
          </div>
        );

      case 'textarea':
        return (
          <div className="space-y-2">
            <div className="relative">
              <textarea
                {...register(fieldName, {
                  required: campo.requerido ? 'Este campo es requerido' : false
                })}
                onBlur={handleBlur}
                rows={3}
                className={`w-full px-3 py-2 pr-12 border rounded-md focus:outline-none focus:ring-2 ${
                  isGuardado
                    ? 'border-green-300 focus:ring-green-500'
                    : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500'
                } dark:bg-gray-700 dark:text-white`}
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
          </div>
        );

      default:
        return (
          <div className="space-y-2">
            <div className="relative">
              <input
                type="text"
                {...register(fieldName, {
                  required: campo.requerido ? 'Este campo es requerido' : false
                })}
                onBlur={handleBlur}
                className={`w-full px-3 py-2 pr-12 border rounded-md focus:outline-none focus:ring-2 ${
                  isGuardado
                    ? 'border-green-300 focus:ring-green-500'
                    : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500'
                } dark:bg-gray-700 dark:text-white`}
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
            <input
              type="text"
              {...register(observacionesName)}
              onBlur={handleBlur}
              className={`w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring-1 ${
                isGuardado
                  ? 'border-green-300 focus:ring-green-500'
                  : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500'
              } dark:bg-gray-700 dark:text-white`}
              placeholder="Observaciones..."
            />
          </div>
        );
    }
  };

  // Check if all exams are completed
  const allCompleted = details.length > 0 && details.every(detail => detail.estado === 'completado');

  if (detailsLoading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <div className="flex items-center">
          <Link
            to="/resultados"
            className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
          </Link>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
            Registrar Resultados Dinámicos
          </h1>
        </div>
      </div>

      {/* Request Information */}
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
        <div className="px-4 py-5 sm:px-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Información de la Solicitud
          </h3>
        </div>
        <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-0">
          <dl className="sm:divide-y sm:divide-gray-200 sm:dark:divide-gray-700">
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Paciente
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {request?.paciente ? `${request.paciente.nombres} ${request.paciente.apellidos}` : 'N/A'}
              </dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                DNI
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {request?.paciente?.dni || 'N/A'}
              </dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                Fecha
              </dt>
              <dd className="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                {request?.fecha ? format(new Date(request.fecha), 'dd/MM/yyyy') : 'N/A'}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      {/* Main Content */}
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Exams List */}
        <div className="lg:col-span-1">
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div className="px-4 py-5 sm:px-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Exámenes
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Seleccione un examen
              </p>
            </div>
            <div className="border-t border-gray-200 dark:border-gray-700">
              <ul className="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                {details.length > 0 ? (
                  details.map((detail) => (
                    <li
                      key={detail.id}
                      className={`px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer ${
                        activeExam?.id === detail.id ? 'bg-primary-50 dark:bg-primary-900' : ''
                      }`}
                      onClick={() => handleSelectExam(detail)}
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {detail.examen?.nombre || 'Examen sin nombre'}
                          </p>
                          <p className="text-xs text-gray-500 dark:text-gray-400">
                            {detail.examen?.categoria?.nombre || 'N/A'}
                          </p>
                          {detail.examen?.tipo && (
                            <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                              detail.examen.tipo === 'compuesto'
                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'
                                : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                            }`}>
                              {detail.examen.tipo === 'compuesto' ? 'Compuesto' : 'Simple'}
                            </span>
                          )}
                        </div>
                        <div className="flex items-center space-x-2 ml-2">
                          {detail.estado !== 'completado' && (
                            <button
                              onClick={(e) => handleStatusChange(detail, e)}
                              className="inline-flex items-center p-1 border border-transparent rounded-full text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                              title={detail.estado === 'pendiente' ? 'Marcar como En Proceso' : 'Marcar como Pendiente'}
                              type="button"
                            >
                              {detail.estado === 'pendiente' ? (
                                <PlayIcon className="h-3 w-3" aria-hidden="true" />
                              ) : (
                                <PauseIcon className="h-3 w-3" aria-hidden="true" />
                              )}
                            </button>
                          )}
                          <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            detail.estado === 'completado'
                              ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                              : detail.estado === 'en_proceso'
                              ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                          }`}>
                            {detail.estado === 'completado' ? 'OK' : detail.estado === 'en_proceso' ? 'EP' : 'P'}
                          </span>
                        </div>
                      </div>
                    </li>
                  ))
                ) : (
                  <li className="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No hay exámenes disponibles
                  </li>
                )}
              </ul>
            </div>
          </div>
        </div>

        {/* Results Form */}
        <div className="lg:col-span-3">
          {activeExam && examFields[activeExam.id] ? (
            <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
              <div className="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div className="flex items-center">
                  <BeakerIcon className="h-6 w-6 text-primary-600 mr-2" />
                  <div>
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                      {activeExam.examen?.nombre}
                    </h3>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      {examFields[activeExam.id].examen.instrucciones_muestra && (
                        <span><strong>Muestra:</strong> {examFields[activeExam.id].examen.instrucciones_muestra}</span>
                      )}
                      {examFields[activeExam.id].examen.metodo_analisis && (
                        <span className="ml-4"><strong>Método:</strong> {examFields[activeExam.id].examen.metodo_analisis}</span>
                      )}
                    </p>
                  </div>
                </div>
              </div>

              <form onSubmit={handleSubmit(onSubmit)} className="p-6">
                {/* Información sobre auto-guardado */}
                <div className="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <div className="flex items-center">
                    <CloudArrowUpIcon className="w-5 h-5 text-blue-600 mr-2" />
                    <div>
                      <p className="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Auto-guardado activado
                      </p>
                      <p className="text-xs text-blue-600 dark:text-blue-300 mt-1">
                        Los valores se guardan automáticamente al salir de cada campo.
                        Los campos guardados se marcan con <CloudArrowUpIcon className="w-4 h-4 inline text-green-500" />.
                      </p>
                    </div>
                  </div>
                </div>

                {/* Debug info - solo en desarrollo */}
                {process.env.NODE_ENV === 'development' && (
                  <div className="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs">
                    <strong>Debug Info:</strong>
                    Examen: {examFields[activeExam.id]?.examen?.nombre} |
                    Tipo: {examFields[activeExam.id]?.examen?.tipo} |
                    Secciones: {Object.keys(examFields[activeExam.id]?.campos_por_seccion || {}).length} |
                    Total campos: {Object.values(examFields[activeExam.id]?.campos_por_seccion || {}).flat().length}
                  </div>
                )}

                <div className="space-y-8">
                  {Object.keys(examFields[activeExam.id].campos_por_seccion || {}).length === 0 ? (
                    <div className="text-center py-12">
                      <BeakerIcon className="mx-auto h-12 w-12 text-gray-400" />
                      <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        No hay campos configurados
                      </h3>
                      <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Este examen no tiene campos de resultados configurados.
                      </p>
                    </div>
                  ) : (
                    Object.entries(examFields[activeExam.id].campos_por_seccion || {}).map(([seccion, campos]) => (
                    <div key={seccion} className="border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-gray-50 dark:bg-gray-800/50">
                      <div className="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        <h4 className="text-lg font-semibold text-gray-900 dark:text-white">
                          {seccion}
                        </h4>
                        <span className="bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 px-2 py-1 rounded-full text-xs font-medium">
                          {campos.length} campo{campos.length !== 1 ? 's' : ''}
                        </span>
                      </div>

                      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {campos.map((campo, fieldIndex) => {
                          const validacion = validations[campo.id];
                          const isOutOfRange = validacion && !validacion.en_rango;
                          
                          return (
                            <div key={campo.id} className="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 space-y-3 hover:shadow-md transition-shadow">
                              {/* Header del campo */}
                              <div className="flex items-center justify-between">
                                <label className="block text-sm font-semibold text-gray-900 dark:text-white">
                                  {campo.nombre}
                                  {campo.requerido && <span className="text-red-500 ml-1">*</span>}
                                  {campo.unidad && (
                                    <span className="text-gray-500 dark:text-gray-400 ml-1 font-normal">({campo.unidad})</span>
                                  )}
                                </label>
                                <span className={`px-2 py-1 text-xs rounded-full font-medium ${
                                  campo.tipo === 'number' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' :
                                  campo.tipo === 'text' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' :
                                  campo.tipo === 'textarea' ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' :
                                  'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                                }`}>
                                  {campo.tipo}
                                </span>
                              </div>

                              {/* Input del campo */}
                              <div className="space-y-2">
                                {renderFieldInput(campo, 0, fieldIndex)}
                              </div>

                              {/* Información adicional */}
                              <div className="space-y-1">
                                {campo.valor_referencia && (
                                  <div className="flex items-start space-x-2">
                                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Referencia:</span>
                                    <p className="text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded">
                                      {campo.valor_referencia}
                                    </p>
                                  </div>
                                )}

                                {isOutOfRange && (
                                  <div className="flex items-center space-x-2 p-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                                    <ExclamationTriangleIcon className="w-4 h-4 text-red-500" />
                                    <p className="text-xs text-red-600 dark:text-red-400 font-medium">
                                      Valor fuera del rango de referencia
                                    </p>
                                  </div>
                                )}

                                {errors[`campo_${campo.id}`] && (
                                  <div className="flex items-center space-x-2 p-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                                    <ExclamationTriangleIcon className="w-4 h-4 text-red-500" />
                                    <p className="text-xs text-red-600 dark:text-red-400 font-medium">
                                      {errors[`campo_${campo.id}`].message}
                                    </p>
                                  </div>
                                )}
                              </div>

                              {/* Debug info para desarrollo */}
                              {process.env.NODE_ENV === 'development' && (
                                <div className="text-xs text-gray-400 border-t pt-2">
                                  ID: {campo.id} | Orden: {campo.orden} | Requerido: {campo.requerido ? 'Sí' : 'No'}
                                </div>
                              )}
                            </div>
                          );
                        })}
                      </div>
                    </div>
                    ))
                  )}
                </div>

                <div className="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                  <button
                    type="button"
                    onClick={() => setActiveExam(null)}
                    className="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {isSubmitting ? 'Guardando...' : activeExam.estado === 'completado' ? 'Actualizar Resultados' : 'Guardar Resultados'}
                  </button>
                </div>
              </form>
            </div>
          ) : activeExam ? (
            <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
              <div className="px-4 py-5 sm:p-6 text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary-500 mx-auto"></div>
                <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  Cargando campos del examen...
                </p>
              </div>
            </div>
          ) : (
            <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
              <div className="px-4 py-5 sm:p-6 text-center">
                {allCompleted ? (
                  <div className="flex flex-col items-center">
                    <CheckCircleIcon className="h-12 w-12 text-green-500 mb-4" />
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                      Todos los exámenes han sido completados
                    </h3>
                    <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                      Puede imprimir los resultados o volver a la lista de solicitudes
                    </p>
                    <div className="mt-6">
                      <Link
                        to={`/resultados/${id}/ver`}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                      >
                        Ver Resultados
                      </Link>
                    </div>
                  </div>
                ) : (
                  <div className="flex flex-col items-center">
                    <BeakerIcon className="h-12 w-12 text-gray-400 mb-4" />
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      Seleccione un examen de la lista para registrar resultados
                    </p>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
