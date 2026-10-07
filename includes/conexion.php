<?php
require_once __DIR__ . '/../config.php';

// Crea (una sola vez por petición) la conexión usando PDO
function conectar(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // No se muestra el detalle del error al usuario (podría revelar datos del servidor)
        error_log('Error de conexión a MySQL: ' . $e->getMessage());
        http_response_code(500);
        die('Error de conexión a la base de datos. Revise que el servidor ' . DB_HOST . ' esté disponible.');
    }
}

// Escapa texto para mostrarlo en HTML de forma segura
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
