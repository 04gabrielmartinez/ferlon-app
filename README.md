# Fase 1 - PHP 8.2 MVC ligero (Auth + RBAC + Tailwind)

Proyecto base en PHP 8.2+ con arquitectura MVC minimalista, login moderno y preparado para Apache + MySQL.

## Características

- Rutas limpias: `/login`, `/logout`, `/dashboard`.
- Front controller en `public/index.php`.
- Seguridad backend:
  - `password_hash` / `password_verify`.
  - sesiones seguras (`strict_mode`, `httponly`, `samesite=Lax`, `secure` automático por HTTPS).
  - regeneración de ID de sesión en login y cada 15 minutos.
  - CSRF token en login.
  - rate limit básico por IP en sesión: 5 intentos fallidos, bloqueo 5 minutos.
  - 2FA opcional por `AUTH_2FA_ENABLED` (apagado por defecto).
  - RBAC básico con middleware por rol (`role:admin`).
- PDO con prepared statements y `utf8mb4`.
- Frontend moderno con Tailwind CSS + Alpine.js.
- Vite solo en desarrollo (`VITE_DEV=true`).
- Logs a archivo en `storage/logs/app.log`.
- Script de respaldo SQL: `scripts/backup_db.sh`.

## Estructura

```text
/
  app/
    Core/
    Controllers/
    Models/
    Views/
  public/
  storage/
  resources/
  scripts/
  docker/
  docker-compose.yml
  composer.json
  .env.example
  README.md
```

## Requisitos

- PHP 8.2+
- MySQL 8+
- Apache con `mod_rewrite`
- Node.js 18+ (solo para usar Vite en desarrollo)

## Desarrollo local con Docker

1. Copiar variables de entorno:

```bash
cp .env.example .env
```

2. Ajustar `.env` con credenciales locales.

3. Levantar servicios:

```bash
docker compose up -d --build
```

4. (Opcional) activar Vite en desarrollo:

```bash
npm install
npm run dev
```

Luego en `.env`:

```env
VITE_DEV=true
VITE_DEV_SERVER=http://localhost:5173
```

5. Abrir app:

- http://localhost:8080/login

Nota: en producción o si no usas Vite, deja `VITE_DEV=false` para cargar Tailwind/Alpine por CDN.

5. Crear/ajustar tabla `users` y usuario admin (desde MySQL):

```sql
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  role VARCHAR(50) NOT NULL DEFAULT 'admin',
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (nombre, email, password_hash, is_active)
VALUES (
  'Administrador',
  'admin@admin.do',
  'admin',
  '$2y$12$XXXXXXXXXXXXXX',
  1
);
```

Genera hash real con `php -r "echo password_hash('Admin123', PASSWORD_DEFAULT), PHP_EOL;"`.

Puedes probar login con:

- Correo: `admin@admin.do`
- Contraseña: `Admin123`

## Deploy en produccion

### Opcion recomendada: contenedor

- Construir imagen con el `Dockerfile` raiz.
- El contenedor expone Apache y sirve `public/` como document root.
- Persistir estas rutas fuera de la imagen:
  - `public/uploads/`
  - `storage/logs/`
  - `storage/backups/`
- Inyectar variables reales desde el entorno del servidor o panel.

### Opcion servidor Apache

1. Subir el proyecto, excluyendo:

- `docker/`
- `docker-compose.yml`

2. Configurar `DocumentRoot` para que apunte a `/public`.

3. Si tu hosting no permite cambiar `DocumentRoot`, mueve el contenido de `public/` a `public_html/` y ajusta rutas internas si aplica.

4. Asegúrate de tener `mod_rewrite` activo y `.htaccess` habilitado.

5. Crear `.env` en el servidor con credenciales reales de MySQL, por ejemplo:

```env
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/Mexico_City
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tu_bd
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

6. Crear la tabla `users` (incluyendo `role`) y un usuario administrador con contraseña hasheada por `password_hash`.

## Backups

Ejecutar respaldo manual:

```bash
./scripts/backup_db.sh
```

Archivo generado en `storage/backups/`.

## Notas

- No se depende de Node en produccion.
- Carpeta de logs:
  - errores PHP/app: `storage/logs/app.log`
  - auditoria de acciones (login/logout/CRUD):
    - tabla MySQL: `activity_logs`
    - fallback en archivo: `storage/logs/activity.log`
