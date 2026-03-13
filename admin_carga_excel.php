<?php
// admin_carga_excel.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

// Aumentar memoria por si el Excel es grande (Opcional, pero recomendado)
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
$resultados_carga = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
    
    $id_modulo = (int)$_POST['id_modulo'];
    $id_docente = (int)$_POST['id_docente'];
    $periodo = trim($_POST['periodo_academico']);
    
    $archivo = $_FILES['archivo_excel'];
    
    // Validaciones básicas
    if (empty($periodo)) {
        $error = "Debe especificar el período académico.";
    } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = "Error al subir el archivo.";
    } else {
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['xls', 'xlsx', 'csv'])) {
            $error = "Formato de archivo no válido. Solo se permiten .xls, .xlsx o .csv.";
        } else {
            // Procesado del Excel
            try {
                $spreadsheet = IOFactory::load($archivo['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                
                $registros_exitosos = 0;
                $registros_fallidos = 0;
                
                // Asumimos que la fila 1 es el encabezado: Identificación, Nombres, Apellidos
                for ($row = 2; $row <= $highestRow; $row++) {
                    $identificacion = trim($worksheet->getCell([1, $row])->getValue());
                    $nombres = trim($worksheet->getCell([2, $row])->getValue());
                    $apellidos = trim($worksheet->getCell([3, $row])->getValue());
                    
                    if (empty($identificacion) || empty($nombres) || empty($apellidos)) {
                        continue; // Saltar filas vacías o mal formateadas
                    }
                    
                    try {
                        $pdo->beginTransaction();
                        
                        // 1. Buscar o Crear Practicante
                        $stmt = $pdo->prepare("SELECT id_practicante FROM practicantes WHERE identificacion = ?");
                        $stmt->execute([$identificacion]);
                        $practicante = $stmt->fetch();
                        
                        $id_practicante = null;
                        
                        if ($practicante) {
                            $id_practicante = $practicante['id_practicante'];
                            // Actualizar nombres por si acaso
                            $stmt_upd = $pdo->prepare("UPDATE practicantes SET nombres = ?, apellidos = ? WHERE id_practicante = ?");
                            $stmt_upd->execute([$nombres, $apellidos, $id_practicante]);
                        } else {
                            $stmt_ins = $pdo->prepare("INSERT INTO practicantes (identificacion, nombres, apellidos) VALUES (?, ?, ?)");
                            $stmt_ins->execute([$identificacion, $nombres, $apellidos]);
                            $id_practicante = $pdo->lastInsertId();
                        }
                        
                        // 2. Crear Asignación
                        // Verificar que no exista ya la misma asignación
                        $stmt_chk_asig = $pdo->prepare("SELECT id_asignacion FROM asignaciones_practicas WHERE id_practicante = ? AND id_modulo = ? AND periodo_academico = ?");
                        $stmt_chk_asig->execute([$id_practicante, $id_modulo, $periodo]);
                        
                        if (!$stmt_chk_asig->fetch()) {
                            $stmt_asig = $pdo->prepare("INSERT INTO asignaciones_practicas (id_practicante, id_modulo, id_docente, periodo_academico) VALUES (?, ?, ?, ?)");
                            $stmt_asig->execute([$id_practicante, $id_modulo, $id_docente, $periodo]);
                            $registros_exitosos++;
                        } else {
                            // Ya estaba asignado a este módulo en este período
                            $resultados_carga[] = "El estudiante $nombres $apellidos ($identificacion) ya estaba asignado a esta rotación en el periodo $periodo.";
                            $registros_fallidos++;
                        }
                        
                        $pdo->commit();
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $registros_fallidos++;
                        $resultados_carga[] = "Error con $identificacion: " . $e->getMessage();
                    }
                }
                
                $mensaje = "Proceso finalizado. Registros exitosos: $registros_exitosos. Fallidos o duplicados: $registros_fallidos.";
                
            } catch (Exception $e) {
                $error = "Error al leer el archivo Excel: " . $e->getMessage();
            }
        }
    }
}

// Obtener datos para los selects
$modulos = $pdo->query("SELECT id_modulo, nombre_modulo FROM modulos_rotacion ORDER BY nombre_modulo")->fetchAll();
$docentes = $pdo->query("SELECT id_usuario, nombre_completo FROM usuarios WHERE rol = 'docente' ORDER BY nombre_completo")->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-8">
    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
        <i class="fa-solid fa-file-import text-orange-500"></i> Carga Masiva de Estudiantes
    </h2>
    <p class="text-slate-500 mt-2">Importa listas de practicantes desde archivos Excel (.xlsx, .xls) o CSV.</p>
</div>

<?php if ($mensaje): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-6 mb-8 rounded-2xl shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <i class="fa-solid fa-circle-check text-xl"></i>
            <p class="font-bold text-lg"><?php echo htmlspecialchars($mensaje); ?></p>
        </div>
        <?php if (!empty($resultados_carga)): ?>
            <div class="mt-4 bg-white/50 p-4 rounded-xl border border-green-200">
                <p class="text-xs font-bold uppercase tracking-widest text-green-800 mb-2">Detalles del proceso:</p>
                <ul class="list-disc ml-5 text-sm space-y-1">
                    <?php foreach(array_slice($resultados_carga, 0, 5) as $res): ?>
                        <li><?php echo htmlspecialchars($res); ?></li>
                    <?php endforeach; ?>
                    <?php if(count($resultados_carga) > 5): ?>
                        <li class="font-bold">... y <?php echo count($resultados_carga) - 5; ?> registros más con observaciones.</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-6 mb-8 rounded-2xl shadow-sm flex items-center gap-4">
        <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
        <p class="font-medium"><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

<div class="bg-white shadow-sm border border-slate-100 rounded-3xl overflow-hidden max-w-5xl mx-auto flex flex-col lg:flex-row">
    <!-- Format Info Sidebar -->
    <div class="lg:w-1/3 bg-slate-50 p-8 border-b lg:border-b-0 lg:border-r border-slate-100">
        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">Instrucciones</h3>
        
        <div class="space-y-6">
            <div class="flex gap-4">
                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0 font-bold">1</div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Formato de Columnas</h4>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">El Excel debe tener 3 columnas en este orden: Identificación, Nombres, Apellidos.</p>
                </div>
            </div>
            
            <div class="flex gap-4">
                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0 font-bold">2</div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Primera Fila</h4>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">La primera fila se ignora automáticamente (se asume que contiene encabezados).</p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0 font-bold">3</div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Asignación Masiva</h4>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Todos los estudiantes en el archivo serán asignados al módulo y docente seleccionados.</p>
                </div>
            </div>
        </div>

        <div class="mt-10 p-4 bg-white rounded-2xl border border-slate-200">
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Ejemplo Visual:</p>
            <table class="w-full text-[10px] border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-400">
                        <th class="p-1 border border-slate-200">ID</th>
                        <th class="p-1 border border-slate-200">Nombre</th>
                        <th class="p-1 border border-slate-200">Apellido</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600">
                    <tr>
                        <td class="p-1 border border-slate-200">12345</td>
                        <td class="p-1 border border-slate-200">Juan</td>
                        <td class="p-1 border border-slate-200">Perez</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="lg:w-2/3 p-10">
        <form action="" method="POST" enctype="multipart/form-data" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Módulo (Rotación)</label>
                    <select name="id_modulo" required class="block w-full py-3 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                        <option value="">-- Seleccione --</option>
                        <?php foreach($modulos as $m): ?>
                            <option value="<?php echo $m['id_modulo']; ?>"><?php echo htmlspecialchars($m['nombre_modulo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Docente Encargado</label>
                    <select name="id_docente" required class="block w-full py-3 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                        <option value="">-- Seleccione --</option>
                        <?php foreach($docentes as $d): ?>
                            <option value="<?php echo $d['id_usuario']; ?>"><?php echo htmlspecialchars($d['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Período Académico</label>
                    <input type="text" name="periodo_academico" required placeholder="Ej: 2024-1" class="block w-full py-3 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Archivo de Datos</label>
                <div class="relative group">
                    <input id="archivo_excel" name="archivo_excel" type="file" class="sr-only" required accept=".xlsx, .xls, .csv">
                    <label for="archivo_excel" class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50 group-hover:bg-white group-hover:border-orange-400 transition-all cursor-pointer">
                        <div class="w-16 h-16 rounded-2xl bg-white shadow-sm flex items-center justify-center text-orange-500 mb-4 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-900 mb-1" id="file-name-display">Haz clic para seleccionar archivo</p>
                        <p class="text-[10px] text-slate-400 uppercase font-black tracking-tighter">Formatos soportados: XLXS, XLS, CSV</p>
                    </label>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full flex justify-center items-center gap-3 py-4 px-6 bg-orange-600 hover:bg-orange-700 text-white rounded-2xl font-bold shadow-lg shadow-orange-600/20 transition-all hover:-translate-y-1">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    Iniciar Importación de Datos
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mostrar nombre del archivo seleccionado con un toque de diseño
    document.getElementById('archivo_excel').addEventListener('change', function(e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : "Haz clic para seleccionar archivo";
        const display = document.getElementById('file-name-display');
        display.textContent = fileName;
        if(e.target.files[0]) {
            display.classList.add('text-orange-600');
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
