# Sistema de Laboratorio Clínico Laredo

Sistema web para la gestión de laboratorio clínico, desarrollado con Laravel y React.

## Características Principales

- Gestión de pacientes, médicos, servicios y exámenes
- Sistema de solicitudes y resultados de exámenes
- Reportes estadísticos avanzados
- Exportación a Excel y PDF
- Notificaciones en tiempo real
- Panel de administración
- Gestión de usuarios y roles
- Exámenes dinámicos con campos personalizables

## Tecnologías Utilizadas

- **Backend**: Laravel 10
- **Frontend**: React con Material-UI y Tailwind CSS
- **Base de datos**: MySQL
- **Autenticación**: Laravel Sanctum
- **Exportación**: Laravel Excel y DomPDF
- **Tiempo real**: Laravel Echo, Pusher y WebSockets

## Sistema de Reportes Excel Mejorado

El sistema cuenta con un avanzado módulo de generación de reportes Excel que permite exportar datos detallados y estadísticas sobre:

- Pacientes
- Exámenes
- Médicos
- Servicios
- Estadísticas generales

### Características de los Reportes Excel:

- **Múltiples hojas**: Cada reporte incluye una hoja de resumen y hojas detalladas según el tipo de reporte.
- **Información completa**: Los reportes incluyen todos los campos relevantes según la estructura real de la base de datos.
- **Filtrado avanzado**: Es posible filtrar los reportes por rangos de fechas, exámenes específicos o servicios.
- **Estilos profesionales**: Los reportes tienen un diseño profesional con encabezados, formatos y estilos adecuados.
- **Datos relacionados**: Cada entidad muestra información sobre sus relaciones (por ejemplo, exámenes realizados por paciente).

### Documentación de Reportes Excel

Se ha documentado detalladamente el sistema de reportes Excel en varios archivos:

- [Mejoras Implementadas en los Reportes Excel](EXCEL_MEJORAS_IMPLEMENTADAS.md)
- [Descripción Detallada de las Hojas de Excel](EXCEL_HOJAS_DETALLADAS.md)
- [Corrección de Datos de Pacientes en Reportes Excel](CORRECCION_EXCEL_PACIENTES.md)
- [Corrección del Campo Género en Reportes Excel](CORRECCION_GENERO_EXCEL.md)

### Validación de Reportes Excel

Puede validar la funcionalidad de exportación a Excel ejecutando el script de prueba:

```bash
php debug_excel_export.php
```

Este script verificará que todos los tipos de reportes se generen correctamente y que contengan los datos esperados.

## Instalación y Configuración

### Requisitos del Sistema

- PHP 8.1+
- MySQL 5.7+ o MariaDB 10.2+
- Composer
- Node.js 16+ y npm/pnpm

### Pasos para la Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/usuario/BackendlaboratorioLaredo.git
cd BackendlaboratorioLaredo
```

2. Instalar dependencias de PHP:
```bash
composer install
```

3. Configurar el archivo .env:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar la base de datos en el archivo .env.

5. Ejecutar migraciones y seeders:
```bash
php artisan migrate --seed
```

6. Instalar dependencias de JavaScript:
```bash
cd frontend
npm install
# o
pnpm install
```

7. Compilar assets:
```bash
npm run build
# o
pnpm run build
```

8. Iniciar el servidor:
```bash
php artisan serve
```

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Licencia

Este proyecto es software propietario de Laboratorio Clínico Laredo.

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
