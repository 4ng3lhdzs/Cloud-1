<?php
require_once 'includes/sesion.php';
requiereLogin();

// Solo se permite eliminar mediante POST (desde el botón de la lista)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $id = (int) ($_POST['id'] ?? 0);

    $pdo  = conectar();
    $stmt = $pdo->prepare("DELETE FROM contactos WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

header('Location: index.php?msg=eliminado');
exit;
