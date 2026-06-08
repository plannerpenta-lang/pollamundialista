<?php
// Para producción cPanel, cambia a PDO MySQL:
// define('DB_HOST', 'localhost'); define('DB_USER', '...'); etc.

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . __DIR__ . '/penta2026.sqlite', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        init_db($pdo);
    }
    return $pdo;
}

function now_bogota(): string {
    $dt = new DateTime('now', new DateTimeZone('America/Bogota'));
    return $dt->format('Y-m-d H:i:s');
}

function format_bogota(string $datetime): string {
    $dt = new DateTime($datetime, new DateTimeZone('America/Bogota'));
    return $dt->format('d/m/Y H:i');
}

function init_db(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS participantes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            created_at TEXT DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS partidos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            local TEXT NOT NULL,
            visitante TEXT NOT NULL,
            fase TEXT DEFAULT 'grupos',
            grupo TEXT DEFAULT '',
            fecha TEXT,
            goles_local INTEGER DEFAULT NULL,
            goles_visitante INTEGER DEFAULT NULL
        );
        CREATE TABLE IF NOT EXISTS pronosticos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            participante_id INTEGER NOT NULL,
            partido_id INTEGER NOT NULL,
            goles_local INTEGER NOT NULL,
            goles_visitante INTEGER NOT NULL,
            ingresado_at TEXT NOT NULL,
            UNIQUE(participante_id, partido_id),
            FOREIGN KEY (participante_id) REFERENCES participantes(id),
            FOREIGN KEY (partido_id) REFERENCES partidos(id)
        );
    ");

    // Solo insertar partidos si la tabla está vacía
    $count = $pdo->query('SELECT COUNT(*) FROM partidos')->fetchColumn();
    if ($count > 0) return;

    $partidos = [
        ['México','Sudáfrica','grupos','Grupo A','2026-06-11'],
        ['Corea del Sur','Chequia','grupos','Grupo A','2026-06-11'],
        ['México','Corea del Sur','grupos','Grupo A','2026-06-18'],
        ['Chequia','Sudáfrica','grupos','Grupo A','2026-06-18'],
        ['Chequia','México','grupos','Grupo A','2026-06-24'],
        ['Sudáfrica','Corea del Sur','grupos','Grupo A','2026-06-24'],
        ['Canadá','Polonia','grupos','Grupo B','2026-06-12'],
        ['Rumania','Honduras','grupos','Grupo B','2026-06-12'],
        ['Canadá','Rumania','grupos','Grupo B','2026-06-19'],
        ['Polonia','Honduras','grupos','Grupo B','2026-06-19'],
        ['Polonia','Rumania','grupos','Grupo B','2026-06-24'],
        ['Honduras','Canadá','grupos','Grupo B','2026-06-24'],
        ['Brasil','Marruecos','grupos','Grupo C','2026-06-13'],
        ['Haití','Escocia','grupos','Grupo C','2026-06-13'],
        ['Escocia','Marruecos','grupos','Grupo C','2026-06-19'],
        ['Brasil','Haití','grupos','Grupo C','2026-06-19'],
        ['Brasil','Escocia','grupos','Grupo C','2026-06-24'],
        ['Marruecos','Haití','grupos','Grupo C','2026-06-24'],
        ['Estados Unidos','Paraguay','grupos','Grupo D','2026-06-12'],
        ['Australia','Turquía','grupos','Grupo D','2026-06-12'],
        ['Estados Unidos','Australia','grupos','Grupo D','2026-06-19'],
        ['Turquía','Paraguay','grupos','Grupo D','2026-06-19'],
        ['Australia','Paraguay','grupos','Grupo D','2026-06-24'],
        ['Turquía','Estados Unidos','grupos','Grupo D','2026-06-24'],
        ['Alemania','Curaçao','grupos','Grupo E','2026-06-14'],
        ['Costa de Marfil','Ecuador','grupos','Grupo E','2026-06-14'],
        ['Alemania','Costa de Marfil','grupos','Grupo E','2026-06-20'],
        ['Ecuador','Curaçao','grupos','Grupo E','2026-06-20'],
        ['Ecuador','Alemania','grupos','Grupo E','2026-06-25'],
        ['Curaçao','Costa de Marfil','grupos','Grupo E','2026-06-25'],
        ['Argentina','Argelia','grupos','Grupo F','2026-06-16'],
        ['Austria','Jordania','grupos','Grupo F','2026-06-16'],
        ['Argentina','Austria','grupos','Grupo F','2026-06-21'],
        ['Jordania','Argelia','grupos','Grupo F','2026-06-21'],
        ['Jordania','Argentina','grupos','Grupo F','2026-06-27'],
        ['Argelia','Austria','grupos','Grupo F','2026-06-27'],
        ['España','Cabo Verde','grupos','Grupo G','2026-06-15'],
        ['Arabia Saudí','Uruguay','grupos','Grupo G','2026-06-15'],
        ['España','Arabia Saudí','grupos','Grupo G','2026-06-21'],
        ['Uruguay','Cabo Verde','grupos','Grupo G','2026-06-21'],
        ['España','Uruguay','grupos','Grupo G','2026-06-26'],
        ['Cabo Verde','Arabia Saudí','grupos','Grupo G','2026-06-26'],
        ['Francia','Irak','grupos','Grupo H','2026-06-15'],
        ['Senegal','Noruega','grupos','Grupo H','2026-06-15'],
        ['Francia','Senegal','grupos','Grupo H','2026-06-22'],
        ['Noruega','Irak','grupos','Grupo H','2026-06-22'],
        ['Francia','Noruega','grupos','Grupo H','2026-06-26'],
        ['Irak','Senegal','grupos','Grupo H','2026-06-26'],
        ['Portugal','Rep. Dem. Congo','grupos','Grupo I','2026-06-14'],
        ['Uzbekistán','Colombia','grupos','Grupo I','2026-06-17'],
        ['Portugal','Uzbekistán','grupos','Grupo I','2026-06-22'],
        ['Colombia','Rep. Dem. Congo','grupos','Grupo I','2026-06-23'],
        ['Colombia','Portugal','grupos','Grupo I','2026-06-27'],
        ['Rep. Dem. Congo','Uzbekistán','grupos','Grupo I','2026-06-27'],
        ['Inglaterra','Croacia','grupos','Grupo J','2026-06-17'],
        ['Ghana','Panamá','grupos','Grupo J','2026-06-17'],
        ['Inglaterra','Ghana','grupos','Grupo J','2026-06-23'],
        ['Panamá','Croacia','grupos','Grupo J','2026-06-23'],
        ['Panamá','Inglaterra','grupos','Grupo J','2026-06-27'],
        ['Croacia','Ghana','grupos','Grupo J','2026-06-27'],
    ];

    $ins = $pdo->prepare('INSERT INTO partidos (local, visitante, fase, grupo, fecha) VALUES (?,?,?,?,?)');
    foreach ($partidos as $p) $ins->execute($p);
}
