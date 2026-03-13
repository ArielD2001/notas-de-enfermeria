<?php
require_once 'config/config.php';

try {
    // Agregar columna detalles_json a la tabla de calificaciones
    $sql = "ALTER TABLE calificaciones ADD COLUMN detalles_json TEXT AFTER observaciones";
    $pdo->exec($sql);
    echo "Columna 'detalles_json' agregada exitosamente.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "La columna 'detalles_json' ya existe.\n";
    } else {
        echo "Error al modificar la tabla: " . $e->getMessage() . "\n";
    }
}
?>
