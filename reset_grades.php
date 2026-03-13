<?php
require 'config/config.php';
try {
    $pdo->exec("UPDATE calificaciones SET detalles_json = NULL, nota_final = NULL, nota_r1 = NULL, nota_r2 = NULL, nota_r3 = NULL WHERE id_calificacion > 0");
    echo "Calificaciones reseteadas para pruebas.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
