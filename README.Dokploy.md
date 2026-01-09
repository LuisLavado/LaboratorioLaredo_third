# 🚀 Deploy en Dokploy - Laboratorio Laredo

## 📋 Requisitos Previos

- Cuenta en Dokploy con Traefik configurado
- Dominio configurado: `labbackend.shiroharu.me` apuntando a tu servidor Dokploy
- Puerto 443 (HTTPS) abierto en el firewall

---

## 🔧 Configuración en Dokploy

### 1️⃣ Crear Aplicación en Dokploy

1. Ve a **Dokploy Dashboard**
2. Click en **"New Application"** o **"Nueva Aplicación"**
3. Selecciona **"Docker Compose"**
4. Configura:
   - **Name**: `laboratorio-laredo-backend`
   - **Repository**: `https://github.com/Dest21/LaboratorioLaredo`
   - **Branch**: `main`
   - **Compose File**: `docker-compose.production.yml`

### 2️⃣ Configurar Variables de Entorno

En la sección **"Environment Variables"** de Dokploy, agrega **MÍNIMO** estas variables:

```env
APP_KEY=base64:PRw/JxKHyyo5o9G96z5jBimA56MVWmj6pYOfgBpU4ak=
DB_PASSWORD=tu_password_mysql_super_seguro
DB_USERNAME=root
DB_DATABASE=laboratoriolaredo
```

**⚠️ IMPORTANTE:**
- Cambia `DB_PASSWORD` por una contraseña segura
- Genera un nuevo `APP_KEY` ejecutando: `php artisan key:generate --show`

### 3️⃣ Configurar Traefik en Dokploy

**Dokploy debería detectar automáticamente** las etiquetas de Traefik del `docker-compose.production.yml`.

Verifica que estén activas:
- ✅ Router para API: `labbackend.shiroharu.me/api`
- ✅ Router para WebSocket: `labbackend.shiroharu.me/app`
- ✅ TLS/HTTPS habilitado con Let's Encrypt

### 4️⃣ Deploy Inicial

1. Click en **"Deploy"** o **"Desplegar"**
2. Espera a que el contenedor se construya (puede tardar 2-5 minutos)
3. Una vez que el estado sea **"Running"**:

### 5️⃣ Ejecutar Migraciones (SOLO LA PRIMERA VEZ)

Desde Dokploy, abre la **Terminal/Console** del contenedor `backend` y ejecuta:

```bash
php artisan migrate --force --seed
```

Esto creará:
- ✅ Todas las tablas de la base de datos
- ✅ Usuario administrador por defecto
- ✅ Categorías y exámenes de muestra
- ✅ Servicios predefinidos

---

## 🧪 Verificar Deployment

### Prueba 1: API Health Check
```bash
curl https://labbackend.shiroharu.me/api/health
```
**Esperado:** `{"status":"ok"}`

### Prueba 2: Login API
```bash
curl -X POST https://labbackend.shiroharu.me/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@laredo.com","password":"password"}'
```
**Esperado:** JSON con token de autenticación

### Prueba 3: WebSocket
Abre el navegador en: `https://lab.shiroharu.me` (frontend)
- Debería conectarse automáticamente al WebSocket
- Revisa la consola del navegador: `WebSocket connection established`

---

## 🔄 Redeploys Posteriores

Para actualizar el backend después de cambios en el código:

1. Haz push a GitHub: `git push origin main`
2. En Dokploy, click en **"Redeploy"**
3. Espera a que se reconstruya el contenedor
4. ✅ Los cambios estarán en producción

**⚠️ NO NECESITAS** volver a ejecutar migraciones a menos que hayas agregado nuevas.

---

## 📂 Estructura de Archivos de Producción

```
LaboratorioLaredo/
├── docker-compose.production.yml  ← Configuración para Dokploy
├── .env.production.example        ← Variables de ejemplo
├── Dockerfile                      ← Build del backend
└── README.Dokploy.md              ← Este archivo
```

---

## 🐛 Troubleshooting

### Error: "Connection refused"
- Verifica que el servicio `mysql` esté corriendo
- Espera 30 segundos después del deploy inicial (MySQL tarda en iniciar)

### Error: "Please provide a valid cache path" (PDFs)
- Asegúrate de que el Dockerfile tenga las carpetas:
  ```dockerfile
  mkdir -p storage/app/temp && \
  mkdir -p storage/fonts
  ```

### Error: "CORS policy"
- Verifica que `SANCTUM_STATEFUL_DOMAINS` incluya tu dominio frontend
- Revisa que los middlewares CORS estén aplicados en Traefik

### Logs del Backend
En Dokploy, ve a **"Logs"** de la aplicación `backend`:
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Ver logs de Apache
tail -f /var/log/apache2/error.log
```

---

## 🔐 Credenciales por Defecto

**Usuario Admin:**
- Email: `admin@laredo.com`
- Password: `password`

**⚠️ CAMBIA LA CONTRASEÑA** después del primer login.

---

## 📞 Soporte

Si tienes problemas con el deployment, verifica:
1. ✅ Variables de entorno configuradas en Dokploy
2. ✅ Traefik router funcionando (SSL habilitado)
3. ✅ MySQL corriendo y accesible
4. ✅ Logs del contenedor sin errores críticos
