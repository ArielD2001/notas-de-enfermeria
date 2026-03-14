<?php
// admin_usuarios.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Lógica para agregar o editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear') {
        $nombre = $_POST['nombre_completo'];
        $correo = $_POST['correo'];
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        $rol = $_POST['rol'];

        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, correo, contrasena, rol) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$nombre, $correo, $contrasena, $rol]);
            $mensaje = "Usuario creado exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al crear usuario. Posible correo duplicado.";
        }
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $id_eliminar = (int)$_POST['id_usuario'];
        // Evitar que el admin se elimine a sí mismo
        if ($id_eliminar !== $_SESSION['id_usuario']) {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id_eliminar]);
            $mensaje = "Usuario eliminado.";
        } else {
            $error = "No puedes eliminar tu propia cuenta actual.";
        }
    }
}

// Parámetros de búsqueda y paginación
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta con filtros
$where = '';
$params = [];
if (!empty($busqueda)) {
    $where = "WHERE nombre_completo LIKE ? OR correo LIKE ?";
    $params = ["%$busqueda%", "%$busqueda%"];
}

// Contar total de registros
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM usuarios $where");
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// Obtener lista con paginación
$query = "SELECT id_usuario, nombre_completo, correo, rol, fecha_creacion FROM usuarios $where ORDER BY nombre_completo LIMIT $por_pagina OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
            <i class="fa-solid fa-users-gear text-orange-500"></i> Gestión de Usuarios
        </h2>
        <p class="text-slate-500 mt-2">Crea y administra las cuentas de acceso para docentes y administradores.</p>
    </div>
    <button onclick="document.getElementById('modalCrearUsuario').classList.remove('hidden')" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-orange-600/20 transition-all flex items-center gap-2">
        <i class="fa-solid fa-plus text-sm"></i>
        Nuevo Usuario
    </button>
</div>

<!-- Barra de búsqueda compacta -->
<div class="flex items-center justify-between gap-4 mb-6">
    <form method="GET" class="flex items-center gap-3 flex-1 max-w-lg">
        <div class="relative flex-1">
            <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar usuarios..." class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm">
            <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
        </div>
        <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-xl shadow-sm hover:shadow-md transition-all">
            <i class="fa-solid fa-search mr-1"></i> Buscar
        </button>
        <?php if (!empty($busqueda)): ?>
            <a href="?<?php echo http_build_query(array_diff_key($_GET, ['busqueda' => ''])); ?>" class="px-3 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">
                <i class="fa-solid fa-times"></i>
            </a>
        <?php endif; ?>
    </form>
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

<!-- Modal para crear usuario -->
<div id="modalCrearUsuario" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[200] flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
        <!-- Header Fijo -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-white flex-shrink-0">
            <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-orange-500"></i> Registrar Nuevo Usuario
            </h3>
            <button type="button" onclick="document.getElementById('modalCrearUsuario').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Formulario con Body Scrollable -->
        <form action="" method="POST" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="accion" value="crear">
            
            <!-- Cuerpo del Formulario (Scrollable) -->
            <div class="p-8 space-y-6 overflow-y-auto flex-1 custom-scrollbar">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Nombre Completo</label>
                        <input type="text" name="nombre_completo" required placeholder="Ej: Juan Pérez García" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Correo Electrónico</label>
                        <input type="email" name="correo" required placeholder="Ej: juan.perez@universidad.edu" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Contraseña Temporal</label>
                        <input type="password" name="contrasena" required placeholder="Contraseña segura" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Rol de Usuario</label>
                        <select name="rol" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm font-medium">
                            <option value="">-- Seleccione --</option>
                            <option value="docente">Docente</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Footer Fijo con Botones -->
            <div class="p-6 border-t border-slate-100 bg-slate-50 flex gap-4 flex-shrink-0">
                <button type="button" onclick="document.getElementById('modalCrearUsuario').classList.add('hidden')" class="flex-1 px-6 py-4 border border-slate-200 text-slate-500 font-bold rounded-2xl hover:bg-slate-200 transition-all">
                    Cancelar
                </button>
                <button type="submit" class="flex-[2] bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 px-6 rounded-2xl shadow-lg shadow-orange-600/20 transition-all">
                    <i class="fa-solid fa-plus mr-2"></i> Guardar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Usuarios Lista -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 border-l-4 border-orange-500 pl-3">Usuarios Registrados</h3>
        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
            Total: <?php echo count($usuarios); ?>
        </span>
    </div>
    <div class="bg-white shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Nombre Completo</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Correo Electrónico</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Rol</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-widest r">Fecha de Creación</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-widest">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($usuarios as $u): ?>
                    <tr class="hover:bg-orange-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($u['nombre_completo']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($u['correo']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($u['rol'] === 'admin'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Admin</span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Docente</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y', strtotime($u['fecha_creacion'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <form action="" method="POST" class="inline" onsubmit="showConfirmModal(this, event, '¿Seguro que deseas eliminar este usuario? Esta acción no se puede deshacer.')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                                <button type="submit" 
                                    class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-full transition-colors <?php echo ($u['id_usuario'] === $_SESSION['id_usuario']) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                    <?php echo ($u['id_usuario'] === $_SESSION['id_usuario']) ? 'disabled title="Tu cuenta actual"' : 'title="Eliminar usuario"'; ?>>
                                    <i class="fa-solid fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No hay usuarios registrados.</td>
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
        Mostrando <?php echo ($offset + 1) . ' - ' . min($offset + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> usuarios
    </div>
    <div class="flex items-center space-x-2">
        <?php
        $query_params = $_GET;
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

<!-- Modal de Confirmación -->
<div id="modalConfirmacion" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[300] flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-triangle-exclamation text-orange-500 text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Confirmar Acción</h3>
                <p id="confirmMessage" class="text-slate-600 mb-6">¿Estás seguro?</p>
                <div class="flex gap-3">
                    <button onclick="document.getElementById('modalConfirmacion').classList.add('hidden')" class="flex-1 px-4 py-3 border border-slate-200 text-slate-500 font-bold rounded-2xl hover:bg-slate-50 transition-all">
                        Cancelar
                    </button>
                    <button onclick="executeConfirm(); document.getElementById('modalConfirmacion').classList.add('hidden')" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-2xl shadow-lg shadow-red-600/20 transition-all">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Modal de confirmación
let confirmForm = null;
function showConfirmModal(form, event, message) {
    event.preventDefault();
    confirmForm = form;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('modalConfirmacion').classList.remove('hidden');
}

function executeConfirm() {
    if (confirmForm) {
        confirmForm.submit();
    }
}

// Cerrar modal de confirmación al hacer clic fuera
document.getElementById('modalConfirmacion').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
