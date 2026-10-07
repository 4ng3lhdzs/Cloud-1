<?php
// Crea un usuario o cambia su contraseña (solo desde la terminal de cualquier instancia)
// Uso:  php /var/www/html/agenda/database/crear_usuario.php <usuario> "<Nombre completo>"
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

$usuario = $argv[1] ?? '';
$nombre  = $argv[2] ?? $usuario;
if ($usuario === '') {
    fwrite(STDERR, "Uso: php crear_usuario.php <usuario> \"<Nombre completo>\"\n");
    exit(1);
}

echo "Contraseña para '$usuario': ";
system('stty -echo 2>/dev/null');
$pass1 = trim((string) fgets(STDIN));
echo "\nRepita la contraseña: ";
$pass2 = trim((string) fgets(STDIN));
system('stty echo 2>/dev/null');
echo "\n";

if (strlen($pass1) < 8 || $pass1 !== $pass2) {
    fwrite(STDERR, "Las contraseñas no coinciden o tienen menos de 8 caracteres.\n");
    exit(1);
}

$stmt = conectar()->prepare(
    'INSERT INTO usuarios (usuario, nombre, password_hash) VALUES (:u, :n, :h)
     ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), password_hash = VALUES(password_hash), activo = 1'
);
$stmt->execute([':u' => $usuario, ':n' => $nombre, ':h' => password_hash($pass1, PASSWORD_DEFAULT)]);

echo "Usuario '$usuario' guardado.\n";
