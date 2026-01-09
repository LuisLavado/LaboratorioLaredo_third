import { useState, useEffect, useRef } from 'react';
import { useNavigate, Link, useParams } from 'react-router-dom';
import { useForm, Controller } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { requestsAPI, patientsAPI, examsAPI, servicesAPI, requestDetailsAPI, dniAPI } from '../../services/api';
import { ArrowLeftIcon, MagnifyingGlassIcon, PlusIcon, XMarkIcon, UserIcon, BeakerIcon, BuildingOfficeIcon } from '@heroicons/react/24/outline';
import PatientSearchModal from '../../components/patients/PatientSearchModal';
import ServiceSearchModal from '../../components/services/ServiceSearchModal';
import ExamsSearchModal from '../../components/exams/ExamsSearchModal';
import toast from 'react-hot-toast';
import { format } from 'date-fns';

export default function DoctorEditRequest() {
  const navigate = useNavigate();
  const { id } = useParams(); // Obtener ID de la solicitud a editar
  const queryClient = useQueryClient();

  const [isPatientModalOpen, setIsPatientModalOpen] = useState(false);
  const [isServiceModalOpen, setIsServiceModalOpen] = useState(false);
  const [isExamsModalOpen, setIsExamsModalOpen] = useState(false);
  const [selectedExams, setSelectedExams] = useState([]);
  const [patient, setPatient] = useState(null);
  const [dniSearchValue, setDniSearchValue] = useState('');
  const [isSearchingDni, setIsSearchingDni] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isRequestEditable, setIsRequestEditable] = useState(true);

  const { control, register, handleSubmit, setValue, watch, reset, formState: { errors } } = useForm({
    defaultValues: {
      fecha: '',
      hora: '',
      servicio_id: '',
      numero_recibo: '',
      tipo_atencion: 'sis',
      paciente_id: '',
      examenes: []
    }
  });

  // Fetch request data for editing
  const { data: requestData, isLoading: requestLoading } = useQuery({
    queryKey: ['request', id],
    queryFn: async () => {
      const response = await requestsAPI.getById(id);
      console.log('Request data response:', response.data);
      return response.data;
    },
    enabled: !!id,
  });

  // Effect to populate form when request data is loaded
  useEffect(() => {
    if (requestData) {
      console.log('Request data received:', requestData);
      // Populate form with existing data
      const fechaFormatted = requestData.fecha ? format(new Date(requestData.fecha), 'yyyy-MM-dd') : '';
      const horaFormatted = requestData.hora ? format(new Date(requestData.hora), 'HH:mm') : '';

      // Determinar tipo de atención basado en los campos booleanos
      let tipoAtencion = 'sis'; // default
      if (requestData.rdr) tipoAtencion = 'rdr';
      else if (requestData.exon) tipoAtencion = 'exon';
      else if (requestData.sis) tipoAtencion = 'sis';

      const formData = {
        fecha: fechaFormatted,
        hora: horaFormatted,
        servicio_id: requestData.servicio_id || '',
        numero_recibo: requestData.numero_recibo || '',
        tipo_atencion: tipoAtencion,
        paciente_id: requestData.paciente_id || '',
        examenes: []
      };

      console.log('Form data to reset:', formData);
      reset(formData);

      // Set patient data if available in the request response
      if (requestData.paciente) {
        setPatient(requestData.paciente);
        console.log('Patient set from request data:', requestData.paciente);
      }
    }
  }, [requestData, reset]);

  // Fetch request details (exams)
  const { data: requestDetailsData } = useQuery({
    queryKey: ['requestDetails', id],
    queryFn: async () => {
      const response = await requestDetailsAPI.getByRequest(id);
      console.log('Request details response:', response.data);
      return response.data?.data || [];
    },
    enabled: !!id,
  });

  // Effect to populate exams when request details are loaded
  useEffect(() => {
    if (requestDetailsData) {
      console.log('Request details received:', requestDetailsData);
      // Set selected exams from existing details with category info
      const exams = requestDetailsData.map(detail => ({
        id: detail.examen_id,
        nombre: detail.examen?.nombre || `Examen ID: ${detail.examen_id}`,
        categoria: detail.examen?.categoria?.nombre || 'Sin categoría',
        estado: detail.estado || 'pendiente'
      }));
      setSelectedExams(exams);
      setValue('examenes', exams.map(exam => exam.id));

      // Verificar si la solicitud es editable
      const hasNonPendingExams = exams.some(exam =>
        exam.estado && exam.estado !== 'pendiente'
      );
      setIsRequestEditable(!hasNonPendingExams);

      if (hasNonPendingExams) {
        toast.error('Esta solicitud no se puede editar porque tiene exámenes en proceso o completados');
      }
    }
  }, [requestDetailsData, setValue]);

  // Fetch services
  const { data: servicesResponse } = useQuery({
    queryKey: ['services'],
    queryFn: () => servicesAPI.getAll().then(res => res.data)
  });

  // Extract services from the response
  const services = servicesResponse?.servicios || [];

  // Debug effect to monitor data loading
  useEffect(() => {
    console.log('=== DEBUG INFO ===');
    console.log('Request ID:', id);
    console.log('Request Data:', requestData);
    console.log('Request Loading:', requestLoading);
    console.log('Patient:', patient);
    console.log('Selected Exams:', selectedExams);
    console.log('Form Values:', watch());
    console.log('==================');
  }, [id, requestData, requestLoading, patient, selectedExams, watch]);

  // Fetch patient data when request data is loaded (only if not already in request data)
  const { data: patientData } = useQuery({
    queryKey: ['patient', requestData?.paciente_id],
    queryFn: async () => {
      if (!requestData?.paciente_id) return null;
      const response = await patientsAPI.getById(requestData.paciente_id);
      console.log('Patient data response:', response.data);
      return response.data;
    },
    enabled: !!requestData?.paciente_id && !requestData?.paciente,
  });

  // Effect to set patient data when loaded separately
  useEffect(() => {
    if (patientData?.paciente) {
      console.log('Patient data received:', patientData);
      setPatient(patientData.paciente);
    }
  }, [patientData]);

  // Update mutation
  const updateRequestMutation = useMutation({
    mutationFn: (requestData) => requestsAPI.update(id, requestData),
    onSuccess: (response) => {
      console.log('Request updated successfully:', response);
      toast.success('Solicitud actualizada exitosamente');

      // Invalidate and refetch requests
      queryClient.invalidateQueries({ queryKey: ['requests'] });
      queryClient.invalidateQueries({ queryKey: ['doctorRequests'] });
      queryClient.invalidateQueries({ queryKey: ['request', id] });
      queryClient.invalidateQueries({ queryKey: ['requestDetails', id] });

      // Navigate back to request detail
      navigate(`/doctor/solicitudes/${id}`);
    },
    onError: (error) => {
      console.error('Error updating request:', error);
      toast.error(error.response?.data?.message || 'Error al actualizar la solicitud');
      setIsSubmitting(false);
    }
  });



  // Handle patient selection
  const handlePatientSelect = (selectedPatient) => {
    setValue('paciente_id', selectedPatient.id);
    setPatient(selectedPatient);
    setIsPatientModalOpen(false);
  };

  // Handle service selection
  const handleServiceSelect = (service) => {
    setValue('servicio_id', service.id);
    // Invalidar la cache de servicios para asegurar que se actualice la lista
    queryClient.invalidateQueries(['services']);
    setIsServiceModalOpen(false);
  };

  // Handle exams selection from modal
  const handleSelectExams = (exams) => {
    // Process exams to ensure consistent structure
    const processedExams = exams.map(exam => ({
      id: exam.id,
      nombre: exam.nombre,
      categoria: exam.categoria?.nombre || 'Sin categoría',
      estado: 'pendiente' // New exams are always pending
    }));
    setSelectedExams(processedExams);
    setValue('examenes', processedExams.map(e => e.id));
  };

  // Handle exam removal
  const handleRemoveExam = (examId) => {
    // Verificar si el examen se puede eliminar (solo pendientes)
    const examToRemove = selectedExams.find(exam => exam.id === examId);
    if (examToRemove && examToRemove.estado && examToRemove.estado !== 'pendiente') {
      toast.error('No se puede eliminar un examen que ya está en proceso o completado');
      return;
    }

    const updatedExams = selectedExams.filter(exam => exam.id !== examId);
    setSelectedExams(updatedExams);
    setValue('examenes', updatedExams.map(e => e.id));
  };

  // Handle form submission
  const onSubmit = (data) => {
    if (selectedExams.length === 0) {
      toast.error('Debe seleccionar al menos un examen');
      return;
    }

    // Verificar si hay exámenes en proceso o completados
    const nonEditableExams = selectedExams.filter(exam =>
      exam.estado && exam.estado !== 'pendiente'
    );

    if (nonEditableExams.length > 0) {
      const examNames = nonEditableExams.map(exam => exam.nombre).join(', ');
      toast.error(`Los siguientes exámenes no se pueden modificar porque ya están en proceso o completados: ${examNames}`);
      return;
    }

    setIsSubmitting(true);

    // Prepare data for submission
    const requestData = {
      ...data,
      examenes: selectedExams.map(exam => exam.id),
      // Convertir tipo_atencion a los campos booleanos esperados por el backend
      rdr: data.tipo_atencion === 'rdr',
      sis: data.tipo_atencion === 'sis',
      exon: data.tipo_atencion === 'exon'
    };

    // Eliminar el campo tipo_atencion ya que no existe en el backend
    delete requestData.tipo_atencion;

    updateRequestMutation.mutate(requestData);
  };



  return (
    <div>
      <div className="sm:flex sm:items-center mb-6">
        <div className="sm:flex-auto">
              <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Editar Solicitud</h1>
              <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                Modificar los datos de la solicitud de exámenes
              </p>
            </div>
            <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
              <Link
                to="/doctor/solicitudes"
                className="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 sm:w-auto"
              >
                <ArrowLeftIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                Volver
              </Link>
            </div>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-8 divide-y divide-gray-200 dark:divide-gray-700">
            <div className="space-y-8 divide-y divide-gray-200 dark:divide-gray-700">
              {/* Patient Information */}
              <div className="pt-8">
                <div>
                  <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">Información del Paciente</h3>
                  <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Seleccione el paciente para el cual se realizará la solicitud
                  </p>
                </div>
                <div className="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                  <div className="sm:col-span-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <UserIcon className="h-8 w-8 text-gray-400" aria-hidden="true" />
                      </div>
                      <div className="ml-4 flex-1">
                        {patient ? (
                          <div>
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                              {patient.nombres} {patient.apellidos}
                            </h3>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                              DNI: {patient.dni} | Edad: {patient.edad} años | HC: {patient.historia_clinica}
                            </p>
                          </div>
                        ) : (
                          <p className="text-sm text-gray-500 dark:text-gray-400">
                            No se ha seleccionado ningún paciente
                          </p>
                        )}
                      </div>
                      <div>
                        <button
                          type="button"
                          disabled={!isRequestEditable}
                          className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                          onClick={() => setIsPatientModalOpen(true)}
                        >
                          <MagnifyingGlassIcon className="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
                          Buscar Paciente
                        </button>
                      </div>
                    </div>
                    {errors.paciente_id && (
                      <p className="mt-2 text-sm text-red-600">{errors.paciente_id.message}</p>
                    )}
                    <input
                      type="hidden"
                      {...register('paciente_id', { required: 'Debe seleccionar un paciente' })}
                    />
                  </div>
                </div>
              </div>

              {/* Request Information */}
              <div className="pt-8">
                <div>
                  <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">Información de la Solicitud</h3>
                  <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Ingrese los detalles de la solicitud
                  </p>
                </div>
                <div className="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                  <div className="sm:col-span-3">
                    <label htmlFor="fecha" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Fecha
                    </label>
                    <div className="mt-1">
                      <input
                        type="date"
                        id="fecha"
                        disabled={!isRequestEditable}
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                        {...register('fecha', { required: 'La fecha es requerida' })}
                      />
                      {errors.fecha && (
                        <p className="mt-2 text-sm text-red-600">{errors.fecha.message}</p>
                      )}
                    </div>
                  </div>

                  <div className="sm:col-span-3">
                    <label htmlFor="hora" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Hora
                    </label>
                    <div className="mt-1">
                      <input
                        type="time"
                        id="hora"
                        disabled={!isRequestEditable}
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                        {...register('hora', { required: 'La hora es requerida' })}
                      />
                      {errors.hora && (
                        <p className="mt-2 text-sm text-red-600">{errors.hora.message}</p>
                      )}
                    </div>
                  </div>

                  <div className="sm:col-span-3">
                    <label htmlFor="numero_recibo" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Número de Recibo <span className="text-gray-500 dark:text-gray-400 text-xs">(Opcional)</span>
                    </label>
                    <div className="mt-1">
                      <input
                        type="text"
                        id="numero_recibo"
                        disabled={!isRequestEditable}
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                        {...register('numero_recibo')}
                        placeholder="Opcional para médicos"
                      />
                    </div>
                  </div>

                  <div className="sm:col-span-3">
                    <label htmlFor="tipo_atencion" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Tipo de Atención
                    </label>
                    <div className="mt-1">
                      <select
                        id="tipo_atencion"
                        disabled={!isRequestEditable}
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                        {...register('tipo_atencion')}
                        defaultValue="sis"
                      >
                        <option value="sis">SIS</option>
                        <option value="rdr">RDR</option>
                        <option value="exon">Exonerado</option>
                      </select>
                    </div>
                  </div>

                  <div className="sm:col-span-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <BuildingOfficeIcon className="h-8 w-8 text-gray-400" aria-hidden="true" />
                      </div>
                      <div className="ml-4 flex-1">
                        {watch('servicio_id') && services.find(s => s.id === parseInt(watch('servicio_id'))) ? (
                          <div>
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                              {services.find(s => s.id === parseInt(watch('servicio_id'))).nombre}
                            </h3>
                          </div>
                        ) : (
                          <p className="text-sm text-gray-500 dark:text-gray-400">
                            No se ha seleccionado ningún servicio
                          </p>
                        )}
                      </div>
                      <div>
                        <button
                          type="button"
                          disabled={!isRequestEditable}
                          className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                          onClick={() => setIsServiceModalOpen(true)}
                        >
                          <MagnifyingGlassIcon className="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
                          Buscar Servicio
                        </button>
                      </div>
                    </div>
                    {errors.servicio_id && (
                      <p className="mt-2 text-sm text-red-600">{errors.servicio_id.message}</p>
                    )}
                    <input
                      type="hidden"
                      {...register('servicio_id', { required: 'Debe seleccionar un servicio' })}
                    />
                  </div>
                </div>
              </div>

              {/* Exams Information */}
              <div className="pt-8">
                <div>
                  <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">Exámenes</h3>
                  <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Seleccione los exámenes a realizar
                  </p>
                </div>
                <div className="mt-6">
                  <div className="flex justify-between items-center mb-4">
                    <h4 className="text-base font-medium text-gray-900 dark:text-white">Exámenes seleccionados</h4>
                    <button
                      type="button"
                      disabled={!isRequestEditable}
                      className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                      onClick={() => setIsExamsModalOpen(true)}
                    >
                      <PlusIcon className="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
                      Agregar Exámenes
                    </button>
                  </div>

                  {selectedExams.length === 0 ? (
                    <div className="text-center py-4 text-sm text-gray-500 dark:text-gray-400 border border-dashed border-gray-300 dark:border-gray-700 rounded-md">
                      No se han seleccionado exámenes
                    </div>
                  ) : (
                    <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
                      <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                        {selectedExams.map((exam) => (
                          <li key={exam.id}>
                            <div className="px-4 py-4 flex items-center sm:px-6">
                              <div className="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                  <div className="flex text-sm">
                                    <p className="font-medium text-primary-600 truncate">{exam.nombre}</p>
                                  </div>
                                  <div className="mt-2 flex">
                                    <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                      <BeakerIcon className="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" aria-hidden="true" />
                                      <p>
                                        {exam.categoria || 'Sin categoría'}
                                      </p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div className="ml-5 flex-shrink-0 flex items-center space-x-2">
                                {/* Estado del examen */}
                                {exam.estado && exam.estado !== 'pendiente' && (
                                  <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    exam.estado === 'en_proceso'
                                      ? 'bg-yellow-100 text-yellow-800'
                                      : exam.estado === 'completado'
                                      ? 'bg-green-100 text-green-800'
                                      : 'bg-gray-100 text-gray-800'
                                  }`}>
                                    {exam.estado === 'en_proceso' ? 'En Proceso' :
                                     exam.estado === 'completado' ? 'Completado' :
                                     exam.estado}
                                  </span>
                                )}

                                {/* Botón eliminar - solo si está pendiente */}
                                {(!exam.estado || exam.estado === 'pendiente') ? (
                                  <button
                                    type="button"
                                    className="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    onClick={() => handleRemoveExam(exam.id)}
                                  >
                                    <XMarkIcon className="h-4 w-4" aria-hidden="true" />
                                  </button>
                                ) : (
                                  <span className="text-xs text-gray-500 dark:text-gray-400">
                                    No editable
                                  </span>
                                )}
                              </div>
                            </div>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                  {errors.examenes && (
                    <p className="mt-2 text-sm text-red-600">{errors.examenes.message}</p>
                  )}
                </div>
              </div>
            </div>

            <div className="pt-5">
              <div className="flex justify-end">
                <Link
                  to="/doctor/solicitudes"
                  className="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  Cancelar
                </Link>
                <button
                  type="submit"
                  disabled={isSubmitting || !isRequestEditable}
                  className="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isSubmitting ? 'Guardando...' : 'Guardar'}
                </button>
              </div>
            </div>
          </form>

          {/* Patient Search Modal */}
          <PatientSearchModal
            isOpen={isPatientModalOpen}
            onClose={() => setIsPatientModalOpen(false)}
            onSelectPatient={handlePatientSelect}
          />

          {/* Service Search Modal */}
          <ServiceSearchModal
            isOpen={isServiceModalOpen}
            onClose={() => setIsServiceModalOpen(false)}
            onSelectService={handleServiceSelect}
          />

          {/* Exams Search Modal */}
          <ExamsSearchModal
            isOpen={isExamsModalOpen}
            onClose={() => setIsExamsModalOpen(false)}
            onSelectExams={handleSelectExams}
            selectedExamIds={selectedExams.map(e => e.id)}
          />
      </div>
 
  );
}

