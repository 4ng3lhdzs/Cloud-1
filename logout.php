<?php
require_once 'includes/sesion.php';

// Solo por POST (botón "Salir" del encabezado) y con token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    cerrarSesion();
    header('Location: login.php?msg=salir');
    exit;
}

header('Location: index.php');
exit;
