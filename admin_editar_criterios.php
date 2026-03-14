<?php
// admin_editar_criterios.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$id_modulo = isset($_GET['id_modulo']) ? (int)$_GET['id_modulo'] : 0;
if (!$id_modulo) {
    header("Location: admin_modulos.php");
    exit;
}

// Obtener info del módulo
$stmt_modulo = $pdo->prepare("SELECT nombre_modulo FROM modulos_rotacion WHERE id_modulo = ?");
$stmt_modulo->execute([$id_modulo]);
$modulo = $stmt_modulo->fetch();
if (!$modulo) {
    header("Location: admin_modulos.php");
    exit;
}

// Procesar guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criterios'])) {
    $criterios = json_decode($_POST['criterios'], true);
    if ($criterios !== null) {
        $json = json_encode($criterios, JSON_UNESCAPED_UNICODE);
        // Inserta o actualiza el registro de criterios para el módulo (evita errores de clave única)
        $stmt = $pdo->prepare(
            "INSERT INTO criterios_formularios (id_modulo, criterios_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE criterios_json = VALUES(criterios_json)"
        );
        $stmt->execute([$id_modulo, $json]);
        $mensaje = "Criterios actualizados exitosamente.";
    } else {
        $error = "Error en el formato de los criterios.";
    }
}

// Cargar criterios actuales
$stmt_criterios = $pdo->prepare("SELECT criterios_json FROM criterios_formularios WHERE id_modulo = ?");
$stmt_criterios->execute([$id_modulo]);
$criterios_row = $stmt_criterios->fetch();
$criterios = $criterios_row ? json_decode($criterios_row['criterios_json'], true) : [];

require_once 'includes/header.php';
?>

<div class="max-w-6xl mx-auto w-full px-2 sm:px-6 lg:px-8">
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-2 text-slate-400 text-sm">
                <a href="admin_modulos.php" class="hover:text-orange-600 transition-colors">Módulos</a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span><?php echo htmlspecialchars($modulo['nombre_modulo']); ?></span>
            </div>
            <a href="admin_modulos.php" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                <i class="fa-solid fa-arrow-left mr-1"></i> Volver a módulos
            </a>
        </div>

        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3 mt-6">
            <i class="fa-solid fa-edit text-orange-500"></i> Editar Criterios de Calificación
        </h2>
        <p class="text-slate-500 mt-2">Configura los criterios de evaluación para el módulo: <strong><?php echo htmlspecialchars($modulo['nombre_modulo']); ?></strong></p>
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

<form id="criteriosForm" action="" method="POST">
    <input type="hidden" name="criterios" id="criteriosInput">
    
    <div class="bg-white shadow-sm border border-slate-100 rounded-2xl p-4 sm:p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div class="w-full md:w-auto">
                    <h3 class="text-lg font-bold text-slate-900">Criterios de Evaluación</h3>
                    <p class="text-sm text-slate-500 mt-1">Organiza el orden de las secciones y edita los criterios de manera clara.</p>
                </div>
                <button type="button" id="addSeccion" class="w-full sm:w-auto bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    <span class="hidden sm:inline">Añadir Sección</span>
                    <span class="sm:hidden">Añadir</span>
                </button>
            </div>

            <div id="seccionesContainer">
                <?php if (empty($criterios)): ?>
                    <div class="text-center py-8 text-slate-500">
                        <i class="fa-solid fa-file-circle-plus text-4xl mb-4"></i>
                        <p>No hay criterios definidos. Haz clic en "Añadir Sección" para comenzar.</p>
                    </div>
                <?php else: ?>
                <?php $sec_idx = 0; foreach ($criterios as $titulo => $info): $sec_idx++; ?>
                    <div class="seccion border border-slate-200 rounded-lg p-4 sm:p-6 mb-4 bg-slate-50">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4 gap-4">
                            <div class="flex-1 mr-4">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Título de la Sección</label>
                                <input type="text" class="seccion-titulo w-full px-3 py-2 border border-slate-300 rounded-md focus:ring-orange-500 focus:border-orange-500" value="<?php echo htmlspecialchars($titulo); ?>">
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-slate-700">Peso (%)</label>
                                <input type="number" class="seccion-peso w-20 px-2 py-2 border border-slate-300 rounded-md focus:ring-orange-500 focus:border-orange-500" value="<?php echo $info['weight'] * 100; ?>" min="0" max="100" step="0.1">
                                <button type="button" class="move-up text-blue-500 hover:text-blue-700 p-2" title="Mover arriba">
                                    <i class="fa-solid fa-arrow-up"></i>
                                </button>
                                <button type="button" class="move-down text-blue-500 hover:text-blue-700 p-2" title="Mover abajo">
                                    <i class="fa-solid fa-arrow-down"></i>
                                </button>
                                <button type="button" class="remove-seccion text-red-500 hover:text-red-700 p-2">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="items-container">
                            <?php if (isset($info['items'])): ?>
                                <?php foreach ($info['items'] as $item): ?>
                                    <div class="item flex flex-row items-start gap-2 mb-2 p-2 bg-white border border-slate-200 rounded-lg shadow-sm">
                                        <textarea class="item-texto flex-1 min-w-0 px-3 py-2 border border-dashed border-slate-300 rounded-md focus:ring-orange-400 focus:border-orange-400 resize-none" rows="2" style="min-height: 2.5rem;"><?php echo htmlspecialchars($item); ?></textarea>
                                        <button type="button" class="remove-item text-red-500 hover:text-red-700 p-2 flex-shrink-0 mt-1">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="add-item bg-slate-600 hover:bg-slate-700 text-white px-3 py-1 rounded text-sm mt-2">
                            <i class="fa-solid fa-plus mr-1"></i> Añadir Criterio
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex justify-between">
        <a href="admin_modulos.php" class="bg-slate-600 hover:bg-slate-700 text-white px-6 py-3 rounded-lg font-medium flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-medium flex items-center gap-2">
            <i class="fa-solid fa-save"></i> Guardar Cambios
        </button>
    </div>
</form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('seccionesContainer');
    const addSeccionBtn = document.getElementById('addSeccion');
    const form = document.getElementById('criteriosForm');
    const criteriosInput = document.getElementById('criteriosInput');
    
    // Función para crear una nueva sección
    function createSeccion(titulo = '', peso = 0, items = []) {
        const seccionDiv = document.createElement('div');
        seccionDiv.className = 'seccion border border-slate-200 rounded-lg p-6 mb-4 bg-slate-50';
        
        seccionDiv.innerHTML = `
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4 gap-4">
                <div class="flex-1 mr-0 sm:mr-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Título de la Sección</label>
                    <input type="text" class="seccion-titulo w-full min-w-0 px-3 py-2 border border-slate-300 rounded-md focus:ring-orange-500 focus:border-orange-500" value="${titulo}">
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-sm font-medium text-slate-700">Peso (%)</label>
                    <input type="number" class="seccion-peso w-20 px-2 py-2 border border-slate-300 rounded-md focus:ring-orange-500 focus:border-orange-500" value="${peso}" min="0" max="100" step="0.1">
                    <button type="button" class="move-up text-blue-500 hover:text-blue-700 p-2" title="Mover arriba">
                        <i class="fa-solid fa-arrow-up"></i>
                    </button>
                    <button type="button" class="move-down text-blue-500 hover:text-blue-700 p-2" title="Mover abajo">
                        <i class="fa-solid fa-arrow-down"></i>
                    </button>
                    <button type="button" class="remove-seccion text-red-500 hover:text-red-700 p-2">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="items-container"></div>
            
            <button type="button" class="add-item bg-slate-600 hover:bg-slate-700 text-white px-3 py-1 rounded text-sm mt-2">
                <i class="fa-solid fa-plus mr-1"></i> Añadir Criterio
            </button>
        `;

        const itemsContainer = seccionDiv.querySelector('.items-container');
        items.forEach(item => {
            itemsContainer.appendChild(createItem(item));
        });

        container.appendChild(seccionDiv);
        attachEvents(seccionDiv);
    }
    
// Función para agregar eventos (mantenemos para futuros usos)
        function addDragHandlers(item) {
            // actualmente no se usa, pero se mantiene en caso de necesitar drag
    }

    // Función para crear un nuevo item
    function createItem(texto = '') {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'item flex flex-row items-start gap-2 mb-2 p-2 bg-white border border-slate-200 rounded-lg shadow-sm';
        itemDiv.innerHTML = `
            <textarea class="item-texto flex-1 min-w-0 px-3 py-2 border border-dashed border-slate-300 rounded-md focus:ring-orange-500 focus:border-orange-500 resize-none" rows="2" style="min-height: 2.5rem;">${texto}</textarea>
            <button type="button" class="remove-item text-red-500 hover:text-red-700 p-2 flex-shrink-0 mt-1">
                <i class="fa-solid fa-minus"></i>
            </button>
        `;
        return itemDiv;
    }
    
    // Función para adjuntar eventos
    function attachEvents(element) {
        // Remover sección
        element.querySelector('.remove-seccion').addEventListener('click', function() {
            element.remove();
        });
        
        // Mover arriba
        element.querySelector('.move-up').addEventListener('click', function() {
            const prev = element.previousElementSibling;
            if (prev && prev.classList.contains('seccion')) {
                container.insertBefore(element, prev);
            }
        });
        
        // Mover abajo
        element.querySelector('.move-down').addEventListener('click', function() {
            const next = element.nextElementSibling;
            if (next && next.classList.contains('seccion')) {
                container.insertBefore(element, next.nextSibling);
            }
        });
        
        // Añadir item
        element.querySelector('.add-item').addEventListener('click', function() {
            const itemsContainer = element.querySelector('.items-container');
            itemsContainer.appendChild(createItem());
        });
        
        // Remover items
        element.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                this.parentElement.remove();
            });
        });

    }
    
    // Evento para añadir sección
    addSeccionBtn.addEventListener('click', function() {
        createSeccion('Nueva Sección', 0, ['Nuevo criterio']);
    });
    
    // Adjuntar eventos iniciales
    document.querySelectorAll('.seccion').forEach(seccion => {
        attachEvents(seccion);
    });
    
    // Procesar envío del formulario
    form.addEventListener('submit', function(e) {
        const criterios = {};
        
        document.querySelectorAll('.seccion').forEach(seccion => {
            const titulo = seccion.querySelector('.seccion-titulo').value.trim();
            const peso = parseFloat(seccion.querySelector('.seccion-peso').value) / 100;
            const items = [];
            
            seccion.querySelectorAll('.item-texto').forEach(input => {
                const texto = input.value.trim();
                if (texto) items.push(texto);
            });
            
            if (titulo && items.length > 0) {
                criterios[titulo] = {
                    weight: peso,
                    items: items
                };
            }
        });
        
        criteriosInput.value = JSON.stringify(criterios);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>