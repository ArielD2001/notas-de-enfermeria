<?php
require_once 'config/config.php';

$modules = [
    'Promoción y prevención',
    'Actividades Básicas',
    'Cuidado medico quirúrgico',
    'Cuidado Materno infantil',
    'Practica Administracion',
    'Adulto mayor',
    'Práctica Integral',
    'Fundamento Socio Educativo IV',
    'Fundamento Socio Educativo V'
];

try {
    // Limpiar tabla sin transacciones para evitar errores de autocommit/DML
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE modulos_rotacion");
    
    $stmt = $pdo->prepare("INSERT INTO modulos_rotacion (nombre_modulo) VALUES (?)");
    foreach($modules as $m) {
        $stmt->execute([$m]);
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Modules updated successfully in database.\n";
    
    // Verificar
    $check = $pdo->query("SELECT COUNT(*) FROM modulos_rotacion")->fetchColumn();
    echo "Total modules now: $check\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
