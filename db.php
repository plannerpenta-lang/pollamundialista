<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

function now_bogota(): string {
    $dt = new DateTime('now', new DateTimeZone('America/Bogota'));
    return $dt->format('Y-m-d H:i:s');
}

function format_bogota(string $datetime): string {
    try {
        $dt = new DateTime($datetime, new DateTimeZone('America/Bogota'));
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $datetime;
    }
}
