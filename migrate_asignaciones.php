<?php
require_once 'config/config.php';

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // 1. Crear tabla temporal para guardar datos actuales de asignaciones
    $pdo->exec("
        CREATE TEMPORARY TABLE temp_asignaciones AS
        SELECT id_practicante, id_lista, id_modulo, id_docente, periodo_academico, fecha_asignacion
        FROM asignaciones_practicas
    ");

    // 2. Crear tabla temporal para calificaciones
    $pdo->exec("
        CREATE TEMPORARY TABLE temp_calificaciones AS
        SELECT a.id_practicante, a.id_modulo, a.periodo_academico, c.nota_final, c.nota_r1, c.nota_r2, c.nota_r3, c.observaciones, c.fecha_registro
        FROM calificaciones c
        JOIN asignaciones_practicas a ON c.id_asignacion = a.id_asignacion
    ");

    // 3. Eliminar tablas existentes
    $pdo->exec("DROP TABLE IF EXISTS calificaciones");
    $pdo->exec("DROP TABLE IF EXISTS asignaciones_practicas");

    // 4. Recrear tabla asignaciones_practicas con nueva estructura
    $pdo->exec("
        CREATE TABLE asignaciones_practicas (
            id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
            id_lista INT DEFAULT NULL,
            id_modulo INT NOT NULL,
            id_docente INT NOT NULL,
            periodo_academico VARCHAR(20) NOT NULL,
            estudiantes JSON NOT NULL,
            fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_lista) REFERENCES listas(id_lista) ON DELETE CASCADE,
            FOREIGN KEY (id_modulo) REFERENCES modulos_rotacion(id_modulo) ON DELETE RESTRICT,
            FOREIGN KEY (id_docente) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
            UNIQUE KEY unica_asignacion (id_lista, id_modulo, periodo_academico)
        )
    ");

    // 5. Recrear tabla calificaciones con nueva estructura
    $pdo->exec("
        CREATE TABLE calificaciones (
            id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
            id_practicante INT NOT NULL,
            id_modulo INT NOT NULL,
            periodo_academico VARCHAR(20) NOT NULL,
            nota_final DECIMAL(4,2) NOT NULL,
            observaciones TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_practicante) REFERENCES practicantes(id_practicante) ON DELETE CASCADE,
            FOREIGN KEY (id_modulo) REFERENCES modulos_rotacion(id_modulo) ON DELETE RESTRICT,
            UNIQUE KEY unica_nota_practicante (id_practicante, id_modulo, periodo_academico)
        )
    ");

    // 6. Migrar datos de asignaciones agrupando por lista, módulo y período
    $stmt = $pdo->query("
        SELECT id_lista, id_modulo, periodo_academico, id_docente, GROUP_CONCAT(id_practicante) as estudiantes_ids, MIN(fecha_asignacion) as fecha_min
        FROM temp_asignaciones
        GROUP BY id_lista, id_modulo, periodo_academico, id_docente
    ");

    $insertAsignacion = $pdo->prepare("
        INSERT INTO asignaciones_practicas (id_lista, id_modulo, id_docente, periodo_academico, estudiantes, fecha_asignacion)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $estudiantes = array_map('intval', explode(',', $row['estudiantes_ids']));
        $insertAsignacion->execute([
            $row['id_lista'],
            $row['id_modulo'],
            $row['id_docente'],
            $row['periodo_academico'],
            json_encode($estudiantes),
            $row['fecha_min']
        ]);
    }

    // 7. Migrar calificaciones
    $stmt = $pdo->query("SELECT * FROM temp_calificaciones");
    $insertCalif = $pdo->prepare("
        INSERT INTO calificaciones (id_practicante, id_modulo, periodo_academico, nota_final, nota_r1, nota_r2, nota_r3, observaciones, fecha_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $insertCalif->execute([
            $row['id_practicante'],
            $row['id_modulo'],
            $row['periodo_academico'],
            $row['nota_final'],
            $row['nota_r1'],
            $row['nota_r2'],
            $row['nota_r3'],
            $row['observaciones'],
            $row['fecha_registro']
        ]);
    }

    // Confirmar transacción
    $pdo->commit();

    echo "Migración completada exitosamente.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error en la migración: " . $e->getMessage() . "\n";
}
?>