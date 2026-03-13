<?php
// docente_lista_detalles.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'docente') {
    header("Location: index.php");
    exit;
}

$id_docente = $_SESSION['id_usuario'];
$id_lista = isset($_GET['id_lista']) ? (int)$_GET['id_lista'] : 0;

if (!$id_lista) {
    header("Location: docente_calificar.php");
    exit;
}

// Verificar que la lista pertenece al docente
$stmt_lista = $pdo->prepare("
    SELECT l.*, m.nombre_modulo, m.rotaciones 
    FROM listas l 
    JOIN modulos_rotacion m ON l.id_modulo = m.id_modulo 
    WHERE l.id_lista = ? AND l.id_docente = ?
");
$stmt_lista->execute([$id_lista, $id_docente]);
$lista_info = $stmt_lista->fetch();

if (!$lista_info) {
    header("Location: docente_calificar.php");
    exit;
}

$mensaje = '';
$error = '';

// Procesar calificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_asignacion'])) {
    $id_asignacion = (int)$_POST['id_asignacion'];
    $nota_final = (float)$_POST['nota_final'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    // Verificar asignación
    $stmt_check = $pdo->prepare("SELECT id_asignacion FROM asignaciones_practicas WHERE id_asignacion = ? AND id_docente = ? AND id_lista = ?");
    $stmt_check->execute([$id_asignacion, $id_docente, $id_lista]);
    
    if ($stmt_check->fetch()) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO calificaciones (id_asignacion, nota_final, observaciones) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                nota_final = VALUES(nota_final), 
                observaciones = VALUES(observaciones)
            ");
            $stmt->execute([$id_asignacion, $nota_final, $observaciones]);
            $mensaje = "Calificación guardada correctamente.";
        } catch(PDOException $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}

// Obtener estudiantes de esta lista
$stmt_est = $pdo->prepare("
    SELECT 
        a.id_asignacion,
        p.identificacion, p.nombres, p.apellidos,
        c.nota_final, c.nota_r1, c.nota_r2, c.nota_r3, c.observaciones
    FROM asignaciones_practicas a
    JOIN practicantes p ON a.id_practicante = p.id_practicante
    LEFT JOIN calificaciones c ON a.id_asignacion = c.id_asignacion
    WHERE a.id_lista = ? AND a.id_docente = ?
    ORDER BY p.apellidos ASC, p.nombres ASC
");
$stmt_est->execute([$id_lista, $id_docente]);
$estudiantes = $stmt_est->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-slate-400 text-sm mb-2">
            <a href="docente_calificar.php" class="hover:text-orange-600 transition-colors">Calificación</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <span><?php echo htmlspecialchars($lista_info['nombre_lista']); ?></span>
        </div>
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
            <i class="fa-solid fa-users-viewfinder text-orange-500"></i> Estudiantes de la Lista
        </h2>
        <p class="text-sm text-slate-500 mt-1 font-medium italic">
            <?php echo htmlspecialchars($lista_info['nombre_modulo']); ?> — Grupo <?php echo htmlspecialchars($lista_info['grupo']); ?> (<?php echo htmlspecialchars($lista_info['semestre']); ?>° Semestre)
        </p>
    </div>
    <a href="docente_calificar.php" class="bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 px-6 py-3 rounded-2xl font-bold transition-all flex items-center gap-2 shadow-sm">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

<?php if ($mensaje): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3">
        <i class="fa-solid fa-circle-check"></i>
        <p><?php echo $mensaje; ?></p>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <p><?php echo $error; ?></p>
    </div>
<?php endif; ?>

<div class="bg-white shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Estudiante</th>
                    <?php if ($lista_info['rotaciones'] > 1): ?>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest bg-slate-100/50">Rotación 1</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest bg-slate-100/50">Rotación 2</th>
                        <?php if ($lista_info['rotaciones'] >= 3): ?>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest bg-slate-100/50">Rotación 3</th>
                        <?php endif; ?>
                    <?php endif; ?>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest bg-slate-100/50">Definitiva</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Observaciones</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest">Acción</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($estudiantes as $e): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($e['apellidos'] . " " . $e['nombres']); ?></div>
                            <div class="text-xs text-slate-400">ID: <?php echo htmlspecialchars($e['identificacion']); ?></div>
                        </td>
                        <?php if ($lista_info['rotaciones'] > 1): ?>
                            <td class="px-6 py-4 bg-slate-50/20 text-center">
                                <span class="text-sm font-bold <?php echo ($e['nota_r1'] !== null) ? 'text-slate-700' : 'text-slate-300'; ?>">
                                    <?php echo $e['nota_r1'] !== null ? number_format($e['nota_r1'], 2) : '---'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 bg-slate-50/20 text-center">
                                <span class="text-sm font-bold <?php echo ($e['nota_r2'] !== null) ? 'text-slate-700' : 'text-slate-300'; ?>">
                                    <?php echo $e['nota_r2'] !== null ? number_format($e['nota_r2'], 2) : '---'; ?>
                                </span>
                            </td>
                            <?php if ($lista_info['rotaciones'] >= 3): ?>
                                <td class="px-6 py-4 bg-slate-50/20 text-center">
                                    <span class="text-sm font-bold <?php echo (isset($e['nota_r3']) && $e['nota_r3'] !== null) ? 'text-slate-700' : 'text-slate-300'; ?>">
                                        <?php echo (isset($e['nota_r3']) && $e['nota_r3'] !== null) ? number_format($e['nota_r3'], 2) : '---'; ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                        <?php endif; ?>
                        <td class="px-6 py-4 bg-slate-50/30">
                            <div class="flex justify-center">
                                <span class="px-3 py-1.5 text-lg font-black rounded-xl <?php echo ($e['nota_final'] !== null && $e['nota_final'] < 3) ? 'text-red-500 bg-red-50' : ($e['nota_final'] !== null ? 'text-green-600 bg-green-50' : 'text-slate-300 bg-slate-100'); ?>">
                                    <?php echo $e['nota_final'] !== null ? number_format($e['nota_final'], 2) : '---'; ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-slate-500 italic max-w-xs truncate" title="<?php echo htmlspecialchars($e['observaciones'] ?? ''); ?>">
                                <?php echo $e['observaciones'] ? htmlspecialchars($e['observaciones']) : '<span class="text-slate-300">Sin observaciones</span>'; ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if (in_array($lista_info['id_modulo'], [2, 3, 4, 5, 6])): ?>
                                <div class="flex flex-col gap-2 min-w-[120px]">
                                    <a href="docente_formulario_calificar.php?id_asignacion=<?php echo $e['id_asignacion']; ?>&id_lista=<?php echo $id_lista; ?>&r=1" 
                                       class="inline-flex items-center justify-center gap-2 px-3 py-1.5 bg-slate-800 hover:bg-black text-white text-[10px] font-bold rounded-lg transition-all">
                                        R1
                                    </a>
                                    <a href="docente_formulario_calificar.php?id_asignacion=<?php echo $e['id_asignacion']; ?>&id_lista=<?php echo $id_lista; ?>&r=2" 
                                       class="inline-flex items-center justify-center gap-2 px-3 py-1.5 bg-slate-800 hover:bg-black text-white text-[10px] font-bold rounded-lg transition-all">
                                        R2
                                    </a>
                                    <?php if ($lista_info['id_modulo'] == 4): ?>
                                        <a href="docente_formulario_calificar.php?id_asignacion=<?php echo $e['id_asignacion']; ?>&id_lista=<?php echo $id_lista; ?>&r=3" 
                                           class="inline-flex items-center justify-center gap-2 px-3 py-1.5 bg-slate-800 hover:bg-black text-white text-[10px] font-bold rounded-lg transition-all">
                                            R3
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <a href="docente_formulario_calificar.php?id_asignacion=<?php echo $e['id_asignacion']; ?>&id_lista=<?php echo $id_lista; ?>" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-orange-600/20 transition-all">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    Calificar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($estudiantes)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center italic text-slate-400">
                            No hay estudiantes vinculados a esta lista.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
