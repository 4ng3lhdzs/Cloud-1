<?php
require_once 'includes/sesion.php';
requiereLogin();

$pdo = conectar();
$buscar = trim($_GET['buscar'] ?? '');

// Listar contactos (con búsqueda opcional)
if ($buscar !== '') {
    $sql = "SELECT * FROM contactos
            WHERE nombre LIKE :b1 OR apellidos LIKE :b2 OR telefono LIKE :b3
            ORDER BY nombre, apellidos";
    $stmt = $pdo->prepare($sql);
    $patron = "%$buscar%";
    $stmt->execute([':b1' => $patron, ':b2' => $patron, ':b3' => $patron]);
} else {
    $stmt = $pdo->query("SELECT * FROM contactos ORDER BY nombre, apellidos");
}
$contactos = $stmt->fetchAll();

$mensajes = [
    'agregado'  => 'Contacto agregado correctamente.',
    'editado'   => 'Contacto actualizado correctamente.',
    'eliminado' => 'Contacto eliminado correctamente.',
];
$msg = $mensajes[$_GET['msg'] ?? ''] ?? null;

require 'includes/header.php';
?>

<?php if ($msg): ?>
    <div class="alerta exito"><?= e($msg) ?></div>
<?php endif; ?>

<div class="barra">
    <form method="get" class="buscador">
        <input type="text" name="buscar" placeholder="Buscar por nombre o teléfono..." value="<?= e($buscar) ?>">
        <button type="submit" class="btn">Buscar</button>
        <?php if ($buscar !== ''): ?>
            <a href="index.php" class="btn secundario">Limpiar</a>
        <?php endif; ?>
    </form>
    <a href="agregar.php" class="btn">+ Nuevo contacto</a>
</div>

<?php if (!$contactos): ?>
    <p class="vacio">No se encontraron contactos.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contactos as $c): ?>
                <tr>
                    <td><?= e($c['nombre'] . ' ' . $c['apellidos']) ?></td>
                    <td><?= e($c['telefono']) ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['direccion']) ?></td>
                    <td class="acciones">
                        <a href="editar.php?id=<?= $c['id'] ?>" class="btn pequeno">Editar</a>
                        <form method="post" action="eliminar.php"
                              onsubmit="return confirm('¿Desea eliminar este contacto?');">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn pequeno peligro">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="total">Total: <?= count($contactos) ?> contacto(s)</p>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
