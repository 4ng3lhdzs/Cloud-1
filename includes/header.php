<?php $usuarioHeader = function_exists('usuarioActual') ? usuarioActual() : null; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Telefónica</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header>
    <h1><a href="index.php">📒 Agenda Telefónica</a></h1>
    <?php if ($usuarioHeader): ?>
        <div class="usuario">
            <span>👤 <?= e($usuarioHeader['nombre'] ?: $usuarioHeader['usuario']) ?></span>
            <form method="post" action="logout.php">
                <?= campoCsrf() ?>
                <button type="submit" class="btn pequeno secundario">Salir</button>
            </form>
        </div>
    <?php endif; ?>
</header>
<main>
