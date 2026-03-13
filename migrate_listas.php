<?php
require_once 'config/config.php';

try {
    // 1. Crear tabla listas
    $sql1 = "CREATE TABLE IF NOT EXISTS `listas` (
        `id_lista` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre_lista` VARCHAR(150) NOT NULL,
        `grupo` VARCHAR(50),
        `semestre` VARCHAR(50),
        `id_modulo` INT NOT NULL,
        `id_docente` INT NOT NULL,
        `periodo_academico` VARCHAR(20) NOT NULL,
        `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`id_modulo`) REFERENCES `modulos_rotacion`(`id_modulo`) ON DELETE RESTRICT,
        FOREIGN KEY (`id_docente`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT
    )";
    $pdo->exec($sql1);
    echo "Tabla 'listas' creada o ya existía.\n";

    // 2. Agregar columna id_lista a asignaciones_practicas si no existe
    $checkColumn = $pdo->query("SHOW COLUMNS FROM `asignaciones_practicas` LIKE 'id_lista'");
    if ($checkColumn->rowCount() == 0) {
        $sql2 = "ALTER TABLE `asignaciones_practicas` 
                 ADD COLUMN `id_lista` INT DEFAULT NULL AFTER `id_practicante`,
                 ADD FOREIGN KEY (`id_lista`) REFERENCES `listas`(`id_lista`) ON DELETE CASCADE";
        $pdo->exec($sql2);
        echo "Columna 'id_lista' añadida a 'asignaciones_practicas'.\n";
    } else {
        echo "Columna 'id_lista' ya existe.\n";
    }

    echo "Migración completada exitosamente.";
} catch (PDOException $e) {
    echo "Error en la migración: " . $e->getMessage();
}
?>
