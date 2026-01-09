import { useState, useEffect } from 'react';
import { useNavigate, Link, useSearchParams } from 'react-router-dom';
import { useForm, Controller } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { requestsAPI, patientsAPI, examsAPI, servicesAPI, dniAPI } from '../../services/api';
import { ArrowLeftIcon, MagnifyingGlassIcon, PlusIcon, XMarkIcon, UserIcon, BeakerIcon, BuildingOfficeIcon, PrinterIcon } from '@heroicons/react/24/outline';
import PatientSearchModal from '../../components/patients/PatientSearchModal';
import ServiceSearchModal from '../../components/services/ServiceSearchModal';
import ExamsSearchModal from '../../components/exams/ExamsSearchModal';
import RealtimeNotifications from '../../components/notifications/RealtimeNotifications';
import toast from 'react-hot-toast';
import { format } from 'date-fns';

export default function DoctorNewRequest() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const patientIdFromUrl = searchParams.get('paciente');
  const queryClient = useQueryClient();

  const [isPatientModalOpen, setIsPatientModalOpen] = useState(false);
  const [isServiceModalOpen, setIsServiceModalOpen] = useState(false);
  const [isExamsModalOpen, setIsExamsModalOpen] = useState(false);
  const [selectedExams, setSelectedExams] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [qrCode, setQrCode] = useState(null);
  const [requestId, setRequestId] = useState(null);

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

  // Fetch patient data if patientId is provided
  const { data: patientData } = useQuery(
    ['patient', pacienteId],
    () => patientsAPI.getById(pacienteId).then(res => res.data),
    {
      enabled: !!pacienteId,
      onError: (error) => {
        console.error('Error fetching patient:', error);
        toast.error('Error al cargar datos del paciente');
      }
    }
  );

  // Extract patient from the response
  const patient = patientData?.paciente;

  // Create mutation
  const createRequestMutation = useMutation(
    (requestData) => requestsAPI.createDoctorRequest(requestData),
    {
      onSuccess: (response) => {
        // Invalidate and refetch requests list
        queryClient.invalidateQueries(['doctor-requests']);
        toast.success('Solicitud creada con éxito');

        // Store the request ID for QR generation
        setRequestId(response.data.id);

        // Generate QR code
        generateQrCode(response.data.id);
      },
      onError: (error) => {
        console.error('Error creating request:', error);
        toast.error(error.response?.data?.message || 'Error al crear la solicitud');
        setIsSubmitting(false);
      }
    }
  );

  // Generate QR code mutation
  const generateQrMutation = useMutation(
    (solicitudId) => requestsAPI.generateQr(solicitudId),
    {
      onSuccess: (response) => {
        setQrCode(response.data.qr_code);
        setIsSubmitting(false);
      },
      onError: (error) => {
        console.error('Error generating QR code:', error);
        toast.error('Error al generar el código QR');
        setIsSubmitting(false);
      }
    }
  );

  // Function to generate QR code
  const generateQrCode = (solicitudId) => {
    generateQrMutation.mutate(solicitudId);
  };

  // Handle patient selection
  const handlePatientSelect = (patient) => {
    setValue('paciente_id', patient.id);
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
    setSelectedExams(exams);
    setValue('examenes', exams.map(e => e.id));
  };

  // Handle exam removal
  const handleRemoveExam = (examId) => {
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

  // Handle print request
  const handlePrintRequest = () => {
    if (!requestId) return;

    // Navigate to print page
    navigate(`/doctor/solicitudes/${requestId}/imprimir`);
  };

  return (
    <div>
      {qrCode ? (
        // Show QR code and confirmation after successful submission
        <div className="max-w-3xl mx-auto">
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div className="px-4 py-5 sm:px-6 text-center">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Solicitud creada exitosamente
              </h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                La solicitud ha sido registrada en el sistema
              </p>
            </div>
            <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
              <div className="flex flex-col items-center">
                <div className="mb-4">
                  <img src={qrCode} alt="QR Code" className="h-64 w-64" />
                </div>
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                  Este código QR contiene la información de la solicitud y puede ser escaneado por el personal de laboratorio.
                </p>
                <div className="flex space-x-4">
                  <button
                    type="button"
                    onClick={handlePrintRequest}
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  >
                    <PrinterIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                    Imprimir Solicitud
                  </button>
                  <Link
                    to="/doctor/solicitudes"
                    className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  >
                    Ver Solicitudes
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : (
        // Show form for creating a new request
        <div>
          <div className="sm:flex sm:items-center mb-6">
            <div className="sm:flex-auto">
              <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Nueva Solicitud</h1>
              <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                Crear una nueva solicitud de exámenes para un paciente
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
                          className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
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
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-md"
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
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-md"
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
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-md"
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
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-md"
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
                          className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
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
                      className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
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
                                        {exam.categoria?.nombre || 'Sin categoría'}
                                      </p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div className="ml-5 flex-shrink-0">
                                <button
                                  type="button"
                                  className="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                  onClick={() => handleRemoveExam(exam.id)}
                                >
                                  <XMarkIcon className="h-4 w-4" aria-hidden="true" />
                                </button>
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
                  disabled={isSubmitting}
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
      )}
    </div>
  );
}
