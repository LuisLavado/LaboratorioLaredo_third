/**
 * WebSocket Service para notificaciones en tiempo real
 * Reemplaza el sistema de polling por WebSockets usando Laravel Reverb
 */
class WebSocketService {
    constructor() {
        this.pusher = null;
        this.channels = {};
        this.isConnected = false;
        this.config = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.callbacks = {
            onNotification: [],
            onConnect: [],
            onDisconnect: [],
            onError: []
        };
    }

    /**
     * Inicializar WebSocket
     */
    async init() {
        try {
            // Obtener configuración del servidor
            await this.loadConfig();
            
            // Inicializar Pusher
            this.initPusher();
            
            // Suscribirse a canales
            this.subscribeToChannels();
            
            console.log('✅ WebSocket Service inicializado');
        } catch (error) {
            console.error('❌ Error inicializando WebSocket:', error);
            this.handleError(error);
        }
    }

    /**
     * Cargar configuración desde el servidor
     */
    async loadConfig() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            throw new Error('No hay token de autenticación');
        }

        const response = await fetch('/api/notifications/websocket-config', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Error obteniendo configuración WebSocket');
        }

        const data = await response.json();
        this.config = data.config;
        this.channels = data.channels;
    }

    /**
     * Inicializar cliente Pusher
     */
    initPusher() {
        // Cargar Pusher desde CDN si no está disponible
        if (typeof Pusher === 'undefined') {
            this.loadPusherScript();
            return;
        }

        this.pusher = new Pusher(this.config.key, {
            wsHost: this.config.host,
            wsPort: this.config.port,
            wssPort: this.config.port,
            forceTLS: this.config.forceTLS,
            enabledTransports: this.config.enabledTransports,
            cluster: this.config.cluster,
            auth: {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            },
            authEndpoint: '/api/broadcasting/auth'
        });

        // Event listeners
        this.pusher.connection.bind('connected', () => {
            this.isConnected = true;
            this.reconnectAttempts = 0;
            console.log('🔗 WebSocket conectado');
            this.triggerCallbacks('onConnect');
        });

        this.pusher.connection.bind('disconnected', () => {
            this.isConnected = false;
            console.log('🔌 WebSocket desconectado');
            this.triggerCallbacks('onDisconnect');
            this.handleReconnect();
        });

        this.pusher.connection.bind('error', (error) => {
            console.error('❌ Error WebSocket:', error);
            this.handleError(error);
        });
    }

    /**
     * Cargar script de Pusher dinámicamente
     */
    loadPusherScript() {
        const script = document.createElement('script');
        script.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
        script.onload = () => {
            this.initPusher();
        };
        document.head.appendChild(script);
    }

    /**
     * Suscribirse a canales
     */
    subscribeToChannels() {
        if (!this.pusher) return;

        // Canal privado de notificaciones del usuario
        const privateChannel = this.pusher.subscribe(`private-${this.channels.private}`);
        privateChannel.bind('notification.sent', (data) => {
            this.handleNotification(data);
        });

        // Canal público del laboratorio
        const publicChannel = this.pusher.subscribe(this.channels.public);
        publicChannel.bind('pusher:subscription_succeeded', () => {
            console.log('✅ Suscrito a canal del laboratorio');
        });

        publicChannel.bind('laboratory', (data) => {
            this.handleLaboratoryEvent(data);
        });
    }

    /**
     * Manejar notificación recibida
     */
    handleNotification(data) {
        console.log('📬 Nueva notificación:', data);
        
        // Actualizar contador de notificaciones
        this.updateNotificationCount();
        
        // Mostrar notificación visual
        this.showNotification(data);
        
        // Ejecutar callbacks
        this.triggerCallbacks('onNotification', data);
    }

    /**
     * Manejar eventos del laboratorio
     */
    handleLaboratoryEvent(data) {
        console.log('🏥 Evento del laboratorio:', data);
        
        // Actualizar dashboard si es necesario
        if (data.event === 'solicitud.created') {
            this.updateDashboard();
        }
    }

    /**
     * Mostrar notificación visual
     */
    showNotification(data) {
        // Crear notificación del navegador si está permitido
        if (Notification.permission === 'granted') {
            new Notification(data.data.title || 'Nueva notificación', {
                body: data.data.message || 'Tienes una nueva notificación',
                icon: '/favicon.ico',
                tag: data.id
            });
        }

        // Actualizar UI de notificaciones
        this.updateNotificationUI(data);
    }

    /**
     * Actualizar UI de notificaciones
     */
    updateNotificationUI(data) {
        // Agregar notificación a la lista
        const notificationsList = document.querySelector('#notifications-list');
        if (notificationsList) {
            const notificationElement = this.createNotificationElement(data);
            notificationsList.insertBefore(notificationElement, notificationsList.firstChild);
        }

        // Actualizar badge de notificaciones
        this.updateNotificationBadge();
    }

    /**
     * Crear elemento de notificación
     */
    createNotificationElement(data) {
        const div = document.createElement('div');
        div.className = 'notification-item unread';
        div.innerHTML = `
            <div class="notification-content">
                <h4>${data.data.title || 'Notificación'}</h4>
                <p>${data.data.message || 'Nueva notificación'}</p>
                <small>${new Date(data.created_at).toLocaleString()}</small>
            </div>
        `;
        return div;
    }

    /**
     * Actualizar contador de notificaciones
     */
    async updateNotificationCount() {
        try {
            const response = await fetch('/api/notifications/unread-count', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.updateNotificationBadge(data.unread_count);
            }
        } catch (error) {
            console.error('Error actualizando contador:', error);
        }
    }

    /**
     * Actualizar badge de notificaciones
     */
    updateNotificationBadge(count = null) {
        const badge = document.querySelector('#notification-badge');
        if (badge) {
            if (count !== null) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'block' : 'none';
            }
        }
    }

    /**
     * Actualizar dashboard
     */
    updateDashboard() {
        // Disparar evento personalizado para que otros componentes se actualicen
        window.dispatchEvent(new CustomEvent('dashboard-update'));
    }

    /**
     * Manejar reconexión
     */
    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = Math.pow(2, this.reconnectAttempts) * 1000; // Backoff exponencial
            
            console.log(`🔄 Reintentando conexión en ${delay/1000}s (intento ${this.reconnectAttempts})`);
            
            setTimeout(() => {
                this.init();
            }, delay);
        } else {
            console.error('❌ Máximo de intentos de reconexión alcanzado');
            this.fallbackToPolling();
        }
    }

    /**
     * Fallback a polling si WebSocket falla
     */
    fallbackToPolling() {
        console.log('🔄 Fallback a polling...');
        // Aquí puedes implementar un sistema de polling como respaldo
        this.startPolling();
    }

    /**
     * Iniciar polling como respaldo
     */
    startPolling() {
        setInterval(async () => {
            try {
                const response = await fetch('/api/notifications/summary', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.updateNotificationBadge(data.unread_count);
                }
            } catch (error) {
                console.error('Error en polling:', error);
            }
        }, 30000); // Polling cada 30 segundos
    }

    /**
     * Manejar errores
     */
    handleError(error) {
        this.triggerCallbacks('onError', error);
    }

    /**
     * Registrar callback
     */
    on(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    }

    /**
     * Ejecutar callbacks
     */
    triggerCallbacks(event, data = null) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error en callback ${event}:`, error);
                }
            });
        }
    }

    /**
     * Solicitar permisos de notificación
     */
    async requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            return permission === 'granted';
        }
        return Notification.permission === 'granted';
    }

    /**
     * Desconectar WebSocket
     */
    disconnect() {
        if (this.pusher) {
            this.pusher.disconnect();
            this.pusher = null;
        }
        this.isConnected = false;
    }

    /**
     * Verificar si está conectado
     */
    isWebSocketConnected() {
        return this.isConnected;
    }
}

// Crear instancia global
window.WebSocketService = new WebSocketService();
