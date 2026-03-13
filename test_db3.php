<?php
require 'config/config.php';
$stmt = $pdo->query("SHOW COLUMNS FROM calificaciones");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
