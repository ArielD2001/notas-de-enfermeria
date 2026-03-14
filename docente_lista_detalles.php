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

// Parámetros de búsqueda y paginación
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta con filtros
$where_extra = '';
$params_est = [$id_lista, $id_docente];
if (!empty($busqueda)) {
    $where_extra = " AND (p.identificacion LIKE ? OR CONCAT(p.nombres, ' ', p.apellidos) LIKE ? OR p.nombres LIKE ? OR p.apellidos LIKE ?)";
    $params_est = array_merge($params_est, ["%$busqueda%", "%$busqueda%", "%$busqueda%", "%$busqueda%"]);
}

// Contar total de estudiantes
$stmt_count = $pdo->prepare("
    SELECT COUNT(*) FROM asignaciones_practicas a
    JOIN practicantes p ON a.id_practicante = p.id_practicante
    WHERE a.id_lista = ? AND a.id_docente = ? $where_extra
");
$stmt_count->execute($params_est);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// Obtener estudiantes con paginación
$stmt_est = $pdo->prepare("
    SELECT 
        a.id_asignacion,
        p.identificacion, p.nombres, p.apellidos,
        c.nota_final, c.nota_r1, c.nota_r2, c.nota_r3, c.observaciones
    FROM asignaciones_practicas a
    JOIN practicantes p ON a.id_practicante = p.id_practicante
    LEFT JOIN calificaciones c ON a.id_asignacion = c.id_asignacion
    WHERE a.id_lista = ? AND a.id_docente = ? $where_extra
    ORDER BY p.apellidos ASC, p.nombres ASC
    LIMIT $por_pagina OFFSET $offset
");
$stmt_est->execute($params_est);
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

<!-- Botones de exportación -->
<div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-file-export text-slate-400"></i>
            <span class="text-sm font-semibold text-slate-700">Exportar Reporte</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="reportes.php?export_type=excel&id_lista=<?php echo $id_lista; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
                <i class="fa-solid fa-file-excel"></i> Excel
            </a>
            <a href="reportes.php?export_type=pdf&id_lista=<?php echo $id_lista; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>
</div>

<!-- Barra de búsqueda compacta -->
<div class="flex items-center justify-between gap-4 mb-6">
    <form method="GET" class="flex items-center gap-3 flex-1 max-w-lg">
        <input type="hidden" name="id_lista" value="<?php echo $id_lista; ?>">
        <div class="relative flex-1">
            <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar estudiantes..." class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm">
            <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
        </div>
        <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-xl shadow-sm hover:shadow-md transition-all">
            <i class="fa-solid fa-search mr-1"></i> Buscar
        </button>
        <?php if (!empty($busqueda)): ?>
            <a href="?id_lista=<?php echo $id_lista; ?><?php echo isset($_GET['pagina']) ? '&pagina=' . $_GET['pagina'] : ''; ?>" class="px-3 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">
                <i class="fa-solid fa-times"></i>
            </a>
        <?php endif; ?>
    </form>
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
                            <?php if ($lista_info['rotaciones'] > 1): ?>
                                <div class="flex flex-col gap-2 min-w-[120px]">
                                    <?php for ($r = 1; $r <= $lista_info['rotaciones']; $r++): ?>
                                        <a href="docente_formulario_calificar.php?id_asignacion=<?php echo $e['id_asignacion']; ?>&id_lista=<?php echo $id_lista; ?>&r=<?php echo $r; ?>" 
                                           class="inline-flex items-center justify-center gap-2 px-3 py-1.5 bg-slate-800 hover:bg-black text-white text-[10px] font-bold rounded-lg transition-all">
                                            R<?php echo $r; ?>
                                        </a>
                                    <?php endfor; ?>
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

<!-- Paginación -->
<?php if ($total_paginas > 1): ?>
<div class="bg-white px-6 py-4 border-t border-slate-100 flex items-center justify-between">
    <div class="text-sm text-slate-500">
        Mostrando <?php echo ($offset + 1) . ' - ' . min($offset + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> estudiantes
    </div>
    <div class="flex items-center space-x-2">
        <?php
        $query_params = $_GET;
        $query_params['id_lista'] = $id_lista;
        if ($pagina > 1): ?>
            <?php $query_params['pagina'] = $pagina - 1; ?>
            <a href="?<?php echo http_build_query($query_params); ?>" class="px-3 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 transition-all">
                <i class="fa-solid fa-chevron-left mr-1"></i> Anterior
            </a>
        <?php endif; ?>

        <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
            <?php $query_params['pagina'] = $i; ?>
            <a href="?<?php echo http_build_query($query_params); ?>" class="px-3 py-2 text-sm font-medium <?php echo $i === $pagina ? 'text-orange-600 bg-orange-50 border-orange-300' : 'text-slate-500 bg-white border-slate-200'; ?> border rounded-2xl hover:bg-slate-50 transition-all">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($pagina < $total_paginas): ?>
            <?php $query_params['pagina'] = $pagina + 1; ?>
            <a href="?<?php echo http_build_query($query_params); ?>" class="px-3 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 transition-all">
                Siguiente <i class="fa-solid fa-chevron-right ml-1"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
