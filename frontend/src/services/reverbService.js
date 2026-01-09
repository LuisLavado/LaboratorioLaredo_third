import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

// Configurar Pusher globalmente
window.Pusher = Pusher;

// Configuración de URLs desde variables de entorno
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';
const REVERB_KEY = import.meta.env.VITE_REVERB_APP_KEY || 'ynws9ha5gsf0proyy0vk';
const REVERB_HOST = import.meta.env.VITE_REVERB_HOST || 'localhost';
const REVERB_PORT = import.meta.env.VITE_REVERB_PORT || 8080;
const REVERB_SCHEME = import.meta.env.VITE_REVERB_SCHEME || 'http';

/**
 * Clase para gestionar la conexión con Laravel Reverb para el frontend normal
 */
export class ReverbService {
  constructor() {
    this.echo = null;
    this.connected = false;
    this.listeners = new Map();
    this.activeChannels = new Map();
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 3;
    this.userRole = null;
    this.globalListenerAttached = false; // Flag para evitar duplicar el listener global
  }

  /**
   * Inicializar la conexión con Laravel Echo y Reverb
   */
  connect(userRole = 'doctor') {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    if (!token) {
      console.warn('⚠️ [Reverb] No hay token, no se puede conectar');
      return;
    }

    // Si ya hay una instancia de Echo, verificar si está realmente conectada
    if (this.echo && this.connected) {
      try {
        // Verificar si el conector de Pusher está activo
        const pusherState = this.echo.connector?.pusher?.connection?.state;
        if (pusherState === 'connected') {
          return;
        }
      } catch (error) {
        console.warn('⚠️ [Reverb] Error verificando estado de conexión:', error);
      }
    }

    // Desconectar cualquier conexión previa antes de crear una nueva
    if (this.echo) {
      try {
        this.echo.disconnect();
        this.activeChannels.clear();
      } catch (error) {
        console.warn('⚠️ [Reverb] Error al desconectar conexión previa:', error);
      }
      this.echo = null;
      this.connected = false;
    }

    this.userRole = userRole;

    try {
      // Crear instancia de Laravel Echo
      this.echo = new Echo({
        broadcaster: 'reverb',
        key: REVERB_KEY,
        wsHost: REVERB_HOST,
        wsPort: REVERB_PORT,
        wssPort: REVERB_PORT,
        forceTLS: REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: `${BACKEND_URL}/api/broadcasting/auth`,
        auth: {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
        },
      });

      if (!this.echo) {
        console.error('❌ [Reverb] No se pudo crear la instancia de Echo');
        return;
      }

      // Configurar event listeners
      this.setupConnectionHandlers();

      // Registrar conexión en el servidor
      this.registerConnection(user);

      // Suscribirse a canales según el role
      this.subscribeToChannels();

      this.connected = true;

    } catch (error) {
      console.error('❌ [Reverb] Error al conectar:', error);
      this.connected = false;
    }
  }

  /**
   * Configurar manejadores de eventos de conexión
   */
  setupConnectionHandlers() {
    if (!this.echo || !this.echo.connector || !this.echo.connector.pusher) {
      console.warn('⚠️ [Reverb] Pusher no está disponible para configurar handlers');
      return;
    }

    const pusher = this.echo.connector.pusher;

    // Evento de conexión establecida
    pusher.connection.bind('connected', () => {
      this.connected = true;
      this.reconnectAttempts = 0;
    });

    // Evento de desconexión
    pusher.connection.bind('disconnected', () => {
      this.connected = false;
    });

    // Evento de error
    pusher.connection.bind('error', (error) => {
      console.error('❌ [Reverb] Error de conexión:', error);
      this.reconnectAttempts++;

      if (this.reconnectAttempts >= this.maxReconnectAttempts) {
        console.error('🚫 [Reverb] Máximo número de intentos de reconexión alcanzado');
      }
    });

    // Evento de estado cambiado (silencioso)
    pusher.connection.bind('state_change', (states) => {
      // Solo log en caso de errores
    });
  }

  /**
   * Registrar conexión en el servidor
   */
  async registerConnection(user) {
    if (!user || !user.id) {
      console.warn('⚠️ [Reverb] No se puede registrar conexión sin información del usuario');
      return;
    }

    const token = localStorage.getItem('token');
    if (!token) {
      console.warn('⚠️ [Reverb] No hay token disponible para registrar conexión');
      return;
    }

    try {
      const fullName = user.name || `${user.nombre || ''} ${user.apellido || ''}`.trim() || 'Usuario';

      const response = await axios.post(
        `${API_BASE_URL}/websocket/connect`,
        {
          userId: user.id,
          name: fullName,
          role: user.role || this.userRole,
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        }
      );

    } catch (error) {
      console.error('❌ [Reverb] Error al registrar conexión:', error.response?.data || error.message);
      // No lanzar error, solo loguearlo
    }
  }

  /**
   * Suscribirse a canales según el role del usuario
   */
  subscribeToChannels() {
    if (!this.echo) {
      console.warn('⚠️ [Reverb] Echo no está inicializado');
      return;
    }

    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // Canales según el role
    if (this.userRole === 'doctor') {
      // Suscribirse al canal privado del doctor para recibir notificaciones de resultados
      const doctorChannel = this.echo.private(`doctor.${user.id}`);
      this.activeChannels.set(`doctor.${user.id}`, doctorChannel);
      
      // Error handler
      doctorChannel.error((error) => {
        console.error('❌ [Reverb] Error en canal doctor:', error);
      });
      
      // Escuchar el evento result_ready
      doctorChannel.listen('result_ready', (data) => {
        this.notifyListeners('result_ready', data);
      });
      
      // Escuchar TODOS los eventos con bind_global
      if (doctorChannel.subscription && doctorChannel.subscription.bind_global) {
        doctorChannel.subscription.bind_global((eventName, data) => {
          if (eventName === 'result_ready') {
            const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
            this.notifyListeners('result_ready', parsedData);
          }
        });
      }
    } else if (this.userRole === 'laboratorio' || this.userRole === 'laboratory') {
      // Suscribirse al canal laboratory para recibir nuevas solicitudes
      let laboratoryChannel = this.activeChannels.get('laboratory');
      
      if (!laboratoryChannel) {
        // Crear nuevo canal solo si no existe
        laboratoryChannel = this.echo.channel('laboratory');
        this.activeChannels.set('laboratory', laboratoryChannel);
      }
      
      // Escuchar TODOS los eventos en el canal usando bind_global de Pusher
      if (laboratoryChannel.subscription && laboratoryChannel.subscription.bind_global) {
        // Crear la función del listener
        const handleNewRequest = (eventName, data) => {
          // Si es new_request, procesarlo
          if (eventName === 'new_request') {
            // Parsear data si es string
            const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
            this.notifyListeners('new_request', parsedData);
          }
        };
        
        // Siempre adjuntar el listener (Pusher maneja duplicados internamente)
        laboratoryChannel.subscription.bind_global(handleNewRequest);
      }
      
      // Error handler
      laboratoryChannel.error((error) => {
        console.error('❌ [Reverb] Error en canal laboratory:', error);
      });
    }
  }

  /**
   * Desconectar del servidor
   */
  async disconnect(force = false) {
    // Si no es forzado (ej: logout), solo marcar como desconectado pero mantener la conexión
    if (!force) {
      this.connected = false;
      return;
    }

    // Desconexión completa (solo para logout o cierre real)
    // Verificar si ya está desconectado para evitar errores
    if (!this.connected && !this.echo) {
      return;
    }

    const user = JSON.parse(localStorage.getItem('user') || '{}');

    if (user.id) {
      try {
        const token = localStorage.getItem('token');
        await axios.post(
          `${API_BASE_URL}/websocket/disconnect`,
          { userId: user.id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        );
      } catch (error) {
        console.error('❌ [Reverb] Error al registrar desconexión:', error);
      }
    }

    // Dejar todos los canales de forma segura
    try {
      this.activeChannels.forEach((channel, channelName) => {
        if (this.echo) {
          this.echo.leave(channelName);
        }
      });
      this.activeChannels.clear();
    } catch (error) {
      console.warn('⚠️ [Reverb] Error al dejar canales:', error);
    }

    // Desconectar Echo de forma segura
    if (this.echo) {
      try {
        this.echo.disconnect();
      } catch (error) {
        console.warn('⚠️ [Reverb] Error al desconectar Echo:', error);
      }
      this.echo = null;
    }

    this.connected = false;
  }

  /**
   * Verificar si está conectado
   */
  isConnected() {
    return this.connected && this.echo !== null;
  }

  /**
   * Suscribirse a un evento
   */
  subscribe(event, callback) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set());
    }
    this.listeners.get(event).add(callback);
  }

  /**
   * Desuscribirse de un evento
   */
  unsubscribe(event, callback) {
    if (this.listeners.has(event)) {
      this.listeners.get(event).delete(callback);
      if (this.listeners.get(event).size === 0) {
        this.listeners.delete(event);
      }
    }
  }

  /**
   * Notificar a los listeners
   */
  notifyListeners(event, data) {
    if (this.listeners.has(event)) {
      this.listeners.get(event).forEach((callback) => {
        try {
          callback(data);
        } catch (error) {
          console.error(`❌ [Reverb] Error en callback para evento ${event}:`, error);
        }
      });
    } else {
      console.warn(`⚠️ [Reverb] No hay listeners registrados para el evento: ${event}`);
    }
  }

  /**
   * Reconectar manualmente
   */
  manualReconnect() {
    if (this.isConnected()) {
      return;
    }

    this.reconnectAttempts = 0;
    this.disconnect();

    setTimeout(() => {
      this.connect(this.userRole);
    }, 1000);
  }
}

// Crear instancia global
export const reverbService = new ReverbService();

// Exponer globalmente para debugging
if (typeof window !== 'undefined') {
  window.reverbService = reverbService;
  window.reconnectReverb = () => {
    reverbService.manualReconnect();
  };
}

export default reverbService;
