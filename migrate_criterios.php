<?php
// migrate_criterios.php - Crear tabla de criterios de formularios
require_once 'config/config.php';

try {
    // Crear tabla criterios_formularios
    $sql = "
        CREATE TABLE IF NOT EXISTS criterios_formularios (
            id_criterio_formulario INT AUTO_INCREMENT PRIMARY KEY,
            id_modulo INT NOT NULL,
            criterios_json LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_modulo) REFERENCES modulos_rotacion(id_modulo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Tabla 'criterios_formularios' creada exitosamente.\n";

    // Datos para módulo 1: Promoción y Prevención
    $criterios_modulo1 = [
        'A.1 Relaciones interpersonales (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Mantiene relaciones cordiales y respetuosas',
                'Expresa sus inquietudes con seguridad y confianza',
                'Sigue las líneas de autoridad establecidas',
                'Reconoce errores y acepta sugerencias',
                'Maneja situaciones de estrés adecuadamente'
            ]
        ],
        'A.2 Responsabilidad y compromiso (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Puntualidad (asistencia 15 a 10 min antes)',
                'Porte de uniforme completo e impecable',
                'Permanencia en el sitio de práctica',
                'Cumplimiento de asistencia a actividades',
                'Entrega y sustento oportuno de trabajos',
                'Cumplimiento de normativa CURN',
                'Actitud y valores formativos',
                'Liderazgo en la toma de decisiones',
                'Asistencia a eventos y encuentros',
                'Uso racional y cuidado de equipos'
            ]
        ],
        'B.1 Conocimiento científico (40%)' => [
            'weight' => 0.4,
            'items' => [
                'Conceptos de salud fam, APS y Salud Pública',
                'Ámbitos de actuación en periodo resolutorio',
                'Componentes de protección y detección temprana',
                'Identificación de necesidades comunitarias',
                'Conceptos: Instrumentos, Charlas, Visitas',
                'Manejo de APGAR, FAMILIOGRAMA y ECOMAPA',
                'Conceptos y contenidos de AIEPI Comunitario',
                'Normas Técnicas, Guías (00412), MIAS y RIAS',
                'PAI, bioseguridad y cadena de frío'
            ]
        ],
        'B.2 Desempeño (40%)' => [
            'weight' => 0.4,
            'items' => [
                'Seguridad y confianza en actividades',
                'Remisión de necesidades detectadas',
                'Planeación, ejecución y evaluación de actividades',
                'Confrontación de carnet de Vacunación',
                'Priorización de actividades en promoción',
                'Realización de Educación en Salud',
                'Uso de ayudas didácticas',
                'Participación en Trabajo de Campo',
                'Aplicación del PAE Comunitario',
                'Evaluación escrita de la rotación'
            ]
        ]
    ];

    $json_modulo1 = json_encode($criterios_modulo1, JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("INSERT INTO criterios_formularios (id_modulo, criterios_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE criterios_json = VALUES(criterios_json)");
    $stmt->execute([1, $json_modulo1]);

    echo "Criterios para módulo 1 insertados como JSON.\n";

    // Aquí puedes agregar más módulos

    echo "Migración completada.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>