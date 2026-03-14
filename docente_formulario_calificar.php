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

// Obtener la asignación y verificar que pertenece al docente
$stmt_asignacion = $pdo->prepare("SELECT a.*, p.nombres, p.apellidos, p.identificacion, m.nombre_modulo, l.nombre_lista, l.periodo_academico FROM asignaciones_practicas a JOIN practicantes p ON a.id_practicante = p.id_practicante JOIN modulos_rotacion m ON a.id_modulo = m.id_modulo JOIN listas l ON a.id_lista = l.id_lista WHERE a.id_asignacion = ? AND a.id_docente = ?");
$stmt_asignacion->execute([$id_asignacion, $id_docente]);
$asignacion = $stmt_asignacion->fetch();

if (!$asignacion) {
    header("Location: docente_calificar.php");
    exit;
}

// Obtener información del estudiante y calificaciones
$stmt = $pdo->prepare("
    SELECT 
        p.nombres, p.apellidos, p.identificacion,
        c.nota_final, c.nota_r1, c.nota_r2, c.nota_r3, c.observaciones, c.detalles_json
    FROM asignaciones_practicas a
    JOIN practicantes p ON a.id_practicante = p.id_practicante
    LEFT JOIN calificaciones c ON a.id_asignacion = c.id_asignacion
    WHERE a.id_asignacion = ?
");
$stmt->execute([$id_asignacion]);
$data = $stmt->fetch();

if (!$data) {
    header("Location: docente_calificar.php");
    exit;
}

// Agregar info de la lista y módulo
$data['id_asignacion'] = $asignacion['id_asignacion']; // Para compatibilidad
$data['id_lista'] = $id_lista;
$data['nombre_lista'] = $lista_info['nombre_lista'];
$data['nombre_modulo'] = $lista_info['nombre_modulo'];
$data['id_modulo'] = $lista_info['id_modulo'];

// Cargar criterios desde la base de datos (una sola vez)
$stmt_criterios = $pdo->query("SELECT id_modulo, criterios_json FROM criterios_formularios");
$criterios_modulos = [];
while ($row = $stmt_criterios->fetch(PDO::FETCH_ASSOC)) {
    $criterios_modulos[$row['id_modulo']] = json_decode($row['criterios_json'], true);
}

// Cargar información de rotaciones por módulo
$stmt_rotaciones = $pdo->query("SELECT id_modulo, rotaciones FROM modulos_rotacion");
$modulo_rotaciones = [];
while ($row = $stmt_rotaciones->fetch(PDO::FETCH_ASSOC)) {
    $modulo_rotaciones[$row['id_modulo']] = $row['rotaciones'];
}

// Configuración de headers por módulo
$modulo_headers = [
    1 => [
        'titulo' => 'Promoción y Prevención',
        'formato' => 'Formato FT-PE-026',
        'icono' => 'fa-shield-alt'
    ],
    2 => [
        'titulo' => 'Actividades Básicas',
        'formato' => 'Formato FT-PE-027',
        'icono' => 'fa-user-nurse'
    ],
    3 => [
        'titulo' => 'Cuidado Médico Quirúrgico',
        'formato' => 'Formato FT-PE-027',
        'icono' => 'fa-stethoscope'
    ],
    4 => [
        'titulo' => 'Materno Infantil',
        'formato' => 'Formato FT-PE-028',
        'icono' => 'fa-baby-carriage'
    ],
    5 => [
        'titulo' => 'Prácticas de Administración',
        'formato' => 'Formato FT-PE-029',
        'icono' => 'fa-clipboard-list'
    ],
    6 => [
        'titulo' => 'Adulto y Adulto Mayor',
        'formato' => 'Formato FT-PE-027',
        'icono' => 'fa-hospital-user'
    ]
];

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

            if ($nota_r1 !== null) {
                $suma += $nota_r1;
                $divisor++;
            }
            if ($nota_r2 !== null) {
                $suma += $nota_r2;
                $divisor++;
            }
            if ($nota_r3 !== null) {
                $suma += $nota_r3;
                $divisor++;
            }

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
            <?php
            $secciones = $criterios_modulos[$data['id_modulo']] ?? [];
            $header = $modulo_headers[$data['id_modulo']] ?? ['titulo' => 'Módulo Desconocido', 'formato' => 'Formato Genérico', 'icono' => 'fa-question-circle'];
            ?>

            <div class="mb-6 p-4 bg-orange-50 border border-orange-100 rounded-2xl flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-black text-orange-400 uppercase tracking-widest"><?php echo $header['titulo']; ?></span>
                    <span class="text-sm font-bold text-orange-700 uppercase italic"><?php echo $header['formato']; ?> (Rotación <?php echo $rotation; ?>)</span>
                </div>
                <i class="fa-solid <?php echo $header['icono']; ?> text-orange-200 text-3xl"></i>
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
        <?php
        // Convertir detalles_json a un arreglo PHP seguro
        $savedDetalles = [];
        if (!empty($data['detalles_json'])) {
            $savedDetalles = json_decode($data['detalles_json'], true);
            if (!is_array($savedDetalles)) {
                $savedDetalles = [];
            }
        }
        ?>
        const savedData = <?php echo json_encode($savedDetalles, JSON_UNESCAPED_UNICODE); ?>;
        const currentModule = <?php echo json_encode($data['id_modulo']); ?>;
        const currentRotation = <?php echo json_encode($rotation); ?>;

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

            // Si es un módulo con varias rotaciones, sólo usar datos de la rotación actual
            const modulosConRotacion = <?php echo json_encode(array_keys($modulo_rotaciones)); ?>;
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