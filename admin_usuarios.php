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

// Obtener lista
$stmt = $pdo->query("SELECT id_usuario, nombre_completo, correo, rol, fecha_creacion FROM usuarios ORDER BY nombre_completo");
$usuarios = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-8">
    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
        <i class="fa-solid fa-users-gear text-orange-500"></i> Gestión de Usuarios
    </h2>
    <p class="text-slate-500 mt-2">Crea y administra las cuentas de acceso para docentes y administradores.</p>
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

<div class="bg-white shadow rounded-lg mb-8 overflow-hidden">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 mb-10 overflow-hidden relative">
        <div class="absolute top-0 right-0 w-32 h-32 bg-orange-50 rounded-full -mr-16 -mt-16 opacity-50"></div>
        <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
            <i class="fa-solid fa-user-plus text-orange-500 text-sm"></i>
            Registrar Nuevo Usuario
        </h3>
        <form action="" method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="accion" value="crear">
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                <input type="text" name="nombre_completo" required class="mt-1 flex-1 block w-full rounded-md sm:text-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500 shadow-sm px-3 py-2 border">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input type="email" name="correo" required class="mt-1 flex-1 block w-full rounded-md sm:text-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500 shadow-sm px-3 py-2 border">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Contraseña temporal</label>
                <input type="password" name="contrasena" required class="mt-1 flex-1 block w-full rounded-md sm:text-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500 shadow-sm px-3 py-2 border">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Rol de Usuario</label>
                <select name="rol" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                    <option value="docente">Docente</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            
            <div class="md:col-span-2 pt-2">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                    <i class="fa-solid fa-plus mr-2 mt-1"></i> Guardar Usuario
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
                            <form action="" method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
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

<?php require_once 'includes/footer.php'; ?>
