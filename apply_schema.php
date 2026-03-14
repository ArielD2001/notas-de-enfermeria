<?php
require_once 'config/config.php';

$sql = file_get_contents('database/schema.sql');

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        try {
            $pdo->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "Schema applied.\n";
?>