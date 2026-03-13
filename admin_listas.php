<?php
// admin_listas.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

// Aumentar memoria por si el Excel es grande
ini_set('memory_limit', '256M');

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Cargar libreria PhpSpreadsheet
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$mensaje = '';
$error = '';

// --- LÓGICA DE PROCESAMIENTO ---

// 1. Eliminar Lista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $id_lista = (int)$_POST['id_lista'];
    try {
        $stmt = $pdo->prepare("DELETE FROM listas WHERE id_lista = ?");
        $stmt->execute([$id_lista]);
        $mensaje = "Lista eliminada correctamente.";
    } catch (PDOException $e) {
        $error = "Error al eliminar la lista: " . $e->getMessage();
    }
}

// 2. Crear Nueva Lista (Carga Excel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre_lista = trim($_POST['nombre_lista']);
    $grupo = trim($_POST['grupo']);
    $semestre = trim($_POST['semestre']);
    $id_modulo = (int)$_POST['id_modulo'];
    $id_docente = (int)$_POST['id_docente'];
    $periodo = trim($_POST['periodo_academico']);
    
    $archivo = $_FILES['archivo_excel'];
    
    if (empty($nombre_lista) || empty($periodo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        $error = "Por favor complete todos los campos y adjunte un archivo válido.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // A. Crear registro en tabla 'listas'
            $stmt_lista = $pdo->prepare("INSERT INTO listas (nombre_lista, grupo, semestre, id_modulo, id_docente, periodo_academico) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_lista->execute([$nombre_lista, $grupo, $semestre, $id_modulo, $id_docente, $periodo]);
            $id_lista = $pdo->lastInsertId();
            
            // B. Procesar Excel
            $spreadsheet = IOFactory::load($archivo['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            
            $exitos = 0;
            
            for ($row = 2; $row <= $highestRow; $row++) {
                $identificacion = trim($worksheet->getCell([1, $row])->getValue());
                $nombres = trim($worksheet->getCell([2, $row])->getValue());
                $apellidos = trim($worksheet->getCell([3, $row])->getValue());
                
                if (empty($identificacion) || empty($nombres)) continue;
                
                // Buscar/Crear Practicante
                $stmt_p = $pdo->prepare("SELECT id_practicante FROM practicantes WHERE identificacion = ?");
                $stmt_p->execute([$identificacion]);
                $practicante = $stmt_p->fetch();
                
                if ($practicante) {
                    $id_p = $practicante['id_practicante'];
                    $pdo->prepare("UPDATE practicantes SET nombres = ?, apellidos = ? WHERE id_practicante = ?")->execute([$nombres, $apellidos, $id_p]);
                } else {
                    $stmt_ins_p = $pdo->prepare("INSERT INTO practicantes (identificacion, nombres, apellidos) VALUES (?, ?, ?)");
                    $stmt_ins_p->execute([$identificacion, $nombres, $apellidos]);
                    $id_p = $pdo->lastInsertId();
                }
                
                // Crear Asignación vinculada a la lista
                // Nota: Ignoramos duplicados de asignación para este proceso masivo o los manejamos con INSERT IGNORE
                $stmt_asig = $pdo->prepare("INSERT IGNORE INTO asignaciones_practicas (id_practicante, id_lista, id_modulo, id_docente, periodo_academico) VALUES (?, ?, ?, ?, ?)");
                $stmt_asig->execute([$id_p, $id_lista, $id_modulo, $id_docente, $periodo]);
                $exitos++;
            }
            
            $pdo->commit();
            $mensaje = "Lista '$nombre_lista' creada con éxito. Se procesaron $exitos estudiantes.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al procesar la lista: " . $e->getMessage();
        }
    }
}

// --- CONSULTAS PARA LA VISTA ---
$query = "
    SELECT l.*, m.nombre_modulo, u.nombre_completo as docente,
    (SELECT COUNT(*) FROM asignaciones_practicas WHERE id_lista = l.id_lista) as total_estudiantes
    FROM listas l
    JOIN modulos_rotacion m ON l.id_modulo = m.id_modulo
    JOIN usuarios u ON l.id_docente = u.id_usuario
    ORDER BY l.fecha_creacion DESC
";
$listas = $pdo->query($query)->fetchAll();

$modulos = $pdo->query("SELECT id_modulo, nombre_modulo FROM modulos_rotacion ORDER BY nombre_modulo")->fetchAll();
$docentes = $pdo->query("SELECT id_usuario, nombre_completo FROM usuarios WHERE rol = 'docente' ORDER BY nombre_completo")->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
            <i class="fa-solid fa-file-invoice text-orange-500"></i> Listas de Practicantes
        </h2>
        <p class="text-slate-500 mt-2">Gestiona los grupos de estudiantes cargados al sistema.</p>
    </div>
    <button onclick="document.getElementById('modal-nueva-lista').classList.remove('hidden')" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-orange-600/20 transition-all flex items-center gap-2">
        <i class="fa-solid fa-plus text-sm"></i>
        Subir Nueva Lista
    </button>
</div>

<?php if ($mensaje): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3">
        <i class="fa-solid fa-circle-check"></i>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <p><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

<!-- Tabla de Listas -->
<div class="bg-white shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Información de la Lista</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Módulo / Docente</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest">Estudiantes</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($listas as $l): ?>
                    <tr class="hover:bg-orange-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($l['nombre_lista']); ?></div>
                            <div class="text-xs text-slate-500 flex gap-2 mt-1">
                                <span class="bg-slate-100 px-1.5 py-0.5 rounded"><?php echo htmlspecialchars($l['grupo']); ?></span>
                                <span class="bg-slate-100 px-1.5 py-0.5 rounded"><?php echo htmlspecialchars($l['semestre']); ?></span>
                                <span class="text-slate-400"><?php echo htmlspecialchars($l['periodo_academico']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-700 font-medium"><?php echo htmlspecialchars($l['nombre_modulo']); ?></div>
                            <div class="text-xs text-slate-400 mt-0.5"><?php echo htmlspecialchars($l['docente']); ?></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center px-3 py-1 text-xs font-bold bg-orange-100 text-orange-700 rounded-full">
                                <?php echo $l['total_estudiantes']; ?> Alumnos
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <form action="" method="POST" onsubmit="return confirm('¿Eliminar esta lista y todas sus asignaciones?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_lista" value="<?php echo $l['id_lista']; ?>">
                                <button type="submit" class="text-red-400 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors" title="Eliminar Lista">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($listas)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-folder-open text-slate-200 text-5xl mb-3"></i>
                                <p class="text-slate-400 font-medium">No hay listas cargadas actualmente.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nueva Lista -->
<div id="modal-nueva-lista" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[200] flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
        <!-- Header Fijo -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-white flex-shrink-0">
            <h3 class="text-xl font-bold text-slate-900">Configurar Nueva Lista</h3>
            <button onclick="document.getElementById('modal-nueva-lista').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Formulario con Body Scrollable -->
        <form action="" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="accion" value="crear">
            
            <!-- Cuerpo del Formulario (Scrollable) -->
            <div class="p-8 space-y-6 overflow-y-auto flex-1 custom-scrollbar">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Nombre de la Lista</label>
                        <input type="text" name="nombre_lista" required placeholder="Ej: Listado Rotación A" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Grupo</label>
                        <input type="text" name="grupo" placeholder="Ej: Grupo 1" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Semestre</label>
                        <select name="semestre" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                            <option value="">-- Seleccione --</option>
                            <?php for($i=1; $i<=10; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?>° Semestre</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Módulo</label>
                        <select name="id_modulo" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                            <option value="">-- Seleccione --</option>
                            <?php foreach($modulos as $m): ?>
                                <option value="<?php echo $m['id_modulo']; ?>"><?php echo htmlspecialchars($m['nombre_modulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Docente</label>
                        <select name="id_docente" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                            <option value="">-- Seleccione --</option>
                            <?php foreach($docentes as $d): ?>
                                <option value="<?php echo $d['id_usuario']; ?>"><?php echo htmlspecialchars($d['nombre_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Período Académico</label>
                        <input type="text" name="periodo_academico" required placeholder="Ej: 2024-1" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Archivo Excel con Estudiantes</label>
                    <div class="relative group">
                        <input id="archivo_excel" name="archivo_excel" type="file" class="sr-only" required accept=".xlsx, .xls, .csv">
                        <label for="archivo_excel" class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50 group-hover:bg-white group-hover:border-orange-400 transition-all cursor-pointer text-center">
                            <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-orange-500 mb-2 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-file-excel text-xl"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-900" id="file-name-modal">Seleccionar Archivo</p>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer Fijo con Botones -->
            <div class="p-6 border-t border-slate-100 bg-slate-50 flex gap-4 flex-shrink-0">
                <button type="button" onclick="document.getElementById('modal-nueva-lista').classList.add('hidden')" class="flex-1 px-6 py-4 border border-slate-200 text-slate-500 font-bold rounded-2xl hover:bg-slate-200 transition-all">
                    Cancelar
                </button>
                <button type="submit" class="flex-[2] bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 px-6 rounded-2xl shadow-lg shadow-orange-600/20 transition-all">
                    Subir e Importar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('archivo_excel').addEventListener('change', function(e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : "Seleccionar Archivo";
        document.getElementById('file-name-modal').textContent = fileName;
        document.getElementById('file-name-modal').classList.add('text-orange-600');
    });
</script>

<?php require_once 'includes/footer.php'; ?>
