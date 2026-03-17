# Deploy en Easypanel

## App

- Tipo: `App`
- Build type: `Dockerfile`
- Dockerfile path: `./Dockerfile`
- Puerto interno: `80`

## Variables de entorno

Definir al menos:

```env
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=UTC
AUTH_2FA_ENABLED=true
VITE_DEV=false
DB_HOST=<host_interno_mysql>
DB_PORT=3306
DB_DATABASE=ferlon
DB_USERNAME=ferlon
DB_PASSWORD=<password_seguro>
```

## Volumenes persistentes

Persistir estas rutas:

- `/var/www/html/public/uploads`
- `/var/www/html/storage/logs`
- `/var/www/html/storage/backups`

## Base de datos

- Motor: `MySQL 8`
- No exponer el puerto publicamente
- Crear un usuario dedicado para la app

## Antes de publicar

- importar el esquema SQL
- crear usuario admin inicial
- validar login
- validar escritura en `public/uploads`
- validar escritura en `storage/logs`
