    </main>
<?php if (isset($_SESSION['id_usuario'])): ?>
        </div> <!-- .flex-grow (Content Area) -->
    </div> <!-- .min-h-full (Main Layout) -->
<?php endif; ?>

    <!-- Loader Script -->
    <script>
        function hideLoader() {
            const loader = document.getElementById('loader-wrapper');
            if (loader) {
                // Manual fallback if CSS doesn't load/work
                loader.style.opacity = '0';
                loader.style.pointerEvents = 'none';
                loader.classList.add('fade-out');
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        }

        // Hide on window load (all assets)
        window.addEventListener('load', hideLoader);

        // Fallback: Hide after 3 seconds anyway if window load hasn't fired
        setTimeout(hideLoader, 3000);

        // Toggle Sidebar on mobile
        const toggleBtn = document.getElementById('toggle-sidebar');
        const closeBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black/50 z-30 hidden transition-opacity duration-300 opacity-0';
        document.body.appendChild(overlay);

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.add('opacity-100'), 10);
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.remove('opacity-100');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
            document.body.style.overflow = '';
        }

        if(toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', openSidebar);
        }

        if(closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        overlay.addEventListener('click', closeSidebar);

        // Modal de Guía de Uso de Reportes
        function openGuideModal() {
            const modal = document.getElementById('guide-modal');
            const overlay = document.getElementById('guide-modal-overlay');
            modal.classList.remove('hidden');
            overlay.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0', 'scale-95');
                modal.classList.add('opacity-100', 'scale-100');
                overlay.classList.remove('opacity-0');
                overlay.classList.add('opacity-100');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeGuideModal() {
            const modal = document.getElementById('guide-modal');
            const overlay = document.getElementById('guide-modal-overlay');
            modal.classList.remove('opacity-100', 'scale-100');
            modal.classList.add('opacity-0', 'scale-95');
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                overlay.classList.add('hidden');
            }, 300);
            document.body.style.overflow = 'auto';
        }

        // Modal de Guía de Uso del Index
        function openIndexGuideModal() {
            const modal = document.getElementById('index-guide-modal');
            const overlay = document.getElementById('index-guide-modal-overlay');
            modal.classList.remove('hidden');
            overlay.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0', 'scale-95');
                modal.classList.add('opacity-100', 'scale-100');
                overlay.classList.remove('opacity-0');
                overlay.classList.add('opacity-100');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeIndexGuideModal() {
            const modal = document.getElementById('index-guide-modal');
            const overlay = document.getElementById('index-guide-modal-overlay');
            modal.classList.remove('opacity-100', 'scale-100');
            modal.classList.add('opacity-0', 'scale-95');
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                overlay.classList.add('hidden');
            }, 300);
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeGuideModal();
                closeIndexGuideModal();
            }
        });
    </script>

    <!-- Modal de Guía de Uso -->
    <div id="guide-modal-overlay" class="fixed inset-0 bg-black/60 z-50 hidden opacity-0 transition-opacity duration-300"></div>

    <div id="guide-modal" class="fixed inset-0 z-50 hidden opacity-0 scale-95 transition-all duration-300 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header del Modal -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-book-open text-white text-xl"></i>
                    <h2 class="text-xl font-bold text-white">Guía de Uso - Generación de Reportes</h2>
                </div>
                <button onclick="closeGuideModal()" class="text-white hover:bg-white/20 rounded-full p-2 transition-colors">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="overflow-y-auto max-h-[calc(90vh-80px)]">
                <div class="p-6 space-y-8">

                    <!-- Introducción -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
                            <i class="fa-solid fa-file-contract text-orange-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">Bienvenido a la Herramienta de Reportes</h3>
                        <p class="text-slate-600 max-w-2xl mx-auto">
                            Esta herramienta te permite generar reportes detallados de calificaciones en formato Excel y PDF.
                            Aprende a usarla de manera efectiva con esta guía paso a paso.
                        </p>
                    </div>

                    <!-- Tipos de Reportes -->
                    <div class="bg-slate-50 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-chart-bar text-orange-500"></i>
                            Tipos de Reportes Disponibles
                        </h4>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="bg-white p-4 rounded-lg border border-slate-200">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-list text-blue-600"></i>
                                    </div>
                                    <h5 class="font-semibold text-slate-900">Reporte General</h5>
                                </div>
                                <p class="text-sm text-slate-600">
                                    Reportes consolidados filtrados por período académico y módulo.
                                    Ideal para vistas generales y análisis comparativos.
                                </p>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-slate-200">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-users text-green-600"></i>
                                    </div>
                                    <h5 class="font-semibold text-slate-900">Reporte Específico</h5>
                                </div>
                                <p class="text-sm text-slate-600">
                                    Reportes detallados de una lista específica con información completa
                                    de estudiantes, calificaciones y observaciones.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Cómo Generar Reportes -->
                    <div class="bg-white border border-slate-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-play-circle text-green-500"></i>
                            Cómo Generar un Reporte
                        </h4>
                        <div class="space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <span class="text-orange-600 font-bold text-sm">1</span>
                                </div>
                                <div>
                                    <h5 class="font-semibold text-slate-900">Elige el Tipo de Reporte</h5>
                                    <p class="text-slate-600 text-sm mt-1">
                                        Decide si quieres un reporte general (usando filtros) o específico (seleccionando una lista).
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <span class="text-orange-600 font-bold text-sm">2</span>
                                </div>
                                <div>
                                    <h5 class="font-semibold text-slate-900">Configura los Filtros</h5>
                                    <p class="text-slate-600 text-sm mt-1">
                                        <strong>Reporte General:</strong> Selecciona período y módulo deseados.<br>
                                        <strong>Reporte Específico:</strong> Elige una lista del dropdown (tiene prioridad sobre otros filtros).
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <span class="text-orange-600 font-bold text-sm">3</span>
                                </div>
                                <div>
                                    <h5 class="font-semibold text-slate-900">Selecciona el Formato</h5>
                                    <p class="text-slate-600 text-sm mt-1">
                                        <strong>Excel:</strong> Para análisis y edición de datos.<br>
                                        <strong>PDF:</strong> Para presentaciones y archivos finales.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <span class="text-orange-600 font-bold text-sm">4</span>
                                </div>
                                <div>
                                    <h5 class="font-semibold text-slate-900">Descarga tu Reporte</h5>
                                    <p class="text-slate-600 text-sm mt-1">
                                        Haz clic en el botón correspondiente y el archivo se descargará automáticamente.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información sobre Filtros -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-blue-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-info-circle text-blue-500"></i>
                            Información Importante sobre Filtros
                        </h4>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-lightbulb text-blue-500 mt-1"></i>
                                <div>
                                    <p class="text-blue-800 font-medium">Prioridad de Filtros</p>
                                    <p class="text-blue-700 text-sm">Si seleccionas una lista específica, los filtros de período y módulo se ignorarán automáticamente.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-magic text-blue-500 mt-1"></i>
                                <div>
                                    <p class="text-blue-800 font-medium">Preselección Automática</p>
                                    <p class="text-blue-700 text-sm">Cuando accedes desde la vista de detalles de una lista, esta se selecciona automáticamente.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-users-gear text-blue-500 mt-1"></i>
                                <div>
                                    <p class="text-blue-800 font-medium">Permisos por Rol</p>
                                    <p class="text-blue-700 text-sm">Solo verás las listas y módulos que tienes asignados según tu rol de docente.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido de los Reportes -->
                    <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-green-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-file-lines text-green-500"></i>
                            ¿Qué Incluyen los Reportes?
                        </h4>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h5 class="font-semibold text-green-800 mb-2">Reportes Excel</h5>
                                <ul class="text-sm text-green-700 space-y-1">
                                    <li>• Identificación del estudiante</li>
                                    <li>• Nombre completo</li>
                                    <li>• Información de la lista (si aplica)</li>
                                    <li>• Módulo y período</li>
                                    <li>• Notas de rotación individuales</li>
                                    <li>• Nota final</li>
                                    <li>• Observaciones</li>
                                </ul>
                            </div>
                            <div>
                                <h5 class="font-semibold text-green-800 mb-2">Reportes PDF</h5>
                                <ul class="text-sm text-green-700 space-y-1">
                                    <li>• Título descriptivo</li>
                                    <li>• Información contextual arriba</li>
                                    <li>• Tabla organizada con estudiantes</li>
                                    <li>• Columnas de rotación dinámicas</li>
                                    <li>• Indicador "sin calificar" cuando aplica</li>
                                    <li>• Fecha de emisión</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Consejos y Mejores Prácticas -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-amber-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-lightbulb text-amber-500"></i>
                            Consejos y Mejores Prácticas
                        </h4>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-check-circle text-amber-500 mt-1"></i>
                                    <p class="text-amber-800 text-sm">Usa reportes específicos cuando necesites detalles de una lista particular.</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-check-circle text-amber-500 mt-1"></i>
                                    <p class="text-amber-800 text-sm">Los reportes Excel son ideales para análisis adicionales y modificaciones.</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-check-circle text-amber-500 mt-1"></i>
                                    <p class="text-amber-800 text-sm">Los reportes PDF son perfectos para presentaciones y entregas finales.</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-check-circle text-amber-500 mt-1"></i>
                                    <p class="text-amber-800 text-sm">Revisa las calificaciones antes de generar reportes importantes.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="bg-slate-50 px-6 py-4 flex justify-end">
                <button onclick="closeGuideModal()" class="px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Guía de Uso del Index -->
    <div id="index-guide-modal-overlay" class="fixed inset-0 bg-black/60 z-50 hidden opacity-0 transition-opacity duration-300"></div>

    <div id="index-guide-modal" class="fixed inset-0 z-50 hidden opacity-0 scale-95 transition-all duration-300 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header del Modal -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-house text-white text-xl"></i>
                    <h2 class="text-xl font-bold text-white">Guía de Uso - Página Principal</h2>
                </div>
                <button onclick="closeIndexGuideModal()" class="text-white hover:bg-white/20 rounded-full p-2 transition-colors">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="overflow-y-auto max-h-[calc(90vh-80px)]">
                <div class="p-6 space-y-8">

                    <!-- Introducción -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
                            <i class="fa-solid fa-house text-orange-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">Bienvenido al Sistema de Notas</h3>
                        <p class="text-slate-600 max-w-2xl mx-auto">
                            Esta es tu página principal donde encontrarás todas las herramientas necesarias para gestionar
                            las calificaciones de enfermería. Conoce cada sección y cómo aprovechar al máximo el sistema.
                        </p>
                    </div>

                    <!-- Panel de Estadísticas -->
                    <div class="bg-slate-50 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-chart-line text-orange-500"></i>
                            Panel de Estadísticas
                        </h4>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="bg-white p-4 rounded-lg border border-slate-200">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-users text-blue-600"></i>
                                    </div>
                                    <h5 class="font-semibold text-slate-900">Estudiantes Activos</h5>
                                </div>
                                <p class="text-sm text-slate-600">
                                    Muestra el número total de estudiantes activos en el sistema.
                                    Para docentes, solo incluye estudiantes asignados a sus listas.
                                </p>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-slate-200">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-book text-green-600"></i>
                                    </div>
                                    <h5 class="font-semibold text-slate-900">Módulos Disponibles</h5>
                                </div>
                                <p class="text-sm text-slate-600">
                                    Indica la cantidad de módulos de rotación configurados.
                                    Los docentes ven solo los módulos donde tienen estudiantes asignados.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Secciones Disponibles -->
                    <div class="bg-white border border-slate-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-th-large text-green-500"></i>
                            Secciones del Sistema
                        </h4>

                        <!-- Para Administradores -->
                        <div class="mb-6">
                            <h5 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-user-shield text-red-500"></i>
                                Funciones de Administrador
                            </h5>
                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fa-solid fa-file-excel text-red-600"></i>
                                        <span class="font-medium text-red-800">Subir Excel</span>
                                    </div>
                                    <p class="text-sm text-red-700">Importa listas completas de estudiantes desde archivos Excel con formato específico.</p>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fa-solid fa-list text-red-600"></i>
                                        <span class="font-medium text-red-800">Listas de Practicantes</span>
                                    </div>
                                    <p class="text-sm text-red-700">Gestiona y administra todas las listas de estudiantes del sistema.</p>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fa-solid fa-user-plus text-red-600"></i>
                                        <span class="font-medium text-red-800">Nuevo Docente</span>
                                    </div>
                                    <p class="text-sm text-red-700">Crea cuentas de acceso para nuevos profesores en el sistema.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Para Docentes -->
                        <div class="mb-6">
                            <h5 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-chalkboard-teacher text-blue-500"></i>
                                Funciones de Docente
                            </h5>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fa-solid fa-pen-to-square text-blue-600"></i>
                                        <span class="font-medium text-blue-800">Registrar Notas</span>
                                    </div>
                                    <p class="text-sm text-blue-700">Accede a tus listas asignadas para calificar las rotaciones de tus estudiantes.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Común para todos -->
                        <div>
                            <h5 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf text-orange-500"></i>
                                Función Común
                            </h5>
                            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fa-solid fa-file-contract text-orange-600"></i>
                                    <span class="font-medium text-orange-800">Generar Reportes</span>
                                </div>
                                <p class="text-sm text-orange-700">Crea reportes detallados en formato Excel y PDF con filtros avanzados por período, módulo o lista específica.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Consejos de Uso -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-amber-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-lightbulb text-amber-500"></i>
                            Consejos para un Mejor Uso
                        </h4>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-clock text-amber-500 mt-1"></i>
                                    <p class="text-amber-800 text-sm">Revisa regularmente tus estadísticas para mantenerte al día con tus responsabilidades.</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-save text-amber-500 mt-1"></i>
                                    <p class="text-amber-800 text-sm">Utiliza la función de reportes para generar respaldos de tus calificaciones.</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-users text-amber-500 mt-1"></i>
                                    <p class="text-amber-800 text-sm">Como docente, enfócate en tus listas asignadas para una gestión más eficiente.</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-shield text-amber-500 mt-1"></i>
                                    <p class="text-amber-800 text-sm">Los administradores tienen acceso completo para gestionar todo el sistema.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Soporte -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-blue-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-headset text-blue-500"></i>
                            ¿Necesitas Ayuda?
                        </h4>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-file-excel text-blue-500 mt-1"></i>
                                <div>
                                    <p class="text-blue-800 font-medium">Problemas con Excel</p>
                                    <p class="text-blue-700 text-sm">Asegúrate de que las columnas del archivo Excel coincidan exactamente con el formato requerido por el sistema.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-question-circle text-blue-500 mt-1"></i>
                                <div>
                                    <p class="text-blue-800 font-medium">Más Guías</p>
                                    <p class="text-blue-700 text-sm">Cada sección del sistema tiene su propia guía de uso detallada. Explora las diferentes páginas para encontrar ayuda específica.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="bg-slate-50 px-6 py-4 flex justify-end">
                <button onclick="closeIndexGuideModal()" class="px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors">
                    Entendido
                </button>
            </div>
        </div>
    </div>

</body>
</html>
