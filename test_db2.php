<?php
require 'config/config.php';
$stmt = $pdo->query("SHOW COLUMNS FROM asignaciones_practicas");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
