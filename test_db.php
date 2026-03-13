<?php
require 'config/config.php';
$stmt = $pdo->query("SELECT id_modulo, nombre_modulo FROM modulos_rotacion");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
?>
