# Frontend del Sistema de Laboratorio

Este proyecto es el frontend para el Sistema de Laboratorio Clínico, desarrollado con React, Vite y Tailwind CSS.

## Características

- Interfaz de usuario moderna y responsive
- Modo claro y oscuro
- Autenticación de usuarios
- Gestión de pacientes, exámenes, solicitudes y resultados
- Optimizado para rendimiento

## Requisitos previos

- Node.js (v14 o superior)
- npm o yarn
- Backend del Sistema de Laboratorio en ejecución

## Instalación

1. Clona este repositorio o descarga los archivos
2. Navega al directorio del proyecto
3. Instala las dependencias:

```bash
npm install
# o
yarn install
```

## Configuración

El proyecto está configurado para conectarse al backend en `https://labbackend.bonelektroniks.com`. Si tu backend se ejecuta en una URL diferente, modifica el archivo `vite.config.js`.

## Ejecución en desarrollo

```bash
npm run dev
# o
yarn dev
```

Esto iniciará el servidor de desarrollo en `http://localhost:5173`.

## Construcción para producción

```bash
npm run build
# o
yarn build
```

Los archivos de producción se generarán en el directorio `dist`.

## Estructura del proyecto

```
frontend/
├── public/             # Archivos estáticos
├── src/                # Código fuente
│   ├── components/     # Componentes reutilizables
│   ├── contexts/       # Contextos de React (Auth, Theme)
│   ├── pages/          # Páginas de la aplicación
│   ├── services/       # Servicios API
│   ├── App.jsx         # Componente principal
│   ├── main.jsx        # Punto de entrada
│   └── index.css       # Estilos globales
├── index.html          # Plantilla HTML
├── package.json        # Dependencias y scripts
├── vite.config.js      # Configuración de Vite
└── tailwind.config.js  # Configuración de Tailwind CSS
```

## Características principales

### Autenticación

El sistema utiliza autenticación basada en tokens JWT. Los tokens se almacenan en localStorage.

### Tema claro/oscuro

El sistema soporta temas claro y oscuro, con detección automática de preferencias del sistema y persistencia de la selección del usuario.

### Gestión de datos

- **Pacientes**: Registro, búsqueda y gestión de pacientes
- **Exámenes**: Catálogo de exámenes disponibles
- **Solicitudes**: Creación y seguimiento de solicitudes de exámenes
- **Resultados**: Registro y consulta de resultados de exámenes

## Licencia

Este proyecto está licenciado bajo la Licencia MIT.
