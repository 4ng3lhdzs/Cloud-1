<?php
require_once 'includes/sesion.php';
requiereLogin();
require_once 'includes/validar.php';

$pdo = conectar();
$id  = (int) ($_GET['id'] ?? 0);

// Buscar el contacto a editar
$stmt = $pdo->prepare("SELECT * FROM contactos WHERE id = :id");
$stmt->execute([':id' => $id]);
$contacto = $stmt->fetch();

if (!$contacto) {
    header('Location: index.php');
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    [$contacto, $errores] = leerFormulario();

    if (!$errores) {
        $sql  = "UPDATE contactos
                 SET nombre = :nombre, apellidos = :apellidos, telefono = :telefono,
                     email = :email, direccion = :direccion
                 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'    => $contacto['nombre'],
            ':apellidos' => $contacto['apellidos'],
            ':telefono'  => $contacto['telefono'],
            ':email'     => $contacto['email'] ?: null,
            ':direccion' => $contacto['direccion'] ?: null,
            ':id'        => $id,
        ]);
        header('Location: index.php?msg=editado');
        exit;
    }
}

require 'includes/header.php';
?>
<h2>Editar contacto</h2>
<?php require 'includes/formulario.php'; ?>
<?php require 'includes/footer.php'; ?>
