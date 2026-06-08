-- Polla Mundialista Penta 2026
-- Ejecutar en phpMyAdmin antes de subir los archivos

CREATE TABLE IF NOT EXISTS participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    local VARCHAR(80) NOT NULL,
    visitante VARCHAR(80) NOT NULL,
    fase ENUM('grupos','r16','qf','sf','final') DEFAULT 'grupos',
    grupo VARCHAR(20) DEFAULT '',
    fecha DATE,
    goles_local TINYINT DEFAULT NULL,
    goles_visitante TINYINT DEFAULT NULL,
    creado_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pronosticos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    partido_id INT NOT NULL,
    goles_local TINYINT NOT NULL,
    goles_visitante TINYINT NOT NULL,
    ingresado_at DATETIME NOT NULL,
    UNIQUE KEY unico_prono (participante_id, partido_id),
    FOREIGN KEY (participante_id) REFERENCES participantes(id),
    FOREIGN KEY (partido_id) REFERENCES partidos(id)
);

-- Partidos Fase de Grupos
INSERT INTO partidos (local, visitante, fase, grupo, fecha) VALUES
-- GRUPO A: México
('México','Sudáfrica','grupos','Grupo A','2026-06-11'),
('Corea del Sur','Chequia','grupos','Grupo A','2026-06-11'),
('México','Corea del Sur','grupos','Grupo A','2026-06-18'),
('Chequia','Sudáfrica','grupos','Grupo A','2026-06-18'),
('Chequia','México','grupos','Grupo A','2026-06-24'),
('Sudáfrica','Corea del Sur','grupos','Grupo A','2026-06-24'),
-- GRUPO B
('Canadá','Polonia','grupos','Grupo B','2026-06-12'),
('Rumania','Honduras','grupos','Grupo B','2026-06-12'),
('Canadá','Rumania','grupos','Grupo B','2026-06-19'),
('Polonia','Honduras','grupos','Grupo B','2026-06-19'),
('Polonia','Rumania','grupos','Grupo B','2026-06-24'),
('Honduras','Canadá','grupos','Grupo B','2026-06-24'),
-- GRUPO C: Brasil
('Brasil','Marruecos','grupos','Grupo C','2026-06-13'),
('Haití','Escocia','grupos','Grupo C','2026-06-13'),
('Escocia','Marruecos','grupos','Grupo C','2026-06-19'),
('Brasil','Haití','grupos','Grupo C','2026-06-19'),
('Brasil','Escocia','grupos','Grupo C','2026-06-24'),
('Marruecos','Haití','grupos','Grupo C','2026-06-24'),
-- GRUPO D: Estados Unidos
('Estados Unidos','Paraguay','grupos','Grupo D','2026-06-12'),
('Australia','Turquía','grupos','Grupo D','2026-06-12'),
('Estados Unidos','Australia','grupos','Grupo D','2026-06-19'),
('Turquía','Paraguay','grupos','Grupo D','2026-06-19'),
('Australia','Paraguay','grupos','Grupo D','2026-06-24'),
('Turquía','Estados Unidos','grupos','Grupo D','2026-06-24'),
-- GRUPO E: Ecuador
('Alemania','Curaçao','grupos','Grupo E','2026-06-14'),
('Costa de Marfil','Ecuador','grupos','Grupo E','2026-06-14'),
('Alemania','Costa de Marfil','grupos','Grupo E','2026-06-20'),
('Ecuador','Curaçao','grupos','Grupo E','2026-06-20'),
('Ecuador','Alemania','grupos','Grupo E','2026-06-25'),
('Curaçao','Costa de Marfil','grupos','Grupo E','2026-06-25'),
-- GRUPO F
('Argentina','Argelia','grupos','Grupo F','2026-06-16'),
('Austria','Jordania','grupos','Grupo F','2026-06-16'),
('Argentina','Austria','grupos','Grupo F','2026-06-21'),
('Jordania','Argelia','grupos','Grupo F','2026-06-21'),
('Jordania','Argentina','grupos','Grupo F','2026-06-27'),
('Argelia','Austria','grupos','Grupo F','2026-06-27'),
-- GRUPO G
('España','Cabo Verde','grupos','Grupo G','2026-06-15'),
('Arabia Saudí','Uruguay','grupos','Grupo G','2026-06-15'),
('España','Arabia Saudí','grupos','Grupo G','2026-06-21'),
('Uruguay','Cabo Verde','grupos','Grupo G','2026-06-21'),
('España','Uruguay','grupos','Grupo G','2026-06-26'),
('Cabo Verde','Arabia Saudí','grupos','Grupo G','2026-06-26'),
-- GRUPO H: Francia
('Francia','Irak','grupos','Grupo H','2026-06-15'),
('Senegal','Noruega','grupos','Grupo H','2026-06-15'),
('Francia','Senegal','grupos','Grupo H','2026-06-22'),
('Noruega','Irak','grupos','Grupo H','2026-06-22'),
('Francia','Noruega','grupos','Grupo H','2026-06-26'),
('Irak','Senegal','grupos','Grupo H','2026-06-26'),
-- GRUPO I: Colombia
('Portugal','Rep. Dem. Congo','grupos','Grupo I','2026-06-14'),
('Uzbekistán','Colombia','grupos','Grupo I','2026-06-17'),
('Portugal','Uzbekistán','grupos','Grupo I','2026-06-22'),
('Colombia','Rep. Dem. Congo','grupos','Grupo I','2026-06-23'),
('Colombia','Portugal','grupos','Grupo I','2026-06-27'),
('Rep. Dem. Congo','Uzbekistán','grupos','Grupo I','2026-06-27'),
-- GRUPO J: Panamá + Inglaterra
('Inglaterra','Croacia','grupos','Grupo J','2026-06-17'),
('Ghana','Panamá','grupos','Grupo J','2026-06-17'),
('Inglaterra','Ghana','grupos','Grupo J','2026-06-23'),
('Panamá','Croacia','grupos','Grupo J','2026-06-23'),
('Panamá','Inglaterra','grupos','Grupo J','2026-06-27'),
('Croacia','Ghana','grupos','Grupo J','2026-06-27');
