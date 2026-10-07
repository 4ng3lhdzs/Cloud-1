<?php
require_once 'includes/sesion.php';

// Si ya inició sesión, directo a la agenda
if (usuarioActual()) {
    header('Location: index.php');
    exit;
}

$usuario = '';
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Escriba su usuario y contraseña.';
    } elseif ($datos = autenticar($usuario, $password)) {
        iniciarSesionUsuario($datos);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$msg = ($_GET['msg'] ?? '') === 'salir' ? 'Sesión cerrada correctamente.' : null;

require 'includes/header.php';
?>
<div class="login">
    <h2>Iniciar sesión</h2>

    <?php if ($msg): ?>
        <div class="alerta exito"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alerta error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="formulario">
        <?= campoCsrf() ?>
        <label>Usuario
            <input type="text" name="usuario" maxlength="50" required autofocus
                   autocomplete="username" value="<?= e($usuario) ?>">
        </label>
        <label>Contraseña
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <div class="acciones">
            <button type="submit" class="btn">Entrar</button>
        </div>
    </form>
</div>
<?php require 'includes/footer.php'; ?>
