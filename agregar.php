<?php
require_once 'includes/sesion.php';
requiereLogin();
require_once 'includes/validar.php';

$contacto = ['nombre' => '', 'apellidos' => '', 'telefono' => '', 'email' => '', 'direccion' => ''];
$errores  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    [$contacto, $errores] = leerFormulario();

    if (!$errores) {
        $pdo  = conectar();
        $sql  = "INSERT INTO contactos (nombre, apellidos, telefono, email, direccion)
                 VALUES (:nombre, :apellidos, :telefono, :email, :direccion)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'    => $contacto['nombre'],
            ':apellidos' => $contacto['apellidos'],
            ':telefono'  => $contacto['telefono'],
            ':email'     => $contacto['email'] ?: null,
            ':direccion' => $contacto['direccion'] ?: null,
        ]);
        header('Location: index.php?msg=agregado');
        exit;
    }
}

require 'includes/header.php';
?>
<h2>Nuevo contacto</h2>
<?php require 'includes/formulario.php'; ?>
<?php require 'includes/footer.php'; ?>
