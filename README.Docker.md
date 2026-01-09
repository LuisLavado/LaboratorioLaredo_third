# 🐳 Docker Setup - Laboratorio Laredo

## Inicio Rápido

```bash
docker-compose up -d
```

**Listo!** 
- Frontend: http://localhost:5173
- Backend:  http://localhost:8000

## Lo que hace

Usa tu `.env` existente con la BD externa que ya tienes configurada.

- **Backend Laravel** en puerto 8000 
- **Frontend React** en puerto 5173

El backend automáticamente:
- Se conecta a tu BD externa (161.132.39.55)
- Ejecuta `migrate` (y `seed` si es primera vez)
- Crea el enlace de storage

## Comandos útiles

```bash
# Ver logs
docker-compose logs -f

# Reiniciar
docker-compose restart

# Detener
docker-compose down
```
