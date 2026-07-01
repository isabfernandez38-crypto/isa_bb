# Maicelo Restobar — Plataforma Web

Plataforma web completa para Maicelo Restobar, San Isidro, Lima.

## Stack
- **Frontend**: HTML5, CSS3, Vanilla JS ES6+, Bootstrap 5.3
- **Backend**: PHP 8.2 (sin frameworks), PDO
- **Base de datos**: MySQL 8 (puerto 3007 en XAMPP local)
- **IA**: Gemini API (gemini-2.0-flash)
- **WhatsApp**: Evolution API

## Instalación en XAMPP

### 1. Clonar / copiar
```
C:\xampp\htdocs\maicelo\
```

### 2. Crear base de datos
```bash
# Acceder a MySQL en puerto 3007
mysql -u root -P 3007 < database/maicelo_db.sql
```
O importar desde phpMyAdmin en `http://localhost/phpmyadmin`.

### 3. Configurar .env
Editar `.env` con tus valores:
```
DB_PORT=3007
GEMINI_API_KEY=tu_clave_aqui
EVOLUTION_API_KEY=tu_clave_evolution
EVOLUTION_INSTANCE=nombre_instancia
```

### 4. Permisos de carpetas
Verificar que Apache tenga permisos de escritura en:
- `cache/`
- `logs/`

### 5. Verificar Apache
- Habilitar `mod_rewrite` en `httpd.conf`
- Asegurarse de que `.htaccess` esté activo (`AllowOverride All`)

## URLs de acceso

| URL | Descripción |
|-----|-------------|
| `http://localhost/maicelo/` | Sitio público |
| `http://localhost/maicelo/admin/` | Panel admin |

## Credenciales admin por defecto

Las credenciales iniciales del administrador se definen al importar
`database/maicelo_db.sql`. Consulta ese archivo (o al responsable del proyecto)
para el usuario y contraseña por defecto.

**⚠️ Cambiar la contraseña inmediatamente tras el primer acceso, sobre todo en producción.**

## Estructura
```
maicelo/
├── .env                    ← Variables de entorno
├── .htaccess               ← Seguridad y rewrites
├── index.html              ← SPA frontend
├── config/                 ← Bootstrap y base de datos
├── src/Core/               ← Logger, RateLimiter, CSRF, Cache, ErrorHandler
├── src/Repository/         ← Acceso a datos PDO
├── src/Services/           ← WhatsApp (Evolution API)
├── api/                    ← Endpoints JSON públicos
├── api/admin/              ← Endpoints JSON protegidos
├── admin/                  ← Panel de administración PHP
├── assets/css/             ← Estilos (main, animations, admin)
├── assets/js/              ← Scripts (main, menu, reservas, chat)
├── database/               ← SQL completo
├── cache/                  ← Caché JSON (auto-generado)
└── logs/                   ← Logs del sistema (auto-generado)
```

## APIs disponibles

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/csrf.php` | GET | Obtener token CSRF |
| `/api/menu.php` | GET | Carta completa (caché 30min) |
| `/api/reservas.php` | GET/POST | Crear y consultar reservas |
| `/api/chat.php` | POST | Chat con IA (Gemini) |
| `/api/horarios.php` | GET | Horarios del restaurante |
| `/api/mesas.php` | GET | Disponibilidad de mesas |
| `/api/admin/auth.php` | GET/POST | Login/logout admin |
| `/api/admin/dashboard.php` | GET | Estadísticas |
| `/api/admin/reservas.php` | GET/PUT/DELETE | Gestión reservas |
| `/api/admin/menu.php` | GET/POST/PUT/DELETE | CRUD menú |
| `/api/admin/mesas.php` | GET/PUT | Estado mesas |
| `/api/admin/conversaciones.php` | GET | Historial chat IA |
| `/api/admin/promociones.php` | GET/POST/PUT/DELETE | CRUD promociones |

## Seguridad implementada
- CSRF tokens en formularios
- Rate limiting por IP (archivos)
- Sesiones HTTP-only con SameSite=Strict
- Prepared statements PDO en todas las queries
- Bloqueo de rutas sensibles (.env, /src/, /config/)
- Headers de seguridad (X-Frame-Options, CSP, etc.)
- Bcrypt costo 12 para contraseñas
- Bloqueo de cuenta tras 5 intentos fallidos

## Variables de entorno requeridas

| Variable | Descripción |
|----------|-------------|
| `DB_HOST` | Host MySQL |
| `DB_PORT` | Puerto MySQL (3007 en XAMPP local) |
| `DB_NAME` | Nombre de la base de datos |
| `DB_USER` | Usuario MySQL |
| `DB_PASS` | Contraseña MySQL |
| `GEMINI_API_KEY` | Clave API de Gemini |
| `EVOLUTION_API_URL` | URL de Evolution API |
| `EVOLUTION_API_KEY` | Clave de Evolution API |
| `EVOLUTION_INSTANCE` | Nombre de instancia WhatsApp |
