<?php
// index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/header.php';

// Obtener estadísticas dinámicas
$id_usuario = $_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

$count_estudiantes = 0;
$count_modulos = 0;

try {
    if ($rol === 'admin') {
        // Totales globales para admin
        $stmt = $pdo->query("SELECT COUNT(*) FROM practicantes WHERE estado = 'activo'");
        $count_estudiantes = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM modulos_rotacion");
        $count_modulos = $stmt->fetchColumn();
    }
    else {
        // Totales personalizados para docente
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_practicante) FROM asignaciones_practicas WHERE id_docente = ?");
        $stmt->execute([$id_usuario]);
        $count_estudiantes = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_modulo) FROM asignaciones_practicas WHERE id_docente = ?");
        $stmt->execute([$id_usuario]);
        $count_modulos = $stmt->fetchColumn();
    }
}
catch (PDOException $e) {
    // Silently fail or log
    error_log("Error en estadísticas: " . $e->getMessage());
}
?>

<!-- Header with welcome message -->
<div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">¡Bienvenido, <?php echo explode(' ', $_SESSION['nombre_completo'])[0]; ?>!</h1>
        <p class="text-slate-500 mt-2 text-lg">Aquí tienes un resumen de tus actividades para hoy.</p>
    </div>
    <div class="flex items-center gap-2 text-sm font-semibold text-slate-400 bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-100">
        <i class="fa-regular fa-calendar"></i>
        <?php
setlocale(LC_TIME, 'es_ES.UTF-8', 'col');
echo date('d \d\e F, Y');
?>
    </div>
</div>

<!-- Stats / Quick Actions Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-5">
        <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Practicantes</p>
            <h3 class="text-2xl font-bold text-slate-900"><?php echo $count_estudiantes; ?> Activos</h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-5">
        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-book-medical"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Módulos</p>
            <h3 class="text-2xl font-bold text-slate-900"><?php echo $count_modulos; ?> Activos</h3>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Left Column: Main Actions -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-bolt-lightning text-orange-500"></i>
                    Acciones Rápidas
                </h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                    <a href="admin_listas.php" class="group p-4 rounded-xl border border-slate-100 bg-slate-50 hover:bg-orange-600 hover:border-orange-600 transition-all">
                        <div class="w-10 h-10 rounded-lg bg-white text-orange-600 flex items-center justify-center mb-3 shadow-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <h4 class="font-bold text-slate-900 group-hover:text-white transition-colors">Listas de Practicantes</h4>
                        <p class="text-xs text-slate-500 mt-1 group-hover:text-orange-100 transition-colors">Ver y subir grupos de estudiantes.</p>
                    </a>
                    
                    <a href="admin_usuarios.php" class="group p-4 rounded-xl border border-slate-100 bg-slate-50 hover:bg-orange-600 hover:border-orange-600 transition-all">
                        <div class="w-10 h-10 rounded-lg bg-white text-orange-600 flex items-center justify-center mb-3 shadow-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <h4 class="font-bold text-slate-900 group-hover:text-white transition-colors">Nuevo Docente</h4>
                        <p class="text-xs text-slate-500 mt-1 group-hover:text-orange-100 transition-colors">Crear acceso para profesores.</p>
                    </a>
                <?php
else: ?>
                    <a href="docente_calificar.php" class="group p-4 rounded-xl border border-slate-100 bg-slate-50 hover:bg-orange-600 hover:border-orange-600 transition-all">
                        <div class="w-10 h-10 rounded-lg bg-white text-orange-600 flex items-center justify-center mb-3 shadow-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <h4 class="font-bold text-slate-900 group-hover:text-white transition-colors">Registrar Notas</h4>
                        <p class="text-xs text-slate-500 mt-1 group-hover:text-orange-100 transition-colors">Calificar rotaciones actuales.</p>
                    </a>
                <?php
endif; ?>
                
                <a href="reportes.php" class="group p-4 rounded-xl border border-slate-100 bg-slate-50 hover:bg-orange-600 hover:border-orange-600 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-white text-orange-600 flex items-center justify-center mb-3 shadow-sm group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 group-hover:text-white transition-colors">Generar PDF</h4>
                    <p class="text-xs text-slate-500 mt-1 group-hover:text-orange-100 transition-colors">Exportar actas finales.</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Right Column: Info / Help -->
    <div class="space-y-6">
        <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
            <i class="fa-solid fa-circle-info absolute -right-4 -bottom-4 text-8xl text-white/10 rotate-12"></i>
            <h3 class="text-lg font-bold mb-2">Información de Soporte</h3>
            <p class="text-orange-100 text-sm mb-4 leading-relaxed">
                Si encuentras dificultades al subir el archivo de Excel, asegúrate de que las columnas coincidan con el formato requerido.
            </p>
            <a href="#" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-bold transition-colors">
                Ver Guía de Uso
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
