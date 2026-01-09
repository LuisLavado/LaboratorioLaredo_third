import { Fragment, useState, useEffect } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { MagnifyingGlassIcon, XMarkIcon, CheckIcon } from '@heroicons/react/24/outline';
import { examsAPI } from '../../services/api';
import toast from 'react-hot-toast';

export default function ExamsSearchModal({ isOpen, onClose, onSelectExams, selectedExamIds = [] }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [exams, setExams] = useState([]);
  const [filteredExams, setFilteredExams] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [selectedExams, setSelectedExams] = useState([]);
  
  // Cargar exámenes cuando se abre el modal
  useEffect(() => {
    if (isOpen) {
      loadExams();
      setSearchTerm('');
    }
  }, [isOpen]);
  
  // Inicializar exámenes seleccionados
  useEffect(() => {
    if (exams.length > 0 && selectedExamIds.length > 0) {
      const selected = exams.filter(exam => selectedExamIds.includes(exam.id));
      setSelectedExams(selected);
    } else {
      setSelectedExams([]);
    }
  }, [exams, selectedExamIds]);
  
  // Filtrar exámenes cuando cambia el término de búsqueda
  useEffect(() => {
    if (exams.length > 0) {
      const filtered = exams.filter(exam => 
        exam.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
        exam.codigo.toLowerCase().includes(searchTerm.toLowerCase())
      );
      setFilteredExams(filtered);
    }
  }, [searchTerm, exams]);
  
  // Cargar todos los exámenes
  const loadExams = async () => {
    setIsLoading(true);
    try {
      const response = await examsAPI.getAll({ all: true });
      if (response.data && response.data.examenes) {
        // Filtrar solo exámenes activos
        const activeExams = response.data.examenes.filter(exam => exam.activo);
        setExams(activeExams);
        setFilteredExams(activeExams);
      }
    } catch (error) {
      console.error('Error al cargar exámenes:', error);
      toast.error('Error al cargar los exámenes');
    } finally {
      setIsLoading(false);
    }
  };
  
  // Manejar la selección de un examen
  const handleToggleExam = (exam) => {
    setSelectedExams(prevSelected => {
      const isSelected = prevSelected.some(e => e.id === exam.id);
      
      if (isSelected) {
        return prevSelected.filter(e => e.id !== exam.id);
      } else {
        return [...prevSelected, exam];
      }
    });
  };
  
  // Confirmar selección de exámenes
  const handleConfirm = () => {
    onSelectExams(selectedExams);
    onClose();
  };
  
  // Manejar la búsqueda al presionar Enter
  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
    }
  };
  
  // Verificar si un examen está seleccionado
  const isExamSelected = (examId) => {
    return selectedExams.some(exam => exam.id === examId);
  };
  
  return (
    <Transition.Root show={isOpen} as={Fragment}>
      <Dialog as="div" className="relative z-10" onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in duration-200"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </Transition.Child>

        <div className="fixed inset-0 z-10 overflow-y-auto">
          <div className="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enterTo="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 translate-y-0 sm:scale-100"
              leaveTo="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <Dialog.Panel className="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <div>
                  <div className="flex justify-between items-center mb-4">
                    <div>
                      <Dialog.Title as="h3" className="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        Seleccionar Exámenes
                      </Dialog.Title>
                      <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {isLoading ? (
                          'Cargando exámenes...'
                        ) : (
                          `${exams.length} examen${exams.length !== 1 ? 'es' : ''} disponible${exams.length !== 1 ? 's' : ''}`
                        )}
                      </p>
                    </div>
                    <button
                      type="button"
                      className="rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
                      onClick={onClose}
                    >
                      <span className="sr-only">Cerrar</span>
                      <XMarkIcon className="h-6 w-6" aria-hidden="true" />
                    </button>
                  </div>
                  
                  <div className="mb-4">
                    <div className="relative">
                      <input
                        type="text"
                        className="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full text-base border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md h-12"
                        placeholder="Buscar por nombre o código..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        onKeyDown={handleKeyDown}
                      />
                      <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                      </div>
                    </div>
                  </div>
                  
                  {selectedExams.length > 0 && (
                    <div className="mb-4">
                      <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Exámenes seleccionados ({selectedExams.length})
                      </h4>
                      <div className="flex flex-wrap gap-2">
                        {selectedExams.map(exam => (
                          <span 
                            key={exam.id}
                            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100"
                          >
                            {exam.nombre}
                            <button
                              type="button"
                              className="ml-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full text-primary-400 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200"
                              onClick={() => handleToggleExam(exam)}
                            >
                              <span className="sr-only">Eliminar</span>
                              <XMarkIcon className="h-3 w-3" aria-hidden="true" />
                            </button>
                          </span>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Información de resultados de búsqueda */}
                  {searchTerm && !isLoading && (
                    <div className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                      {filteredExams.length > 0 ? (
                        `Mostrando ${filteredExams.length} de ${exams.length} exámenes`
                      ) : (
                        `No se encontraron exámenes que coincidan con "${searchTerm}"`
                      )}
                    </div>
                  )}

                  <div className="mt-4 max-h-80 overflow-y-auto">
                    {isLoading ? (
                      <div className="flex justify-center py-4">
                        <div className="animate-spin h-8 w-8 border-t-2 border-b-2 border-primary-500 rounded-full"></div>
                      </div>
                    ) : filteredExams.length > 0 ? (
                      <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                        {filteredExams.map((exam) => (
                          <li
                            key={exam.id}
                            className={`px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer ${
                              isExamSelected(exam.id) ? 'bg-primary-50 dark:bg-primary-900/20' : ''
                            }`}
                            onClick={() => handleToggleExam(exam)}
                          >
                            <div className="flex justify-between items-center">
                              <div>
                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                  {exam.nombre}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                  Categoría: {exam.categoria?.nombre || 'N/A'}
                                </p>
                              </div>
                              {isExamSelected(exam.id) && (
                                <CheckIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" aria-hidden="true" />
                              )}
                            </div>
                          </li>
                        ))}
                      </ul>
                    ) : (
                      <div className="text-center py-4">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          No se encontraron exámenes
                        </p>
                      </div>
                    )}
                  </div>
                </div>
                
                <div className="mt-5 sm:mt-6 flex justify-between">
                  <button
                    type="button"
                    className="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-base font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:text-sm"
                    onClick={onClose}
                  >
                    Cancelar
                  </button>
                  <button
                    type="button"
                    className="inline-flex justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:text-sm"
                    onClick={handleConfirm}
                  >
                    Confirmar ({selectedExams.length})
                  </button>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition.Root>
  );
}
