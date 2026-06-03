-- =============================================
-- MAICELO RESTOBAR — Base de Datos Completa
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS maicelo_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE maicelo_db;

-- TABLA: usuarios
CREATE TABLE IF NOT EXISTS usuarios (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NOT NULL,
  password        VARCHAR(255) NOT NULL,
  rol             ENUM('superadmin','admin','staff') DEFAULT 'staff',
  activo          TINYINT(1) DEFAULT 1,
  ultimo_login    DATETIME NULL,
  login_intentos  TINYINT DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: mesas
CREATE TABLE IF NOT EXISTS mesas (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero      INT NOT NULL,
  capacidad   INT NOT NULL,
  zona        ENUM('interior','exterior','bar','vip') DEFAULT 'interior',
  estado      ENUM('disponible','ocupada','reservada','mantenimiento') DEFAULT 'disponible',
  descripcion VARCHAR(255) NULL,
  activa      TINYINT(1) DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: reservas
CREATE TABLE IF NOT EXISTS reservas (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo           VARCHAR(20) NOT NULL,
  nombre_cliente   VARCHAR(150) NOT NULL,
  telefono         VARCHAR(20) NOT NULL,
  email            VARCHAR(150) NULL,
  fecha            DATE NOT NULL,
  hora             TIME NOT NULL,
  num_personas     TINYINT UNSIGNED NOT NULL,
  mesa_id          INT UNSIGNED NULL,
  estado           ENUM('pendiente','confirmada','cancelada','completada','no_show') DEFAULT 'pendiente',
  comentarios      TEXT NULL,
  origen           ENUM('web','chat_ia','telefono','admin') DEFAULT 'web',
  whatsapp_enviado TINYINT(1) DEFAULT 0,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_codigo (codigo),
  KEY idx_fecha (fecha),
  KEY idx_fecha_hora (fecha, hora),
  KEY idx_estado (estado),
  KEY idx_telefono (telefono),
  FOREIGN KEY fk_mesa (mesa_id) REFERENCES mesas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: categorias_menu
CREATE TABLE IF NOT EXISTS categorias_menu (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  slug   VARCHAR(100) NOT NULL,
  icono  VARCHAR(10) NULL,
  orden  TINYINT DEFAULT 0,
  activa TINYINT(1) DEFAULT 1,
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: menu
CREATE TABLE IF NOT EXISTS menu (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id  INT UNSIGNED NOT NULL,
  nombre        VARCHAR(150) NOT NULL,
  descripcion   TEXT NULL,
  precio        DECIMAL(8,2) NOT NULL,
  precio_alt    DECIMAL(8,2) NULL COMMENT 'Precio alternativo (ej: jarra)',
  unidad_alt    VARCHAR(20) NULL COMMENT 'Etiqueta precio alt (ej: jarra)',
  imagen        VARCHAR(255) NULL,
  es_destacado  TINYINT(1) DEFAULT 0,
  es_disponible TINYINT(1) DEFAULT 1,
  es_nuevo      TINYINT(1) DEFAULT 0,
  orden         INT DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_categoria (categoria_id),
  KEY idx_destacado (es_destacado),
  FOREIGN KEY fk_cat (categoria_id) REFERENCES categorias_menu(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: promociones
CREATE TABLE IF NOT EXISTS promociones (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo       VARCHAR(150) NOT NULL,
  descripcion  TEXT NULL,
  imagen       VARCHAR(255) NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin    DATE NOT NULL,
  activa       TINYINT(1) DEFAULT 1,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: horarios
CREATE TABLE IF NOT EXISTS horarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dia           ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  hora_apertura TIME NOT NULL,
  hora_cierre   TIME NOT NULL,
  cerrado       TINYINT(1) DEFAULT 0,
  UNIQUE KEY uq_dia (dia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: conversaciones (chat IA)
CREATE TABLE IF NOT EXISTS conversaciones (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id       VARCHAR(100) NOT NULL,
  nombre_usuario   VARCHAR(100) NULL,
  telefono         VARCHAR(20) NULL,
  ip_address       VARCHAR(45) NULL,
  iniciada_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  reserva_generada TINYINT(1) DEFAULT 0,
  reserva_id       INT UNSIGNED NULL,
  KEY idx_session (session_id),
  KEY idx_ultima (ultima_actividad),
  FOREIGN KEY fk_reserva_conv (reserva_id) REFERENCES reservas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: mensajes (chat IA)
CREATE TABLE IF NOT EXISTS mensajes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversacion_id INT UNSIGNED NOT NULL,
  rol             ENUM('user','assistant') NOT NULL,
  contenido       TEXT NOT NULL,
  tokens_usados   INT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_conv (conversacion_id),
  FOREIGN KEY fk_conv (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DATOS INICIALES
-- =============================================

-- Admin (contraseña: Maicelo2025! — hash bcrypt costo 12)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@maicelorestbar.com',
 '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMaLbMCKVQO0VKmr8PBY9Pmrm2',
 'superadmin');
-- NOTA: Cambiar contraseña inmediatamente en producción

-- Mesas
INSERT INTO mesas (numero, capacidad, zona) VALUES
(1,2,'bar'),(2,2,'bar'),(3,4,'interior'),(4,4,'interior'),
(5,4,'interior'),(6,4,'interior'),(7,6,'interior'),
(8,8,'vip'),(9,4,'exterior'),(10,4,'exterior');

-- Categorías del menú
INSERT INTO categorias_menu (nombre, slug, icono, orden) VALUES
('Entradas',           'entradas',      '🥗', 1),
('Bajada Criolla',     'bajada-criolla','🍽️', 2),
('Bajada Marina',      'bajada-marina', '🐟', 3),
('Bajada for the Night','bajada-night', '🌙', 4),
('Festival de Pastas', 'pastas',        '🍝', 5),
('Parrillas Maicelo',  'parrillas',     '🔥', 6),
('Pisco & Cócteles',   'piscos',        '🍹', 7),
('Tragos Mañosos',     'tragos',        '🍸', 8),
('Refrescantes',       'refrescantes',  '🥤', 9),
('Chelas',             'chelas',        '🍺', 10),
('Aguayu',             'aguayu',        '🥤', 11);

-- MENÚ COMPLETO
-- Entradas (categoria_id = 1)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible) VALUES
(1,'Choritos a la Chalaca', NULL, 20.90, 1),
(1,'Conchas a la Chalaca', NULL, 25.90, 1),
(1,'Conchas a la Parmesana x7', NULL, 35.90, 1),
(1,'Tequeños con Jamón y Queso', NULL, 20.90, 1),
(1,'Tequeños con Lomo', NULL, 24.90, 1),
(1,'Causa con Jalea de Pejerrey', NULL, 35.90, 1),
(1,'Causa Acevichada', NULL, 35.90, 1),
(1,'Trio de Causas', NULL, 35.90, 1);

-- Bajada Criolla (categoria_id = 2)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible, es_destacado) VALUES
(2,'Arroz con Pollo con Sarsa Criolla', NULL, 28.90, 1, 0),
(2,'Ají de Gallina', NULL, 32.90, 1, 0),
(2,'Maicelo a la Plancha','Pollo, arroz blanco, papas sancochadas y ensalada', 32.90, 1, 0),
(2,'Milanesa Empanizada','Arroz blanco, papas sancochadas o fritas y ensalada', 32.90, 1, 0),
(2,'Pollada','Pecho o pierna, arroz blanco, papas sancochadas o fritas y ensalada', 32.90, 1, 0),
(2,'Chicharrón de Pollo', NULL, 35.90, 1, 0),
(2,'Chaufa a lo Maicelo', NULL, 35.90, 1, 0),
(2,'Triqui Mancha Pecho','Ceviche, tallarines rojos y huancaína', 40.90, 1, 0),
(2,'Bistec a lo Maicelo', NULL, 40.90, 1, 0),
(2,'Lomo Saltado', NULL, 42.90, 1, 0),
(2,'Tacu Tacu con Lomo Saltado', NULL, 42.90, 1, 0),
(2,'Tacu Tacu en Salsa de Mariscos', NULL, 45.90, 1, 0),
(2,'Lomo Saltado a lo Maicelo', NULL, 45.90, 1, 0),
(2,'Cuatro Colores','Chanfainita, tallarines rojos, ceviche y papa a la huancaína', 45.90, 1, 1),
(2,'Ronda de Patas','2 sabores de alitas a escoger, tequeños, chicharrón de pollo y papas fritas', 74.90, 1, 0),
(2,'Ronda Piqueros','2 sabores de alitas a escoger y papas fritas', 74.90, 1, 0);

-- Bajada Marina (categoria_id = 3)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible) VALUES
(3,'Tiradito de Pescado', NULL, 24.90, 1),
(3,'Leche de Tigre', NULL, 25.90, 1),
(3,'Chicharrón de Pejerrey', NULL, 27.90, 1),
(3,'Sudado de Pescado (Filete)','Filete', 30.90, 1),
(3,'Sudado de Pescado (Entero)','Entero', 45.90, 1),
(3,'Pescado a lo Macho', NULL, 32.90, 1),
(3,'Arroz Chaufa de Mariscos', NULL, 35.90, 1),
(3,'Ceviche Carretillero', NULL, 38.90, 1),
(3,'Chicharrón de Pescado', NULL, 38.90, 1),
(3,'Dúo Marino','Ice fish, chicharrón de pescado o arroz con mariscos', 38.90, 1),
(3,'Ceviche Clásico', NULL, 42.90, 1),
(3,'Ceviche de Conchas Negras', NULL, 42.90, 1),
(3,'Trio Marino','Ice fish, chicharrón de pescado y arroz con mariscos', 42.90, 1),
(3,'Arroz con Mariscos', NULL, 45.90, 1),
(3,'Ceviche Mixto', NULL, 45.90, 1);

-- Bajada for the Night (categoria_id = 4)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible) VALUES
(4,'Burguer Clásico','Jamón y queso', 25.90, 1),
(4,'Salchimisio','Papas Maicelo, fingerhuber, huevo y plátano', 28.90, 1),
(4,'Alitas BBQ, Picante Maracuyá Acevichada', NULL, 30.90, 1),
(4,'Mollejitas a la Parrilla', NULL, 30.90, 1),
(4,'Burguer Maicelo','Jamón, queso, tocino, huevo y papas', 32.90, 1),
(4,'Anticuchos', NULL, 32.90, 1),
(4,'Duqui Carretillero', NULL, 36.90, 1);

-- Festival de Pastas (categoria_id = 5)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible) VALUES
(5,'Fettuccini a la Huancaína', NULL, 36.90, 1),
(5,'Fettuccini a lo Alfredo o a la Huancaína', NULL, 38.90, 1),
(5,'Fettuccini al Pesto con Churrasco', NULL, 38.90, 1),
(5,'Fettuccini a la Huancaína con Lomo Saltado', NULL, 43.90, 1);

-- Parrillas (categoria_id = 6)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible, es_destacado) VALUES
(6,'Parrilla Personal','Churrasco beef, anticucho y chorizo', 70.90, 1, 1),
(6,'Parrilla Familiar','Mechero, churrasco, bife, mallobos, anticuchos y chorizo con papas fritas', 149.90, 1, 1);

-- Pisco & Cócteles (categoria_id = 7)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible) VALUES
(7,'Chilcano de Pisco','Múltiples sabores: fresa, maracuyá, piña, canela, Jamaica y más', 15.90, 1),
(7,'Pisco Sour', NULL, 19.90, 1),
(7,'El Guantazo de Maicelo','Frozen acuaí, una de gato, lime juice y sours', 31.90, 1),
(7,'El Maraco', NULL, 31.90, 1),
(7,'La Bendición de Maicelo','Licor de avellanas, Baileys y piña', 32.90, 1),
(7,'Maicelo Gym Passion','Gin, maracuyá y champagne', 32.90, 1);

-- Tragos Mañosos (categoria_id = 8)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible) VALUES
(8,'Cuba Libre', NULL, 21.90, 1),
(8,'Caipirina', NULL, 21.90, 1),
(8,'Mojito', NULL, 23.90, 1),
(8,'Piña Colada', NULL, 23.90, 1),
(8,'Daiquiri', NULL, 24.90, 1),
(8,'Margarita', NULL, 24.90, 1),
(8,'Long Island Ice Tea', NULL, 24.90, 1),
(8,'Mai Tai', NULL, 25.90, 1),
(8,'Capitan', NULL, 26.90, 1),
(8,'Margarita Corona', NULL, 27.90, 1),
(8,'Piscina', NULL, 28.90, 1);

-- Refrescantes (categoria_id = 9)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, precio_alt, unidad_alt, es_disponible) VALUES
(9,'Chicha', NULL, 8.00, 17.90, 'jarra', 1),
(9,'Limonada', NULL, 8.00, 17.90, 'jarra', 1),
(9,'Maracuyá', NULL, 7.00, 17.90, 'jarra', 1),
(9,'Limonada Frozen', NULL, 12.00, 19.90, 'grande', 1),
(9,'Maracuyá Frozen', NULL, 12.00, 19.90, 'grande', 1);

-- Chelas (categoria_id = 10)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, precio_alt, unidad_alt, es_disponible) VALUES
(10,'Pilsen', NULL, 12.00, 15.90, 'grande', 1),
(10,'Cusqueña', NULL, 13.00, 17.90, 'grande', 1),
(10,'Cusqueña Malta', NULL, 13.00, NULL, NULL, 1),
(10,'Corona', NULL, 15.00, 17.90, 'grande', 1),
(10,'Heineken', NULL, 15.00, 17.90, 'grande', 1);

-- Aguayu (categoria_id = 11)
INSERT INTO menu (categoria_id, nombre, descripcion, precio, es_disponible) VALUES
(11,'Inkacola', NULL, 5.90, 1),
(11,'Coca Cola', NULL, 5.90, 1),
(11,'Agua sin Gas', NULL, 5.90, 1),
(11,'Agua con Gas', NULL, 6.90, 1);

-- Horarios
INSERT INTO horarios (dia, hora_apertura, hora_cierre, cerrado) VALUES
('lunes',    '12:00:00', '23:00:00', 0),
('martes',   '12:00:00', '23:00:00', 0),
('miercoles','12:00:00', '23:00:00', 0),
('jueves',   '12:00:00', '23:30:00', 0),
('viernes',  '12:00:00', '00:00:00', 0),
('sabado',   '12:00:00', '00:00:00', 0),
('domingo',  '12:00:00', '22:00:00', 0);

-- Promociones
INSERT INTO promociones (titulo, descripcion, fecha_inicio, fecha_fin, activa) VALUES
('Happy Hour Pisco','2x1 en Pisco Sour y Chilcanos, lunes a jueves de 6pm a 8pm','2025-01-01','2025-12-31',1),
('10% Descuento Google Maps','Califícanos con 5 estrellas en Google Maps y obtén 10% de descuento','2025-01-01','2025-12-31',1),
('Escanea el QR','Escanea el QR de la carta para ver nuestras últimas promociones','2025-01-01','2025-12-31',1);

SET FOREIGN_KEY_CHECKS = 1;
