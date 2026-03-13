<?php
require 'config/config.php';
try {
    $pdo->exec("DELETE FROM calificaciones");
    echo "Calificaciones de prueba eliminadas.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
