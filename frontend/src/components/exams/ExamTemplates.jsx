import React, { useState, useEffect } from 'react';
import { 
  BeakerIcon, 
  PlusIcon, 
  DocumentDuplicateIcon,
  InformationCircleIcon 
} from '@heroicons/react/24/outline';

const ExamTemplates = ({ onSelectTemplate, onCreateFromTemplate }) => {
  const [plantillas, setPlantillas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedTemplate, setSelectedTemplate] = useState(null);

  useEffect(() => {
    fetchPlantillas();
  }, []);

  const fetchPlantillas = async () => {
    try {
      const response = await fetch('/api/examenes-compuestos/plantillas');
      const data = await response.json();
      setPlantillas(data);
    } catch (error) {
      console.error('Error fetching templates:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateFromTemplate = async (plantilla) => {
    if (onCreateFromTemplate) {
      onCreateFromTemplate(plantilla);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-32">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-lg p-6">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-xl font-bold text-gray-900 flex items-center">
          <BeakerIcon className="w-6 h-6 mr-2 text-blue-600" />
          Plantillas de Exámenes Compuestos
        </h2>
        <div className="flex items-center text-sm text-gray-500">
          <InformationCircleIcon className="w-4 h-4 mr-1" />
          Selecciona una plantilla para crear un examen compuesto
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {plantillas.map((plantilla, index) => (
          <TemplateCard
            key={index}
            plantilla={plantilla}
            onSelect={() => setSelectedTemplate(plantilla)}
            onCreateFrom={() => handleCreateFromTemplate(plantilla)}
            isSelected={selectedTemplate?.nombre === plantilla.nombre}
          />
        ))}
      </div>

      {selectedTemplate && (
        <TemplateDetail
          plantilla={selectedTemplate}
          onClose={() => setSelectedTemplate(null)}
          onCreateFrom={() => handleCreateFromTemplate(selectedTemplate)}
        />
      )}
    </div>
  );
};

const TemplateCard = ({ plantilla, onSelect, onCreateFrom, isSelected }) => {
  return (
    <div 
      className={`border rounded-lg p-4 cursor-pointer transition-all duration-200 hover:shadow-md ${
        isSelected 
          ? 'border-blue-500 bg-blue-50' 
          : 'border-gray-200 hover:border-gray-300'
      }`}
      onClick={onSelect}
    >
      <div className="flex items-start justify-between mb-3">
        <h3 className="font-semibold text-gray-900 text-sm">
          {plantilla.nombre}
        </h3>
        <span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
          {plantilla.examenes.length} exámenes
        </span>
      </div>
      
      <p className="text-gray-600 text-xs mb-3 line-clamp-2">
        {plantilla.descripcion}
      </p>
      
      <div className="space-y-1 mb-4">
        {plantilla.examenes.slice(0, 3).map((examen, index) => (
          <div key={index} className="flex items-center text-xs text-gray-500">
            <div className="w-1 h-1 bg-gray-400 rounded-full mr-2"></div>
            {examen.nombre}
          </div>
        ))}
        {plantilla.examenes.length > 3 && (
          <div className="text-xs text-gray-400 ml-3">
            +{plantilla.examenes.length - 3} más...
          </div>
        )}
      </div>
      
      <button
        onClick={(e) => {
          e.stopPropagation();
          onCreateFrom();
        }}
        className="w-full flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition-colors"
      >
        <DocumentDuplicateIcon className="w-4 h-4 mr-1" />
        Usar Plantilla
      </button>
    </div>
  );
};

const TemplateDetail = ({ plantilla, onClose, onCreateFrom }) => {
  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div className="p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-xl font-bold text-gray-900">
              {plantilla.nombre}
            </h3>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <p className="text-gray-600 mb-6">
            {plantilla.descripcion}
          </p>
          
          <div className="mb-6">
            <h4 className="font-semibold text-gray-900 mb-3">
              Exámenes Incluidos ({plantilla.examenes.length})
            </h4>
            <div className="space-y-2">
              {plantilla.examenes.map((examen, index) => (
                <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                  <div className="flex items-center">
                    <span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-3">
                      {examen.orden}
                    </span>
                    <span className="font-medium text-gray-900">
                      {examen.nombre}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
          
          <div className="flex justify-end space-x-3">
            <button
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              onClick={() => {
                onCreateFrom();
                onClose();
              }}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center"
            >
              <DocumentDuplicateIcon className="w-4 h-4 mr-2" />
              Crear Examen con esta Plantilla
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ExamTemplates;
