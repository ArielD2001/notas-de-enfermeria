<?php
// migrate_asignaciones.php - Corregido para mantener consistencia
require_once 'config/config.php';

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // 1. Asegurar que las columnas necesarias existan en calificaciones
    // En lugar de borrar y recrear, modificamos la tabla existente
    
    // Columnas de notas de rotaciones
    $columns_to_add = [
        'nota_r1' => "DECIMAL(4,2) DEFAULT NULL AFTER nota_final",
        'nota_r2' => "DECIMAL(4,2) DEFAULT NULL AFTER nota_r1",
        'nota_r3' => "DECIMAL(4,2) DEFAULT NULL AFTER nota_r2",
        'detalles_json' => "TEXT AFTER observaciones"
    ];

    foreach ($columns_to_add as $col => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM calificaciones LIKE '$col'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE calificaciones ADD COLUMN $col $definition");
            echo "Columna '$col' añadida a 'calificaciones'.\n";
        }
    }

    // 2. Asegurar que id_lista existe en asignaciones_practicas
    $check_list = $pdo->query("SHOW COLUMNS FROM asignaciones_practicas LIKE 'id_lista'");
    if ($check_list->rowCount() == 0) {
        $pdo->exec("ALTER TABLE asignaciones_practicas ADD COLUMN id_lista INT DEFAULT NULL AFTER id_practicante");
        $pdo->exec("ALTER TABLE asignaciones_practicas ADD FOREIGN KEY (id_lista) REFERENCES listas(id_lista) ON DELETE CASCADE");
        echo "Columna 'id_lista' añadida a 'asignaciones_practicas'.\n";
    }

    // 3. Verificar si existe el índice único correcto en asignaciones_practicas
    // El sistema espera una asignación por estudiante, módulo y periodo
    try {
        $pdo->exec("ALTER TABLE asignaciones_practicas ADD UNIQUE KEY unica_asignacion (id_practicante, id_modulo, periodo_academico)");
        echo "Índice único 'unica_asignacion' verificado.\n";
    } catch (PDOException $e) {
        // Ignorar si ya existe
    }

    // 4. Verificar si existe el índice único en calificaciones
    try {
        $pdo->exec("ALTER TABLE calificaciones ADD UNIQUE KEY unica_nota_asignacion (id_asignacion)");
        echo "Índice único 'unica_nota_asignacion' verificado.\n";
    } catch (PDOException $e) {
        // Ignorar si ya existe
    }

    // Confirmar transacción
    $pdo->commit();

    echo "Migración de mantenimiento completada exitosamente.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error en la migración: " . $e->getMessage() . "\n";
}
?>