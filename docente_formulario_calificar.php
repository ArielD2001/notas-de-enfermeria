<?php
// docente_formulario_calificar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'docente') {
    header("Location: index.php");
    exit;
}

$id_docente = $_SESSION['id_usuario'];
$id_asignacion = isset($_GET['id_asignacion']) ? (int)$_GET['id_asignacion'] : 0;
$id_lista = isset($_GET['id_lista']) ? (int)$_GET['id_lista'] : 0;
$rotation = isset($_GET['r']) ? (int)$_GET['r'] : 1; // 1 or 2

// Map specific modules to multiple rotations
$modulo_rotaciones = [
    2 => 2, // Actividades Básicas
    3 => 2, // Médico Quirúrgico
    5 => 2, // Prácticas de Administración
    6 => 2, // Adulto Mayor
    4 => 3  // Materno Infantil
];

if (!$id_asignacion) {
    header("Location: docente_calificar.php");
    exit;
}

// Obtener información de la asignación, estudiante y módulo
$stmt = $pdo->prepare("
    SELECT 
        a.id_asignacion, a.id_lista,
        p.nombres, p.apellidos, p.identificacion,
        m.nombre_modulo, m.id_modulo,
        l.nombre_lista,
        c.nota_final, c.nota_r1, c.nota_r2, c.nota_r3, c.observaciones, c.detalles_json
    FROM asignaciones_practicas a
    JOIN practicantes p ON a.id_practicante = p.id_practicante
    JOIN modulos_rotacion m ON a.id_modulo = m.id_modulo
    JOIN listas l ON a.id_lista = l.id_lista
    LEFT JOIN calificaciones c ON a.id_asignacion = c.id_asignacion
    WHERE a.id_asignacion = ? AND a.id_docente = ?
");
$stmt->execute([$id_asignacion, $id_docente]);
$data = $stmt->fetch();

if (!$data) {
    header("Location: docente_calificar.php");
    exit;
}

$mensaje = '';
$error = '';

// Procesar guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nota_actual = (float)$_POST['nota_definitiva'];
    $observaciones = $_POST['observaciones'] ?? '';
    $id_modulo = (int)$data['id_modulo'];
    
    // Recuperar detalles anteriores para no borrarlos
    $detalles_existentes = json_decode($data['detalles_json'] ?? '{}', true);
    
    // Capturar nuevos ítems
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'item_') === 0 || strpos($key, 'input') === 0 || strpos($key, 'slider_') === 0) {
            // Si es rotación, adjuntar el prefijo para no colisionar
            if (isset($modulo_rotaciones[$id_modulo])) {
                $detalles_existentes["r{$rotation}_" . $key] = $value;
            } else {
                $detalles_existentes[$key] = $value;
            }
        }
    }
    $detalles_json = json_encode($detalles_existentes);
    
    try {
        if (isset($modulo_rotaciones[$id_modulo])) {
            $total_rotaciones = $modulo_rotaciones[$id_modulo];
            
            // Lógica para Módulos de Rotación (Promedio según cantidad de rotaciones)
            $nota_r1 = ($rotation === 1) ? $nota_actual : ($data['nota_r1'] ?? null);
            $nota_r2 = ($rotation === 2) ? $nota_actual : ($data['nota_r2'] ?? null);
            $nota_r3 = null;
            
            if ($total_rotaciones == 3) {
                $nota_r3 = ($rotation === 3) ? $nota_actual : ($data['nota_r3'] ?? null);
            }
            
            // Si todas existen, promedio. Si no, la que haya (o 0)
            $suma = 0;
            $divisor = 0;
            
            if ($nota_r1 !== null) { $suma += $nota_r1; $divisor++; }
            if ($nota_r2 !== null) { $suma += $nota_r2; $divisor++; }
            if ($nota_r3 !== null) { $suma += $nota_r3; $divisor++; }
            
            $final = ($divisor > 0) ? ($suma / $divisor) : 0;
            
            $stmt_save = $pdo->prepare("
                INSERT INTO calificaciones (id_asignacion, nota_final, nota_r1, nota_r2, nota_r3, observaciones, detalles_json) 
                VALUES (?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                nota_final = VALUES(nota_final), 
                nota_r1 = VALUES(nota_r1),
                nota_r2 = VALUES(nota_r2),
                nota_r3 = VALUES(nota_r3),
                observaciones = VALUES(observaciones),
                detalles_json = VALUES(detalles_json)
            ");
            $stmt_save->execute([$id_asignacion, $final, $nota_r1, $nota_r2, $nota_r3, $observaciones, $detalles_json]);
            $data['nota_r1'] = $nota_r1;
            $data['nota_r2'] = $nota_r2;
            if ($total_rotaciones == 3) {
                $data['nota_r3'] = $nota_r3;
            }
            $data['nota_final'] = $final;
        } else {
            // Lógica genérica
            $stmt_save = $pdo->prepare("
                INSERT INTO calificaciones (id_asignacion, nota_final, observaciones, detalles_json) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                nota_final = VALUES(nota_final), 
                observaciones = VALUES(observaciones),
                detalles_json = VALUES(detalles_json)
            ");
            $stmt_save->execute([$id_asignacion, $nota_actual, $observaciones, $detalles_json]);
            $data['nota_final'] = $nota_actual;
        }
        
        $mensaje = "Calificación guardada exitosamente.";
        $data['observaciones'] = $observaciones;
        $data['detalles_json'] = $detalles_json;
    } catch (Exception $e) {
        $error = "Error al guardar: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-slate-400 text-sm mb-2">
            <a href="docente_calificar.php" class="hover:text-orange-600 transition-colors">Calificación</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <a href="docente_lista_detalles.php?id_lista=<?php echo $data['id_lista']; ?>" class="hover:text-orange-600 transition-colors"><?php echo htmlspecialchars($data['nombre_lista']); ?></a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <span class="text-slate-600 font-medium">Calificar Estudiante</span>
        </div>
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
            <i class="fa-solid fa-file-signature text-orange-500"></i> Formulario de Calificación
        </h2>
        <p class="text-slate-500 mt-1 font-medium italic">
            Módulo: <span class="text-orange-600"><?php echo htmlspecialchars($data['nombre_modulo']); ?></span>
        </p>
    </div>
    <a href="docente_lista_detalles.php?id_lista=<?php echo $data['id_lista']; ?>" class="bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 px-6 py-3 rounded-2xl font-bold transition-all flex items-center gap-2 shadow-sm">
        <i class="fa-solid fa-arrow-left"></i> Volver a la Lista
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Panel de Información del Estudiante -->
    <div class="lg:col-span-1">
        <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm sticky top-8 max-h-[calc(100vh-4rem)] overflow-y-auto custom-scrollbar">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-24 h-24 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-4xl font-black mb-4 border-4 border-white shadow-lg">
                    <?php echo strtoupper(substr($data['nombres'], 0, 1) . substr($data['apellidos'], 0, 1)); ?>
                </div>
                <h3 class="text-xl font-black text-slate-900"><?php echo htmlspecialchars($data['nombres'] . " " . $data['apellidos']); ?></h3>
                <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-1">ID: <?php echo htmlspecialchars($data['identificacion']); ?></p>
            </div>
            
            <div class="space-y-4">
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Estado de Calificación</p>
                    <?php if (isset($modulo_rotaciones[$data['id_modulo']])): ?>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-600">Rotación 1:</span>
                                <span class="text-xs font-black <?php echo $data['nota_r1'] !== null ? 'text-green-600' : 'text-slate-300'; ?>">
                                    <?php echo $data['nota_r1'] !== null ? number_format($data['nota_r1'], 2) : '---'; ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-600">Rotación 2:</span>
                                <span class="text-xs font-black <?php echo $data['nota_r2'] !== null ? 'text-green-600' : 'text-slate-300'; ?>">
                                    <?php echo $data['nota_r2'] !== null ? number_format($data['nota_r2'], 2) : '---'; ?>
                                </span>
                            </div>
                            <?php if ($modulo_rotaciones[$data['id_modulo']] == 3): ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-600">Rotación 3:</span>
                                    <span class="text-xs font-black <?php echo (isset($data['nota_r3']) && $data['nota_r3'] !== null) ? 'text-green-600' : 'text-slate-300'; ?>">
                                        <?php echo (isset($data['nota_r3']) && $data['nota_r3'] !== null) ? number_format($data['nota_r3'], 2) : '---'; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php if ($data['nota_final'] !== null): ?>
                            <div class="flex items-center gap-2 text-green-600 font-bold">
                                <i class="fa-solid fa-circle-check"></i>
                                Calificado (<?php echo number_format($data['nota_final'], 2); ?>)
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-2 text-orange-500 font-bold">
                                <i class="fa-solid fa-circle-play"></i>
                                Pendiente
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Final Actual</p>
                    <p class="text-4xl font-black <?php echo ($data['nota_final'] !== null && $data['nota_final'] < 3) ? 'text-red-500' : 'text-slate-900'; ?>">
                        <?php echo $data['nota_final'] !== null ? number_format($data['nota_final'], 2) : '---'; ?>
                    </p>
                    <?php if (isset($modulo_rotaciones[$data['id_modulo']])): ?>
                        <p class="text-[10px] text-slate-400 font-medium italic mt-1">(Promedio de Rotaciones)</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Evaluación -->
    <div class="lg:col-span-2">
        <?php if ($mensaje): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-2xl flex items-center gap-3">
                <i class="fa-solid fa-check-double"></i>
                <p class="font-bold text-sm"><?php echo $mensaje; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-2xl flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation"></i>
                <p class="font-bold text-sm"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="formCalificacion" class="space-y-6">
            <?php if ($data['id_modulo'] == 1): // Promoción y Prevención ?>
                <?php
                // Obtener criterios desde la base de datos
                $stmt_criterios = $pdo->prepare("SELECT criterios_json FROM criterios_formularios WHERE id_modulo = ?");
                $stmt_criterios->execute([$data['id_modulo']]);
                $criterios_json = $stmt_criterios->fetchColumn();
                $secciones = json_decode($criterios_json, true);
                ?>

                <div class="mb-6 p-4 bg-orange-50 border border-orange-100 rounded-2xl flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-orange-400 uppercase tracking-widest">Promoción y Prevención</span>
                        <span class="text-sm font-bold text-orange-700 uppercase italic">Formato FT-PE-025</span>
                    </div>
                    <i class="fa-solid fa-heart-pulse text-orange-200 text-3xl"></i>
                </div>

                <?php $sec_idx = 0; foreach ($secciones as $titulo => $info): $sec_idx++; ?>
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm section-group" data-weight="<?php echo $info['weight']; ?>">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs"><?php echo $sec_idx; ?></span>
                            <?php echo $titulo; ?>
                        </h4>
                        
                        <div class="space-y-4">
                            <?php foreach ($info['items'] as $item_idx => $item_text): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-50 group hover:bg-white hover:border-orange-100 hover:shadow-sm transition-all duration-300">
                                    <label class="text-sm font-semibold text-slate-600 mb-2 sm:mb-0 pr-4">
                                        <?php echo ($item_idx + 1) . '. ' . $item_text; ?>
                                    </label>
                                    <div class="flex items-center gap-2 bg-white p-1 rounded-xl shadow-inner border border-slate-100">
                                        <?php for ($v = 0; $v <= 5; $v++): ?>
                                            <button type="button" 
                                                    class="btn-rating w-8 h-8 rounded-lg text-xs font-bold transition-all <?php echo $v == 0 ? 'bg-slate-100 text-slate-400' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600'; ?>"
                                                    data-value="<?php echo $v; ?>">
                                                <?php echo $v; ?>
                                            </button>
                                        <?php endfor; ?>
                                        <input type="hidden" name="item_<?php echo $sec_idx . '_' . $item_idx; ?>" class="input-item" value="0">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center px-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subtotal Sección</span>
                            <span class="text-lg font-black text-slate-900 subtotal-label">0.00</span>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($data['id_modulo'] == 2): 
                // Actividades Básicas - CORREGIDO
                $secciones = [
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
                ?>

                <div class="mb-6 p-4 bg-orange-50 border border-orange-100 rounded-2xl flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-orange-400 uppercase tracking-widest">Actividades Básicas</span>
                        <span class="text-sm font-bold text-orange-700 uppercase italic">Formato FT-PE-026 (Rotación <?php echo $rotation; ?>)</span>
                    </div>
                    <i class="fa-solid fa-user-nurse text-orange-200 text-3xl"></i>
                </div>

                <?php $sec_idx = 0; foreach ($secciones as $titulo => $info): $sec_idx++; ?>
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm section-group mb-8" data-weight="<?php echo $info['weight']; ?>" <?php echo isset($info['custom_weights']) ? "data-custom-weights='".json_encode($info['custom_weights'])."'" : ""; ?>>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs"><?php echo $sec_idx; ?></span>
                            <?php echo $titulo; ?>
                        </h4>
                        
                        <div class="space-y-4">
                            <?php foreach ($info['items'] as $item_idx => $item_text): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-50 group hover:bg-white hover:border-orange-100 hover:shadow-sm transition-all duration-300">
                                    <label class="text-xs font-semibold text-slate-600 mb-2 sm:mb-0 pr-4">
                                        <?php echo ($item_idx + 1) . '. ' . $item_text; ?>
                                    </label>
                                    <div class="flex items-center gap-1 bg-white p-1 rounded-xl shadow-inner border border-slate-100">
                                        <?php for ($v = 0; $v <= 5; $v++): ?>
                                            <button type="button" 
                                                    class="btn-rating w-7 h-7 rounded-lg text-[10px] font-bold transition-all <?php echo $v == 0 ? 'bg-slate-100 text-slate-400' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600'; ?>"
                                                    data-value="<?php echo $v; ?>">
                                                <?php echo $v; ?>
                                            </button>
                                        <?php endfor; ?>
                                        <input type="hidden" name="item_<?php echo $sec_idx . '_' . $item_idx; ?>" class="input-item" value="0">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center px-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subtotal Sección</span>
                            <span class="text-lg font-black text-slate-900 subtotal-label">0.00</span>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($data['id_modulo'] == 3): 
                // Cuidado Médico Quirúrgico
                $secciones = [
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
                ?>

                <div class="mb-6 p-4 bg-orange-50 border border-orange-100 rounded-2xl flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-orange-400 uppercase tracking-widest">Cuidado Médico Quirúrgico</span>
                        <span class="text-sm font-bold text-orange-700 uppercase italic">Formato FT-PE-027 (Rotación <?php echo $rotation; ?>)</span>
                    </div>
                    <i class="fa-solid fa-stethoscope text-orange-200 text-3xl"></i>
                </div>

                <?php $sec_idx = 0; foreach ($secciones as $titulo => $info): $sec_idx++; ?>
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm section-group mb-8" data-weight="<?php echo $info['weight']; ?>" <?php echo isset($info['custom_weights']) ? "data-custom-weights='".json_encode($info['custom_weights'])."'" : ""; ?>>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs"><?php echo $sec_idx; ?></span>
                            <?php echo $titulo; ?>
                        </h4>
                        
                        <div class="space-y-4">
                            <?php foreach ($info['items'] as $item_idx => $item_text): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-50 group hover:bg-white hover:border-orange-100 hover:shadow-sm transition-all duration-300">
                                    <label class="text-xs font-semibold text-slate-600 mb-2 sm:mb-0 pr-4">
                                        <?php echo ($item_idx + 1) . '. ' . $item_text; ?>
                                    </label>
                                    <div class="flex items-center gap-1 bg-white p-1 rounded-xl shadow-inner border border-slate-100">
                                        <?php for ($v = 0; $v <= 5; $v++): ?>
                                            <button type="button" 
                                                    class="btn-rating w-7 h-7 rounded-lg text-[10px] font-bold transition-all <?php echo $v == 0 ? 'bg-slate-100 text-slate-400' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600'; ?>"
                                                    data-value="<?php echo $v; ?>">
                                                <?php echo $v; ?>
                                            </button>
                                        <?php endfor; ?>
                                        <input type="hidden" name="item_<?php echo $sec_idx . '_' . $item_idx; ?>" class="input-item" value="0">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center px-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subtotal Sección</span>
                            <span class="text-lg font-black text-slate-900 subtotal-label">0.00</span>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($data['id_modulo'] == 4): 
                // Cuidado Materno Infantil y Familia (3 Rotaciones)
                $secciones = [
                    'A.COMPETENCIAS ACTITUDINALES Y DE COMPORTAMIENTO (20%)' => [
                        'weight' => 0.2, // Sum of A1 and A2
                        'items' => [
                            'A.1 Relaciones interpersonales (10%): Mantiene relaciones cordiales y respetuosas',
                            'A.1 Expresa sus inquietudes con seguridad y confianza',
                            'A.1 Sigue las lineas de autoridad establecidas',
                            'A.1 Reconoce errores y acepta sugerencias',
                            'A.1 Maneja situaciones de estres adecuadamente',
                            'A.2 Responsabilidad (10%): Se presenta a sus actividades 15 a 10 min antes',
                            'A.2 Utiliza el uniforme completo en buen estado',
                            'A.2 Demuestra respeto por las normas de la Institucion',
                            'A.2 Cumple con la etica profesional',
                            'A.2 Asume con responsabilidad y madurez sus actividades',
                            'A.2 Desarrolla iniciativa aplicando conocimientos adquiridos',
                            'A.2 Genera, transmite y asume valores formativos',
                            'A.2 Ejerce liderazgo en la toma de decisiones',
                            'A.2 Asiste a eventos, encuentros y reuniones programados',
                            'A.2 Hace uso racional y cuidadoso de los equipos'
                        ],
                        'custom_weights' => [
                            0.02, 0.02, 0.02, 0.02, 0.02, // A1 items (10% total)
                            0.01, 0.01, 0.01, 0.01, 0.01, 0.01, 0.01, 0.01, 0.01, 0.01 // A2 items (10% total)
                        ]
                    ],
                    'B. COMPETENCIAS COGNITIVAS Y PROCEDIMENTALES (60%)' => [
                        'weight' => 0.6,
                        'items' => [
                            'B.1 Conocimiento (25%): Reconoce principios cientificos en cuidado basico',
                            'B.1 Enuncia en forma verbal o escrita las bases teoricas',
                            'B.1 Determina etapas del PAE y plan de cuidados',
                            'B.1 Conoce tecnicas asepticas necesarias para cuidado',
                            'B.1 Conoce normas bioseguridad y manejo de residuos',
                            'B.1 Conoce, identifica y diferencia insumos biomédicos',
                            'B.1 Conoce actividades básicas de bienestar y seguridad',
                            'B.1 Identifica procedimientos de admisión y egreso',
                            'B.1 Conceptualiza medicamentos y vias de administracion',
                            'B.2 Desempeño (35%): Recibe y entrega el turno, participa en ronda',
                            'B.2 Realiza los cinco momentos del lavado de manos',
                            'B.2 Realiza valoracion fisica del paciente',
                            'B.2 Detecta problemas, diagnostico de enfermeria (PAE)',
                            'B.2 Desarrolla procedimientos de Higiene y Bienestar',
                            'B.2 Controla Liquidos Ingeridos y Eliminados',
                            'B.2 Realiza Procedimiento de Terapia Intravenosa',
                            'B.2 Manejo de Soluciones, distribucion de liquidos',
                            'B.2 Calculo de Goteo',
                            'B.2 Diligencia Registros (Notas, Kardex, Tarjetas de med)',
                            'B.2 Aplica tecnicas de Administracion de Medicamentos',
                            'B.2 Realiza calculo de dosis',
                            'B.2 Mantiene tecnica aseptica en procedimientos',
                            'B.2 Realiza curaciones de heridas',
                            'B.2 Desarrolla procedimiento de control de signos vitales',
                            'B.2 Mantiene limpios y organizados los equipos',
                            'B.2 Administra oxigeno segun parametros',
                            'B.2 Maneja situacion ante posibilidad de muerte',
                            'B.2 Realiza visita domiciliaria'
                        ],
                        'custom_weights' => [
                            0.0277, 0.0277, 0.0277, 0.0277, 0.0277, 0.0277, 0.0277, 0.0277, 0.0284, // B1: 9 items (aproximado 25%)
                            0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0184, 0.0188 // B2: 19 items (aproximado 35%)
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
                ?>

                <div class="mb-6 p-4 bg-orange-50 border border-orange-100 rounded-2xl flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-orange-400 uppercase tracking-widest">Materno Infantil</span>
                        <span class="text-sm font-bold text-orange-700 uppercase italic">Formato FT-PE-028 (Rotación <?php echo $rotation; ?>)</span>
                    </div>
                    <i class="fa-solid fa-baby-carriage text-orange-200 text-3xl"></i>
                </div>

                <?php $sec_idx = 0; foreach ($secciones as $titulo => $info): $sec_idx++; ?>
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm section-group mb-8" data-weight="<?php echo $info['weight']; ?>" <?php echo isset($info['custom_weights']) ? "data-custom-weights='".json_encode($info['custom_weights'])."'" : ""; ?>>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs"><?php echo $sec_idx; ?></span>
                            <?php echo $titulo; ?>
                        </h4>
                        
                        <div class="space-y-4">
                            <?php foreach ($info['items'] as $item_idx => $item_text): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-50 group hover:bg-white hover:border-orange-100 hover:shadow-sm transition-all duration-300">
                                    <label class="text-xs font-semibold text-slate-600 mb-2 sm:mb-0 pr-4">
                                        <?php echo ($item_idx + 1) . '. ' . $item_text; ?>
                                    </label>
                                    <div class="flex items-center gap-1 bg-white p-1 rounded-xl shadow-inner border border-slate-100">
                                        <?php for ($v = 0; $v <= 5; $v++): ?>
                                            <button type="button" 
                                                    class="btn-rating w-7 h-7 rounded-lg text-[10px] font-bold transition-all <?php echo $v == 0 ? 'bg-slate-100 text-slate-400' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600'; ?>"
                                                    data-value="<?php echo $v; ?>">
                                                <?php echo $v; ?>
                                            </button>
                                        <?php endfor; ?>
                                        <input type="hidden" name="item_<?php echo $sec_idx . '_' . $item_idx; ?>" class="input-item" value="0">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center px-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subtotal Sección</span>
                            <span class="text-lg font-black text-slate-900 subtotal-label">0.00</span>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($data['id_modulo'] == 6): 
                // Adulto y Adulto Mayor
                $secciones = [
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
                ?>

                <div class="mb-6 p-4 bg-orange-50 border border-orange-100 rounded-2xl flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-orange-400 uppercase tracking-widest">Adulto y Adulto Mayor</span>
                        <span class="text-sm font-bold text-orange-700 uppercase italic">Formato FT-PE-027 (Rotación <?php echo $rotation; ?>)</span>
                    </div>
                    <i class="fa-solid fa-hospital-user text-orange-200 text-3xl"></i>
                </div>

                <?php $sec_idx = 0; foreach ($secciones as $titulo => $info): $sec_idx++; ?>
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm section-group mb-8" data-weight="<?php echo $info['weight']; ?>" <?php echo isset($info['custom_weights']) ? "data-custom-weights='".json_encode($info['custom_weights'])."'" : ""; ?>>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs"><?php echo $sec_idx; ?></span>
                            <?php echo $titulo; ?>
                        </h4>
                        
                        <div class="space-y-4">
                            <?php foreach ($info['items'] as $item_idx => $item_text): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-50 group hover:bg-white hover:border-orange-100 hover:shadow-sm transition-all duration-300">
                                    <label class="text-xs font-semibold text-slate-600 mb-2 sm:mb-0 pr-4">
                                        <?php echo ($item_idx + 1) . '. ' . $item_text; ?>
                                    </label>
                                    <div class="flex items-center gap-1 bg-white p-1 rounded-xl shadow-inner border border-slate-100">
                                        <?php for ($v = 0; $v <= 5; $v++): ?>
                                            <button type="button" 
                                                    class="btn-rating w-7 h-7 rounded-lg text-[10px] font-bold transition-all <?php echo $v == 0 ? 'bg-slate-100 text-slate-400' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600'; ?>"
                                                    data-value="<?php echo $v; ?>">
                                                <?php echo $v; ?>
                                            </button>
                                        <?php endfor; ?>
                                        <input type="hidden" name="item_<?php echo $sec_idx . '_' . $item_idx; ?>" class="input-item" value="0">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center px-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subtotal Sección</span>
                            <span class="text-lg font-black text-slate-900 subtotal-label">0.00</span>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($data['id_modulo'] == 5):
                // Prácticas de Administración - FT-PE-029
                $secciones = [
                    'A. COMPETENCIAS ACTITUDINALES Y DE COMPORTAMIENTO (20%)' => [
                        'weight' => 0.2,
                        'items' => [
                            'A.1 Mantiene relaciones cordiales y respetuosas: Docentes',
                            'A.1 Mantiene relaciones cordiales y respetuosas: Compañeros',
                            'A.1 Mantiene relaciones cordiales y respetuosas: Equipo de trabajo',
                            'A.1 Mantiene relaciones cordiales y respetuosas: Pacientes',
                            'A.1 Expresa sus inquietudes con seguridad y confianza',
                            'A.1 Sigue las lineas de autoridad establecidas',
                            'A.1 Reconoce errores y acepta sugerencias',
                            'A.1 Maneja situaciones de estres adecuadamente',
                            'A.2 Se presenta a sus actividades 15 a 10 min antes y permanece en el servicio',
                            'A.2 Utiliza el uniforme completo en buen estado y mantiene buena presentación personal',
                            'A.2 Demuestra respeto por las normas de la institucion de practica',
                            'A.2 Cumple con la etica profesional',
                            'A.2 Asume con responsabilidad y madurez sus actividades',
                            'A.2 Desarrolla iniciativa aplicando conocimientos adquiridos',
                            'A.2 Genera, transmite y asume valores formativos en su comportamiento y actitud',
                            'A.2 Ejerce liderazgo en la toma de decisiones dentro del grupo',
                            'A.2 Asiste a eventos, encuentros y reuniones programados por la facultad',
                            'A.2 Hace uso racional y cuidadoso de los equipos y materiales a su cargo'
                        ],
                        'custom_weights' => [
                            0.0125, 0.0125, 0.0125, 0.0125, 0.0125, 0.0125, 0.0125, 0.0125, // A1: 8 items (10%)
                            0.0111, 0.0111, 0.0111, 0.0111, 0.0111, 0.0111, 0.0111, 0.0111, 0.0111, 0.0111 // A2: 10 items (10%)
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
                ?>

                <div class="mb-6 p-4 bg-orange-50 border border-orange-100 rounded-2xl flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-orange-400 uppercase tracking-widest">Prácticas de Administración</span>
                        <span class="text-sm font-bold text-orange-700 uppercase italic">Formato FT-PE-029 (Rotación <?php echo $rotation; ?>)</span>
                    </div>
                    <i class="fa-solid fa-clipboard-list text-orange-200 text-3xl"></i>
                </div>

                <?php $sec_idx = 0; foreach ($secciones as $titulo => $info): $sec_idx++; ?>
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm section-group mb-8" data-weight="<?php echo $info['weight']; ?>" <?php echo isset($info['custom_weights']) ? "data-custom-weights='".json_encode($info['custom_weights'])."'" : ""; ?>>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs"><?php echo $sec_idx; ?></span>
                            <?php echo $titulo; ?>
                        </h4>
                        
                        <div class="space-y-4">
                            <?php foreach ($info['items'] as $item_idx => $item_text): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-50 group hover:bg-white hover:border-orange-100 hover:shadow-sm transition-all duration-300">
                                    <label class="text-xs font-semibold text-slate-600 mb-2 sm:mb-0 pr-4">
                                        <?php echo ($item_idx + 1) . '. ' . $item_text; ?>
                                    </label>
                                    <div class="flex items-center gap-1 bg-white p-1 rounded-xl shadow-inner border border-slate-100">
                                        <?php for ($v = 0; $v <= 5; $v++): ?>
                                            <button type="button" 
                                                    class="btn-rating w-7 h-7 rounded-lg text-[10px] font-bold transition-all <?php echo $v == 0 ? 'bg-slate-100 text-slate-400' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600'; ?>"
                                                    data-value="<?php echo $v; ?>">
                                                <?php echo $v; ?>
                                            </button>
                                        <?php endfor; ?>
                                        <input type="hidden" name="item_<?php echo $sec_idx . '_' . $item_idx; ?>" class="input-item" value="0">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center px-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subtotal Sección</span>
                            <span class="text-lg font-black text-slate-900 subtotal-label">0.00</span>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: // Generic Form ?>
                <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm">
                    <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-8 border-b border-slate-100 pb-4">
                        Dimensiones de Evaluación
                    </h4>
                    
                    <div class="space-y-8">
                        <!-- Competencia Cognitiva -->
                        <div class="group">
                            <div class="flex justify-between items-center mb-4">
                                <label class="font-extrabold text-slate-800 text-lg flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">1</span>
                                    Competencia Cognitiva (30%)
                                </label>
                                <span class="text-slate-400 text-xs font-bold uppercase">Nota (0 - 5)</span>
                            </div>
                            <input type="range" min="0" max="5" step="0.1" value="0" class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-orange-600 slider-competencia" data-peso="0.3" id="inputCognitiva">
                            <div class="flex justify-between mt-2">
                                <p class="text-xs text-slate-400 font-medium italic">Conocimientos teóricos y fundamentación.</p>
                                <span class="font-black text-slate-900 valor-calif">0.00</span>
                            </div>
                        </div>

                        <!-- Competencia Procedimental -->
                        <div class="group">
                            <div class="flex justify-between items-center mb-4">
                                <label class="font-extrabold text-slate-800 text-lg flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-sm">2</span>
                                    Competencia Procedimental (40%)
                                </label>
                                <span class="text-slate-400 text-xs font-bold uppercase">Nota (0 - 5)</span>
                            </div>
                            <input type="range" min="0" max="5" step="0.1" value="0" class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-orange-600 slider-competencia" data-peso="0.4" id="inputProcedimental">
                            <div class="flex justify-between mt-2">
                                <p class="text-xs text-slate-400 font-medium italic">Habilidades técnicas y aplicación práctica.</p>
                                <span class="font-black text-slate-900 valor-calif">0.00</span>
                            </div>
                        </div>

                        <!-- Competencia Actitudinal -->
                        <div class="group">
                            <div class="flex justify-between items-center mb-4">
                                <label class="font-extrabold text-slate-800 text-lg flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm">3</span>
                                    Competencia Actitudinal (30%)
                                </label>
                                <span class="text-slate-400 text-xs font-bold uppercase">Nota (0 - 5)</span>
                            </div>
                            <input type="range" min="0" max="5" step="0.1" value="0" class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-orange-600 slider-competencia" data-peso="0.3" id="inputActitudinal">
                            <div class="flex justify-between mt-2">
                                <p class="text-xs text-slate-400 font-medium italic">Ética, responsabilidad y trato humano.</p>
                                <span class="font-black text-slate-900 valor-calif">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Observaciones y Definitiva -->
            <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm">
                <div class="mb-6">
                    <label class="block text-sm font-black text-slate-400 uppercase tracking-widest mb-3">Observaciones Adicionales</label>
                    <textarea name="observaciones" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium" placeholder="Escribe aquí el desempeño detallado del estudiante..."><?php echo htmlspecialchars($data['observaciones'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center border-t border-slate-100 pt-8">
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Nota Definitiva Calculada</p>
                        <div class="flex items-center gap-4">
                            <span id="labelDefinitiva" class="text-5xl font-black text-slate-900">0.00</span>
                            <div id="badgeResultado" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-400">
                                Sin Evaluar
                            </div>
                        </div>
                        <input type="hidden" name="nota_definitiva" id="inputDefinitiva" value="0">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white px-8 py-5 rounded-3xl font-black text-lg transition-all shadow-xl shadow-slate-900/10 flex items-center justify-center gap-3">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            Guardar Calificación
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labelDefinitiva = document.getElementById('labelDefinitiva');
    const inputDefinitiva = document.getElementById('inputDefinitiva');
    const badgeResultado = document.getElementById('badgeResultado');

    // --- Lógica para Formulario Específico (Botones) ---
    const btnRatings = document.querySelectorAll('.btn-rating');
    const sectionGroups = document.querySelectorAll('.section-group');

    function actualizarCalculoEspecífico() {
        let notaFinal = 0;

        sectionGroups.forEach(group => {
            const weight = parseFloat(group.getAttribute('data-weight'));
            const inputs = group.querySelectorAll('.input-item');
            const subtotalLabel = group.querySelector('.subtotal-label');
            const customWeightsAttr = group.getAttribute('data-custom-weights');
            
            let promedioSeccion = 0;
            
            if (customWeightsAttr) {
                const customWeights = JSON.parse(customWeightsAttr);
                inputs.forEach((input, idx) => {
                    promedioSeccion += parseFloat(input.value) * (customWeights[idx] || 0);
                });
            } else {
                let sum = 0;
                inputs.forEach(input => sum += parseFloat(input.value));
                promedioSeccion = inputs.length > 0 ? sum / inputs.length : 0;
            }
            
            subtotalLabel.textContent = promedioSeccion.toFixed(2);
            notaFinal += promedioSeccion * weight;
        });

        mostrarResultado(notaFinal);
    }

    btnRatings.forEach(btn => {
        btn.addEventListener('click', function() {
            const container = this.parentElement;
            const input = container.querySelector('.input-item');
            const val = this.getAttribute('data-value');

            // Resetear botones del mismo ítem
            container.querySelectorAll('.btn-rating').forEach(b => {
                b.classList.remove('bg-orange-600', 'text-white');
                b.classList.add('bg-slate-100', 'text-slate-500');
            });

            // Activar este botón
            this.classList.remove('bg-slate-100', 'text-slate-500');
            this.classList.add('bg-orange-600', 'text-white');
            
            input.value = val;
            actualizarCalculoEspecífico();
        });
    });

    // --- Lógica para Formulario Genérico (Sliders) ---
    const sliders = document.querySelectorAll('.slider-competencia');

    function actualizarCalculoGenerico() {
        let total = 0;
        sliders.forEach(slider => {
            const peso = parseFloat(slider.getAttribute('data-peso'));
            const valor = parseFloat(slider.value);
            total += valor * peso;
            slider.closest('.group').querySelector('.valor-calif').textContent = valor.toFixed(2);
        });
        mostrarResultado(total);
    }

    sliders.forEach(slider => {
        slider.addEventListener('input', actualizarCalculoGenerico);
    });

    // --- Función Común para Mostrar Resultados ---
    function mostrarResultado(total) {
        labelDefinitiva.textContent = total.toFixed(2);
        inputDefinitiva.value = total.toFixed(2);

        if (total >= 3.0) {
            labelDefinitiva.className = "text-5xl font-black text-green-600";
            badgeResultado.className = "px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-green-100 text-green-700";
            badgeResultado.textContent = "Aprobado";
        } else if (total > 0) {
            labelDefinitiva.className = "text-5xl font-black text-red-500";
            badgeResultado.className = "px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-100 text-red-700";
            badgeResultado.textContent = "Reprobado";
        } else {
            labelDefinitiva.className = "text-5xl font-black text-slate-900";
            badgeResultado.className = "px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-400";
            badgeResultado.textContent = "Sin Evaluar";
        }
    }

    // --- Restaurar Estado Guardado ---
    const savedData = <?php echo $data['detalles_json'] ?: '{}'; ?>;
    const currentModule = <?php echo $data['id_modulo']; ?>;
    const currentRotation = <?php echo $rotation; ?>;
    
    // Primero inicializar (esto asegura que subtotales empiecen bien)
    if (sectionGroups.length > 0) {
        actualizarCalculoEspecífico();
    } else if (sliders.length > 0) {
        actualizarCalculoGenerico();
    }

    // Aplicar datos guardados
    Object.keys(savedData).forEach(key => {
        let val = savedData[key];
        let targetKey = key;

        // Si es el módulo de rotaciones, filtrar solo las de la rotación actual
        const modulosConRotacion = [2, 3, 4, 6];
        if (modulosConRotacion.includes(currentModule)) {
            const prefix = `r${currentRotation}_`;
            if (key.startsWith(prefix)) {
                targetKey = key.replace(prefix, '');
            } else {
                return; // Ignorar si es de otra rotación
            }
        }
        
        // Item de botones (Promoción o Actividades Básicas)
        if (targetKey.startsWith('item_')) {
            const hiddenInput = document.querySelector(`input[name="${targetKey}"]`);
            if (hiddenInput) {
                const container = hiddenInput.parentElement;
                const btn = container.querySelector(`.btn-rating[data-value="${val}"]`);
                if (btn) btn.click();
            }
        }
        
        // Sliders (Genérico)
        if (targetKey.startsWith('input') && targetKey !== 'inputDefinitiva') {
            const slider = document.getElementById(targetKey);
            if (slider) {
                slider.value = val;
                actualizarCalculoGenerico();
            }
        }
    });

});
</script>

<?php require_once 'includes/footer.php'; ?>
