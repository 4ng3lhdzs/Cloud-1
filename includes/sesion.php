<?php
// =====================================================
//  Manejo de sesiones y autenticación
//
//  Detrás del balanceador de carga cada petición puede llegar a una
//  instancia distinta (Ng1, Ng2 o Ng3). Si la sesión se guardara en
//  archivos (/var/lib/php/sessions) cada servidor tendría las suyas y el
//  usuario "perdería" el login al cambiar de servidor. Por eso las
//  sesiones se guardan en la tabla `sesiones` de la base de datos central.
// =====================================================
require_once __DIR__ . '/conexion.php';

class SesionMySQL implements SessionHandlerInterface
{
    public function __construct(private PDO $pdo) {}

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT datos FROM sesiones
             WHERE id = :id AND ultima_actividad > (NOW() - INTERVAL :vida SECOND)'
        );
        $stmt->execute([':id' => $id, ':vida' => SESION_DURACION]);
        $datos = $stmt->fetchColumn();
        return $datos === false ? '' : $datos;
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sesiones (id, datos, ultima_actividad) VALUES (:id, :datos, NOW())
             ON DUPLICATE KEY UPDATE datos = VALUES(datos), ultima_actividad = NOW()'
        );
        return $stmt->execute([':id' => $id, ':datos' => $data]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM sesiones WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM sesiones WHERE ultima_actividad < (NOW() - INTERVAL :vida SECOND)'
        );
        $stmt->execute([':vida' => $max_lifetime]);
        return $stmt->rowCount();
    }
}

// Inicia la sesión usando la base de datos como almacenamiento
function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.gc_maxlifetime', (string) SESION_DURACION);
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');
    ini_set('session.use_strict_mode', '1');

    session_set_save_handler(new SesionMySQL(conectar()), true);
    session_name(SESION_NOMBRE);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function usuarioActual(): ?array
{
    iniciarSesion();
    return $_SESSION['usuario'] ?? null;
}

// Protege una página: si no hay sesión, manda al login
function requiereLogin(): array
{
    $usuario = usuarioActual();
    if (!$usuario) {
        header('Location: login.php');
        exit;
    }
    return $usuario;
}

// Busca al usuario y verifica la contraseña (password_hash / password_verify)
function autenticar(string $usuario, string $password): ?array
{
    $stmt = conectar()->prepare(
        'SELECT id, usuario, nombre, password_hash FROM usuarios WHERE usuario = :u AND activo = 1'
    );
    $stmt->execute([':u' => $usuario]);
    $fila = $stmt->fetch();

    if (!$fila || !password_verify($password, $fila['password_hash'])) {
        return null;
    }

    conectar()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id')
              ->execute([':id' => $fila['id']]);

    return ['id' => (int) $fila['id'], 'usuario' => $fila['usuario'], 'nombre' => $fila['nombre']];
}

function iniciarSesionUsuario(array $usuario): void
{
    iniciarSesion();
    session_regenerate_id(true); // evita fijación de sesión
    $_SESSION['usuario'] = $usuario;
}

function cerrarSesion(): void
{
    iniciarSesion();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    session_destroy();
}

// ---------- Protección CSRF para los formularios POST ----------
function tokenCsrf(): string
{
    iniciarSesion();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(tokenCsrf()) . '">';
}

function verificarCsrf(): void
{
    iniciarSesion();
    $enviado = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $enviado)) {
        http_response_code(400);
        die('Solicitud no válida (token CSRF). Recargue la página e intente de nuevo.');
    }
}
