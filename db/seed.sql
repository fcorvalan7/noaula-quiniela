CREATE DATABASE IF NOT EXISTS quiniela CHARACTER SET utf8mb4;
USE quiniela;

CREATE TABLE sorteos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    turno ENUM('previa','matutina','vespertina','nocturna') NOT NULL
);

CREATE TABLE extracciones (
    sorteo_id INT NOT NULL,
    posicion INT NOT NULL,
    numero CHAR(2) DEFAULT NULL,
    PRIMARY KEY (sorteo_id, posicion),
    FOREIGN KEY (sorteo_id) REFERENCES sorteos(id)
);

CREATE TABLE apuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sorteo_id INT NOT NULL,
    nombre_apostador VARCHAR(100) NOT NULL,
    numero_apostado CHAR(2) NOT NULL,
    modalidad ENUM('cabeza','numero') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha DATETIME NOT NULL,
    FOREIGN KEY (sorteo_id) REFERENCES sorteos(id)
);

INSERT INTO sorteos (id, fecha, turno) VALUES
    (1, '2026-07-20', 'vespertina'),
    (2, '2026-07-21', 'nocturna');

-- Sorteo 1: resultado ya cargado (20 extracciones)
INSERT INTO extracciones (sorteo_id, posicion, numero) VALUES
    (1,1,'47'),(1,2,'12'),(1,3,'83'),(1,4,'05'),(1,5,'99'),
    (1,6,'21'),(1,7,'34'),(1,8,'56'),(1,9,'78'),(1,10,'09'),
    (1,11,'60'),(1,12,'71'),(1,13,'82'),(1,14,'93'),(1,15,'14'),
    (1,16,'25'),(1,17,'36'),(1,18,'47'),(1,19,'58'),(1,20,'69');

-- Sorteo 2: sin resultado todavía (para probar admin_resultado.php)
INSERT INTO extracciones (sorteo_id, posicion, numero) VALUES
    (2,1,NULL),(2,2,NULL),(2,3,NULL),(2,4,NULL),(2,5,NULL),
    (2,6,NULL),(2,7,NULL),(2,8,NULL),(2,9,NULL),(2,10,NULL),
    (2,11,NULL),(2,12,NULL),(2,13,NULL),(2,14,NULL),(2,15,NULL),
    (2,16,NULL),(2,17,NULL),(2,18,NULL),(2,19,NULL),(2,20,NULL);

-- Apuestas de ejemplo sobre el sorteo 1
INSERT INTO apuestas (sorteo_id, nombre_apostador, numero_apostado, modalidad, monto, fecha) VALUES
    (1, 'Juan Perez', '47', 'cabeza', 100.00, '2026-07-20 10:15:00'),
    (1, 'Maria Gomez', '05', 'numero', 50.00, '2026-07-20 11:00:00'),
    (1, 'Carlos Ruiz', '99', 'numero', 200.00, '2026-07-20 12:30:00');
