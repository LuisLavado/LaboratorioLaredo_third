# Migración Frontend a Laravel Reverb

## 📋 Cambios Realizados

### Archivos Nuevos
- ✅ `.env` - Configuración de variables de entorno
- ✅ `src/services/reverbService.js` - Nuevo servicio para Laravel Reverb

### Archivos Modificados
- ✅ `src/services/api.js` - Actualizado para usar Reverb
- ✅ `package.json` - laravel-echo ya instalado

### Archivos a Eliminar (Obsoletos)
Estos archivos ya NO se deben usar:
- ❌ `src/services/websocketService.js`
- ❌ `src/services/socketIOService.js`
- ❌ `src/services/socketService.js`
- ❌ `src/services/webhookService.js`
- ❌ `src/services/webSocketProvider.js`

---

## 🔧 Cómo Usar el Nuevo Servicio

### Importar el Servicio

```javascript
import { reverbService } from '../services/api';
// o
import reverbService from '../services/reverbService';
```

### Conectar al WebSocket

```javascript
// En tu componente de autenticación o App.jsx
import { useEffect } from 'react';
import { reverbService } from './services/api';

function App() {
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  
  useEffect(() => {
    if (user && user.id) {
      // Conectar según el role del usuario
      reverbService.connect(user.role); // 'doctor', 'laboratorio', etc.
    }
    
    return () => {
      reverbService.disconnect();
    };
  }, [user.id]);
  
  return <YourApp />;
}
```

### Escuchar Eventos

```javascript
import { useEffect } from 'react';
import { reverbService } from '../services/api';

function NotificationsComponent() {
  useEffect(() => {
    // Suscribirse a notificaciones
    const handleNotification = (data) => {
      console.log('Nueva notificación:', data);
      // Mostrar notificación al usuario
    };
    
    reverbService.subscribe('notification', handleNotification);
    
    // Limpiar al desmontar
    return () => {
      reverbService.unsubscribe('notification', handleNotification);
    };
  }, []);
  
  return <div>Notificaciones</div>;
}
```

### Eventos Disponibles

Según el role del usuario, se recibirán diferentes eventos:

#### Para Doctores (`role: 'doctor'`)
- `notification` - Notificaciones personales
- `result_ready` - Resultado de examen listo

#### Para Laboratorio (`role: 'laboratorio'`)
- `notification` - Notificaciones personales
- `new_request` - Nueva solicitud de examen

---

## 🔄 Migrar Componentes Existentes

### Antes (Socket.IO)
```javascript
import websocketService from '../services/websocketService';

// Conectar
websocketService.connect(token);

// Escuchar evento
websocketService.callbacks.onMessage = (data) => {
  console.log('Mensaje:', data);
};

// Desconectar
websocketService.disconnect();
```

### Ahora (Laravel Reverb)
```javascript
import { reverbService } from '../services/api';

// Conectar
reverbService.connect(userRole);

// Escuchar evento
const handleEvent = (data) => {
  console.log('Evento:', data);
};
reverbService.subscribe('event_name', handleEvent);

// Desconectar
reverbService.unsubscribe('event_name', handleEvent);
reverbService.disconnect();
```

---

## 🐛 Debugging

Abre la consola del navegador y ejecuta:

```javascript
// Ver estado de conexión
window.reverbService.isConnected()

// Reconectar manualmente
window.reconnectReverb()

// Ver canales activos
window.reverbService.activeChannels

// Ver listeners registrados
window.reverbService.listeners
```

---

## ⚙️ Configuración de Producción

Actualiza el archivo `.env` para producción:

```env
VITE_API_URL=https://tu-dominio.com/api
VITE_BACKEND_URL=https://tu-dominio.com
VITE_REVERB_APP_KEY=tu-production-key
VITE_REVERB_HOST=tu-dominio.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

---

## 📝 TODOs

- [ ] Actualizar AuthContext.jsx para usar reverbService
- [ ] Actualizar componentes de notificaciones
- [ ] Actualizar hooks personalizados (useWebSocket, useNotifications)
- [ ] Eliminar imports de servicios obsoletos
- [ ] Probar todas las funcionalidades en desarrollo
- [ ] Probar en producción

---

## 📚 Recursos

- [Laravel Reverb Docs](https://laravel.com/docs/11.x/reverb)
- [Laravel Echo Docs](https://laravel.com/docs/11.x/broadcasting#client-side-installation)
- Documentación del Backend: `MIGRACION_LARAVEL_REVERB.md`

---

**Fecha:** 2 de Octubre, 2025  
**Status:** ✅ Servicio creado - Pendiente migración de componentes
