<?php
require_once 'config/config.php';

echo "--- MODULOS ---\n";
$stmt = $pdo->query("SELECT * FROM modulos_rotacion");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id_modulo'] . " - Name: " . $row['nombre_modulo'] . "\n";
}

echo "\n--- LISTAS ---\n";
$count_listas = $pdo->query("SELECT COUNT(*) FROM listas")->fetchColumn();
echo "Total Listas: $count_listas\n";

if ($count_listas > 0) {
    $stmt = $pdo->query("SELECT l.id_lista, l.nombre_lista, l.id_modulo, m.nombre_modulo as current_mod_name FROM listas l LEFT JOIN modulos_rotacion m ON l.id_modulo = m.id_modulo LIMIT 5");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id_lista'] . " - Name: " . $row['nombre_lista'] . " - Mod ID: " . $row['id_modulo'] . " - Mod Name: " . ($row['current_mod_name'] ?: 'MISSING') . "\n";
    }
}
