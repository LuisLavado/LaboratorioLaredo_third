import { useState, useEffect } from 'react';
import { useNavigate, Link, useSearchParams } from 'react-router-dom';
import { useForm, Controller } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { requestsAPI, patientsAPI, examsAPI, servicesAPI, dniAPI } from '../../services/api';
import { ArrowLeftIcon, MagnifyingGlassIcon, PlusIcon, XMarkIcon, UserIcon, BeakerIcon, BuildingOfficeIcon } from '@heroicons/react/24/outline';
import PatientSearchModal from '../../components/patients/PatientSearchModal';
import ServiceSearchModal from '../../components/services/ServiceSearchModal';
import ExamsSearchModal from '../../components/exams/ExamsSearchModal';
import toast from 'react-hot-toast';
import { format } from 'date-fns';

export default function NewRequest() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [searchParams] = useSearchParams();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [selectedExams, setSelectedExams] = useState([]);
  const [isPatientModalOpen, setIsPatientModalOpen] = useState(false);
  const [isServiceModalOpen, setIsServiceModalOpen] = useState(false);
  const [isExamsModalOpen, setIsExamsModalOpen] = useState(false);

  // Obtener el ID del paciente de los parámetros de URL (si existe)
  const patientIdFromUrl = searchParams.get('paciente_id');

  const { control, register, handleSubmit, setValue, watch, formState: { errors } } = useForm({
    defaultValues: {
      fecha: format(new Date(), 'yyyy-MM-dd'),
      hora: format(new Date(), 'HH:mm'),
      servicio_id: '',
      numero_recibo: '',
      tipo_atencion: 'sis', // Valor predeterminado: SIS
      paciente_id: patientIdFromUrl || '',
      examenes: []
    }
  });

  const pacienteId = watch('paciente_id');

  // Fetch services
  const { data: servicesResponse } = useQuery(
    ['services'],
    () => servicesAPI.getAll().then(res => res.data)
  );

  // Extract services from the response
  const services = servicesResponse?.servicios || [];

  // Fetch exams
  const { data: examsResponse } = useQuery(
    ['exams', { all: true }],
    () => examsAPI.getAll({ all: true }).then(res => res.data)
  );

  // Extract exams from the response
  const exams = examsResponse?.examenes || [];

  // Fetch patient details if pacienteId is set
  const { data: patientData, isLoading: patientLoading } = useQuery(
    ['patient', pacienteId],
    () => patientsAPI.getById(pacienteId).then(res => res.data),
    {
      enabled: !!pacienteId,
      onSuccess: (data) => {
        // Patient data loaded
      }
    }
  );

  // Extract patient from the response
  const patient = patientData?.paciente;

  // Create mutation
  const createRequestMutation = useMutation(
    (requestData) => requestsAPI.create(requestData),
    {
      onSuccess: () => {
        // Invalidate and refetch requests list
        queryClient.invalidateQueries(['requests']);
        toast.success('Solicitud creada con éxito');
        navigate('/solicitudes');
      },
      onError: (error) => {
        console.error('Error creating request:', error);
        toast.error(error.response?.data?.message || 'Error al crear la solicitud');
        setIsSubmitting(false);
      }
    }
  );



  // Handle patient selection from modal
  const handleSelectPatient = async (patient) => {
    if (patient.isNew) {
      // Create new patient first
      try {
        const newPatientData = {
          dni: patient.dni,
          nombres: patient.nombres,
          apellidos: patient.apellidos,
          fecha_nacimiento: patient.fecha_nacimiento,
          sexo: patient.sexo
        };

        const response = await patientsAPI.create(newPatientData);
        const createdPatient = response.data.paciente;

        setValue('paciente_id', createdPatient.id);
        toast.success(`Paciente ${createdPatient.nombres} ${createdPatient.apellidos} creado con éxito`);

        // Opcionalmente, podríamos abrir una nueva pestaña con los detalles del paciente
        // window.open(`/pacientes/${createdPatient.id}`, '_blank');
      } catch (error) {
        console.error('Error creating patient:', error);
        toast.error('Error al crear el paciente');
        return;
      }
    } else {
      setValue('paciente_id', patient.id);
    }
  };

  // Handle service selection from modal
  const handleSelectService = (service) => {
    setValue('servicio_id', service.id);
    // Invalidar la cache de servicios para asegurar que se actualice la lista
    queryClient.invalidateQueries(['services']);
  };

  // Handle exams selection from modal
  const handleSelectExams = (exams) => {
    setSelectedExams(exams);
    setValue('examenes', exams.map(e => e.id));
  };

  // Handle exam removal
  const handleRemoveExam = (examId) => {
    const updatedExams = selectedExams.filter(exam => exam.id !== examId);
    setSelectedExams(updatedExams);
    setValue('examenes', updatedExams.map(e => e.id));

    // Si no quedan exámenes, mostrar mensaje
    if (updatedExams.length === 0) {
      toast.info('No hay exámenes seleccionados');
    }
  };

  // Handle form submission
  const onSubmit = (data) => {
    if (selectedExams.length === 0) {
      toast.error('Debe seleccionar al menos un examen');
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

    createRequestMutation.mutate(requestData);
  };



  return (
    <div>
      <div className="mb-6">
        <div className="flex items-center">
          <Link
            to="/solicitudes"
            className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
          </Link>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
            Nueva Solicitud
          </h1>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
            {/* Patient Selection Section */}
            <div>
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Información del Paciente
              </h3>

              {patient ? (
                <div className="mt-4 bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                  <div className="flex justify-between">
                    <div>
                      <h4 className="text-md font-medium text-gray-900 dark:text-white">
                        {patient.nombres} {patient.apellidos}
                      </h4>
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        DNI: {patient.dni}
                      </p>
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        Historia Clínica: {patient.historia_clinica || 'N/A'}
                      </p>
                    </div>
                    <button
                      type="button"
                      onClick={() => setValue('paciente_id', '')}
                      className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                    >
                      <XMarkIcon className="h-5 w-5" aria-hidden="true" />
                    </button>
                  </div>
                </div>
              ) : (
                <div className="mt-4">
                  <button
                    type="button"
                    onClick={() => setIsPatientModalOpen(true)}
                    className="w-full flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-700 shadow-sm text-base font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 h-12"
                  >
                    <UserIcon className="-ml-1 mr-2 h-5 w-5 text-gray-400" aria-hidden="true" />
                    Buscar o seleccionar paciente
                  </button>

                  <input
                    type="hidden"
                    {...register('paciente_id', {
                      required: 'Debe seleccionar un paciente'
                    })}
                  />
                  {errors.paciente_id && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.paciente_id.message}</p>
                  )}
                </div>
              )}

              {/* Patient Search Modal */}
              <PatientSearchModal
                isOpen={isPatientModalOpen}
                onClose={() => setIsPatientModalOpen(false)}
                onSelectPatient={handleSelectPatient}
              />
            </div>

            {/* Request Details Section */}
            <div>
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Detalles de la Solicitud
              </h3>

              <div className="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div className="sm:col-span-3">
                  <label htmlFor="fecha" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Fecha
                  </label>
                  <div className="mt-1">
                    <input
                      type="date"
                      id="fecha"
                      className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                      {...register('fecha', { required: 'La fecha es requerida' })}
                    />
                    {errors.fecha && (
                      <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.fecha.message}</p>
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
                      className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                      {...register('hora', { required: 'La hora es requerida' })}
                    />
                    {errors.hora && (
                      <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.hora.message}</p>
                    )}
                  </div>
                </div>

                <div className="sm:col-span-3">
                  <label htmlFor="servicio_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Servicio
                  </label>
                  <div className="mt-1">
                    {watch('servicio_id') ? (
                      <div className="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                          {services.find(s => s.id === parseInt(watch('servicio_id')))?.nombre || 'Servicio seleccionado'}
                        </div>
                        <button
                          type="button"
                          onClick={() => setIsServiceModalOpen(true)}
                          className="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 text-sm"
                        >
                          Cambiar
                        </button>
                      </div>
                    ) : (
                      <button
                        type="button"
                        onClick={() => setIsServiceModalOpen(true)}
                        className="w-full flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-700 shadow-sm text-base font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 h-12"
                      >
                        <BuildingOfficeIcon className="-ml-1 mr-2 h-5 w-5 text-gray-400" aria-hidden="true" />
                        Seleccionar servicio
                      </button>
                    )}
                    <input
                      type="hidden"
                      {...register('servicio_id', { required: 'El servicio es requerido' })}
                    />
                    {errors.servicio_id && (
                      <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.servicio_id.message}</p>
                    )}
                  </div>
                </div>

                {/* Service Search Modal */}
                <ServiceSearchModal
                  isOpen={isServiceModalOpen}
                  onClose={() => setIsServiceModalOpen(false)}
                  onSelectService={handleSelectService}
                />

                <div className="sm:col-span-3">
                  <label htmlFor="numero_recibo" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Número de Recibo <span className="text-gray-500 dark:text-gray-400 text-xs">(Opcional)</span>
                  </label>
                  <div className="mt-1">
                    <input
                      type="text"
                      id="numero_recibo"
                      className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                      {...register('numero_recibo')}
                      placeholder="Opcional"
                    />
                  </div>
                </div>

                <div className="sm:col-span-6">
                  <fieldset>
                    <legend className="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Atención</legend>
                    <div className="mt-2 space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-10">
                      <div className="flex items-center">
                        <input
                          id="tipo_rdr"
                          type="radio"
                          value="rdr"
                          className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-700"
                          {...register('tipo_atencion', { required: 'Debe seleccionar un tipo de atención' })}
                        />
                        <label htmlFor="tipo_rdr" className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          RDR
                        </label>
                      </div>
                      <div className="flex items-center">
                        <input
                          id="tipo_sis"
                          type="radio"
                          value="sis"
                          className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-700"
                          {...register('tipo_atencion', { required: 'Debe seleccionar un tipo de atención' })}
                          defaultChecked={true}
                        />
                        <label htmlFor="tipo_sis" className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          SIS
                        </label>
                      </div>
                      <div className="flex items-center">
                        <input
                          id="tipo_exon"
                          type="radio"
                          value="exon"
                          className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-700"
                          {...register('tipo_atencion', { required: 'Debe seleccionar un tipo de atención' })}
                        />
                        <label htmlFor="tipo_exon" className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Exonerado
                        </label>
                      </div>
                    </div>
                    {errors.tipo_atencion && (
                      <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.tipo_atencion.message}</p>
                    )}
                  </fieldset>
                </div>
              </div>
            </div>

            {/* Exams Selection Section */}
            <div>
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Exámenes
              </h3>

              <div className="mt-4">
                <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                  <div className="sm:col-span-6">
                    <div className="flex justify-between items-center mb-2">
                      <label htmlFor="exams" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Exámenes
                      </label>
                      <button
                        type="button"
                        onClick={() => setIsExamsModalOpen(true)}
                        className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                      >
                        <PlusIcon className="-ml-1 mr-1 h-4 w-4" aria-hidden="true" />
                        {selectedExams.length > 0 ? 'Cambiar exámenes' : 'Seleccionar exámenes'}
                      </button>
                    </div>
                    <div className="mt-1">
                      {selectedExams.length === 0 ? (
                        <button
                          type="button"
                          onClick={() => setIsExamsModalOpen(true)}
                          className="w-full flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-700 shadow-sm text-base font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 h-12"
                        >
                          <BeakerIcon className="-ml-1 mr-2 h-5 w-5 text-gray-400" aria-hidden="true" />
                          Seleccionar exámenes
                        </button>
                      ) : null}
                    </div>
                  </div>
                </div>

                {/* Exams Search Modal */}
                <ExamsSearchModal
                  isOpen={isExamsModalOpen}
                  onClose={() => setIsExamsModalOpen(false)}
                  onSelectExams={handleSelectExams}
                  selectedExamIds={selectedExams.map(e => e.id)}
                />

                {selectedExams.length > 0 && (
                  <div className="mt-4">
                    <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Exámenes seleccionados ({selectedExams.length})
                    </h4>
                    <ul className="mt-2 divide-y divide-gray-200 dark:divide-gray-700">
                      {selectedExams.map((exam) => (
                        <li key={exam.id} className="py-3 flex justify-between items-center">
                          <div>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                              {exam.nombre}
                            </p>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                              Categoría: {exam.categoria?.nombre || 'N/A'}
                            </p>
                          </div>
                          <button
                            type="button"
                            onClick={() => handleRemoveExam(exam.id)}
                            className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                          >
                            <XMarkIcon className="h-5 w-5" aria-hidden="true" />
                          </button>
                        </li>
                      ))}
                    </ul>
                    <div className="mt-3 flex justify-end">
                      <button
                        type="button"
                        onClick={() => setIsExamsModalOpen(true)}
                        className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-700 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                      >
                        <PlusIcon className="-ml-1 mr-1 h-4 w-4" aria-hidden="true" />
                        Agregar más exámenes
                      </button>
                    </div>
                  </div>
                )}

                <input
                  type="hidden"
                  {...register('examenes', {
                    validate: value => value.length > 0 || 'Debe seleccionar al menos un examen'
                  })}
                />
                {errors.examenes && (
                  <p className="mt-2 text-sm text-red-600 dark:text-red-500">{errors.examenes.message}</p>
                )}
              </div>
            </div>

            <div className="flex justify-end space-x-3">
              <Link
                to="/solicitudes"
                className="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-700 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                disabled={isSubmitting}
                className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? 'Guardando...' : 'Guardar'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
