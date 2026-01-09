import axios from 'axios';
import toast from 'react-hot-toast';
import { reverbService } from './reverbService';

// Configuración desde variables de entorno
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';

// Create axios instance for Laravel backend
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  },
  (error) => Promise.reject(error)
);

// Add response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const { response } = error;

    // No redirigir automáticamente en errores de autenticación durante el login
    const isLoginRequest = error.config &&
                          error.config.url &&
                          error.config.url.includes('/login');

    // Handle authentication errors (excepto en la página de login)
    if (response && response.status === 401 && !isLoginRequest) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');

      // Usar history.push en lugar de window.location para evitar recargas
      if (window.location.pathname !== '/login') {
        toast.error('Sesión expirada. Por favor inicie sesión nuevamente.');
        setTimeout(() => {
          window.location.href = '/login';
        }, 1000);
      }
    }

    // Handle validation errors
    if (response && response.status === 422) {
      const errors = response.data.errors;
      if (errors) {
        Object.keys(errors).forEach(key => {
          toast.error(errors[key][0]);
        });
      }
    }

    // Handle server errors
    if (response && response.status >= 500) {
      toast.error('Error del servidor. Por favor intente más tarde.');
    }

    return Promise.reject(error);
  }
);

// Auth API
export const authAPI = {
  login: (credentials) => api.post('/login', credentials),
  register: (userData) => api.post('/register', userData),
  logout: () => api.post('/logout'),

  // Webhook endpoints
  registerWebhook: (data) => api.post('/webhooks/register', data),
  unregisterWebhook: () => api.post('/webhooks/unregister'),
};

// Patients API
export const patientsAPI = {
  getAll: (incluirEliminados = false) => api.get('/pacientes', {
    params: {
      incluir_eliminados: incluirEliminados ? 1 : 0, // Asegurarse de que sea 1 o 0
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  getById: (id) => api.get(`/pacientes/${id}`),
  create: (data) => api.post('/pacientes', data),
  update: (id, data) => api.put(`/pacientes/${id}`, data),
  delete: (id) => api.delete(`/pacientes/${id}`),
  searchByDNI: (dni) => api.get(`/pacientes/search/dni/${dni}`),
  search: (query) => api.get('/pacientes/search', {
    params: {
      query,
      _t: Date.now() // Evitar caché
    }
  }),
  getTrashed: () => api.get('/pacientes-eliminados', {
    params: {
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  restore: (id) => api.patch(`/pacientes/restore/${id}`, {}, {
    params: {
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  forceDelete: (id) => api.delete(`/pacientes/force/${id}`, {
    params: {
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
};

// Exams API
export const examsAPI = {
  getAll: (params) => api.get('/examenes', {
    params: {
      ...params,
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  getById: (id) => api.get(`/examenes/${id}`, {
    params: {
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  create: (data) => api.post('/examenes', data),
  update: (id, data) => api.put(`/examenes/${id}`, data),
  delete: (id) => api.delete(`/examenes/${id}`, {
    params: {
      _t: Date.now() // Evitar problemas de caché
    }
  }),
  activate: (id) => api.put(`/examenes/${id}`, { activo: true }, {
    params: {
      _t: Date.now() // Evitar problemas de caché
    }
  }),
  getInactive: (params) => api.get('/examenes-inactivos', {
    params: {
      ...params,
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  // Nuevas funciones para exámenes dinámicos
  getSimpleExams: () => api.get('/examenes?tipo=simple&all=true'),
  getSimplesSinPerfiles: () => api.get('/examenes-simples-sin-perfiles'),
  getTemplates: () => api.get('/examenes-compuestos/plantillas'),
};

// Campos de Examen API
export const examFieldsAPI = {
  getByExam: (examId) => api.get(`/campos-examen?examen_id=${examId}`),
  create: (data) => api.post('/campos-examen', data),
  createMultiple: (data) => api.post('/campos-examen/multiple', data),
  update: (id, data) => api.put(`/campos-examen/${id}`, data),
  delete: (id) => api.delete(`/campos-examen/${id}`),
  reorder: (data) => api.post('/campos-examen/reorder', data),
};

// Exámenes Compuestos API
export const compositeExamsAPI = {
  getByParent: (parentId) => api.get(`/examenes-compuestos?examen_padre_id=${parentId}`),
  create: (data) => api.post('/examenes-compuestos', data),
  update: (id, data) => api.put(`/examenes-compuestos/${id}`, data),
  delete: (id) => api.delete(`/examenes-compuestos/${id}`),
  reorder: (data) => api.post('/examenes-compuestos/reorder', data),
};

// Valores de Resultado API
export const resultValuesAPI = {
  getByDetail: (detailId) => api.get(`/valores-resultado/detalle/${detailId}`),
  create: (data) => api.post('/valores-resultado', data),
  update: (id, data) => api.put(`/valores-resultado/${id}`, data),
  delete: (id) => api.delete(`/valores-resultado/${id}`),
  getTemplate: (examId) => api.get(`/valores-resultado/plantilla?examen_id=${examId}`),
  validate: (data) => api.post('/valores-resultado/validar', data),
  export: (detailId) => api.get(`/valores-resultado/exportar?detalle_solicitud_id=${detailId}`),
  // Nuevos métodos para auto-guardado
  storeCampo: (data) => api.post('/valores-resultado/campo', data),
  // Método optimizado para guardado en batch
  storeBatch: (data) => api.post('/valores-resultado/batch', data),
  // Método para exámenes sin campos definidos
  storeSimple: (data) => api.post('/valores-resultado/simple', data),
};

// Reports API
export const reportsAPI = {
  getReports: (type, startDate, endDate, params = {}) => {
    // Convertir el array de exam_ids a formato de parámetros de URL si existe
    const processedParams = { ...params };
    if (processedParams.exam_ids && Array.isArray(processedParams.exam_ids)) {
      // Asegurarse de que todos los IDs sean números
      processedParams.exam_ids = processedParams.exam_ids.map(id => Number(id));
    }

    return api.get('/reportes', {
      params: {
        type,
        start_date: startDate,
        end_date: endDate,
        ...processedParams,
        _t: Date.now() // Agregar timestamp para evitar caché
      },
      paramsSerializer: params => {
        // Usar URLSearchParams para serializar correctamente los arrays
        const searchParams = new URLSearchParams();
        for (const key in params) {
          if (Array.isArray(params[key])) {
            // Para arrays, añadir múltiples entradas con el mismo nombre
            // Usar formato exam_ids[] para PHP
            params[key].forEach(value => {
              searchParams.append(`${key}[]`, value);
            });

            // También añadir como string separado por comas como respaldo
            searchParams.append(`${key}_csv`, params[key].join(','));
          } else {
            searchParams.append(key, params[key]);
          }
        }
        const result = searchParams.toString();
        return result;
      }
    });
  },
  generatePDF: (type, startDate, endDate, params = {}) => {
    // Convertir el array de exam_ids a formato de parámetros de URL si existe
    const processedParams = { ...params };
    if (processedParams.exam_ids && Array.isArray(processedParams.exam_ids)) {
      // Asegurarse de que todos los IDs sean números
      processedParams.exam_ids = processedParams.exam_ids.map(id => Number(id));
    }

    return api.get('/reportes/pdf', {
      params: {
        type,
        start_date: startDate,
        end_date: endDate,
        ...processedParams,
        _t: Date.now()
      },
      responseType: 'blob',
      paramsSerializer: params => {
        // Usar URLSearchParams para serializar correctamente los arrays
        const searchParams = new URLSearchParams();
        for (const key in params) {
          if (Array.isArray(params[key])) {
            // Para arrays, añadir múltiples entradas con el mismo nombre
            // Usar formato exam_ids[] para PHP
            params[key].forEach(value => {
              searchParams.append(`${key}[]`, value);
            });

            // También añadir como string separado por comas como respaldo
            searchParams.append(`${key}_csv`, params[key].join(','));
          } else {
            searchParams.append(key, params[key]);
          }
        }
        const result = searchParams.toString();
        return result;
      }
    });
  },
  
  // Función para generar reportes en Excel
  generateExcel: (type, startDate, endDate, params = {}) => {
    // Convertir el array de exam_ids a formato de parámetros de URL si existe
    const processedParams = { ...params };
    if (processedParams.exam_ids && Array.isArray(processedParams.exam_ids)) {
      // Asegurarse de que todos los IDs sean números
      processedParams.exam_ids = processedParams.exam_ids.map(id => Number(id));
    }

    return api.get('/reportes/excel', {
      params: {
        type,
        start_date: startDate,
        end_date: endDate,
        ...processedParams,
        _t: Date.now()
      },
      responseType: 'blob',
      paramsSerializer: params => {
        // Usar URLSearchParams para serializar correctamente los arrays
        const searchParams = new URLSearchParams();
        for (const key in params) {
          if (Array.isArray(params[key])) {
            // Para arrays, añadir múltiples entradas con el mismo nombre
            // Usar formato exam_ids[] para PHP
            params[key].forEach(value => {
              searchParams.append(`${key}[]`, value);
            });

            // También añadir como string separado por comas como respaldo
            searchParams.append(`${key}_csv`, params[key].join(','));
          } else {
            searchParams.append(key, params[key]);
          }
        }
        const result = searchParams.toString();
        return result;
      }
    });
  },

  // Nuevos endpoints específicos para cada tipo de reporte
  getPatientsReport: (startDate, endDate) => api.get('/reportes/patients', {
    params: {
      start_date: startDate,
      end_date: endDate,
      _t: Date.now()
    }
  }),
  getResultsReport: (startDate, endDate, status = '') => api.get('/reportes/results', {
    params: {
      start_date: startDate,
      end_date: endDate,
      status,
      _t: Date.now()
    }
  }),
  getCategoriesReport: (startDate, endDate) => api.get('/reportes/categories', {
    params: {
      start_date: startDate,
      end_date: endDate,
      _t: Date.now()
    }
  }),
  // Endpoints específicos para reportes de doctor
  getDoctorReports: (type, startDate, endDate, doctorId) => {
    return api.get('/reportes', {
      params: {
        type,
        start_date: startDate,
        end_date: endDate,
        doctor_id: doctorId,
        _t: Date.now()
      }
    });
  },
  generateDoctorPDF: (type, startDate, endDate, doctorId) => api.get('/reportes/pdf', {
    params: {
      type,
      start_date: startDate,
      end_date: endDate,
      doctor_id: doctorId,
      _t: Date.now()
    },
    responseType: 'blob'
  }),


  // Nuevos endpoints para gráficos y WhatsApp
  getChartData: (type, startDate, endDate, doctorId = null) => api.get('/reportes/chart-data', {
    params: {
      type: type,
      start_date: startDate,
      end_date: endDate,
      doctor_id: doctorId,
      _t: Date.now()
    }
  }),

  sendWhatsApp: (data) => api.post('/reportes/send-whatsapp', data),

  getNotificationHistory: () => api.get('/reportes/notification-history'),
};

// Categories API
export const categoriesAPI = {
  getAll: () => api.get('/categorias'),
  getById: (id) => api.get(`/categorias/${id}`),
  create: (data) => api.post('/categorias', data),
  update: (id, data) => api.put(`/categorias/${id}`, data),
  delete: (id) => api.delete(`/categorias/${id}`),
};

// Services API
export const servicesAPI = {
  getAll: (params) => api.get('/servicios', {
    params: {
      ...params,
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  getAllWithStats: () => api.get('/servicios-con-stats'),
  getById: (id) => api.get(`/servicios/${id}`, {
    params: {
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  create: (data) => api.post('/servicios', data),
  update: (id, data) => api.put(`/servicios/${id}`, data),
  delete: (id, data) => api.delete(`/servicios/${id}`, { data }),
  getInactive: (params) => api.get('/servicios-inactivos', {
    params: {
      ...params,
      _t: Date.now() // Agregar timestamp para evitar caché
    }
  }),
  activate: (id) => api.patch(`/servicios/${id}/activar`, {}, {
    params: {
      _t: Date.now() // Evitar problemas de caché
    }
  }),
};

// Requests API
export const requestsAPI = {
  getAll: () => api.get('/solicitudes'),
  getById: (id) => api.get(`/solicitudes/${id}`, {
    params: {
      _t: Date.now() // Evitar caché
    }
  }),
  create: (data) => api.post('/solicitudes', data),
  update: (id, data) => api.put(`/solicitudes/${id}`, data),
  delete: (id) => api.delete(`/solicitudes/${id}`),
  updateResult: (id, data) => api.patch(`/solicitudes/${id}/resultado`, data),
  updateStatus: (id, estado) => api.patch(`/solicitudes/${id}/estado`, { estado }),
  getAllWithStatus: () => api.get('/solicitudes-con-estado'),
  getPendingRequests: (params) => {
    return api.get('/solicitudes-con-estado', {
      params: {
        estado: 'pendiente',
        ...params,
        _t: Date.now() // Evitar caché
      }
    }).then(response => {
      return response;
    }).catch(error => {
      console.error('Error al obtener solicitudes pendientes:', error);
      throw error;
    });
  },
  // Doctor-specific endpoints
  getDoctorRequests: () => api.get('/doctor/solicitudes'),
  createDoctorRequest: (data) => api.post('/doctor/solicitudes', data),
  generateQr: (id) => api.get(`/solicitudes/${id}/qr`, {
    params: {
      _t: Date.now() // Evitar caché
    }
  }),
};

// Request Details API
export const requestDetailsAPI = {
  getAll: () => api.get('/detallesolicitud'),
  getById: (id) => api.get(`/detallesolicitud/${id}`),
  create: (data) => api.post('/detallesolicitud', data),
  update: (id, data) => api.put(`/detallesolicitud/${id}`, data),
  delete: (id) => api.delete(`/detallesolicitud/${id}`),
  getByRequest: (requestId) => api.get(`/solicitudes/${requestId}/detalles`, {
    params: {
      _t: Date.now() // Evitar caché
    }
  }),
  registerResults: (id, data) => api.post(`/detallesolicitud/${id}/resultados`, data),
  updateStatus: (id, estado) => api.patch(`/detallesolicitud/${id}/estado`, { estado }),
};

// Results API
export const resultsAPI = {
  getAll: () => api.get('/resultados-examen'),
  getById: (id) => api.get(`/resultados-examen/${id}`),
  create: (data) => api.post('/resultados-examen', data),
  update: (id, data) => api.put(`/resultados-examen/${id}`, data),
  delete: (id) => api.delete(`/resultados-examen/${id}`),
};

// DNI API
export const dniAPI = {
  consult: (dni) => api.get(`/dni/${dni}`),
};

// Notifications API
export const notificationsAPI = {
  getAll: () => api.get('/notifications'),
  getUnread: () => api.get('/notifications/unread'),
  markAsRead: (id) => api.post(`/notifications/${id}/read`),
  markAllAsRead: () => api.post('/notifications/mark-all-read'),
  delete: (id) => api.delete(`/notifications/${id}`),
  clearRead: () => api.delete('/notifications/read/clear'),
};

// Dashboard API optimizada
export const dashboardAPI = {
  getStats: () => api.get('/dashboard/stats'),
  getPendingRequests: () => api.get('/dashboard/pending-requests'),
  getRecentActivity: () => api.get('/dashboard/recent-activity'),
  
  // Dashboard de doctor
  getDoctorStats: () => api.get('/dashboard/doctor/stats'),
  getDoctorRecentRequests: () => api.get('/dashboard/doctor/recent-requests'),
};

// Exportar servicio de Reverb para WebSocket
export { reverbService };

export default api;

