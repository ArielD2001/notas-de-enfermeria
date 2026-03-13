<?php
// docente_calificar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

// Solo el rol 'docente' puede entrar aquí
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'docente') {
    header("Location: index.php");
    exit;
}

$id_docente = $_SESSION['id_usuario'];

// Procesar calificación si se envió formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_asignacion'])) {
    $id_asignacion = (int)$_POST['id_asignacion'];
    $nota_final = (float)$_POST['nota_final'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    // Verificar que la asignación pertenezca a este docente
    $stmt_check = $pdo->prepare("SELECT id_asignacion FROM asignaciones_practicas WHERE id_asignacion = ? AND id_docente = ?");
    $stmt_check->execute([$id_asignacion, $id_docente]);
    
    if ($stmt_check->fetch()) {
        try {
            // Guardar calificación manual
            $stmt = $pdo->prepare("
                INSERT INTO calificaciones (id_asignacion, nota_final, observaciones) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                nota_final = VALUES(nota_final), 
                observaciones = VALUES(observaciones)
            ");
            $stmt->execute([$id_asignacion, $nota_final, $observaciones]);
            $mensaje = "Calificación guardada exitosamente.";
        } catch(PDOException $e) {
            $error = "Error al guardar la calificación. " . $e->getMessage();
        }
    } else {
        $error = "No tienes permiso para calificar a este estudiante en este módulo.";
    }
}

// Filtros
$filtro_modulo = $_GET['modulo'] ?? '';
$filtro_periodo = $_GET['periodo'] ?? '';

// Obtener los módulos y períodos únicos asignados a este docente
$mis_modulos = $pdo->prepare("SELECT DISTINCT m.id_modulo, m.nombre_modulo 
                              FROM asignaciones_practicas a 
                              JOIN modulos_rotacion m ON a.id_modulo = m.id_modulo 
                              WHERE a.id_docente = ? ORDER BY m.nombre_modulo");
$mis_modulos->execute([$id_docente]);
$modulos_docente = $mis_modulos->fetchAll();

$mis_periodos = $pdo->prepare("SELECT DISTINCT periodo_academico 
                               FROM asignaciones_practicas 
                               WHERE id_docente = ? ORDER BY periodo_academico DESC");
$mis_periodos->execute([$id_docente]);
$periodos_docente = $mis_periodos->fetchAll();

// Obtener las listas asignadas a este docente
$query = "
    SELECT l.*, m.nombre_modulo,
    (SELECT COUNT(*) FROM asignaciones_practicas WHERE id_lista = l.id_lista) as total_estudiantes
    FROM listas l
    JOIN modulos_rotacion m ON l.id_modulo = m.id_modulo
    WHERE l.id_docente = :id_docente
";

$params = [':id_docente' => $id_docente];

if ($filtro_modulo) {
    $query .= " AND l.id_modulo = :id_modulo";
    $params[':id_modulo'] = $filtro_modulo;
}
if ($filtro_periodo) {
    $query .= " AND l.periodo_academico = :periodo";
    $params[':periodo'] = $filtro_periodo;
}

$query .= " ORDER BY l.fecha_creacion DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$listas = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-8">
    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
        <i class="fa-solid fa-star text-orange-500"></i> Calificación de Prácticas
    </h2>
    <p class="text-slate-500 mt-2">Selecciona una lista para comenzar a calificar a los estudiantes.</p>
</div>

<!-- Filtros -->
<div class="bg-white shadow-sm border border-slate-100 rounded-2xl mb-8 p-6 flex flex-col md:flex-row gap-4 items-end">
    <form action="" method="GET" class="flex flex-col md:flex-row gap-4 w-full h-full">
        <div class="flex-1">
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Período</label>
            <select name="periodo" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                <option value="">Todos los períodos</option>
                <?php foreach($periodos_docente as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['periodo_academico']); ?>" <?php echo $filtro_periodo === $p['periodo_academico'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['periodo_academico']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Módulo</label>
            <select name="modulo" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                <option value="">Todos los módulos</option>
                <?php foreach($modulos_docente as $m): ?>
                    <option value="<?php echo $m['id_modulo']; ?>" <?php echo $filtro_modulo == $m['id_modulo'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m['nombre_modulo']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2 h-[50px]">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold transition-all flex items-center gap-2">
                <i class="fa-solid fa-filter text-sm"></i> Filtrar
            </button>
            <a href="docente_calificar.php" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 px-6 py-3 rounded-2xl font-bold transition-all flex items-center gap-2">
                Limpiar
            </a>
        </div>
    </form>
</div>

<!-- Grid de Listas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($listas as $l): ?>
        <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-file-contract"></i>
                </div>
                <span class="px-3 py-1 bg-slate-100 text-slate-500 text-[10px] font-bold uppercase rounded-full tracking-widest">
                    <?php echo htmlspecialchars($l['periodo_academico']); ?>
                </span>
            </div>
            
            <h3 class="text-lg font-extrabold text-slate-900 mb-1 group-hover:text-orange-600 transition-colors">
                <?php echo htmlspecialchars($l['nombre_lista']); ?>
            </h3>
            <p class="text-sm text-slate-500 mb-4 line-clamp-1"><?php echo htmlspecialchars($l['nombre_modulo']); ?></p>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-slate-50 p-3 rounded-2xl">
                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Grupo / Sem</p>
                    <p class="text-sm font-bold text-slate-700">
                        <?php echo htmlspecialchars($l['grupo']); ?> / <?php echo htmlspecialchars($l['semestre']); ?>°
                    </p>
                </div>
                <div class="bg-slate-50 p-3 rounded-2xl">
                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Estudiantes</p>
                    <p class="text-sm font-bold text-slate-700"><?php echo $l['total_estudiantes']; ?> Alumnos</p>
                </div>
            </div>
            
            <a href="docente_lista_detalles.php?id_lista=<?php echo $l['id_lista']; ?>" 
               class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-4 rounded-2xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-orange-600/10">
                Calificar Estudiantes
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>
    <?php endforeach; ?>

    <?php if (empty($listas)): ?>
        <div class="col-span-1 md:col-span-2 lg:col-span-3 py-16 flex flex-col items-center justify-center bg-white border-2 border-dashed border-slate-100 rounded-3xl">
            <div class="w-20 h-20 rounded-full bg-slate-50 flex items-center justify-center text-slate-200 text-4xl mb-4">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <p class="text-slate-400 font-bold text-lg">No tienes listas asignadas todavía.</p>
            <p class="text-slate-300 text-sm mt-1">Contacta al administrador para que te asigne una rotación.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; exit; ?>

<?php require_once 'includes/footer.php'; ?>
