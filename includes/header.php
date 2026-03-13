<?php
// includes/header.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegurarse de que BASE_URL esté definido
if (!defined('BASE_URL')) {
    define('BASE_URL', '/codigos/notas_enfermeria/');
}

// Función para obtener iniciales
function getInitials($name)
{
    if (empty($name))
        return '??';
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= strtoupper(substr($w, 0, 1));
        }
    }
    return substr($initials, 0, 2);
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas Enfermería - Gestión de Notas</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/image.png">
    <!-- Tailwind CSS via CDN for consistency -->
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
    <!-- Custom output.css for specific animations/loader if available -->
    <link href="<?php echo BASE_URL; ?>assets/css/output.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Loader specific inline CSS */
        #loader-wrapper {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            transition: opacity 0.5s;
        }
        #loader-wrapper.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>
</head>
<body class="h-full">

    <!-- Minimalist Loader -->
    <div id="loader-wrapper">
        <div class="flex flex-col items-center">
            <div class="w-12 h-12 border-4 border-orange-100 border-t-orange-600 rounded-full animate-spin"></div>
            <p class="mt-4 text-orange-600 font-medium animate-pulse">Cargando Notas Enfermería...</p>
        </div>
    </div>

    <?php if (isset($_SESSION['id_usuario'])): ?>
    <div class="flex flex-col md:flex-row md:h-screen md:overflow-hidden">
        
        <!-- Sidebar - Left Navigation -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex-shrink-0 transition-transform duration-300 -translate-x-full md:translate-x-0 md:static md:flex flex-col shadow-sm h-full">
            <div class="h-20 flex items-center px-6 border-b border-slate-100 flex-shrink-0 bg-white">
                <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center gap-2">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo_v2.png" alt="Logo" class="h-10 w-auto object-contain">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-slate-800 leading-none tracking-tighter uppercase">Notas</span>
                        <span class="text-[9px] font-bold text-orange-600 uppercase tracking-widest mt-0.5">Enfermería</span>
                    </div>
                </a>
                <!-- Close Button for Mobile -->
                <button id="close-sidebar" class="md:hidden ml-auto p-2 text-slate-400 hover:text-orange-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <nav class="flex-grow py-6 px-4 space-y-2 overflow-y-auto custom-scrollbar">
                <p class="text-[10px] uppercase font-bold text-slate-400 px-3 py-2 tracking-widest">Menú Principal</p>
                
                <a href="<?php echo BASE_URL; ?>index.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $current_page == 'index.php' ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600'; ?>">
                    <i class="fa-solid fa-house w-5"></i>
                    Dashboard
                </a>

                <?php if ($_SESSION['rol'] === 'admin'): ?>
                    <p class="text-[10px] uppercase font-bold text-slate-400 px-3 py-2 pt-4 tracking-widest">Administración</p>
                    <a href="<?php echo BASE_URL; ?>admin_usuarios.php" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $current_page == 'admin_usuarios.php' ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600'; ?>">
                        <i class="fa-solid fa-users-gear w-5"></i>
                        Usuarios
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin_modulos.php" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $current_page == 'admin_modulos.php' ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600'; ?>">
                        <i class="fa-solid fa-cubes w-5"></i>
                        Módulos
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin_listas.php" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $current_page == 'admin_listas.php' ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600'; ?>">
                        <i class="fa-solid fa-file-lines w-5"></i>
                        Listas de Practicantes
                    </a>
                <?php
    else: ?>
                    <p class="text-[10px] uppercase font-bold text-slate-400 px-3 py-2 pt-4 tracking-widest">Docencia</p>
                    <a href="<?php echo BASE_URL; ?>docente_calificar.php" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $current_page == 'docente_calificar.php' ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600'; ?>">
                        <i class="fa-solid fa-star w-5"></i>
                        Calificar
                    </a>
                <?php
    endif; ?>

                <p class="text-[10px] uppercase font-bold text-slate-400 px-3 py-2 pt-4 tracking-widest">Reportes</p>
                <a href="<?php echo BASE_URL; ?>reportes.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $current_page == 'reportes.php' ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600'; ?>">
                    <i class="fa-solid fa-file-invoice w-5"></i>
                    Exportar Datos
                </a>
            </nav>

            <div class="p-4 border-t border-slate-100 mb-2">
                <a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 transition-colors">
                    <i class="fa-solid fa-right-from-bracket w-5"></i>
                    Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-grow flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-8 flex-shrink-0">
                <button id="toggle-sidebar" class="md:hidden p-2 text-slate-400 hover:text-orange-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                
                <div class="flex items-center gap-1">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider hidden md:inline"><?php echo $_SESSION['rol'] === 'admin' ? 'Administración' : 'Panel Docente'; ?> / </span>
                    <span class="text-xs font-bold text-orange-600 uppercase tracking-wider">Dashboard</span>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-900 leading-tight"><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></p>
                        <p class="text-[10px] text-slate-400 uppercase font-bold tracking-tighter"><?php echo htmlspecialchars($_SESSION['rol'] == 'admin' ? 'Administrador' : 'Docente CURN'); ?></p>
                    </div>
                    <!-- Initials circle -->
                    <div class="w-10 h-10 rounded-full bg-orange-600 flex items-center justify-center text-white font-bold text-sm shadow-sm ring-2 ring-orange-100">
                        <?php echo getInitials($_SESSION['nombre_completo']); ?>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-grow p-4 md:p-8 overflow-y-auto bg-slate-50 fade-in custom-scrollbar relative">
    <?php
else: ?>
        <main class="min-h-screen">
    <?php
endif; ?>
