-- =====================================================
--  Agenda Telefónica - Script de instalación
--  Se ejecuta SOLO en la instancia de base de datos (Ng1 - 10.0.0.77)
--  Crea la base de datos, el usuario y las tablas
--  Uso:  sudo mysql < database/agenda.sql
-- =====================================================

-- 1. Crear la base de datos
CREATE DATABASE IF NOT EXISTS agenda
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 2. Crear un usuario exclusivo para la aplicación
--    'localhost'  -> conexiones desde la misma Ng1
--    '10.0.0.%'   -> conexiones desde Ng1, Ng2 y Ng3 por la red privada de la VCN
--    (cambie la contraseña y actualice config.php)
CREATE USER IF NOT EXISTS 'agenda_user'@'localhost' IDENTIFIED BY 'Agenda2026!';
CREATE USER IF NOT EXISTS 'agenda_user'@'10.0.0.%'  IDENTIFIED BY 'Agenda2026!';
GRANT SELECT, INSERT, UPDATE, DELETE ON agenda.* TO 'agenda_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON agenda.* TO 'agenda_user'@'10.0.0.%';
FLUSH PRIVILEGES;

-- 3. Seleccionar la base de datos
USE agenda;

-- 4. Crear la tabla de contactos
CREATE TABLE IF NOT EXISTS contactos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(50)  NOT NULL,
    apellidos       VARCHAR(80)  NOT NULL,
    telefono        VARCHAR(20)  NOT NULL,
    email           VARCHAR(100) DEFAULT NULL,
    direccion       VARCHAR(150) DEFAULT NULL,
    fecha_registro  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla de usuarios para el login (contraseñas con password_hash de PHP)
CREATE TABLE IF NOT EXISTS usuarios (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario        VARCHAR(50)  NOT NULL UNIQUE,
    nombre         VARCHAR(100) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    activo         TINYINT(1)   NOT NULL DEFAULT 1,
    ultimo_acceso  DATETIME     DEFAULT NULL,
    fecha_registro TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla de sesiones compartida por las 3 instancias del balanceador
CREATE TABLE IF NOT EXISTS sesiones (
    id               VARCHAR(128) NOT NULL PRIMARY KEY,
    datos            MEDIUMTEXT   NOT NULL,
    ultima_actividad DATETIME     NOT NULL,
    INDEX idx_actividad (ultima_actividad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Usuario inicial:  admin / Admin2026!   (cámbielo después de entrar)
--    Para crear más usuarios:  php database/crear_usuario.php <usuario> "<nombre>"
INSERT IGNORE INTO usuarios (usuario, nombre, password_hash) VALUES
('admin', 'Administrador', '$2y$10$0NldcYLLrHSQMzfppYg1h.Eu5Z9pETTIaCDta0kX/14IY1XBXt7Xm');

-- 8. Datos de ejemplo (solo si la tabla está vacía)
INSERT INTO contactos (nombre, apellidos, telefono, email, direccion)
SELECT * FROM (
    SELECT 'Ana' AS n, 'García López' AS a, '555-123-4567' AS t, 'ana.garcia@correo.com' AS e, 'Av. Reforma 100' AS d
    UNION ALL SELECT 'Luis',   'Martínez Pérez',   '555-234-5678', 'luis.martinez@correo.com', 'Calle Juárez 25'
    UNION ALL SELECT 'María',  'Hernández Ruiz',   '555-345-6789', 'maria.hdz@correo.com',     'Col. Centro 8'
    UNION ALL SELECT 'Carlos', 'Sánchez Torres',   '555-456-7890', NULL,                       'Blvd. Norte 300'
    UNION ALL SELECT 'Sofía',  'Ramírez Castillo', '555-567-8901', 'sofia.rc@correo.com',      NULL
) AS ejemplo
WHERE NOT EXISTS (SELECT 1 FROM contactos);
