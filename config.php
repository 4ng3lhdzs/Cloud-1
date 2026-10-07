<?php
// Configuración de la conexión a MySQL
// La base de datos vive SOLO en Ng1 (IP privada 10.0.0.77).
// Las 3 instancias (Ng1, Ng2, Ng3) se conectan a ella por la red privada de la VCN.
// Ajuste estos valores si cambió el usuario o la contraseña en database/agenda.sql
define('DB_HOST', '10.0.0.77');
define('DB_NAME', 'agenda');
define('DB_USER', 'agenda_user');
define('DB_PASS', 'Agenda2026!');

// Sesiones: se guardan en MySQL para que el login funcione sin importar
// a qué instancia mande el balanceador cada petición.
define('SESION_NOMBRE', 'AGENDASESSID');
define('SESION_DURACION', 1800); // segundos de inactividad antes de cerrar sesión
