<?php
// admin_modulos.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Lógica para agregar o editar módulo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear') {
        $nombre = $_POST['nombre_modulo'];
        $descripcion = $_POST['descripcion'];
        $creditos = (int)$_POST['creditos'];

        $stmt = $pdo->prepare("INSERT INTO modulos_rotacion (nombre_modulo, descripcion, creditos) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$nombre, $descripcion, $creditos]);
            $mensaje = "Módulo creado exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al crear el módulo.";
        }
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $id_eliminar = (int)$_POST['id_modulo'];
        try {
            $stmt = $pdo->prepare("DELETE FROM modulos_rotacion WHERE id_modulo = ?");
            $stmt->execute([$id_eliminar]);
            $mensaje = "Módulo eliminado.";
        } catch (PDOException $e) {
            $error = "No puedes eliminar este módulo porque ya existen calificaciones o asignaciones vinculadas a él.";
        }
    }
}

// Obtener lista
$stmt = $pdo->query("SELECT id_modulo, nombre_modulo, descripcion, creditos FROM modulos_rotacion ORDER BY id_modulo");
$modulos = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-8">
    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
        <i class="fa-solid fa-cubes text-orange-500"></i> Módulos de Rotación
    </h2>
    <p class="text-slate-500 mt-2">Define y configura los períodos de práctica clínica.</p>
</div>

<?php if (isset($mensaje)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
        <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 mb-10 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-32 h-32 bg-orange-50 rounded-full -mr-16 -mt-16 opacity-50"></div>
    <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-plus-circle text-orange-500 text-sm"></i>
        Crear Nuevo Módulo
    </h3>
    <form action="" method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="accion" value="crear">
        
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Nombre del Módulo</label>
            <input type="text" name="nombre_modulo" required class="mt-1 flex-1 block w-full rounded-md sm:text-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500 shadow-sm px-3 py-2 border">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Descripción</label>
            <textarea name="descripcion" rows="2" class="mt-1 flex-1 block w-full rounded-md sm:text-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500 shadow-sm px-3 py-2 border"></textarea>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Créditos / Peso</label>
            <input type="number" name="creditos" min="1" value="2" required class="mt-1 flex-1 block w-full rounded-md sm:text-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500 shadow-sm px-3 py-2 border">
        </div>
        
        <div class="md:col-span-2 pt-2">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                <i class="fa-solid fa-plus mr-2 mt-1"></i> Guardar Módulo
            </button>
        </div>
    </form>
</div>

<!-- Tabla de Módulos Lista -->
<div class="bg-white shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-list-check text-orange-500"></i> Rotaciones Registradas
        </h3>
        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
            Total: <?php echo count($modulos); ?>
        </span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest w-16">ID</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Nombre del Módulo</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Descripción</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest w-32">Créditos</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest w-24">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($modulos as $m): ?>
                    <tr class="hover:bg-orange-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #<?php echo $m['id_modulo']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($m['nombre_modulo']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500 truncate max-w-xs" title="<?php echo htmlspecialchars($m['descripcion']); ?>">
                                <?php echo htmlspecialchars($m['descripcion']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?php echo $m['creditos']; ?> CR
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <form action="" method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar este módulo?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_modulo" value="<?php echo $m['id_modulo']; ?>">
                                <button type="submit" title="Eliminar módulo" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-full transition-colors">
                                    <i class="fa-solid fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($modulos)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No hay módulos registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
