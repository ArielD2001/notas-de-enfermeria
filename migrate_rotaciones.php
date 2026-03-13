<?php
require_once 'config/config.php';

try {
    // Agregar columnas nota_r1 y nota_r2 a la tabla de calificaciones
    $sql = "ALTER TABLE calificaciones 
            ADD COLUMN nota_r1 DECIMAL(4,2) DEFAULT NULL AFTER nota_final,
            ADD COLUMN nota_r2 DECIMAL(4,2) DEFAULT NULL AFTER nota_r1";
    $pdo->exec($sql);
    echo "Columnas 'nota_r1' y 'nota_r2' agregadas exitosamente.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Las columnas ya existen.\n";
    } else {
        echo "Error al modificar la tabla: " . $e->getMessage() . "\n";
    }
}
?>
