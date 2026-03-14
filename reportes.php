<?php
// reportes.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

// Procesar exportación
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export_type'])) {
    $tipo_export = $_GET['export_type'];
    $periodo = $_GET['periodo'] ?? '';
    $modulo = $_GET['modulo'] ?? '';
    $id_lista = $_GET['id_lista'] ?? '';
    
    // Consulta base
    $query = "
        SELECT 
            p.identificacion, p.apellidos, p.nombres,
            m.nombre_modulo, m.rotaciones,
            a.periodo_academico,
            c.nota_final, c.nota_r1, c.nota_r2, c.nota_r3, c.observaciones,
            u.nombre_completo as docente,
            l.nombre_lista, l.grupo, l.semestre
        FROM asignaciones_practicas a
        JOIN practicantes p ON a.id_practicante = p.id_practicante
        JOIN modulos_rotacion m ON a.id_modulo = m.id_modulo
        JOIN usuarios u ON a.id_docente = u.id_usuario
        JOIN listas l ON a.id_lista = l.id_lista
        LEFT JOIN calificaciones c ON a.id_asignacion = c.id_asignacion
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($rol === 'docente') {
        $query .= " AND a.id_docente = :id_docente";
        $params[':id_docente'] = $id_usuario;
    }
    
    if ($id_lista) {
        $query .= " AND a.id_lista = :id_lista";
        $params[':id_lista'] = $id_lista;
    }
    
    if ($modulo) {
        $query .= " AND a.id_modulo = :modulo";
        $params[':modulo'] = $modulo;
    }
    
    if ($periodo) {
        $query .= " AND a.periodo_academico = :periodo";
        $params[':periodo'] = $periodo;
    }
    
    $query .= " ORDER BY a.periodo_academico DESC, m.nombre_modulo ASC, p.apellidos ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $datos = $stmt->fetchAll();
    
    if ($tipo_export === 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Encabezados
        $sheet->setCellValue('A1', 'Identificación')
              ->setCellValue('B1', 'Apellidos')
              ->setCellValue('C1', 'Nombres')
              ->setCellValue('D1', 'Lista')
              ->setCellValue('E1', 'Grupo')
              ->setCellValue('F1', 'Semestre')
              ->setCellValue('G1', 'Módulo')
              ->setCellValue('H1', 'Período')
              ->setCellValue('I1', 'Docente')
              ->setCellValue('J1', 'Nota Final')
              ->setCellValue('K1', 'Observaciones');
              
        // Estilo encabezados
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()
              ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFF97316'); // Naranja Tailwind
        $sheet->getStyle('A1:K1')->getFont()->getColor()->setARGB('FFFFFFFF');
        
        $row = 2;
        foreach ($datos as $d) {
            $sheet->setCellValue('A'.$row, $d['identificacion'])
                  ->setCellValue('B'.$row, $d['apellidos'])
                  ->setCellValue('C'.$row, $d['nombres'])
                  ->setCellValue('D'.$row, $d['nombre_lista'])
                  ->setCellValue('E'.$row, $d['grupo'])
                  ->setCellValue('F'.$row, $d['semestre'])
                  ->setCellValue('G'.$row, $d['nombre_modulo'])
                  ->setCellValue('H'.$row, $d['periodo_academico'])
                  ->setCellValue('I'.$row, $d['docente'])
                  ->setCellValue('J'.$row, $d['nota_final'])
                  ->setCellValue('K'.$row, $d['observaciones']);
            $row++;
        }
        
        // Auto size
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $filename = "Reporte_Notas_" . date('Ymd_His') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'. $filename .'"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } elseif ($tipo_export === 'pdf') {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        // Determinar el título e información según filtros aplicados
        $titulo = 'REPORTE DE CALIFICACIONES DE PRÁCTICAS';
        $info_adicional = '';
        $num_rotaciones = 1; // Por defecto

        if ($id_lista && !empty($datos)) {
            // Reporte específico de una lista
            $primer_registro = $datos[0];
            $titulo = 'REPORTE DE CALIFICACIONES - ' . htmlspecialchars($primer_registro['nombre_lista']);
            $num_rotaciones = (int)$primer_registro['rotaciones'];
            $info_adicional = '<div class="info-adicional">
                <p><strong>Semestre:</strong> ' . htmlspecialchars($primer_registro['semestre']) . '° | <strong>Módulo:</strong> ' . htmlspecialchars($primer_registro['nombre_modulo']) . ' | <strong>Grupo:</strong> ' . htmlspecialchars($primer_registro['grupo']) . ' | <strong>Período:</strong> ' . htmlspecialchars($primer_registro['periodo_academico']) . ' | <strong>Rotaciones:</strong> ' . $num_rotaciones . '</p>
            </div>';
        } elseif ($modulo || $periodo) {
            // Reporte con filtros generales - determinar rotaciones del módulo
            if ($modulo) {
                $stmt_mod = $pdo->prepare("SELECT nombre_modulo, rotaciones FROM modulos_rotacion WHERE id_modulo = ?");
                $stmt_mod->execute([$modulo]);
                $mod_info = $stmt_mod->fetch();
                if ($mod_info) {
                    $num_rotaciones = (int)$mod_info['rotaciones'];
                    $filtros[] = '<strong>Módulo:</strong> ' . htmlspecialchars($mod_info['nombre_modulo']) . ' (' . $num_rotaciones . ' rotaciones)';
                }
            }
            if ($periodo) {
                $filtros[] = '<strong>Período:</strong> ' . htmlspecialchars($periodo);
            }
            if (!empty($filtros)) {
                $info_adicional = '<div class="info-adicional"><p>' . implode(' | ', $filtros) . '</p></div>';
            }
        }

        $html = '
        <html>
        <head>
            <style>
                body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; }
                .title { text-align: center; color: #f97316; font-size: 20px; margin-bottom: 10px; }
                .info-adicional { text-align: center; margin-bottom: 15px; font-size: 14px; background-color: #f8f9fa; padding: 10px; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background-color: #f97316; color: white; padding: 8px; border: 1px solid #ddd; }
                td { padding: 8px; border: 1px solid #ddd; }
                .text-center { text-align: center; }
                .sin-calificar { color: #dc2626; font-style: italic; }
                .rotacion-header { background-color: #fed7aa !important; }
            </style>
        </head>
        <body>
            <div class="title">' . $titulo . '</div>
            ' . $info_adicional . '
            <p style="text-align: center; margin-bottom: 20px;"><strong>Fecha de emisión:</strong> ' . date('d/m/Y H:i') . '</p>
            <table>
                <thead>
                    <tr>
                        <th>Identificación</th>
                        <th>Estudiante</th>';

        // Agregar columnas de rotación dinámicamente
        for ($i = 1; $i <= $num_rotaciones; $i++) {
            $html .= '<th class="rotacion-header">Rotación ' . $i . '</th>';
        }

        $html .= '<th>Nota Final</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($datos as $d) {
            $nota_display = $d['nota_final'] !== null ? number_format($d['nota_final'], 2) : '<span class="sin-calificar">sin calificar</span>';
            $observaciones = $d['observaciones'] ?? '';

            $html .= '<tr>
                        <td>'.$d['identificacion'].'</td>
                        <td>'.$d['apellidos'].' '.$d['nombres'].'</td>';

            // Agregar celdas de rotación dinámicamente
            for ($i = 1; $i <= $num_rotaciones; $i++) {
                $nota_rotacion = $d['nota_r' . $i] !== null ? number_format($d['nota_r' . $i], 2) : '---';
                $html .= '<td class="text-center">' . $nota_rotacion . '</td>';
            }

            $html .= '<td class="text-center"><strong>'.$nota_display.'</strong></td>
                        <td>'.$observaciones.'</td>
                      </tr>';
        }

        $html .= '</tbody></table></body></html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream("Reporte_Notas_" . date('Ymd_His') . ".pdf", array("Attachment" => true));
        exit;
    }
}

// Interfaz para generar reportes
// Obtener filtros disponibles según rol
$q_mod = "SELECT DISTINCT m.id_modulo, m.nombre_modulo FROM asignaciones_practicas a JOIN modulos_rotacion m ON a.id_modulo = m.id_modulo";
$q_per = "SELECT DISTINCT periodo_academico FROM asignaciones_practicas";
$q_list = "SELECT DISTINCT l.id_lista, l.nombre_lista, m.nombre_modulo, l.grupo, l.semestre
           FROM listas l
           JOIN modulos_rotacion m ON l.id_modulo = m.id_modulo";

$p_mod = []; $p_per = []; $p_list = [];
if ($rol === 'docente') {
    $q_mod .= " WHERE a.id_docente = ?";
    $q_per .= " WHERE id_docente = ?";
    $q_list .= " WHERE l.id_docente = ?";
    $p_mod[] = $id_usuario;
    $p_per[] = $id_usuario;
    $p_list[] = $id_usuario;
}
$q_mod .= " ORDER BY m.nombre_modulo";
$q_per .= " ORDER BY periodo_academico DESC";
$q_list .= " ORDER BY l.nombre_lista";

$stmt_m = $pdo->prepare($q_mod); $stmt_m->execute($p_mod); $filtro_modulos = $stmt_m->fetchAll();
$stmt_p = $pdo->prepare($q_per); $stmt_p->execute($p_per); $filtro_periodos = $stmt_p->fetchAll();
$stmt_l = $pdo->prepare($q_list); $stmt_l->execute($p_list); $filtro_listas = $stmt_l->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-8 text-center sm:text-left">
    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center justify-center sm:justify-start gap-3">
        <i class="fa-solid fa-file-contract text-orange-500"></i> Generación de Reportes
    </h2>
    <p class="text-slate-500 mt-2">Exporta las calificaciones consolidadas en formato Excel o PDF.</p>
</div>

<div class="bg-white shadow-sm border border-slate-100 rounded-2xl mb-8 overflow-hidden max-w-3xl mx-auto relative group">
    <div class="absolute top-0 left-0 w-2 h-full bg-orange-500 group-hover:w-3 transition-all"></div>
    <div class="p-10 pl-12">
        <form action="reportes.php" method="GET" class="space-y-8">
            <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-filter text-orange-500 text-sm"></i>
                Criterios del Reporte
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Período Académico</label>
                    <select name="periodo" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                        <option value="">Todos los períodos</option>
                        <?php foreach($filtro_periodos as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['periodo_academico']); ?>">
                                <?php echo htmlspecialchars($p['periodo_academico']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Módulo (Rotación)</label>
                    <select name="modulo" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                        <option value="">Todos los módulos</option>
                        <?php foreach($filtro_modulos as $m): ?>
                            <option value="<?php echo $m['id_modulo']; ?>">
                                <?php echo htmlspecialchars($m['nombre_modulo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Lista Específica</label>
                    <select name="id_lista" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                        <option value="">Seleccionar lista específica</option>
                        <?php
                        $lista_seleccionada = $_GET['id_lista'] ?? '';
                        foreach($filtro_listas as $l):
                            $selected = ($lista_seleccionada == $l['id_lista']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $l['id_lista']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($l['nombre_lista'] . ' - ' . $l['nombre_modulo'] . ' (Grupo ' . $l['grupo'] . ' - ' . $l['semestre'] . '°)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Si seleccionas una lista, se ignorarán los filtros de módulo y período</p>
                </div>
            </div>

            <!-- Información estática sobre filtros -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fa-solid fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                    <div>
                        <h4 class="text-sm font-medium text-blue-800">Información sobre filtros</h4>
                        <p class="text-sm text-blue-700 mt-1">
                            Puedes generar reportes generales filtrando por período y módulo, o seleccionar una lista específica para obtener un reporte detallado de esa lista en particular.
                            <?php if (isset($_GET['id_lista']) && !empty($_GET['id_lista'])): ?>
                                <strong>Lista preseleccionada:</strong> Se ha seleccionado automáticamente una lista específica.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

                <button type="button" onclick="openGuideModal()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-orange-600 hover:text-orange-700 hover:bg-orange-50 rounded-lg transition-colors border border-orange-200 hover:border-orange-300">
                    <i class="fa-solid fa-question-circle"></i>
                    ¿Cómo usar esta herramienta?
                </button>
            </div>

            <div class="pt-6 border-t border-gray-200 mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <button type="submit" name="export_type" value="excel" class="w-full sm:w-auto inline-flex justify-center items-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                    <i class="fa-solid fa-file-excel mr-2"></i> Exportar a Excel
                </button>
                
                <button type="submit" name="export_type" value="pdf" class="w-full sm:w-auto inline-flex justify-center items-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Exportar a PDF
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
