# 🍽️ Maicelo Restobar - Plataforma Web

Sistema web desarrollado para **Maicelo Restobar**, que permite mostrar información del restaurante, gestionar reservas, administrar el contenido desde un panel administrativo e integrar servicios externos como IA y WhatsApp.

---

# Tecnologías utilizadas

## Frontend
- HTML5
- CSS3
- JavaScript (ES6)
- Bootstrap 5.3

## Backend
- PHP 8.2
- PDO
- Apache (XAMPP)

## Base de datos
- MySQL

## Integraciones
- Groq API (IA)
- Evolution API (WhatsApp)

---

# Requisitos

Antes de ejecutar el proyecto debes tener instalado:

- XAMPP 8.x
- PHP 8.2 o superior
- MySQL
- Git (opcional)
- Visual Studio Code (recomendado)

---

# Instalación

## 1. Clonar el proyecto

```bash
git clone https://github.com/isabfernandez38-crypto/isa_bb.git
```

o descargar el ZIP desde GitHub.

---

## 2. Copiar el proyecto

Copia la carpeta dentro de:

```
C:\xampp\htdocs\
```

Por ejemplo:

```
C:\xampp\htdocs\maicelo\
```

> Si utilizas otro nombre de carpeta, recuerda actualizar las rutas del archivo `.htaccess`.

---

## 3. Iniciar XAMPP

Inicia los siguientes servicios:

- Apache
- MySQL

Si Apache utiliza un puerto diferente (por ejemplo **8012**), accede utilizando ese puerto.

Ejemplo:

```
http://localhost:8012/maicelo/
```

---

## 4. Crear la base de datos

Abre phpMyAdmin

```
http://localhost/phpmyadmin
```

o

```
http://localhost:8012/phpmyadmin
```

(según tu configuración).

Importa el archivo:

```
database/maicelo_db.sql
```

---

## 5. Configurar el archivo .env

Copia:

```
.env.example
```

como

```
.env
```

y modifica las variables.

Ejemplo:

```env
DB_HOST=localhost
DB_PORT=3307
DB_DATABASE=maicelo_db
DB_USERNAME=root
DB_PASSWORD=

GROQ_API_KEY=TU_API_KEY
EVOLUTION_API_KEY=TU_API_KEY
EVOLUTION_INSTANCE=TU_INSTANCIA
```

> Ajusta el puerto MySQL según la configuración de tu XAMPP.

---

# Estructura del proyecto

```
maicelo/
│
├── admin/
├── api/
├── assets/
├── cache/
├── config/
├── cron/
├── database/
├── logs/
├── src/
│   ├── Core/
│   ├── Repository/
│   └── Services/
│
├── .env
├── .env.example
├── .htaccess
├── index.html
└── README.md
```

---

# Acceso al sistema

## Sitio web

```
http://localhost/maicelo/
```

o

```
http://localhost:8012/maicelo/
```

si Apache utiliza el puerto 8012.

---

## Panel administrativo

```
http://localhost/maicelo/admin/
```

---

# Credenciales del administrador

```
Correo:
admin@maicelorestbar.com

Contraseña:
Maicelo2025!
```

> Cambiar estas credenciales antes de publicar el proyecto.

---

# Características

- Página principal del restobar.
- Panel administrativo.
- Gestión de reservas.
- Gestión de horarios.
- Gestión de menú.
- Gestión de mensajes.
- Integración con WhatsApp.
- Integración con IA mediante Groq.
- Arquitectura basada en PHP sin frameworks.
- Acceso a datos mediante PDO.

---

# Seguridad

El proyecto incorpora:

- CSRF Protection
- Rate Limiter
- Error Handler
- Sistema de Logs
- Cache
- Variables de entorno mediante `.env`

---

# Desarrollo

Para contribuir al proyecto:

```bash
git clone https://github.com/isabfernandez38-crypto/isa_bb.git

cd isa_bb
```

Crear una nueva rama:

```bash
git checkout -b feature/nueva-funcionalidad
```

Realizar cambios:

```bash
git add .
git commit -m "Nueva funcionalidad"
git push origin feature/nueva-funcionalidad
```

---

# Licencia

Proyecto desarrollado con fines académicos y de prácticas profesionales.