import React, { useState } from 'react';
import { Dialog } from '@headlessui/react';
import { XMarkIcon, PaperAirplaneIcon } from '@heroicons/react/24/outline';
import { reportsAPI } from '../../services/api';
import toast from 'react-hot-toast';

const WhatsAppModal = ({ isOpen, onClose, reportParams }) => {
  const [formData, setFormData] = useState({
    phone: '',
    fileType: 'pdf',
    message: 'Reporte del Laboratorio Laredo adjunto'
  });
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.phone.trim()) {
      toast.error('Por favor ingresa un número de teléfono');
      return;
    }

    // Validar formato de teléfono básico
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{9,15}$/;
    if (!phoneRegex.test(formData.phone.trim())) {
      toast.error('Por favor ingresa un número de teléfono válido');
      return;
    }

    try {
      setLoading(true);
      
      const requestData = {
        phone: formData.phone.trim(),
        file_type: formData.fileType,
        message: formData.message.trim(),
        ...reportParams
      };

      const response = await reportsAPI.sendWhatsApp(requestData);
      
      if (response.data.status) {
        toast.success('Reporte enviado por WhatsApp exitosamente');
        onClose();
        // Resetear formulario
        setFormData({
          phone: '',
          fileType: 'pdf',
          message: 'Reporte del Laboratorio Laredo adjunto'
        });
      } else {
        toast.error(response.data.message || 'Error enviando el reporte');
      }
    } catch (error) {
      console.error('Error sending WhatsApp:', error);
      const errorMessage = error.response?.data?.message || 'Error enviando el reporte por WhatsApp';
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handlePhoneChange = (e) => {
    let value = e.target.value;
    // Permitir solo números, espacios, guiones, paréntesis y el signo +
    value = value.replace(/[^0-9\s\-\(\)\+]/g, '');
    setFormData(prev => ({ ...prev, phone: value }));
  };

  const formatPhonePreview = (phone) => {
    if (!phone) return '';
    
    // Si no empieza con +, mostrar que se agregará +51
    if (!phone.startsWith('+')) {
      if (phone.startsWith('51')) {
        return `+${phone}`;
      } else {
        return `+51${phone}`;
      }
    }
    return phone;
  };

  return (
    <Dialog open={isOpen} onClose={onClose} className="relative z-50">
      {/* Backdrop */}
      <div className="fixed inset-0 bg-black/30" aria-hidden="true" />
      
      {/* Modal */}
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <Dialog.Panel className="mx-auto max-w-md w-full bg-white dark:bg-gray-800 rounded-lg shadow-xl">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <div className="flex items-center space-x-3">
              <div className="flex-shrink-0">
                <div className="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                  <PaperAirplaneIcon className="w-4 h-4 text-green-600 dark:text-green-400" />
                </div>
              </div>
              <div>
                <Dialog.Title className="text-lg font-medium text-gray-900 dark:text-white">
                  Enviar por WhatsApp
                </Dialog.Title>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Envía el reporte directamente a WhatsApp
                </p>
              </div>
            </div>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
            >
              <XMarkIcon className="w-6 h-6" />
            </button>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="p-6 space-y-4">
            {/* Número de teléfono */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Número de WhatsApp
              </label>
              <input
                type="tel"
                value={formData.phone}
                onChange={handlePhoneChange}
                placeholder="Ej: 999999999 o +51999999999"
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                required
              />
              {formData.phone && (
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Se enviará a: <span className="font-medium">{formatPhonePreview(formData.phone)}</span>
                </p>
              )}
            </div>

            {/* Tipo de archivo */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Formato del archivo
              </label>
              <select
                value={formData.fileType}
                onChange={(e) => setFormData(prev => ({ ...prev, fileType: e.target.value }))}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
              </select>
            </div>

            {/* Mensaje personalizado */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Mensaje (opcional)
              </label>
              <textarea
                value={formData.message}
                onChange={(e) => setFormData(prev => ({ ...prev, message: e.target.value }))}
                placeholder="Mensaje que acompañará al archivo..."
                rows={3}
                maxLength={500}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white resize-none"
              />
              <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {formData.message.length}/500 caracteres
              </p>
            </div>

            {/* Información del reporte */}
            {reportParams && (
              <div className="bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Información del reporte:
                </h4>
                <div className="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                  <p>Tipo: {reportParams.report_type}</p>
                  <p>Período: {reportParams.start_date} - {reportParams.end_date}</p>
                </div>
              </div>
            )}

            {/* Buttons */}
            <div className="flex space-x-3 pt-4">
              <button
                type="button"
                onClick={onClose}
                className="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                disabled={loading}
              >
                Cancelar
              </button>
              <button
                type="submit"
                disabled={loading || !formData.phone.trim()}
                className="flex-1 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
              >
                {loading ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>Enviando...</span>
                  </>
                ) : (
                  <>
                    <PaperAirplaneIcon className="w-4 h-4" />
                    <span>Enviar</span>
                  </>
                )}
              </button>
            </div>
          </form>
        </Dialog.Panel>
      </div>
    </Dialog>
  );
};

export default WhatsAppModal;
