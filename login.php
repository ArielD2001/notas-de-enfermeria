<?php
// login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

// Asegurarse de que BASE_URL esté definido
if (!defined('BASE_URL')) {
    define('BASE_URL', '/codigos/notas_enfermeria/');
}

// Si ya está logueado, redirigir
if (isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($correo) || empty($contrasena)) {
        $error = 'Por favor, ingrese correo y contraseña.';
    } else {
        $stmt = $pdo->prepare("SELECT id_usuario, nombre_completo, contrasena, rol FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
            $_SESSION['rol'] = $usuario['rol'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas Enfermería - Iniciar Sesión</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            light: '#fed7aa',
                            DEFAULT: '#f97316',
                            dark: '#c2410c',
                        }
                    }
                }
            }
        }
    </script>
    <link href="<?php echo BASE_URL; ?>assets/css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-card {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-50">

    <!-- Loader -->
    <div id="loader-wrapper" class="fixed inset-0 z-50 flex items-center justify-center bg-white transition-opacity duration-500">
        <div class="flex flex-col items-center">
            <div class="w-12 h-12 border-4 border-orange-200 border-t-orange-600 rounded-full animate-spin"></div>
            <p class="mt-4 text-orange-600 font-medium animate-pulse">Notas Enfermería</p>
        </div>
    </div>

    <div class="max-w-md w-full login-card bg-white rounded-3xl overflow-hidden border border-slate-100 shadow-2xl shadow-orange-600/5 fade-in">
        <!-- Header with Gradient - More compact vertically -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 py-6 px-8 text-center text-white relative">
            <div class="mb-3 inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white p-2 shadow-inner">
                <img src="<?php echo BASE_URL; ?>assets/images/logo_v2.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-black tracking-tight mb-0.5 uppercase leading-none">Notas</h1>
            <p class="text-orange-100 text-[10px] font-bold tracking-[0.2em] uppercase opacity-90">Enfermería</p>
            
            <!-- Abstract background shapes -->
            <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute bottom-0 left-0 -mb-2 -ml-2 w-16 h-16 bg-black/10 rounded-full blur-xl"></div>
        </div>
        
        <div class="p-8">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 mb-6 rounded-xl flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-xs"></i>
                    <p class="text-xs font-bold"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                <div>
                    <label for="correo" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Correo Electrónico</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-300 group-focus-within:text-orange-500 transition-colors">
                            <i class="fa-regular fa-envelope text-sm"></i>
                        </div>
                        <input type="email" name="correo" id="correo" value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" required
                            class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all placeholder:text-slate-300 text-sm font-medium" 
                            placeholder="email@ejemplo.com">
                    </div>
                </div>

                <div>
                    <label for="contrasena" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Contraseña</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-300 group-focus-within:text-orange-500 transition-colors">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input type="password" name="contrasena" id="contrasena" required
                            class="block w-full pl-11 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all placeholder:text-slate-300 text-sm font-medium" 
                            placeholder="********">
                        <!-- Toggle Password Visibility -->
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-300 hover:text-orange-500 transition-colors">
                            <i id="eye-icon" class="fa-solid fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center items-center gap-2 py-3.5 px-6 bg-orange-600 hover:bg-orange-700 text-white rounded-2xl font-extrabold shadow-lg shadow-orange-600/20 transition-all active:scale-[0.98]">
                        Ingresar al Sistema
                        <i class="fa-solid fa-arrow-right-to-bracket text-xs opacity-70"></i>
                    </button>
                </div>
            </form>
            
            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] mb-1">Ariel Caraballo Diaz - Jesus Valencia Torres</p>
                <p class="text-[9px] font-bold text-slate-300 uppercase letter tracking-tighter">©<?php echo date('Y'); ?> CURN - Proyecto de grado</p>
            </div>
        </div>
    </div>

    <script>
        function hideLoader() {
            const loader = document.getElementById('loader-wrapper');
            if (loader) {
                loader.style.opacity = '0';
                loader.style.pointerEvents = 'none';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        }
        window.addEventListener('load', hideLoader);
        setTimeout(hideLoader, 3000);

        function togglePassword() {
            const input = document.getElementById('contrasena');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>
