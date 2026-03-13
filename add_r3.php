<?php
require 'config/config.php';
try {
    $pdo->exec("ALTER TABLE calificaciones ADD COLUMN nota_r3 DECIMAL(4,2) NULL AFTER nota_r2");
    echo "Columna nota_r3 añadida correctamente.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "La columna nota_r3 ya existe.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
