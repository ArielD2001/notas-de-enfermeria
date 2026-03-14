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

    // Agregar columna 'rotaciones' a modulos_rotacion si no existe
    $alter_sql = "
        ALTER TABLE modulos_rotacion 
        ADD COLUMN IF NOT EXISTS rotaciones INT DEFAULT 1;
    ";
    $pdo->exec($alter_sql);
    echo "Columna 'rotaciones' añadida a 'modulos_rotacion'.\n";

    // Eliminar columna 'creditos' si existe
    try {
        $pdo->exec("ALTER TABLE modulos_rotacion DROP COLUMN IF EXISTS creditos");
        echo "Columna 'creditos' eliminada de 'modulos_rotacion'.\n";
    } catch (PDOException $e) {
        echo "No se pudo eliminar la columna 'creditos': " . $e->getMessage() . "\n";
    }

    // Asegurar que no existan múltiples registros por módulo
    try {
        $pdo->exec("ALTER TABLE criterios_formularios ADD UNIQUE KEY uk_id_modulo (id_modulo)");
        echo "Índice único 'uk_id_modulo' creado en criterios_formularios.\n";
    } catch (PDOException $e) {
        // Ignorar si el índice ya existe o si hay duplicados existentes
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "No se pudo crear el índice único en criterios_formularios (posibles duplicados existentes).\n";
        } else {
            throw $e;
        }
    }

    // Preparar consulta INSERT
    $stmt = $pdo->prepare("INSERT INTO criterios_formularios (id_modulo, criterios_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE criterios_json = VALUES(criterios_json)");

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
    $stmt->execute([1, $json_modulo1]);

    // Datos para módulo 2: Actividades Básicas
    $criterios_modulo2 = [
        'A.1 Relaciones interpersonales (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Relaciones cordiales: Docentes',
                'Relaciones cordiales: Compañeros',
                'Relaciones cordiales: Equipo interdisciplinario',
                'Relaciones cordiales: Pacientes y familiares',
                'Expresa inquietudes con seguridad y confianza',
                'Sigue las lineas de autoridad establecidas',
                'Reconoce errores y acepta sugerencias',
                'Maneja situaciones de estrés adecuadamente'
            ]
        ],
        'A.2 Responsabilidad y compromiso (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Puntualidad (15-10 min antes) y permanencia',
                'Uniforme completo y presentación personal',
                'Respeto por las normas de la institución',
                'Cumplimiento de la ética profesional',
                'Asume responsabilidad y madurez',
                'Iniciativa aplicando conocimientos',
                'Valores formativos en comportamiento',
                'Liderazgo en la toma de decisiones',
                'Asistencia a eventos y reuniones',
                'Uso racional de equipos y materiales'
            ]
        ],
        'B.1 Conocimiento científico (25%)' => [
            'weight' => 0.25,
            'items' => [
                'Principios científicos en procedimientos básicos',
                'Enuncia bases teóricas (verbal/escrita)',
                'Proceso de enfermería y plan de cuidados',
                'Técnicas asépticas en el cuidado',
                'Normas de bioseguridad y residuos',
                'Identifica insumos y equipos biomédicos',
                'Conoce actividades básicas (bienestar/comodidad)',
                'Procedimientos de admisión y egreso',
                'Medicamentos, soluciones y vías de admin.'
            ]
        ],
        'B.2 Desempeño (35%)' => [
            'weight' => 0.35,
            'items' => [
                'Recibo/entrega de turno y participación ronda',
                'Cinco momentos del lavado de manos',
                'Valoración física del paciente asignado',
                'Diagnóstico de enfermería y planifica PAE',
                'Higiene: Baño del paciente',
                'Higiene: Arreglo de la unidad',
                'Higiene: Cambios de posición, masajes y traslado',
                'Higiene: Procedimientos de Nutrición',
                'Higiene: Procedimientos de Eliminación',
                'Higiene: Cateterismo y manejo sonda vesical',
                'Control de líquidos ingeridos y eliminados',
                'Terapia intravenosa',
                'Manejo de Soluciones y distribución de líquidos',
                'Cálculo de Goteo',
                'Registros: Notas de Enfermería',
                'Registros: Control de Líquidos',
                'Registros: Kardex',
                'Registros: Tarjeta de Medicamentos',
                'Registros: Registro de Medicamentos',
                'Técnicas de administración de medicamentos',
                'Cálculo de dosis',
                'Técnica aséptica y bioseguridad',
                'Curación de heridas e infecciones',
                'Control de signos vitales',
                'Orden de equipos y áreas de trabajo',
                'Administración de oxígeno',
                'Ética ante muerte del paciente',
                'Realiza visita domiciliaria'
            ]
        ],
        'C. Evaluación Escrita (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Evaluación Escrita de la rotación'
            ]
        ],
        'D. Producto Final (10%)' => [
            'weight' => 0.1,
            'items' => [
                'CLUB DE REVISTA (4%)',
                'NARRATIVAS y PAE (6%)'
            ],
            'custom_weights' => [0.4, 0.6]
        ]
    ];

    $json_modulo2 = json_encode($criterios_modulo2, JSON_UNESCAPED_UNICODE);
    $stmt->execute([2, $json_modulo2]);

    // Datos para módulo 3: Cuidado Médico Quirúrgico
    $criterios_modulo3 = [
        'A.1 Relaciones interpersonales (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Mantiene relaciones cordiales: Docentes',
                'Mantiene relaciones cordiales: Compañeros',
                'Mantiene relaciones cordiales: Equipo de trabajo',
                'Mantiene relaciones cordiales: Personas (Pacientes)',
                'Expresa inquietudes con seguridad y confianza',
                'Sigue las líneas de autoridad establecidas',
                'Reconoce errores y acepta sugerencias',
                'Maneja situaciones de estrés adecuadamente'
            ]
        ],
        'A.2 Responsabilidad y compromiso (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Puntualidad (15-10 min antes) y permanencia',
                'Uniforme completo y presentación personal',
                'Respeto por las normas de la institución',
                'Cumplimiento de la ética profesional',
                'Asume responsabilidad y madurez',
                'Iniciativa aplicando conocimientos',
                'Valores formativos en comportamiento',
                'Liderazgo en la toma de decisiones',
                'Asistencia a eventos y reuniones',
                'Uso racional de equipos y materiales'
            ]
        ],
        'B.1 Conocimiento científico (25%)' => [
            'weight' => 0.25,
            'items' => [
                'Conceptos de anatomía y fisiología de sistemas',
                'Reconoce signos y síntomas de patologías',
                'Conceptos farmacológicos según patologías',
                'Interpreta resultados de paraclínicos básicos',
                'Reconoce complicaciones de patologías/procedimientos',
                'Enuncia tratamiento y fundamentación teórica'
            ]
        ],
        'B.2 Desempeño (35%)' => [
            'weight' => 0.35,
            'items' => [
                'Seguridad y confianza en actividades',
                'Observa y valora clínicamente al paciente',
                'Planea cuidados según problemas identificados',
                'Ejecuta los planes de cuidados',
                'Evalúa los planes de cuidados aplicados',
                'Realiza procedimientos según protocolos',
                'Manejo de registros (HC y formatos)',
                'Informa oportunamente cambios en signos/síntomas',
                'Prioriza e interpreta exámenes de laboratorio',
                'Informa eventos adversos y participa en calidad',
                'Explica al paciente/familia el procedimiento',
                'Realización de educación al paciente/familia',
                'Organiza equipo necesario para procedimientos',
                'Aplica las normas de bioseguridad',
                'Evalúa diariamente actividades realizadas',
                'Elabora plan de cuidados de Enfermería'
            ]
        ],
        'C. Evaluación Escrita (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Evaluación Escrita de la rotación'
            ]
        ],
        'D. Producto Final (10%)' => [
            'weight' => 0.1,
            'items' => [
                'CLUB DE REVISTA (4%)',
                'NARRATIVAS y PAE (6%)'
            ],
            'custom_weights' => [0.4, 0.6]
        ]
    ];

    $json_modulo3 = json_encode($criterios_modulo3, JSON_UNESCAPED_UNICODE);
    $stmt->execute([3, $json_modulo3]);

    // Datos para módulo 4: Materno Infantil
    $criterios_modulo4 = [
        'A.1 Relaciones interpersonales (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Mantiene relaciones cordiales: Docentes',
                'Mantiene relaciones cordiales: Compañeros',
                'Mantiene relaciones cordiales: Equipo de trabajo',
                'Mantiene relaciones cordiales: Personas (Pacientes)',
                'Expresa inquietudes con seguridad y confianza',
                'Sigue las líneas de autoridad establecidas',
                'Reconoce errores y acepta sugerencias',
                'Maneja situaciones de estrés adecuadamente'
            ]
        ],
        'A.2 Responsabilidad y compromiso (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Puntualidad (15-10 min antes) y permanencia',
                'Uniforme completo y presentación personal',
                'Respeto por las normas de la institución',
                'Cumplimiento de la ética profesional',
                'Asume responsabilidad y madurez',
                'Iniciativa aplicando conocimientos',
                'Valores formativos en comportamiento',
                'Liderazgo en la toma de decisiones',
                'Asistencia a eventos y reuniones',
                'Uso racional de equipos y materiales'
            ]
        ],
        'B. Conocimiento cientifico (25%)' => [
            'weight' => 0.25,
            'items' => [
                'Conocimiento (25%): Reconoce principios cientificos en cuidado basico',
                'Enuncia en forma verbal o escrita las bases teoricas',
                'Determina etapas del PAE y plan de cuidados',
                'Conoce tecnicas asepticas necesarias para cuidado',
                'Conoce normas bioseguridad y manejo de residuos',
                'Conoce, identifica y diferencia insumos biomédicos',
                'Conoce actividades básicas de bienestar y seguridad',
                'Identifica procedimientos de admisión y egreso',
                'Conceptualiza medicamentos y vias de administracion'
            ]
        ],
        'B. Desempeño (25%)' => [
            'weight' => 0.35,
            'items' => [
                'Desempeño (35%): Recibe y entrega el turno, participa en ronda',
                'Realiza los cinco momentos del lavado de manos',
                'Realiza valoracion fisica del paciente',
                'Detecta problemas, diagnostico de enfermeria (PAE)',
                'Desarrolla procedimientos de Higiene y Bienestar',
                'Controla Liquidos Ingeridos y Eliminados',
                'Realiza Procedimiento de Terapia Intravenosa',
                'Manejo de Soluciones, distribucion de liquidos',
                'Calculo de Goteo',
                'Diligencia Registros (Notas, Kardex, Tarjetas de med)',
                'Aplica tecnicas de Administracion de Medicamentos',
                'Realiza calculo de dosis',
                'Mantiene tecnica aseptica en procedimientos',
                'Realiza curaciones de heridas',
                'Desarrolla procedimiento de control de signos vitales',
                'Mantiene limpios y organizados los equipos',
                'Administra oxigeno segun parametros',
                'Maneja situacion ante posibilidad de muerte',
                'Realiza visita domiciliaria'
            ]
        ],
        'C. Evaluación Escrita de la rotación (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Evaluación Escrita de la rotación'
            ]
        ],
        'D. PRODUCTO FINAL DE ROTACION (10%)' => [
            'weight' => 0.1,
            'items' => [
                'CLUB DE REVISTA (4%)',
                'NARRATIVAS y PAE (6%)'
            ],
            'custom_weights' => [0.4, 0.6]
        ]
    ];

    $json_modulo4 = json_encode($criterios_modulo4, JSON_UNESCAPED_UNICODE);
    $stmt->execute([4, $json_modulo4]);

    // Datos para módulo 5: Practica Administracion
    $criterios_modulo5 = [
        'A.1 Relaciones interpersonales (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Mantiene relaciones cordiales: Docentes',
                'Mantiene relaciones cordiales: Compañeros',
                'Mantiene relaciones cordiales: Equipo de trabajo',
                'Mantiene relaciones cordiales: Personas (Pacientes)',
                'Expresa inquietudes con seguridad y confianza',
                'Sigue las líneas de autoridad establecidas',
                'Reconoce errores y acepta sugerencias',
                'Maneja situaciones de estrés adecuadamente'
            ]
        ],
        'A.2 Responsabilidad y compromiso (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Puntualidad (15-10 min antes) y permanencia',
                'Uniforme completo y presentación personal',
                'Respeto por las normas de la institución',
                'Cumplimiento de la ética profesional',
                'Asume responsabilidad y madurez',
                'Iniciativa aplicando conocimientos',
                'Valores formativos en comportamiento',
                'Liderazgo en la toma de decisiones',
                'Asistencia a eventos y reuniones',
                'Uso racional de equipos y materiales'
            ]
        ],
    
        'B.1 Conocimiento Científico (25%)' => [
            'weight' => 0.25,
            'items' => [
                'Identifica conceptos y funciones generales de la Administración en Salud - Enfermería',
                'Conoce las normas institucionales, profesionales y eticas (manuales de funciones)',
                'Reconoce la normatividad vigente en salud, leyes y decretos reglamentarios',
                'Identifica los regimenes del SGSSS',
                'Conoce sus funciones como estudiante de administracion en el servicio asignado',
                'Sabe formular un diagnostico situacional y disenar planes estrategicos',
                'Planea y distribuye su tiempo y el de sus delegados',
                'Conoce los lineamientos basicos para la elaboracion de un cuadro de turnos',
                'Conoce los lineamientos basicos para la asignacion de personal segun cada servicio',
                'Reconoce cada una de las etapas del proceso de vinculacion de personal',
                'Define perfiles de acuerdo al cargo',
                'Conoce los derechos y deberes de los trabajadores segun su contratacion',
                'Conoce los conductos regulares para el manejo de los canales de comunicacion',
                'Reconoce la importancia de la delegacion de funciones',
                'Conoce los indicadores de gestion del area funcional',
                'Define conceptos de auditoria y administracion en enfermeria'
            ]
        ],
        'B.2 Desempeño (35%)' => [
            'weight' => 0.35,
            'items' => [
                'Observa y valora clinicamente a su paciente',
                'Planea el cuidado de Enfermería de acuerdo con los problemas identificados',
                'Brinda cuidado integral de enfermeria a los usuarios de su servicio',
                'Ejecuta los planes de cuidado segun lo planeado',
                'Reajusta y evalua el plan de cuidados de acuerdo a los cambios presentados',
                'Establece prioridades en la prestacion del servicio al sujeto de cuidado',
                'Elabora diagnostico situacional de la institucion como herramienta de planeacion',
                'Elabora planes estrategicos de acuerdo a los requerimientos de la institucion',
                'Evalua programas de atencion basica',
                'Participa en la elaboracion de instrumentos, manuales y protocolos',
                'Evalua y compara con los estandares los servicios prestados en la Institucion',
                'Aplica los conocimientos sobre legislacion durante la administracion del servicio',
                'Se preocupa por la organizacion y buena presentacion del servicio',
                'Aplica y evalua encuestas de satisfaccion de clientes y propone estrategias de mejoramiento',
                'Aplica instrumentos elaborados para el manejo de la comunicacion',
                'Aplica conocimientos del proceso administrativo en las actividades de enfermeria',
                'Programa capacitacion y entrenamiento al personal a su cargo',
                'Supervisa actividades del personal a su cargo y rinde informe',
                'Planea y distribuye su tiempo y el de las personas a su cargo',
                'Establece prioridades en la asignacion de funciones y actividades al personal',
                'Realiza calculo de personal y elabora horarios segun lineamientos institucionales',
                'Diligencia correctamente los registros de enfermeria y realiza auditoria',
                'Tiene en cuenta los criterios para la admision y egreso del paciente',
                'Realiza Rondas de Enfermeria y elabora plan de cuidados',
                'Brinda cuidado integral de enfermeria a los usuarios de su servicio',
                'Rinde informes claros, precisos y significativos sobre actividades realizadas'
            ]
        ],
        'C. Evaluación Escrita de la rotación (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Evaluación Escrita de la rotación'
            ]
        ],
        'D. PRODUCTO FINAL DE ROTACION (10%)' => [
            'weight' => 0.1,
            'items' => [
                'CLUB DE REVISTA (4%)',
                'DIAGNOSTICO SITUACIONAL (6%)'
            ],
            'custom_weights' => [0.4, 0.6]
        ]
    ];

    $json_modulo5 = json_encode($criterios_modulo5, JSON_UNESCAPED_UNICODE);
    $stmt->execute([5, $json_modulo5]);

    // Datos para módulo 6: Adulto y Adulto Mayor
    $criterios_modulo6 = [
        'A.1 Relaciones interpersonales (7%)' => [
            'weight' => 0.07,
            'items' => [
                'Relaciones cordiales: Docentes',
                'Relaciones cordiales: Compañeros',
                'Relaciones cordiales: Equipo de trabajo',
                'Relaciones cordiales: Pacientes y familiares',
                'Expresa sus inquietudes con seguridad y confianza',
                'Sigue las lineas de autoridad establecidas',
                'Reconoce errores y acepta sugerencias',
                'Maneja situaciones de estrés adecuadamente'
            ]
        ],
        'A.2 Responsabilidad y compromiso (8%)' => [
            'weight' => 0.08,
            'items' => [
                'Puntualidad (15-10 min antes) y permanencia',
                'Uniforme completo y presentación personal',
                'Respeto por las normas de la institución',
                'Cumplimiento de la ética profesional',
                'Asume con responsabilidad y madurez',
                'Iniciativa aplicando conocimientos adquiridos',
                'Valores formativos en comportamiento y actitud',
                'Liderazgo en la toma de decisiones',
                'Asistencia a eventos, encuentros y reuniones',
                'Uso racional de equipos y materiales'
            ]
        ],
        'B.1 Conocimiento científico (20%)' => [
            'weight' => 0.2,
            'items' => [
                'Conceptos y funciones generales Administración',
                'Normas institucionales, profesionales y éticas',
                'Normatividad vigente en salud y leyes',
                'Identifica los regímenes del SGSSS',
                'Describe indicadores de gestión del área',
                'Funciones Estudiante administración en serv.',
                'Diagnóstico situacional, planes y programas',
                'Lineamientos elaboración cuadro de turnos',
                'Asignación personal según necesidades',
                'Proceso enfermería en planeación/ejecución',
                'Calidad, oportunidad y eficiencia en salud',
                'Integración conocimientos formación académica',
                'Fisiopatología del diagnóstico de pacientes',
                'Correctos y precauciones en medicamentos',
                'Interpreta exámenes laboratorios/estudios',
                'Bases científicas signos y síntomas patologías',
                'Protección específica y detección temprana',
                'Norma Técnica Detección Temprana Adulto',
                'Tratamiento y acción farmacológica'
            ]
        ],
        'B.2 Desempeño (35%)' => [
            'weight' => 0.35,
            'items' => [
                'Diagnóstico situacional como herramienta',
                'Planes estratégicos requerimientos institución',
                'Organiza equipo ejecución procedimientos',
                'Supervisa personal a cargo y rinde informe',
                'Prioridades asignación funciones/actividades',
                'Autonomía, liderazgo y gestión del cuidado',
                'Proceso administrativo en actividades enf.',
                'Observa y Valora clínicamente al paciente',
                'Planea cuidados según problemas identificados',
                'Ejecuta planes de cuidado según planeado',
                'Reajusta y evalúa plan de cuidados',
                'Prioridades en prestación servicio al sujeto',
                'Diligencia registros y realiza auditoria',
                'Criterios admisión y egreso del paciente',
                'Rondas Enfermería y planeación cuidados',
                'Cuidado integral de enfermería a usuarios',
                'Motiva personal normas seguridad/bioseguridad',
                'Registros historia clínica y formatos',
                'Educación Contínua y entrenamiento personal',
                'Confronta normas técnicas y guías atención',
                'Prioriza actividades Promoción de Salud',
                'Evalúa programas de Atención Básica',
                'Aplica y evalúa encuestas satisfacción'
            ]
        ],
        'C. Evaluación Escrita (10%)' => [
            'weight' => 0.1,
            'items' => [
                'Evaluación Escrita de la rotación'
            ]
        ],
        'D. Producto Final (20%)' => [
            'weight' => 0.2,
            'items' => [
                'CLUB DE REVISTA (5%)',
                'NARRATIVAS y PAE (5%)',
                'DOFA y ACTIVIDADES EDUCATIVAS (10%)'
            ],
            'custom_weights' => [0.25, 0.25, 0.5]
        ]
    ];

    $json_modulo6 = json_encode($criterios_modulo6, JSON_UNESCAPED_UNICODE);
    $stmt->execute([6, $json_modulo6]);


    echo "Criterios para todos los módulos insertados.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
