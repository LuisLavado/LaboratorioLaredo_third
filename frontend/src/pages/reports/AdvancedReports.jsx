import React, { useState, useEffect, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { reportsAPI, examsAPI } from '../../services/api';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import {
  ArrowPathIcon,
  DocumentArrowDownIcon,
  CalendarIcon,
  UserGroupIcon,
  UserIcon,
  BeakerIcon,
  ClipboardDocumentCheckIcon,
  TagIcon,
  FunnelIcon,
  XMarkIcon,
  DocumentTextIcon,
  UsersIcon
} from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';
import ServicesSearchModal from '../../components/services/ServicesSearchModal';
import ReportCharts from '../../components/charts/ReportCharts';

export default function AdvancedReports() {
  const { user, isDoctor } = useAuth();

  // Estados para los filtros
  const [dateRange, setDateRange] = useState([
    // Fecha de inicio (30 días atrás)
    {
      format: (format) => {
        const date = new Date(new Date().setDate(new Date().getDate() - 30));
        return format === 'YYYY-MM-DD'
          ? date.toISOString().split('T')[0]
          : date.toLocaleDateString();
      },
      startDate: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0]
    },
    // Fecha de fin (hoy)
    {
      format: (format) => {
        const date = new Date();
        return format === 'YYYY-MM-DD'
          ? date.toISOString().split('T')[0]
          : date.toLocaleDateString();
      },
      endDate: new Date().toISOString().split('T')[0]
    }
  ]);
  const [reportType, setReportType] = useState('general');
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedExams, setSelectedExams] = useState([]);
  const [examOptions, setExamOptions] = useState([]);
  const [showExamModal, setShowExamModal] = useState(false);
  const [tempSelectedExams, setTempSelectedExams] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');

  // Estados para servicios
  const [selectedServices, setSelectedServices] = useState([]);
  const [showServicesModal, setShowServicesModal] = useState(false);

  // Estados para doctores
  const [selectedDoctors, setSelectedDoctors] = useState([]);
  const [showDoctorsModal, setShowDoctorsModal] = useState(false);
  const [doctorOptions, setDoctorOptions] = useState([]);
  const [tempSelectedDoctors, setTempSelectedDoctors] = useState([]);
  const [doctorSearchTerm, setDoctorSearchTerm] = useState('');

  // Filtrar exámenes basados en el término de búsqueda
  const filteredExams = useMemo(() => {
    if (!searchTerm.trim()) return examOptions;

    return examOptions.filter(exam =>
      exam.label.toLowerCase().includes(searchTerm.toLowerCase())
    );
  }, [examOptions, searchTerm]);

  // Filtrar doctores basados en el término de búsqueda
  const filteredDoctors = useMemo(() => {
    if (!doctorSearchTerm.trim()) return doctorOptions;

    return doctorOptions.filter(doctor =>
      doctor.label.toLowerCase().includes(doctorSearchTerm.toLowerCase())
    );
  }, [doctorOptions, doctorSearchTerm]);

  // Manejar la selección/deselección de un examen
  const handleExamToggle = (exam) => {
    console.log('handleExamToggle called with exam:', exam);

    setTempSelectedExams(prev => {
      // Verificar si el examen ya está seleccionado
      const isSelected = prev.some(e => e.value === exam.value);
      console.log('Exam is currently selected:', isSelected);

      if (isSelected) {
        // Si ya está seleccionado, lo quitamos de la lista
        const newSelection = prev.filter(e => e.value !== exam.value);
        console.log('New selection after removal:', newSelection);
        return newSelection;
      } else {
        // Si no está seleccionado, lo añadimos a la lista
        const newSelection = [...prev, exam];
        console.log('New selection after addition:', newSelection);
        return newSelection;
      }
    });
  };

  // Manejar seleccionar/deseleccionar todos los exámenes
  const handleSelectAll = () => {
    console.log('handleSelectAll called');
    console.log('Current tempSelectedExams:', tempSelectedExams);
    console.log('Current filteredExams:', filteredExams);

    // Verificar si todos los exámenes filtrados están seleccionados
    const allSelected = filteredExams.every(exam =>
      tempSelectedExams.some(selected => selected.value === exam.value)
    );

    console.log('All exams are currently selected:', allSelected);

    if (allSelected) {
      // Si todos están seleccionados, deseleccionar los que están en la lista filtrada
      const newSelection = tempSelectedExams.filter(selected =>
        !filteredExams.some(exam => exam.value === selected.value)
      );
      console.log('New selection after deselecting all:', newSelection);
      setTempSelectedExams(newSelection);
    } else {
      // Si no todos están seleccionados, añadir los que faltan
      const newSelection = [...tempSelectedExams];

      filteredExams.forEach(exam => {
        if (!newSelection.some(selected => selected.value === exam.value)) {
          newSelection.push(exam);
        }
      });

      console.log('New selection after selecting all:', newSelection);
      setTempSelectedExams(newSelection);
    }
  };

  // Manejar la selección/deselección de un doctor
  const handleDoctorToggle = (doctor) => {
    console.log('handleDoctorToggle called with doctor:', doctor);

    setTempSelectedDoctors(prev => {
      const isSelected = prev.some(d => d.value === doctor.value);
      console.log('Doctor is currently selected:', isSelected);

      if (isSelected) {
        const newSelection = prev.filter(d => d.value !== doctor.value);
        console.log('New doctor selection after removal:', newSelection);
        return newSelection;
      } else {
        const newSelection = [...prev, doctor];
        console.log('New doctor selection after addition:', newSelection);
        return newSelection;
      }
    });
  };

  // Manejar seleccionar/deseleccionar todos los doctores
  const handleSelectAllDoctors = () => {
    console.log('handleSelectAllDoctors called');
    console.log('Current tempSelectedDoctors:', tempSelectedDoctors);
    console.log('Current filteredDoctors:', filteredDoctors);

    const allSelected = filteredDoctors.every(doctor =>
      tempSelectedDoctors.some(selected => selected.value === doctor.value)
    );

    console.log('All doctors are currently selected:', allSelected);

    if (allSelected) {
      const newSelection = tempSelectedDoctors.filter(selected =>
        !filteredDoctors.some(doctor => doctor.value === selected.value)
      );
      console.log('New doctor selection after deselecting all:', newSelection);
      setTempSelectedDoctors(newSelection);
    } else {
      const newSelection = [...tempSelectedDoctors];

      filteredDoctors.forEach(doctor => {
        if (!newSelection.some(selected => selected.value === doctor.value)) {
          newSelection.push(doctor);
        }
      });

      console.log('New doctor selection after selecting all:', newSelection);
      setTempSelectedDoctors(newSelection);
    }
  };

  // Obtener lista de exámenes para el filtro
  const { data: examsResponse, isLoading: examsLoading } = useQuery(
    ['exams-list'],
    () => examsAPI.getAll({ all: true }).then(res => res.data),
    {
      staleTime: 300000, // 5 minutos
      enabled: true // Cargar siempre para tener los datos disponibles
    }
  );

  // Actualizar opciones de exámenes cuando se carguen los datos
  useEffect(() => {
    console.log('Respuesta de exámenes:', examsResponse);

    if (examsResponse?.examenes) {
      // Filtrar exámenes activos si existe la propiedad activo
      const activeExams = Array.isArray(examsResponse.examenes)
        ? examsResponse.examenes.filter(exam => exam.activo !== false)
        : [];

      console.log('Exámenes activos:', activeExams);

      // Mapear los exámenes a opciones para el selector con información adicional
      const options = activeExams.map(exam => {
        // Crear una etiqueta para mostrar en la interfaz (solo el nombre)
        const label = exam.nombre || `Examen ID: ${exam.id}`;

        // Incluir toda la información relevante del examen
        return {
          value: exam.id,
          label: label,
          name: exam.nombre,
          code: exam.codigo,
          category: exam.categoria?.nombre || (exam.categoria_id ? `Categoría ${exam.categoria_id}` : 'Sin categoría'),
          categoria: exam.categoria?.nombre,
          categoria_id: exam.categoria_id,
          // Incluir el objeto original para acceder a todas sus propiedades
          original: exam
        };
      });

      setExamOptions(options);
      console.log('Opciones de exámenes cargadas:', options);
    }
  }, [examsResponse]);

  // Obtener lista de doctores para el filtro
  const { data: doctorsResponse, isLoading: doctorsLoading } = useQuery(
    ['doctors-list'],
    () => reportsAPI.getDoctorsList(
      new Date(new Date().setDate(new Date().getDate() - 365)).toISOString().split('T')[0],
      new Date().toISOString().split('T')[0]
    ).then(res => res.data),
    {
      staleTime: 300000, // 5 minutos
      enabled: true // Cargar siempre para tener los datos disponibles
    }
  );

  // Actualizar opciones de doctores cuando se carguen los datos
  useEffect(() => {
    console.log('🔍 DEBUG: Respuesta completa de doctores:', doctorsResponse);
    console.log('🔍 DEBUG: doctorsLoading:', doctorsLoading);

    if (doctorsResponse) {
      console.log('🔍 DEBUG: Estructura de doctorsResponse:', Object.keys(doctorsResponse));

      // Intentar diferentes estructuras de respuesta
      let doctorsList = [];

      if (doctorsResponse.doctorStats) {
        doctorsList = Array.isArray(doctorsResponse.doctorStats)
          ? doctorsResponse.doctorStats
          : [];
        console.log('🔍 DEBUG: Usando doctorStats, encontrados:', doctorsList.length);
      } else if (doctorsResponse.data && doctorsResponse.data.doctorStats) {
        doctorsList = Array.isArray(doctorsResponse.data.doctorStats)
          ? doctorsResponse.data.doctorStats
          : [];
        console.log('🔍 DEBUG: Usando data.doctorStats, encontrados:', doctorsList.length);
      } else if (Array.isArray(doctorsResponse)) {
        doctorsList = doctorsResponse;
        console.log('🔍 DEBUG: Respuesta es array directo, encontrados:', doctorsList.length);
      }

      console.log('🔍 DEBUG: Lista final de doctores:', doctorsList);

      if (doctorsList.length > 0) {
        // Mapear los doctores a opciones para el selector
        const options = doctorsList.map(doctor => {
          console.log('🔍 DEBUG: Procesando doctor:', doctor);

          const nombres = doctor.nombres || doctor.nombre || '';
          const apellidos = doctor.apellidos || doctor.apellido || '';
          const fullName = `${nombres} ${apellidos}`.trim() || `Doctor ID: ${doctor.id}`;
          const especialidad = doctor.especialidad || 'No especificada';

          return {
            value: doctor.id,
            label: `${fullName} - ${especialidad}`,
            nombres: nombres,
            apellidos: apellidos,
            fullName: fullName,
            especialidad: especialidad,
            cmp: doctor.cmp || doctor.colegiatura,
            email: doctor.email,
            // Incluir el objeto original para acceder a todas sus propiedades
            original: doctor
          };
        });

        setDoctorOptions(options);
        console.log('🔍 DEBUG: Opciones de doctores cargadas:', options);
      } else {
        console.log('🔍 DEBUG: No se encontraron doctores en la respuesta');
        setDoctorOptions([]);
      }
    } else {
      console.log('🔍 DEBUG: No hay respuesta de doctores');
    }
  }, [doctorsResponse, doctorsLoading]);

  // Inicializar tempSelectedExams cuando se abre el modal
  useEffect(() => {
    console.log('Modal visibility changed:', showExamModal);

    if (showExamModal) {
      // Hacer una copia profunda para evitar problemas de referencia
      const examsCopy = selectedExams.map(exam => ({...exam}));
      console.log('Initializing tempSelectedExams with:', examsCopy);
      setTempSelectedExams(examsCopy);
    } else {
      // Limpiar el término de búsqueda cuando se cierra el modal
      setSearchTerm('');
    }
  }, [showExamModal, selectedExams]);

  // Efecto para cambiar el tipo de reporte si el usuario es un doctor y el tipo actual es "doctors"
  useEffect(() => {
    if (isDoctor() && reportType === 'doctors') {
      setReportType('general');
      toast.info('Cambiando a reporte general');
    }
  }, [isDoctor, reportType]);

  // Efecto para limpiar exámenes seleccionados cuando se cambia el tipo de reporte
  useEffect(() => {
    if (reportType !== 'exams' && selectedExams.length > 0) {
      setSelectedExams([]);
      setTempSelectedExams([]);
    }
  }, [reportType]);

  // Efecto para limpiar servicios seleccionados cuando se cambia el tipo de reporte
  useEffect(() => {
    if (reportType !== 'services' && selectedServices.length > 0) {
      setSelectedServices([]);
    }
  }, [reportType]);

  // Efecto para limpiar doctores seleccionados cuando se cambia el tipo de reporte
  useEffect(() => {
    if (reportType !== 'doctors' && selectedDoctors.length > 0) {
      setSelectedDoctors([]);
      setTempSelectedDoctors([]);
    }
  }, [reportType]);

  // Inicializar tempSelectedDoctors cuando se abre el modal
  useEffect(() => {
    console.log('Doctor modal visibility changed:', showDoctorsModal);

    if (showDoctorsModal) {
      const doctorsCopy = selectedDoctors.map(doctor => ({...doctor}));
      console.log('Initializing tempSelectedDoctors with:', doctorsCopy);
      setTempSelectedDoctors(doctorsCopy);
    } else {
      setDoctorSearchTerm('');
    }
  }, [showDoctorsModal, selectedDoctors]);

  // Obtener datos de reportes
  const { data: reportsData, isLoading: reportsLoading, error: reportsError, refetch } = useQuery(
    ['advanced-reports', reportType, dateRange, statusFilter, selectedExams, selectedServices, selectedDoctors],
    async () => {
      // Extraer fechas correctamente del dateRange
      let startDate = null;
      let endDate = null;

      if (dateRange && dateRange.length >= 2) {
        startDate = dateRange[0]?.startDate || dateRange[0];
        endDate = dateRange[1]?.endDate || dateRange[1];
      }

      // Asegurar formato de fecha correcto (YYYY-MM-DD)
      if (startDate && typeof startDate === 'object' && startDate.toISOString) {
        startDate = startDate.toISOString().split('T')[0];
      }
      if (endDate && typeof endDate === 'object' && endDate.toISOString) {
        endDate = endDate.toISOString().split('T')[0];
      }

      const params = {
        type: reportType,
        start_date: startDate,
        end_date: endDate
      };

      if (reportType === 'results' && statusFilter) {
        params.status = statusFilter;
      }

      // Añadir filtro de exámenes solo si el tipo de reporte es 'exams' y hay seleccionados
      if (reportType === 'exams' && selectedExams.length > 0) {
        // Enviamos los IDs como un array de números
        params.exam_ids = selectedExams.map(exam => exam.value);
        console.log('Enviando exámenes seleccionados:', params.exam_ids);

        // Añadir un parámetro adicional para indicar que solo queremos estos exámenes
        params.filter_by_exams = true;

        // Añadir un parámetro para forzar el filtrado en el frontend
        params.selected_exams_only = true;
      }

      // Añadir filtro de servicios solo si el tipo de reporte es 'services' y hay seleccionados
      if (reportType === 'services' && selectedServices.length > 0) {
        // Enviamos los IDs como un array de números
        params.service_ids = selectedServices.map(service => service.id);
        console.log('Enviando servicios seleccionados:', params.service_ids);

        // Añadir un parámetro adicional para indicar que solo queremos estos servicios
        params.filter_by_services = true;

        // Añadir un parámetro para forzar el filtrado en el frontend
        params.selected_services_only = true;
      }

      // Añadir filtro de doctores solo si el tipo de reporte es 'doctors' y hay seleccionados
      if (reportType === 'doctors' && selectedDoctors.length > 0) {
        // Enviamos los IDs como un array de números
        params.doctor_ids = selectedDoctors.map(doctor => doctor.value);
        console.log('Enviando doctores seleccionados:', params.doctor_ids);

        // Añadir un parámetro adicional para indicar que solo queremos estos doctores
        params.filter_by_doctors = true;

        // Añadir un parámetro para forzar el filtrado en el frontend
        params.selected_doctors_only = true;
      }

      try {
        console.log('Solicitando reportes con parámetros:', params);
        console.log('🔍 Fechas procesadas:', { startDate, endDate, originalDateRange: dateRange });

        const response = await reportsAPI.getReports(reportType, startDate, endDate, params);
        console.log('Datos de reporte recibidos:', response.data);
        return response.data;
      } catch (error) {
        console.error('Error al obtener reportes:', error);
        toast.error('Error al cargar los datos de reportes');
        throw error;
      }
    },
    {
      refetchOnWindowFocus: false,
      staleTime: 0,
      cacheTime: 0
    }
  );

  // Generar PDF
  const generatePDF = () => {
    // Extraer fechas correctamente del dateRange
    let startDate = null;
    let endDate = null;

    if (dateRange && dateRange.length >= 2) {
      startDate = dateRange[0]?.startDate || dateRange[0];
      endDate = dateRange[1]?.endDate || dateRange[1];
    }

    // Asegurar formato de fecha correcto (YYYY-MM-DD)
    if (startDate && typeof startDate === 'object' && startDate.toISOString) {
      startDate = startDate.toISOString().split('T')[0];
    }
    if (endDate && typeof endDate === 'object' && endDate.toISOString) {
      endDate = endDate.toISOString().split('T')[0];
    }

    const params = {
      type: reportType,
      start_date: startDate,
      end_date: endDate
    };

    if (reportType === 'results' && statusFilter) {
      params.status = statusFilter;
    }

    // Añadir filtro de exámenes solo si el tipo de reporte es 'exams' y hay seleccionados
    if (reportType === 'exams' && selectedExams.length > 0) {
      // Enviamos los IDs como un array de números
      params.exam_ids = selectedExams.map(exam => exam.value);
      console.log('Enviando exámenes seleccionados para PDF:', params.exam_ids);

      // Añadir un parámetro adicional para indicar que solo queremos estos exámenes
      params.filter_by_exams = true;

      // Añadir un parámetro para forzar el filtrado en el frontend
      params.selected_exams_only = true;
    }

    // Añadir filtro de servicios solo si el tipo de reporte es 'services' y hay seleccionados
    if (reportType === 'services' && selectedServices.length > 0) {
      // Enviamos los IDs como un array de números
      params.service_ids = selectedServices.map(service => service.id);
      console.log('Enviando servicios seleccionados para PDF:', params.service_ids);

      // Añadir un parámetro adicional para indicar que solo queremos estos servicios
      params.filter_by_services = true;

      // Añadir un parámetro para forzar el filtrado en el frontend
      params.selected_services_only = true;
    }

    // Añadir filtro de doctores solo si el tipo de reporte es 'doctors' y hay seleccionados
    if (reportType === 'doctors' && selectedDoctors.length > 0) {
      // Enviamos los IDs como un array de números
      params.doctor_ids = selectedDoctors.map(doctor => doctor.value);
      console.log('Enviando doctores seleccionados para PDF:', params.doctor_ids);

      // Añadir un parámetro adicional para indicar que solo queremos estos doctores
      params.filter_by_doctors = true;

      // Añadir un parámetro para forzar el filtrado en el frontend
      params.selected_doctors_only = true;
    }

    toast.promise(
      reportsAPI.generatePDF(reportType, startDate, endDate, params),
      {
        loading: 'Generando PDF...',
        success: (response) => {
          // Crear un objeto URL para el blob
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `reporte_${reportType}_${startDate}_${endDate}.pdf`);
          document.body.appendChild(link);
          link.click();
          link.remove();
          return 'PDF generado correctamente';
        },
        error: 'Error al generar PDF'
      }
    );
  };

  // Generar Excel
  const generateExcel = () => {
    // Extraer fechas correctamente del dateRange
    let startDate = null;
    let endDate = null;

    if (dateRange && dateRange.length >= 2) {
      startDate = dateRange[0]?.startDate || dateRange[0];
      endDate = dateRange[1]?.endDate || dateRange[1];
    }

    // Asegurar formato de fecha correcto (YYYY-MM-DD)
    if (startDate && typeof startDate === 'object' && startDate.toISOString) {
      startDate = startDate.toISOString().split('T')[0];
    }
    if (endDate && typeof endDate === 'object' && endDate.toISOString) {
      endDate = endDate.toISOString().split('T')[0];
    }

    const params = {
      type: reportType,
      start_date: startDate,
      end_date: endDate
    };

    if (reportType === 'results' && statusFilter) {
      params.status = statusFilter;
    }

    // Añadir filtro de exámenes solo si el tipo de reporte es 'exams' y hay seleccionados
    if (reportType === 'exams' && selectedExams.length > 0) {
      // Enviamos los IDs como un array de números
      params.exam_ids = selectedExams.map(exam => exam.value);
      console.log('Enviando exámenes seleccionados para Excel:', params.exam_ids);

      // Añadir un parámetro adicional para indicar que solo queremos estos exámenes
      params.filter_by_exams = true;

      // Añadir un parámetro para forzar el filtrado en el frontend
      params.selected_exams_only = true;
    }

    // Añadir filtro de servicios solo si el tipo de reporte es 'services' y hay seleccionados
    if (reportType === 'services' && selectedServices.length > 0) {
      // Enviamos los IDs como un array de números
      params.service_ids = selectedServices.map(service => service.id);
      console.log('Enviando servicios seleccionados para Excel:', params.service_ids);

      // Añadir un parámetro adicional para indicar que solo queremos estos servicios
      params.filter_by_services = true;

      // Añadir un parámetro para forzar el filtrado en el frontend
      params.selected_services_only = true;
    }

    // Añadir filtro de doctores solo si el tipo de reporte es 'doctors' y hay seleccionados
    if (reportType === 'doctors' && selectedDoctors.length > 0) {
      // Enviamos los IDs como un array de números
      params.doctor_ids = selectedDoctors.map(doctor => doctor.value);
      console.log('Enviando doctores seleccionados para Excel:', params.doctor_ids);

      // Añadir un parámetro adicional para indicar que solo queremos estos doctores
      params.filter_by_doctors = true;

      // Añadir un parámetro para forzar el filtrado en el frontend
      params.selected_doctors_only = true;
    }

    toast.promise(
      reportsAPI.generateExcel(reportType, startDate, endDate, params),
      {
        loading: 'Generando Excel...',
        success: (response) => {
          // Crear un objeto URL para el blob
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `reporte_${reportType}_${startDate}_${endDate}.xlsx`);
          document.body.appendChild(link);
          link.click();
          link.remove();
          return 'Excel generado correctamente';
        },
        error: 'Error al generar Excel'
      }
    );
  };




  // Renderizar el contenido según el tipo de reporte
  const renderReportContent = () => {
    // Depuración: Mostrar los datos recibidos en la consola
    console.log('Renderizando reporte con datos:', reportsData);

    if (reportsLoading) {
      return (
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
        </div>
      );
    }

    if (reportsError) {
      console.error('Error en el reporte:', reportsError);
      return (
        <div className="bg-red-100 dark:bg-red-900/30 p-4 rounded-lg text-red-700 dark:text-red-300">
          Error al cargar los datos: {reportsError.message}
        </div>
      );
    }

    if (!reportsData?.data) {
      return (
        <div className="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-lg text-yellow-700 dark:text-yellow-300">
          No hay datos disponibles para el período seleccionado
        </div>
      );
    }

    // Renderizar según el tipo de reporte
    switch (reportType) {
      case 'general':
        return renderGeneralReport(reportsData.data);
      case 'exams':
        return renderExamsReport(reportsData.data);
      case 'services':
        return renderServicesReport(reportsData.data);
      case 'doctors':
        return renderDoctorsReport(reportsData.data);
      case 'patients':
        return renderPatientsReport(reportsData.data);
      case 'results':
        return renderResultsReport(reportsData.data);
      case 'categories':
        return <CategoriesReport data={reportsData.data} />;
      default:
        return (
          <div className="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-lg text-yellow-700 dark:text-yellow-300">
            Tipo de reporte no soportado
          </div>
        );
    }
  };

  // Renderizar reporte general
  const renderGeneralReport = (data) => {
    return (
      <div className="space-y-6">
        {/* Gráficos con datos procesados */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
              Gráficos Estadísticos Generales
            </h3>
            <ReportCharts
              reportType={reportType}
              startDate={dateRange && dateRange[0] ? dateRange[0].startDate : null}
              endDate={dateRange && dateRange[1] ? dateRange[1].endDate : null}
              reportsData={{ data: data }}
            />
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Resumen */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Resumen
            </h3>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
              <div className="bg-primary-50 dark:bg-primary-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-md p-3">
                      <CalendarIcon className="h-6 w-6 text-primary-600 dark:text-primary-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Solicitudes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalRequests || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
              <div className="bg-indigo-50 dark:bg-indigo-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-indigo-100 dark:bg-indigo-800 rounded-md p-3">
                      <UserGroupIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Pacientes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalPatients || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
              <div className="bg-green-50 dark:bg-green-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-green-100 dark:bg-green-800 rounded-md p-3">
                      <BeakerIcon className="h-6 w-6 text-green-600 dark:text-green-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Exámenes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalExams || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Distribución por Estado */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Distribución por Estado
            </h3>
            <div className="grid grid-cols-3 gap-6 mb-6">
              <div className="bg-amber-100 dark:bg-amber-900/30 p-6 rounded-lg text-center min-h-[120px] flex flex-col justify-center items-center">
                <div className="text-amber-600 dark:text-amber-400 text-3xl font-bold mb-2">{data.pendingCount || 0}</div>
                <div className="text-gray-600 dark:text-gray-400 text-sm font-medium whitespace-nowrap">Pendientes</div>
              </div>
              <div className="bg-blue-100 dark:bg-blue-900/30 p-6 rounded-lg text-center min-h-[120px] flex flex-col justify-center items-center">
                <div className="text-blue-600 dark:text-blue-400 text-3xl font-bold mb-2">{data.inProcessCount || 0}</div>
                <div className="text-gray-600 dark:text-gray-400 text-sm font-medium whitespace-nowrap">En Proceso</div>
              </div>
              <div className="bg-green-100 dark:bg-green-900/30 p-6 rounded-lg text-center min-h-[120px] flex flex-col justify-center items-center">
                <div className="text-green-600 dark:text-green-400 text-3xl font-bold mb-2">{data.completedCount || 0}</div>
                <div className="text-gray-600 dark:text-gray-400 text-sm font-medium whitespace-nowrap">Completados</div>
              </div>
            </div>
          </div>
        </div>

        {/* Tabla de datos */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-2">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Datos Diarios
            </h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Solicitudes</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pacientes</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Exámenes</th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {data.dailyStats?.length > 0 ? (
                    data.dailyStats.map((stat, index) => (
                      <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{stat.date}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{stat.count}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{stat.patientCount}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{stat.examCount}</td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="4" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No hay datos disponibles para el período seleccionado
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
        </div>
      </div>
    );
  };

  // Renderizar exámenes seleccionados
  const renderSelectedExams = () => {
    if (!selectedExams || selectedExams.length === 0) {
      return null;
    }

    // Función para agrupar exámenes duplicados por nombre y sumar sus counts
    const groupExamsByName = (examStats) => {
      const grouped = {};

      examStats.forEach(exam => {
        const key = exam.name || exam.codigo || `exam_${exam.id}`;

        if (grouped[key]) {
          // Si ya existe, sumar el count
          grouped[key].count += exam.count || 0;
        } else {
          // Si no existe, crear nuevo registro
          grouped[key] = {
            ...exam,
            count: exam.count || 0
          };
        }
      });

      return Object.values(grouped);
    };

    // Obtener los datos filtrados para los exámenes seleccionados
    let examStatsData = [];
    if (reportsData?.data?.examStats) {
      // Aplicar agrupación para evitar duplicados
      examStatsData = groupExamsByName(reportsData.data.examStats);
    }

    return (
      <div className="mt-4 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4 flex justify-between items-center">
            <span>Exámenes seleccionados ({selectedExams.length})</span>
            <button
              onClick={() => setShowExamModal(true)}
              className="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
            >
              Editar selección
            </button>
          </h3>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th scope="col" className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Examen
                  </th>
                  <th scope="col" className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Cantidad
                  </th>
                  <th scope="col" className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Porcentaje
                  </th>
                  <th scope="col" className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Categoría
                  </th>
                  <th scope="col" className="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    <span className="sr-only">Acciones</span>
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {selectedExams.map((exam, index) => {
                  // Buscar datos reales del examen en los datos del reporte
                  const examData = examStatsData.find(stat =>
                    Number(stat.id) === Number(exam.value) || stat.name === exam.name
                  );

                  // Usar los datos de estadísticas si existen, o generar valores simulados
                  const count = examData?.count || exam.count || '-';
                  const percentage = examData?.percentage !== undefined && examData?.percentage !== null
  ? examData.percentage
  : '-';
                  const category = examData?.category || exam.category || exam.categoria || 'BIOQUÍMICA';

                  return (
                    <tr key={exam.value} className={index % 2 === 0 ? "bg-white dark:bg-gray-800" : "bg-gray-50 dark:bg-gray-700"}>
                      <td className="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {exam.name || exam.nombre || exam.label}
                      </td>
                      <td className="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {count}
                      </td>
                      <td className="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {percentage !== '-' ? `${percentage}%` : '-'}
                      </td>
                      <td className="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {category}
                      </td>
                      <td className="px-3 py-2 whitespace-nowrap text-sm text-right">
                        <button
                          type="button"
                          onClick={() => {
                            const newSelectedExams = selectedExams.filter(
                              (e) => e.value !== exam.value
                            );
                            setSelectedExams(newSelectedExams);

                            // Si no quedan exámenes seleccionados, refrescar los datos
                            if (newSelectedExams.length === 0) {
                              toast.loading('Actualizando datos...', { id: 'refresh-toast' });
                              setTimeout(() => {
                                refetch().then(() => {
                                  toast.success('Datos actualizados', { id: 'refresh-toast' });
                                }).catch(() => {
                                  toast.error('Error al actualizar datos', { id: 'refresh-toast' });
                                });
                              }, 300);
                            }
                          }}
                          className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                        >
                          <XMarkIcon className="h-5 w-5" />
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    );
  };

  // Renderizar reporte por exámenes
  const renderExamsReport = (data) => {
    console.log('Renderizando reporte de exámenes con datos:', data);

    // Función para agrupar exámenes duplicados por nombre y sumar sus counts
    const groupExamsByName = (examStats) => {
      const grouped = {};

      examStats.forEach(exam => {
        const key = exam.name || exam.codigo || `exam_${exam.id}`;

        if (grouped[key]) {
          // Si ya existe, sumar el count
          grouped[key].count += exam.count || 0;
        } else {
          // Si no existe, crear nuevo registro
          grouped[key] = {
            ...exam,
            count: exam.count || 0
          };
        }
      });

      return Object.values(grouped);
    };

    // Filtrar los datos si hay exámenes seleccionados
    let filteredData = { ...data };

    if (selectedExams.length > 0 && data.examStats) {
      console.log('Filtrando datos para exámenes seleccionados:', selectedExams.map(e => e.value));

      // Verificar si el backend ya filtró los datos
      if (data.selected_exams_only) {
        console.log('El backend ya filtró los datos');
        // Agrupar exámenes duplicados antes de usar los datos
        filteredData = {
          ...data,
          examStats: groupExamsByName(data.examStats)
        };
      } else {
        // Filtrar examStats para incluir solo los exámenes seleccionados
        const selectedIds = selectedExams.map(exam => exam.value);
        console.log('IDs de exámenes seleccionados:', selectedIds);
        console.log('Exámenes disponibles en los datos:', data.examStats);

        // Convertir IDs a números para comparación consistente
        const numericSelectedIds = selectedIds.map(id => Number(id));

        filteredData.examStats = data.examStats.filter(stat => {
          // Convertir el ID del stat a número para comparación consistente
          const statId = Number(stat.id);

          // Intentar hacer coincidir por ID o por nombre
          const matchById = numericSelectedIds.includes(statId);
          const matchByName = selectedExams.some(exam => exam.name === stat.name);

          console.log(`Examen ${stat.name} (ID: ${stat.id}): matchById=${matchById}, matchByName=${matchByName}`);

          return matchById || matchByName;
        });

        // Agrupar exámenes filtrados para evitar duplicados
        filteredData.examStats = groupExamsByName(filteredData.examStats);

        console.log('Estadísticas filtradas y agrupadas:', filteredData.examStats);

        // Recalcular el total de exámenes basado en los exámenes filtrados
        filteredData.totalExams = filteredData.examStats.reduce((sum, stat) => sum + stat.count, 0);
      }

      // Si no hay datos después de filtrar, mostrar mensaje
      if (!filteredData.examStats || filteredData.examStats.length === 0) {
        console.log('No hay datos para los exámenes seleccionados después de filtrar');
        return (
          <div className="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-lg text-yellow-700 dark:text-yellow-300">
            No hay datos disponibles para los exámenes seleccionados en el período indicado.
            <div className="mt-2">
              <button
                onClick={() => {
                  setSelectedExams([]);
                  toast.loading('Actualizando datos...', { id: 'refresh-toast' });
                  setTimeout(() => {
                    refetch().then(() => {
                      toast.success('Datos actualizados', { id: 'refresh-toast' });
                    }).catch(() => {
                      toast.error('Error al actualizar datos', { id: 'refresh-toast' });
                    });
                  }, 300);
                }}
                className="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
              >
                Limpiar selección y mostrar todos los exámenes
              </button>
            </div>
          </div>
        );
      }
    }

    // Verificar si tenemos datos válidos
    if (!filteredData || !filteredData.examStats || filteredData.examStats.length === 0) {
      console.log('No hay datos para mostrar');
      return (
        <div className="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-lg text-yellow-700 dark:text-yellow-300">
          No hay datos disponibles para el período indicado.
        </div>
      );
    }

    // Agrupar exámenes para evitar duplicados cuando no hay filtros específicos
    if (selectedExams.length === 0 && filteredData.examStats) {
      filteredData.examStats = groupExamsByName(filteredData.examStats);
    }

    // Calcular el total de exámenes
    const totalExams = filteredData.totalExams ||
                      (filteredData.examStats?.reduce((sum, stat) => sum + stat.count, 0) || 0);

    // Procesar datos de distribución diaria si existen
    const dailyStats = filteredData.dailyStats || filteredData.examTimeStats || [];

    return (
      <div className="space-y-6">
        {/* Gráficos con datos procesados */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
              Gráficos Estadísticos de Exámenes
            </h3>
            <ReportCharts
              reportType={reportType}
              startDate={dateRange && dateRange[0] ? dateRange[0].startDate : null}
              endDate={dateRange && dateRange[1] ? dateRange[1].endDate : null}
              reportsData={{ data: filteredData }}
            />
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Resumen (solo se muestra si no hay exámenes seleccionados) */}
        {selectedExams.length === 0 && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                Resumen de Exámenes
              </h3>
              <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div className="bg-primary-50 dark:bg-primary-900/30 overflow-hidden shadow rounded-lg">
                  <div className="px-4 py-5 sm:p-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-md p-3">
                        <BeakerIcon className="h-6 w-6 text-primary-600 dark:text-primary-300" aria-hidden="true" />
                      </div>
                      <div className="ml-5 w-0 flex-1">
                        <dl>
                          <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Total Exámenes
                          </dt>
                          <dd>
                            <div className="text-lg font-medium text-gray-900 dark:text-white">
                              {totalExams}
                            </div>
                          </dd>
                        </dl>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="bg-indigo-50 dark:bg-indigo-900/30 overflow-hidden shadow rounded-lg">
                  <div className="px-4 py-5 sm:p-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 bg-indigo-100 dark:bg-indigo-800 rounded-md p-3">
                        <UserGroupIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-300" aria-hidden="true" />
                      </div>
                      <div className="ml-5 w-0 flex-1">
                        <dl>
                          <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Examenes Unicos
                          </dt>
                          <dd>
                            <div className="text-lg font-medium text-gray-900 dark:text-white">
                              {filteredData.uniqueExams || 0}
                            </div>
                          </dd>
                        </dl>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="bg-green-50 dark:bg-green-900/30 overflow-hidden shadow rounded-lg">
                  <div className="px-4 py-5 sm:p-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 bg-green-100 dark:bg-green-800 rounded-md p-3">
                        <ClipboardDocumentCheckIcon className="h-6 w-6 text-green-600 dark:text-green-300" aria-hidden="true" />
                      </div>
                      <div className="ml-5 w-0 flex-1">
                        <dl>
                          <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Período
                          </dt>
                          <dd>
                            <div className="text-lg font-medium text-gray-900 dark:text-white">
                              {dateRange && dateRange[0] && dateRange[1]
                                ? `${dateRange[0].startDate} a ${dateRange[1].endDate}`
                                : 'Período completo'}
                            </div>
                          </dd>
                        </dl>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* No mostramos la lista de exámenes seleccionados aquí para evitar duplicación */}

        {/* Contenido del reporte de exámenes - solo se muestra si no hay exámenes seleccionados o si no se está mostrando la tabla de exámenes seleccionados */}
        {filteredData.examStats && filteredData.examStats.length > 0 && selectedExams.length === 0 && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                Exámenes más solicitados
              </h3>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead className="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Examen</th>
                      <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cantidad</th>
                      <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Porcentaje</th>
                      <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Categoría</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    {filteredData.examStats.map((stat, index) => {
                      // Recalcular porcentaje basado en el total agrupado
                      const percentage = totalExams > 0 ? ((stat.count / totalExams) * 100).toFixed(1) : 0;
                      return (
                        <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                          <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{stat.name}</td>
                          <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{stat.count}</td>
                          <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{percentage}%</td>
                          <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{stat.categoria || 'N/A'}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* Distribución diaria */}
        {dailyStats.length > 0 && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                Distribución por Tiempo
              </h3>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead className="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pendientes</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">En Proceso</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Completados</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    {dailyStats.map((stat, index) => (
                      <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                          {stat.date || stat.fecha || `Día ${index + 1}`}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {stat.pendiente || 0}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {stat.en_proceso || 0}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {stat.completado || stat.count || 0}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {(stat.pendiente || 0) + (stat.en_proceso || 0) + (stat.completado || stat.count || 0)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* Tiempos promedio de procesamiento */}
        {data.processingTimeStats && data.processingTimeStats.length > 0 && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                Tiempo Promedio de Procesamiento
              </h3>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead className="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Examen</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tiempo Promedio (horas)</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    {data.processingTimeStats.map((stat, index) => (
                      <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                          {stat.examen?.nombre || stat.name || `Examen ID: ${stat.examen_id || index}`}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          {parseFloat(stat.avg_hours).toFixed(2)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}
        </div>
      </div>
    );
  };

  // Renderizar reporte por servicios
  const renderServicesReport = (data) => {
    return (
      <div className="space-y-6">
        {/* Gráficos con datos procesados */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
              Gráficos Estadísticos de Servicios
            </h3>
            <ReportCharts
              reportType={reportType}
              startDate={dateRange && dateRange[0] ? dateRange[0].startDate : null}
              endDate={dateRange && dateRange[1] ? dateRange[1].endDate : null}
              reportsData={{ data: data }}
            />
          </div>
        </div>

        {/* Tabla principal de servicios */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Servicios más solicitados
            </h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Servicio</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cantidad</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Porcentaje</th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {data.serviceStats?.length > 0 ? (
                    data.serviceStats.map((stat, index) => (
                      <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{stat.name}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{stat.count}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{stat.percentage}%</td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="3" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No hay datos disponibles para el período seleccionado
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Exámenes por servicio */}
        {data.serviceStats?.length > 0 && data.topExamsByService && (
          <div className="space-y-4">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              Exámenes más solicitados por servicio
            </h3>
            {data.serviceStats.map((service, serviceIndex) => {
              const serviceExams = data.topExamsByService[service.id];
              if (!serviceExams || serviceExams.length === 0) return null;

              return (
                <div key={serviceIndex} className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                  <div className="px-4 py-5 sm:p-6">
                    <h4 className="text-md leading-6 font-medium text-gray-900 dark:text-white mb-4">
                      {service.name} ({service.count} solicitudes)
                    </h4>
                    <div className="overflow-x-auto">
                      <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-700">
                          <tr>
                            <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Examen</th>
                            <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Categoría</th>
                            <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cantidad</th>
                          </tr>
                        </thead>
                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                          {serviceExams.map((exam, examIndex) => (
                            <tr key={examIndex} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                              <td className="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{exam.name}</td>
                              <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{exam.category}</td>
                              <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{exam.count}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    );
  };

  // Renderizar reporte por doctores
  const renderDoctorsReport = (data) => {
    console.log('Renderizando reporte de doctores con datos:', data);

    return (
      <div className="space-y-6">
        {/* Gráficos con datos procesados */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
              Gráficos Estadísticos de Doctores
            </h3>
            <ReportCharts
              reportType={reportType}
              startDate={dateRange && dateRange[0] ? dateRange[0].startDate : null}
              endDate={dateRange && dateRange[1] ? dateRange[1].endDate : null}
              reportsData={{ data: data }}
            />
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Resumen de datos */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Resumen General
            </h3>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
              <div className="bg-primary-50 dark:bg-primary-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-md p-3">
                      <UserGroupIcon className="h-6 w-6 text-primary-600 dark:text-primary-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Usuarios
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalDoctors || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
              <div className="bg-indigo-50 dark:bg-indigo-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-indigo-100 dark:bg-indigo-800 rounded-md p-3">
                      <DocumentTextIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Solicitudes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalRequests || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
              <div className="bg-green-50 dark:bg-green-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-green-100 dark:bg-green-800 rounded-md p-3">
                      <UsersIcon className="h-6 w-6 text-green-600 dark:text-green-300" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Pacientes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalPatients || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Contenido del reporte de doctores */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Solicitudes por Usuario 
            </h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usuario</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Especialidad/Rol</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Colegiatura</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Solicitudes</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {data.doctorStats?.length > 0 ? (
                    data.doctorStats.map((stat, index) => {
                      // Calcular el porcentaje respecto al total de solicitudes
                      const totalSolicitudes = data.totalRequests || 1;
                      const porcentaje = ((stat.total_solicitudes / totalSolicitudes) * 100).toFixed(1);
                      
                      return (
                        <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {`${stat.nombres || ''} ${stat.apellidos || ''}`}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {stat.especialidad || stat.role_sistema || 'No especificada'}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {stat.cmp || 'No especificada'}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {stat.total_solicitudes} ({porcentaje}%)
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {stat.email || 'No especificado'}
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td colSpan="5" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No hay datos disponibles para el período seleccionado
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
        </div>
      </div>
    );
  };

  // Renderizar reporte por pacientes
  const renderPatientsReport = (data) => {
    // Los datos vienen como un objeto con la estructura completa del reporte
    const patients = data?.patients || [];

    return (
      <div className="space-y-6">
        {/* Gráficos con datos procesados */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
              Gráficos Estadísticos de Pacientes
            </h3>
            <ReportCharts
              reportType={reportType}
              startDate={dateRange && dateRange[0] ? dateRange[0].startDate : null}
              endDate={dateRange && dateRange[1] ? dateRange[1].endDate : null}
              reportsData={{ data: data }}
            />
          </div>
        </div>

        {/* Resumen estadístico */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-4">
          <div className="bg-blue-50 dark:bg-blue-900/30 overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-blue-100 dark:bg-blue-800 rounded-md p-3">
                  <UserGroupIcon className="h-6 w-6 text-blue-600 dark:text-blue-300" aria-hidden="true" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Pacientes
                    </dt>
                    <dd>
                      <div className="text-lg font-medium text-gray-900 dark:text-white">
                        {patients.length}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
          
          <div className="bg-green-50 dark:bg-green-900/30 overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-green-100 dark:bg-green-800 rounded-md p-3">
                  <DocumentTextIcon className="h-6 w-6 text-green-600 dark:text-green-300" aria-hidden="true" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Solicitudes
                    </dt>
                    <dd>
                      <div className="text-lg font-medium text-gray-900 dark:text-white">
                        {patients.reduce((total, patient) => total + (patient.total_solicitudes || 0), 0)}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
          
          <div className="bg-purple-50 dark:bg-purple-900/30 overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-purple-100 dark:bg-purple-800 rounded-md p-3">
                  <BeakerIcon className="h-6 w-6 text-purple-600 dark:text-purple-300" aria-hidden="true" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Exámenes
                    </dt>
                    <dd>
                      <div className="text-lg font-medium text-gray-900 dark:text-white">
                        {patients.reduce((total, patient) => total + (patient.total_examenes || 0), 0)}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
          
          <div className="bg-orange-50 dark:bg-orange-900/30 overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-orange-100 dark:bg-orange-800 rounded-md p-3">
                  <ClipboardDocumentCheckIcon className="h-6 w-6 text-orange-600 dark:text-orange-300" aria-hidden="true" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Exámenes Completados
                    </dt>
                    <dd>
                      <div className="text-lg font-medium text-gray-900 dark:text-white">
                        {patients.reduce((total, patient) => total + (patient.examenes_completados || 0), 0)}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Tabla de pacientes */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Pacientes Detallados
            </h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Paciente
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      DNI
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Edad
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Sexo
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Solicitudes
                    </th>
 
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Última Visita
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Estado
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {patients.length > 0 ? (
                    patients.map((patient, index) => (
                      <tr key={patient.id || index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center">
                            <div className="flex-shrink-0 h-10 w-10">
                              <div className="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <UserIcon className="h-6 w-6 text-gray-500 dark:text-gray-400" />
                              </div>
                            </div>
                            <div className="ml-4">
                              <div className="text-sm font-medium text-gray-900 dark:text-white">
                                {patient.nombres} {patient.apellidos}
                              </div>
                              <div className="text-sm text-gray-500 dark:text-gray-400">
                                HC: {patient.historia_clinica || 'N/A'}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                          {patient.documento || 'N/A'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                          {patient.edad || 'N/A'} años
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            patient.sexo === 'masculino' 
                              ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                              : 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300'
                          }`}>
                            {patient.sexo === 'masculino' ? 'M' : 'F'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                            {patient.total_solicitudes || 0}
                          </span>
                        </td>

                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                          {patient.ultima_visita ? new Date(patient.ultima_visita).toLocaleDateString() : 'N/A'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            patient.examenes_pendientes > 0
                              ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                              : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                          }`}>
                            {patient.examenes_pendientes > 0 ? 'Con pendientes' : 'Al día'}
                          </span>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="8" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No hay datos disponibles para el período seleccionado
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Detalles expandibles de solicitudes (opcional) */}
        {patients.length > 0 && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                Solicitudes Detalladas por Paciente
              </h3>
              <div className="space-y-6">
                {patients.slice(0, 3).map((patient) => ( // Mostrar solo los primeros 3 para no sobrecargar
                  <div key={patient.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h4 className="text-md font-medium text-gray-900 dark:text-white mb-3">
                      {patient.nombres} {patient.apellidos} - {patient.documento}
                    </h4>
                    <div className="space-y-2">
                      {patient.solicitudes_detalle?.slice(0, 5).map((solicitud) => ( // Mostrar solo las primeras 5 solicitudes
                        <div key={solicitud.id} className="bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                          <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-medium text-gray-900 dark:text-white">
                              Solicitud #{solicitud.id} - {solicitud.fecha}
                            </span>
                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                              solicitud.estado_solicitud === 'completado'
                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                            }`}>
                              {solicitud.estado_solicitud}
                            </span>
                          </div>
                          <div className="text-sm text-gray-600 dark:text-gray-400">
                            <p>Servicio: {solicitud.servicio}</p>
                            <p>Médico: {solicitud.medico_solicitante}</p>
                            <p>Exámenes: {solicitud.examenes?.map(e => e.nombre).join(', ')}</p>
                          </div>
                        </div>
                      ))}
                      {patient.solicitudes_detalle?.length > 5 && (
                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center">
                          ... y {patient.solicitudes_detalle.length - 5} solicitudes más
                        </p>
                      )}
                    </div>
                  </div>
                ))}
                {patients.length > 3 && (
                  <p className="text-sm text-gray-500 dark:text-gray-400 text-center">
                    ... y {patients.length - 3} pacientes más
                  </p>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    );
  };
  // Renderizar reporte por resultados
  const renderResultsReport = (data) => {
    // Procesar los datos de processingTimeStats para adaptarlos al formato esperado
    const processedTimeStats = Array.isArray(data.processingTimeStats)
      ? data.processingTimeStats.map(stat => ({
          name: stat.range || stat.examen?.nombre || `Rango ID: ${stat.examen_id || 'N/A'}`,
          avg_hours: stat.avg_hours || 0,
          count: stat.count || 0
        }))
      : [];

    return (
      <div className="space-y-6">
        {/* Resumen de estadísticas principales */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
              📊 Resumen Ejecutivo de Resultados
            </h3>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
              <div className="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 overflow-hidden shadow-lg rounded-lg border border-blue-200 dark:border-blue-700">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-blue-500 rounded-md p-3">
                      <BeakerIcon className="h-6 w-6 text-white" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-blue-600 dark:text-blue-300 truncate">
                          Total Exámenes
                        </dt>
                        <dd>
                          <div className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                            {data.totalExams || 0}
                          </div>
                          <div className="text-xs text-blue-500 dark:text-blue-400">
                            Exámenes procesados
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>

              <div className="bg-gradient-to-r from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/30 overflow-hidden shadow-lg rounded-lg border border-amber-200 dark:border-amber-700">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-amber-500 rounded-md p-3">
                      <ClipboardDocumentCheckIcon className="h-6 w-6 text-white" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-amber-600 dark:text-amber-300 truncate">
                          Pendientes
                        </dt>
                        <dd>
                          <div className="text-2xl font-bold text-amber-900 dark:text-amber-100">
                            {data.pendingCount || 0}
                          </div>
                          <div className="text-xs text-amber-500 dark:text-amber-400">
                            {data.totalExams > 0 ? `${Math.round((data.pendingCount / data.totalExams) * 100)}%` : '0%'} del total
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>

              <div className="bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 overflow-hidden shadow-lg rounded-lg border border-indigo-200 dark:border-indigo-700">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                      <ClipboardDocumentCheckIcon className="h-6 w-6 text-white" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-indigo-600 dark:text-indigo-300 truncate">
                          En Proceso
                        </dt>
                        <dd>
                          <div className="text-2xl font-bold text-indigo-900 dark:text-indigo-100">
                            {data.inProcessCount || 0}
                          </div>
                          <div className="text-xs text-indigo-500 dark:text-indigo-400">
                            {data.totalExams > 0 ? `${Math.round((data.inProcessCount / data.totalExams) * 100)}%` : '0%'} del total
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>

              <div className="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 overflow-hidden shadow-lg rounded-lg border border-green-200 dark:border-green-700">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-green-500 rounded-md p-3">
                      <ClipboardDocumentCheckIcon className="h-6 w-6 text-white" aria-hidden="true" />
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-green-600 dark:text-green-300 truncate">
                          Completados
                        </dt>
                        <dd>
                          <div className="text-2xl font-bold text-green-900 dark:text-green-100">
                            {data.completedCount || 0}
                          </div>
                          <div className="text-xs text-green-500 dark:text-green-400">
                            {data.totalExams > 0 ? `${Math.round((data.completedCount / data.totalExams) * 100)}%` : '0%'} del total
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Gráficos estadísticos */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
              📈 Análisis Visual de Resultados
            </h3>
            <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
              <ReportCharts
                reportType={reportType}
                startDate={dateRange && dateRange[0] ? dateRange[0].startDate : null}
                endDate={dateRange && dateRange[1] ? dateRange[1].endDate : null}
                reportsData={{ data: data }}
              />
            </div>
          </div>
        </div>

        {/* Distribución diaria */}
        {data.dailyStats && data.dailyStats.length > 0 && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
                📅 Distribución Diaria de Resultados
              </h3>
              <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                    <thead className="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700">
                      <tr>
                        <th scope="col" className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                          📅 Fecha
                        </th>
                        <th scope="col" className="px-6 py-4 text-center text-sm font-semibold text-amber-600 dark:text-amber-300 uppercase tracking-wider">
                          ⏳ Pendientes
                        </th>
                        <th scope="col" className="px-6 py-4 text-center text-sm font-semibold text-indigo-600 dark:text-indigo-300 uppercase tracking-wider">
                          🔄 En Proceso
                        </th>
                        <th scope="col" className="px-6 py-4 text-center text-sm font-semibold text-green-600 dark:text-green-300 uppercase tracking-wider">
                          ✅ Completados
                        </th>
                        <th scope="col" className="px-6 py-4 text-center text-sm font-semibold text-blue-600 dark:text-blue-300 uppercase tracking-wider">
                          📊 Total
                        </th>
                      </tr>
                    </thead>
                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                      {data.dailyStats.map((stat, index) => {
                        const total = stat.count || 0;
                        const pendingPerc = total > 0 ? Math.round((stat.pending / total) * 100) : 0;
                        const processPerc = total > 0 ? Math.round((stat.in_process / total) * 100) : 0;
                        const completedPerc = total > 0 ? Math.round((stat.completed / total) * 100) : 0;

                        return (
                          <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                              {new Date(stat.date).toLocaleDateString('es-ES', {
                                weekday: 'short',
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                              })}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-center">
                              <div className="text-sm font-semibold text-amber-600 dark:text-amber-300">
                                {stat.pending || 0}
                              </div>
                              <div className="text-xs text-amber-500 dark:text-amber-400">
                                {pendingPerc}%
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-center">
                              <div className="text-sm font-semibold text-indigo-600 dark:text-indigo-300">
                                {stat.in_process || 0}
                              </div>
                              <div className="text-xs text-indigo-500 dark:text-indigo-400">
                                {processPerc}%
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-center">
                              <div className="text-sm font-semibold text-green-600 dark:text-green-300">
                                {stat.completed || 0}
                              </div>
                              <div className="text-xs text-green-500 dark:text-green-400">
                                {completedPerc}%
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-center">
                              <div className="text-sm font-bold text-blue-600 dark:text-blue-300">
                                {total}
                              </div>
                              <div className="text-xs text-blue-500 dark:text-blue-400">
                                100%
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Distribución de tiempos de procesamiento */}
        {processedTimeStats.length > 0 && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
                ⏱️ Análisis de Tiempos de Procesamiento
              </h3>
              <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                  {processedTimeStats.map((stat, index) => {
                    const totalExams = processedTimeStats.reduce((sum, s) => sum + s.count, 0);
                    const percentage = totalExams > 0 ? Math.round((stat.count / totalExams) * 100) : 0;

                    // Determinar color basado en el rango de tiempo
                    let colorClass = 'from-gray-50 to-gray-100 border-gray-200';
                    let iconColor = 'text-gray-500';
                    let textColor = 'text-gray-700';

                    if (stat.name.includes('0-2') || stat.name.includes('rápido')) {
                      colorClass = 'from-green-50 to-green-100 border-green-200';
                      iconColor = 'text-green-500';
                      textColor = 'text-green-700';
                    } else if (stat.name.includes('2-6') || stat.name.includes('medio')) {
                      colorClass = 'from-yellow-50 to-yellow-100 border-yellow-200';
                      iconColor = 'text-yellow-500';
                      textColor = 'text-yellow-700';
                    } else if (stat.name.includes('6+') || stat.name.includes('lento')) {
                      colorClass = 'from-red-50 to-red-100 border-red-200';
                      iconColor = 'text-red-500';
                      textColor = 'text-red-700';
                    }

                    return (
                      <div key={index} className={`bg-gradient-to-r ${colorClass} dark:from-gray-800 dark:to-gray-700 dark:border-gray-600 border rounded-lg p-4 shadow-sm`}>
                        <div className="flex items-center justify-between">
                          <div className="flex items-center">
                            <div className={`p-2 rounded-full bg-white dark:bg-gray-800 ${iconColor}`}>
                              <ClipboardDocumentCheckIcon className="h-5 w-5" />
                            </div>
                            <div className="ml-3">
                              <h4 className={`text-sm font-semibold ${textColor} dark:text-gray-200`}>
                                {stat.name}
                              </h4>
                              <p className="text-xs text-gray-500 dark:text-gray-400">
                                Rango de tiempo
                              </p>
                            </div>
                          </div>
                          <div className="text-right">
                            <div className={`text-lg font-bold ${textColor} dark:text-gray-200`}>
                              {stat.count}
                            </div>
                            <div className="text-xs text-gray-500 dark:text-gray-400">
                              {percentage}% del total
                            </div>
                          </div>
                        </div>

                        {/* Barra de progreso */}
                        <div className="mt-3">
                          <div className="bg-white dark:bg-gray-600 rounded-full h-2">
                            <div
                              className={`h-2 rounded-full ${iconColor.replace('text-', 'bg-')}`}
                              style={{ width: `${percentage}%` }}
                            ></div>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>

                {/* Tabla detallada */}
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                    <thead className="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700">
                      <tr>
                        <th scope="col" className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                          ⏱️ Rango de Tiempo
                        </th>
                        <th scope="col" className="px-6 py-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                          📊 Cantidad
                        </th>
                        <th scope="col" className="px-6 py-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                          📈 Porcentaje
                        </th>
                        <th scope="col" className="px-6 py-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                          ⚡ Eficiencia
                        </th>
                      </tr>
                    </thead>
                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                      {processedTimeStats.map((stat, index) => {
                        const totalExams = processedTimeStats.reduce((sum, s) => sum + s.count, 0);
                        const percentage = totalExams > 0 ? Math.round((stat.count / totalExams) * 100) : 0;

                        // Determinar nivel de eficiencia
                        let efficiencyLabel = 'Normal';
                        let efficiencyColor = 'text-gray-600';

                        if (stat.name.includes('0-2') || stat.name.includes('rápido')) {
                          efficiencyLabel = 'Excelente';
                          efficiencyColor = 'text-green-600';
                        } else if (stat.name.includes('2-6') || stat.name.includes('medio')) {
                          efficiencyLabel = 'Buena';
                          efficiencyColor = 'text-yellow-600';
                        } else if (stat.name.includes('6+') || stat.name.includes('lento')) {
                          efficiencyLabel = 'Mejorable';
                          efficiencyColor = 'text-red-600';
                        }

                        return (
                          <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                              {stat.name}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-center">
                              <div className="text-sm font-semibold text-blue-600 dark:text-blue-300">
                                {stat.count}
                              </div>
                              <div className="text-xs text-blue-500 dark:text-blue-400">
                                exámenes
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-center">
                              <div className="text-sm font-semibold text-purple-600 dark:text-purple-300">
                                {percentage}%
                              </div>
                              <div className="text-xs text-purple-500 dark:text-purple-400">
                                del total
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-center">
                              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${efficiencyColor} bg-gray-100 dark:bg-gray-700`}>
                                {efficiencyLabel}
                              </span>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  };

  // Renderizar reporte por categorías
  // Componente separado para el reporte de categorías para manejar correctamente el estado
  const CategoriesReport = ({ data }) => {
    const [expandedCategory, setExpandedCategory] = useState(null);
    const [topExamsByCategory, setTopExamsByCategory] = useState({});
    
    console.log('Renderizando reporte de categorías con datos:', data);
    
    // Verificar si hay datos en data.categoryStats o directamente en data
    let categoryStats = [];
    if (data?.categoryStats && Array.isArray(data.categoryStats)) {
      categoryStats = data.categoryStats;
      console.log('Usando data.categoryStats, encontrado', categoryStats.length, 'categorías');
    } else if (Array.isArray(data)) {
      categoryStats = data;
      console.log('Usando data directamente como array, encontrado', categoryStats.length, 'categorías');
    } else {
      console.warn('No se encontraron datos de categorías en el formato esperado:', data);
    }
    
    // Actualizar topExamsByCategory en el estado cuando cambien los datos
    React.useEffect(() => {
      const examsByCategory = data?.topExamsByCategory || {};
      console.log('Actualizando topExamsByCategory con', Object.keys(examsByCategory).length, 'entradas');
      console.log('Datos de topExamsByCategory:', examsByCategory);
      setTopExamsByCategory(examsByCategory);
    }, [data]);
    
    // Calcular porcentajes si no vienen en los datos
    const totalCount = categoryStats.reduce((sum, stat) => sum + (stat.count || 0), 0);
    console.log('Total de exámenes contados en categorías:', totalCount);
    
    categoryStats = categoryStats.map(stat => ({
      ...stat,
      percentage: stat.percentage || (totalCount > 0 ? Math.round((stat.count / totalCount) * 100 * 100) / 100 : 0)
    }));
    
    console.log('Categorías procesadas:', categoryStats);
    console.log('Exámenes por categoría (estado):', topExamsByCategory);
    
    // Calcular estadísticas de resumen para debugging
    const statsResumen = {
      totalCategorias: categoryStats.length,
      totalExamenes: totalCount,
      totalPacientes: data?.totalPatients || 0,
      totalSolicitudes: data?.totalRequests || 0
    };
    console.log('Resumen estadístico:', statsResumen);
    
    return (
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Resumen de estadísticas */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Resumen de Categorías
            </h3>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-4">
              <div className="bg-blue-50 dark:bg-blue-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-blue-100 dark:bg-blue-800 rounded-md p-3">
                      <svg className="h-6 w-6 text-blue-600 dark:text-blue-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Categorías
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {categoryStats.length}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="bg-green-50 dark:bg-green-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-green-100 dark:bg-green-800 rounded-md p-3">
                      <svg className="h-6 w-6 text-green-600 dark:text-green-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Exámenes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalExams || totalCount || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="bg-purple-50 dark:bg-purple-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-purple-100 dark:bg-purple-800 rounded-md p-3">
                      <svg className="h-6 w-6 text-purple-600 dark:text-purple-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Pacientes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalPatients || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="bg-orange-50 dark:bg-orange-900/30 overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 bg-orange-100 dark:bg-orange-800 rounded-md p-3">
                      <svg className="h-6 w-6 text-orange-600 dark:text-orange-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div className="ml-5 w-0 flex-1">
                      <dl>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                          Total Solicitudes
                        </dt>
                        <dd>
                          <div className="text-lg font-medium text-gray-900 dark:text-white">
                            {data.totalRequests || 0}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        {/* Tabla de categorías - Simplificada solo con nombre y conteo */}
        <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Categorías más solicitadas
            </h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Categoría</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cantidad</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Porcentaje</th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {categoryStats.length > 0 ? (
                    categoryStats.map((stat, index) => (
                      <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{stat.name}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{stat.count}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{stat.percentage}%</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                          <button
                            onClick={() => {
                              const newExpandedCategory = expandedCategory === stat.id ? null : stat.id;
                              console.log('Expandiendo categoría:', stat.id, 'Datos disponibles:', topExamsByCategory[stat.id]);
                              setExpandedCategory(newExpandedCategory);
                            }}
                            className="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                          >
                            {expandedCategory === stat.id ? 'Ocultar exámenes' : 'Ver exámenes con solicitudes'}
                          </button>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="4" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No hay datos disponibles para el período seleccionado
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        {/* Sección de exámenes por categoría */}
        {expandedCategory && (
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg lg:col-span-3">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                Exámenes de la categoría: {categoryStats.find(cat => cat.id === expandedCategory)?.name || 'Desconocida'}
              </h3>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead className="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Código</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Examen</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cantidad</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    {topExamsByCategory[expandedCategory] && topExamsByCategory[expandedCategory].length > 0 ? (
                      topExamsByCategory[expandedCategory].map((exam, index) => (
                        <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {exam.code || exam.codigo || 'Sin código'}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {exam.name || exam.nombre || 'Sin nombre'}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {exam.count || 0}
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="3" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                          {expandedCategory ? 'No hay exámenes disponibles para esta categoría' : 'Selecciona una categoría para ver los exámenes'}
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  };

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
        <div className="px-4 py-5 sm:px-6 flex flex-col md:flex-row md:items-center md:justify-between">
          <div>
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              Reportes Avanzados
            </h2>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Visualiza estadísticas detalladas del laboratorio
            </p>
          </div>
          <div className="mt-4 md:mt-0 flex flex-wrap gap-2">

            <button
              onClick={() => {
                // Mostrar un mensaje de carga
                toast.loading('Actualizando datos...', { id: 'refresh-toast' });
                refetch().then(() => {
                  toast.success('Datos actualizados', { id: 'refresh-toast' });
                }).catch(() => {
                  toast.error('Error al actualizar datos', { id: 'refresh-toast' });
                });
              }}
              className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
            >
              <ArrowPathIcon className="h-4 w-4 mr-1" />
              Actualizar
            </button>
            <button
              onClick={generatePDF}
              className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800 mr-2"
            >
              <DocumentArrowDownIcon className="h-4 w-4 mr-1" />
              PDF
            </button>
            <button
              onClick={generateExcel}
              className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-gray-800"
            >
              <DocumentArrowDownIcon className="h-4 w-4 mr-1" />
              Excel
            </button>
          </div>
        </div>
        <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* Tipo de reporte */}
            <div>
              <label htmlFor="reportType" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Tipo de Reporte
              </label>
              <select
                id="reportType"
                name="reportType"
                value={reportType}
                onChange={(e) => setReportType(e.target.value)}
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              >
                <option value="general">General</option>
                <option value="exams">Exámenes</option>
                <option value="services">Servicios</option>
                {!isDoctor() && <option value="doctors">Doctores</option>}
                <option value="patients">Pacientes</option>
                <option value="results">Resultados</option>
                <option value="categories">Categorías</option>
              </select>
            </div>

            {/* Filtro de estado (solo para resultados) */}
            {reportType === 'results' && (
              <div>
                <label htmlFor="statusFilter" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Estado
                </label>
                <select
                  id="statusFilter"
                  name="statusFilter"
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                  className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                  <option value="">Todos</option>
                  <option value="pendiente">Pendiente</option>
                  <option value="en_proceso">En Proceso</option>
                  <option value="completado">Completado</option>
                </select>
              </div>
            )}

            {/* Filtro de exámenes (solo para tipo exams) */}
            {reportType === 'exams' && (
              <div>
                <label htmlFor="examFilter" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Exámenes
                </label>
                <button
                  type="button"
                  onClick={() => setShowExamModal(true)}
                  className="mt-1 flex justify-between items-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600"
                >
                  <span className="truncate">
                    {selectedExams.length > 0
                      ? `${selectedExams.length} examen(es) seleccionado(s)`
                      : "Seleccionar exámenes..."}
                  </span>
                  <FunnelIcon className="ml-2 h-5 w-5 text-gray-400" aria-hidden="true" />
                </button>

                {/* No mostramos la lista de exámenes seleccionados aquí */}
              </div>
            )}

            {/* Filtro de servicios (solo para tipo services) */}
            {reportType === 'services' && (
              <div>
                <label htmlFor="serviceFilter" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Servicios
                </label>
                <button
                  type="button"
                  onClick={() => setShowServicesModal(true)}
                  className="mt-1 flex justify-between items-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600"
                >
                  <span className="truncate">
                    {selectedServices.length > 0
                      ? `${selectedServices.length} servicio(s) seleccionado(s)`
                      : "Seleccionar servicios..."}
                  </span>
                  <FunnelIcon className="ml-2 h-5 w-5 text-gray-400" aria-hidden="true" />
                </button>
              </div>
            )}

            {/* Filtro de doctores (solo para tipo doctors) */}
            {reportType === 'doctors' && (
              <div>
                <label htmlFor="doctorFilter" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Doctores
                </label>
                <button
                  type="button"
                  onClick={() => setShowDoctorsModal(true)}
                  className="mt-1 flex justify-between items-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600"
                >
                  <span className="truncate">
                    {selectedDoctors.length > 0
                      ? `${selectedDoctors.length} doctor(es) seleccionado(s)`
                      : "Seleccionar doctores..."}
                  </span>
                  <FunnelIcon className="ml-2 h-5 w-5 text-gray-400" aria-hidden="true" />
                </button>
              </div>
            )}

            {/* Rango de fechas */}
            <div className={reportType === 'results' || reportType === 'exams' || reportType === 'services' ? "md:col-span-1" : "md:col-span-2"}>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Rango de Fechas
              </label>
              <div className="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                  <label htmlFor="startDate" className="sr-only">Fecha Inicio</label>
                  <input
                    type="date"
                    id="startDate"
                    name="startDate"
                    value={dateRange && dateRange[0] ? dateRange[0].startDate : ''}
                    onChange={(e) => {
                      const newDateRange = [...dateRange];
                      newDateRange[0] = {
                        ...newDateRange[0],
                        startDate: e.target.value,
                        format: (format) => {
                          return format === 'YYYY-MM-DD' ? e.target.value : new Date(e.target.value).toLocaleDateString();
                        }
                      };
                      setDateRange(newDateRange);
                    }}
                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  />
                </div>
                <div>
                  <label htmlFor="endDate" className="sr-only">Fecha Fin</label>
                  <input
                    type="date"
                    id="endDate"
                    name="endDate"
                    value={dateRange && dateRange[1] ? dateRange[1].endDate : ''}
                    onChange={(e) => {
                      const newDateRange = [...dateRange];
                      newDateRange[1] = {
                        ...newDateRange[1],
                        endDate: e.target.value,
                        format: (format) => {
                          return format === 'YYYY-MM-DD' ? e.target.value : new Date(e.target.value).toLocaleDateString();
                        }
                      };
                      setDateRange(newDateRange);
                    }}
                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Sección de Gráficos - COMENTADA: Ahora cada tipo de reporte maneja sus propios gráficos */}
      {/*
      {!reportsLoading && !reportsError && reportsData?.data && (
        <div className="mt-6">
          <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
                Gráficos Estadísticos de {reportType === 'patients' ? 'Pacientes' :
                  reportType === 'exams' ? 'Exámenes' :
                  reportType === 'services' ? 'Servicios' :
                  reportType === 'doctors' ? 'Doctores' : 'Datos'}
              </h3>
              <ReportCharts
                reportType={reportType}
                startDate={dateRange && dateRange[0] ? dateRange[0].startDate : null}
                endDate={dateRange && dateRange[1] ? dateRange[1].endDate : null}
                reportsData={reportsData}
              />
            </div>
          </div>
        </div>
      )}
      */}

      {/* Mostrar exámenes seleccionados si hay alguno - DESPUÉS de los gráficos */}
      {renderSelectedExams()}

      {/* Contenido del reporte */}
      <div className="mt-6">
        {renderReportContent()}
      </div>

      {/* Modal de selección de exámenes */}
      {showExamModal && (
        <div className="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {/* Overlay de fondo */}
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              aria-hidden="true"
              onClick={() => setShowExamModal(false)}
            ></div>

            {/* Centrar el modal */}
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {/* Contenido del modal */}
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <div className="flex justify-between items-center mb-4">
                      <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                        Exámenes más solicitados
                      </h3>
                      <div className="relative">
                        <input
                          type="text"
                          className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                          placeholder="Buscar exámenes..."
                          value={searchTerm}
                          onChange={(e) => setSearchTerm(e.target.value)}
                        />
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                          </svg>
                        </div>
                      </div>
                    </div>

                    <div className="mt-2 border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
                      <div className="max-h-96 overflow-y-auto">
                        {examsLoading ? (
                          <div className="flex justify-center items-center h-32">
                            <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary-500"></div>
                          </div>
                        ) : filteredExams.length > 0 ? (
                          <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                              <thead className="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                <tr>
                                  <th scope="col" className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-10">
                                    <span className="sr-only">Seleccionar</span>
                                  </th>
                                  <th scope="col" className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Examen
                                  </th>


                                  <th scope="col" className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Categoría
                                  </th>
                                </tr>
                              </thead>
                              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                {filteredExams.map((exam) => {
                                  // Buscar datos reales del examen en los datos del reporte
                                  let examData = null;
                                  if (reportsData?.data?.examStats) {
                                    examData = reportsData.data.examStats.find(stat =>
                                      Number(stat.id) === Number(exam.value) || stat.name === exam.name
                                    );
                                  }

                                  // Usar los datos reales si existen, o valores estáticos si no
                                  const count = examData?.count || exam.count || '-';
                                  const percentage = examData?.percentage || exam.percentage || '-';
                                  const isSelected = tempSelectedExams.some(e => e.value === exam.value);

                                  return (
                                    <tr
                                      key={exam.value}
                                      className={`hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer ${isSelected ? 'bg-blue-50 dark:bg-blue-900/20' : ''}`}
                                      onClick={(e) => {
                                        e.preventDefault();
                                        console.log('Toggling exam:', exam);
                                        handleExamToggle(exam);
                                      }}
                                    >
                                      <td className="px-3 py-2 whitespace-nowrap">
                                        <input
                                          id={`exam-${exam.value}`}
                                          name={`exam-${exam.value}`}
                                          type="checkbox"
                                          className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                          checked={isSelected}
                                          onChange={(e) => {
                                            // Evitar que el clic en el checkbox dispare el evento de la fila
                                            e.stopPropagation();
                                            console.log('Checkbox clicked for exam:', exam);
                                            handleExamToggle(exam);
                                          }}
                                        />
                                      </td>
                                      <td className="px-3 py-2 text-sm font-medium text-gray-900 dark:text-white">
                                        {exam.name || exam.nombre || exam.label}
                                      </td>


                                      <td className="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {exam.category || exam.categoria || 'N/A'}
                                      </td>
                                    </tr>
                                  );
                                })}
                              </tbody>
                            </table>
                          </div>
                        ) : (
                          <div className="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            No se encontraron exámenes
                          </div>
                        )}
                      </div>
                    </div>

                    <div className="mt-4 flex justify-between items-center">
                      <div className="text-sm text-gray-500 dark:text-gray-400">
                        {tempSelectedExams.length} examen(es) seleccionado(s)
                      </div>
                      <button
                        type="button"
                        className="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                        onClick={handleSelectAll}
                      >
                        {tempSelectedExams.length === examOptions.length ? 'Deseleccionar todos' : 'Seleccionar todos'}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div className="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                  onClick={() => {
                    console.log('Applying exam selection:', tempSelectedExams);
                    // Hacer una copia profunda para evitar problemas de referencia
                    const examsCopy = tempSelectedExams.map(exam => ({...exam}));
                    setSelectedExams(examsCopy);
                    setShowExamModal(false);
                    // Solo refrescar si la selección ha cambiado
                    const selectionChanged =
                      examsCopy.length !== selectedExams.length ||
                      examsCopy.some(exam => !selectedExams.some(e => e.value === exam.value)) ||
                      selectedExams.some(exam => !examsCopy.some(e => e.value === exam.value));

                    if (selectionChanged) {
                      // Mostrar un mensaje de carga
                      toast.loading('Actualizando datos...', { id: 'refresh-toast' });

                      // Refrescar los datos con un pequeño retraso
                      setTimeout(() => {
                        refetch().then(() => {
                          toast.success('Datos actualizados', { id: 'refresh-toast' });
                        }).catch(() => {
                          toast.error('Error al actualizar datos', { id: 'refresh-toast' });
                        });
                      }, 300);
                    }
                  }}
                >
                  Aplicar
                </button>
                <button
                  type="button"
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700"
                  onClick={() => setShowExamModal(false)}
                >
                  Cancelar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal de selección de servicios */}
      <ServicesSearchModal
        isOpen={showServicesModal}
        onClose={() => setShowServicesModal(false)}
        onSelectServices={(services) => {
          setSelectedServices(services);
          setShowServicesModal(false);
          // Refrescar los datos
          toast.loading('Actualizando datos...', { id: 'refresh-toast' });
          setTimeout(() => {
            refetch().then(() => {
              toast.success('Datos actualizados', { id: 'refresh-toast' });
            }).catch(() => {
              toast.error('Error al actualizar datos', { id: 'refresh-toast' });
            });
          }, 300);
        }}
        selectedServiceIds={selectedServices.map(service => service.id)}
      />

      {/* Modal de selección de doctores */}
      {showDoctorsModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div className="mt-3">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                  Seleccionar Doctores
                </h3>
                <button
                  onClick={() => setShowDoctorsModal(false)}
                  className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <XMarkIcon className="h-6 w-6" />
                </button>
              </div>

              <div className="mb-4">
                <input
                  type="text"
                  placeholder="Buscar doctores..."
                  value={doctorSearchTerm}
                  onChange={(e) => setDoctorSearchTerm(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                />
              </div>

              <div className="max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md">
                <div className="divide-y divide-gray-200 dark:divide-gray-600">
                  {doctorsLoading ? (
                    <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                      Cargando doctores...
                    </div>
                  ) : filteredDoctors.length === 0 ? (
                    <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                      {doctorSearchTerm ? 'No se encontraron doctores con ese criterio de búsqueda' : 'No hay doctores disponibles'}
                      <div className="text-xs mt-2">
                        Total doctores cargados: {doctorOptions.length}
                      </div>
                    </div>
                  ) : (
                    filteredDoctors.map((doctor) => {
                      const isSelected = tempSelectedDoctors.some(d => d.value === doctor.value);

                      return (
                        <div
                          key={doctor.value}
                          className={`p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 ${
                            isSelected ? 'bg-primary-50 dark:bg-primary-900' : ''
                          }`}
                          onClick={() => handleDoctorToggle(doctor)}
                        >
                          <div className="flex items-center justify-between">
                            <div className="flex-1">
                              <div className="text-sm font-medium text-gray-900 dark:text-white">
                                {doctor.fullName}
                              </div>
                              <div className="text-sm text-gray-500 dark:text-gray-400">
                                {doctor.especialidad}
                              </div>
                              {doctor.cmp && (
                                <div className="text-xs text-gray-400 dark:text-gray-500">
                                  CMP: {doctor.cmp}
                                </div>
                              )}
                            </div>
                            <div className="ml-3">
                              <input
                                type="checkbox"
                                checked={isSelected}
                                onChange={() => handleDoctorToggle(doctor)}
                                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                              />
                            </div>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>

              <div className="mt-4 flex justify-between items-center">
                <div className="text-sm text-gray-500 dark:text-gray-400">
                  {tempSelectedDoctors.length} doctor(es) seleccionado(s) de {doctorOptions.length} disponibles
                  {doctorsLoading && <span className="ml-2">(Cargando...)</span>}
                </div>
                <button
                  type="button"
                  className="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                  onClick={handleSelectAllDoctors}
                  disabled={doctorsLoading || doctorOptions.length === 0}
                >
                  {tempSelectedDoctors.length === doctorOptions.length ? 'Deseleccionar todos' : 'Seleccionar todos'}
                </button>
              </div>
            </div>

            <div className="mt-6 flex justify-end space-x-3">
              <button
                type="button"
                className="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:text-sm"
                onClick={() => {
                  const doctorsCopy = tempSelectedDoctors.map(doctor => ({...doctor}));
                  setSelectedDoctors(doctorsCopy);
                  setShowDoctorsModal(false);
                  // Solo refrescar si la selección ha cambiado
                  const selectionChanged =
                    doctorsCopy.length !== selectedDoctors.length ||
                    doctorsCopy.some(doctor => !selectedDoctors.some(d => d.value === doctor.value)) ||
                    selectedDoctors.some(doctor => !doctorsCopy.some(d => d.value === doctor.value));

                  if (selectionChanged) {
                    toast.loading('Actualizando datos...', { id: 'refresh-toast' });
                    setTimeout(() => {
                      refetch().then(() => {
                        toast.success('Datos actualizados', { id: 'refresh-toast' });
                      }).catch(() => {
                        toast.error('Error al actualizar datos', { id: 'refresh-toast' });
                      });
                    }, 300);
                  }
                }}
              >
                Aplicar
              </button>
              <button
                type="button"
                className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700"
                onClick={() => setShowDoctorsModal(false)}
              >
                Cancelar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}