<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ------------------------------------

require_once 'includes/auth.php';
// tutorias.php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$tituloPagina = "Gestión de Sesiones";
$usuarioId = $_SESSION['usuario_id'];
$rolUsuario = $_SESSION['usuario_rol'];
$mensaje = "";
$error = "";

// --- 1. REGLA DE ÉTICA: ADMIN BLOQUEADO ---
if ($rolUsuario === 'ADMIN') {
    require_once 'includes/header.php';
    echo '<div class="content-wrapper"><section class="content pt-4"><div class="alert alert-warning">
            <h4><i class="icon fas fa-exclamation-triangle"></i> Acceso Restringido</h4>
            Por políticas de ética y privacidad, la gestión de tutorías es exclusiva entre Docentes y Estudiantes.
          </div></section></div>';
    require_once 'includes/footer.php';
    exit;
}

// --- 2. PROCESAR FORMULARIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A) SOLICITAR NUEVA SESIÓN (Ambos roles)
    if (isset($_POST['accion']) && $_POST['accion'] === 'solicitar') {
        $titulo = trim($_POST['titulo']);
        $tipo = $_POST['tipo']; // TUTORIA o MENTORIA
        $fecha = $_POST['fecha'];
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fin = $_POST['hora_fin'];
        $modalidad = $_POST['modalidad'];
        $lugar = trim($_POST['lugar']);
        
        // Determinar quién es el tutor y quién el estudiante según quién esté logueado
        if ($rolUsuario === 'DOCENTE') {
            $id_tutor = $usuarioId;
            $id_estudiante = $_POST['id_contraparte']; // El docente elige al estudiante
        } else { // ESTUDIANTE
            $id_estudiante = $usuarioId;
            $id_tutor = $_POST['id_contraparte']; // El estudiante elige al docente
        }

        try {
            $sql = "INSERT INTO tutorias 
                    (solicitado_por, tipo, tutor_id, estudiante_id, titulo, fecha, hora_inicio, hora_fin, modalidad, lugar, estado, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE', NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuarioId, $tipo, $id_tutor, $id_estudiante, $titulo, $fecha, $hora_inicio, $hora_fin, $modalidad, $lugar]);
            $mensaje = "Solicitud de " . strtolower($tipo) . " enviada correctamente.";
        } catch (Exception $e) {
            $error = "Error al solicitar: " . $e->getMessage();
        }
    }

    // B) RESPONDER SOLICITUD (Aceptar o Rechazar)
    if (isset($_POST['accion']) && in_array($_POST['accion'], ['confirmar', 'rechazar'])) {
        $tutoria_id = $_POST['tutoria_id'];
        $nuevo_estado = ($_POST['accion'] === 'confirmar') ? 'CONFIRMADA' : 'RECHAZADA';
        $motivo = ($_POST['accion'] === 'rechazar') ? trim($_POST['motivo_rechazo']) : null;

        if ($nuevo_estado === 'RECHAZADA' && empty($motivo)) {
            $error = "Es obligatorio indicar el motivo del rechazo.";
        } else {
            // Validar que la tutoría me pertenezca (seguridad)
            $sqlCheck = "SELECT id FROM tutorias WHERE id = ? AND (tutor_id = ? OR estudiante_id = ?)";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([$tutoria_id, $usuarioId, $usuarioId]);
            
            if ($stmtCheck->fetch()) {
                $upd = $pdo->prepare("UPDATE tutorias SET estado = ?, motivo_rechazo = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$nuevo_estado, $motivo, $tutoria_id]);
                $mensaje = "La sesión ha sido " . strtolower($nuevo_estado) . ".";
            } else {
                $error = "No tienes permiso para gestionar esta solicitud.";
            }
        }
    }
}

// --- 3. PREPARAR DATOS PARA LA VISTA ---

// Cargar lista de usuarios para el select (La contraparte)
$listaUsuarios = [];
if ($rolUsuario === 'DOCENTE') {
    // Si soy docente, busco estudiantes
    $stmtUsers = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'ESTUDIANTE' AND estado = 'ACTIVO' ORDER BY nombre");
} else {
    // Si soy estudiante, busco docentes
    $stmtUsers = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'DOCENTE' AND estado = 'ACTIVO' ORDER BY nombre");
}
$listaUsuarios = $stmtUsers->fetchAll();

// Cargar mis tutorias
$sql = "SELECT t.*, 
        doc.nombre as nombre_tutor, 
        est.nombre as nombre_estudiante 
        FROM tutorias t
        JOIN usuarios doc ON t.tutor_id = doc.id
        JOIN usuarios est ON t.estudiante_id = est.id
        WHERE t.deleted_at IS NULL 
        AND (t.tutor_id = ? OR t.estudiante_id = ?)
        ORDER BY t.fecha DESC, t.hora_inicio ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId, $usuarioId]);
$tutorias = $stmt->fetchAll();

require_once 'includes/header.php'; 
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Mis Tutorías y Mentorías</h1>
            </div>
            <div class="col-sm-6 text-right">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalSolicitar">
                    <i class="fas fa-plus-circle mr-2"></i>Solicitar Nueva
                </button>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        
        <?php if($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle mr-2"></i><?php echo $mensaje; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="card card-outline card-primary">
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead class="bg-light">
                        <tr>
                            <th>Estado</th>
                            <th>Tipo</th>
                            <th>Fecha / Hora</th>
                            <th>Título / Tema</th>
                            <th>Con quién</th>
                            <th>Modalidad</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tutorias) > 0): ?>
                            <?php foreach($tutorias as $t): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $badge = 'secondary';
                                    $txtEstado = $t['estado'];
                                    
                                    if($t['estado']=='PENDIENTE') {
                                        $badge = 'warning';
                                        $txtEstado = '<i class="fas fa-clock mr-1"></i> Pendiente';
                                    }
                                    if($t['estado']=='CONFIRMADA') {
                                        $badge = 'success';
                                        $txtEstado = '<i class="fas fa-check mr-1"></i> Confirmada';
                                    }
                                    if($t['estado']=='RECHAZADA') $badge = 'danger';
                                    
                                    echo "<span class='badge badge-{$badge} p-2'>{$txtEstado}</span>";
                                    ?>
                                </td>
                                
                                <td><span class="text-muted font-weight-bold"><?php echo $t['tipo']; ?></span></td>

                                <td>
                                    <?php echo date('d/m/Y', strtotime($t['fecha'])); ?> <br>
                                    <small class="text-muted"><?php echo substr($t['hora_inicio'],0,5) . ' - ' . substr($t['hora_fin'],0,5); ?></small>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($t['titulo']); ?></strong>
                                    <?php if($t['estado'] == 'RECHAZADA'): ?>
                                        <div class="text-danger small mt-1">
                                            <strong>Motivo:</strong> <?php echo htmlspecialchars($t['motivo_rechazo']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php 
                                    // Mostrar el nombre de la OTRA persona
                                    $nombreContraparte = ($rolUsuario === 'DOCENTE') ? $t['nombre_estudiante'] : $t['nombre_tutor'];
                                    echo htmlspecialchars($nombreContraparte); 
                                    ?>
                                </td>
                                
                                <td><?php echo $t['modalidad']; ?><br><small><?php echo htmlspecialchars($t['lugar']); ?></small></td>

                                <td class="text-right">
                                    <?php if($t['estado'] === 'PENDIENTE'): ?>
                                        <?php if($t['solicitado_por'] == $usuarioId): ?>
                                            <span class="text-muted small font-italic">Esperando respuesta...</span>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="accion" value="confirmar">
                                                <input type="hidden" name="tutoria_id" value="<?php echo $t['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Aceptar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-danger btn-rechazar" 
                                                    data-id="<?php echo $t['id']; ?>" 
                                                    data-titulo="<?php echo htmlspecialchars($t['titulo']); ?>"
                                                    data-toggle="modal" data-target="#modalRechazar" title="Rechazar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No tienes sesiones registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSolicitar">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Solicitar Nueva Sesión</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="solicitar">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Título / Tema a tratar *</label>
                                <input type="text" name="titulo" class="form-control" required placeholder="Ej: Revisión de tesis, Dudas clase 4...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo *</label>
                                <select name="tipo" class="form-control">
                                    <option value="TUTORIA">Tutoría Académica</option>
                                    <option value="MENTORIA">Mentoría</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <?php echo ($rolUsuario === 'DOCENTE') ? 'Estudiante a citar:' : 'Docente solicitado:'; ?> *
                        </label>
                        <select name="id_contraparte" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($listaUsuarios as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha *</label>
                                <input type="date" name="fecha" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Hora Inicio *</label>
                                <input type="time" name="hora_inicio" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Hora Fin *</label>
                                <input type="time" name="hora_fin" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Modalidad</label>
                                <select name="modalidad" class="form-control">
                                    <option value="PRESENCIAL">Presencial</option>
                                    <option value="VIRTUAL">Virtual</option>
                                    <option value="HIBRIDA">Híbrida</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Lugar / Enlace</label>
                                <input type="text" name="lugar" class="form-control" placeholder="Aula, Oficina o Link Zoom">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRechazar">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Rechazar Solicitud</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="rechazar">
                    <input type="hidden" name="tutoria_id" id="rechazo_id">
                    
                    <p>Vas a rechazar la sesión: <strong id="rechazo_titulo"></strong></p>
                    
                    <div class="form-group">
                        <label>Motivo obligatorio *</label>
                        <textarea name="motivo_rechazo" class="form-control" rows="3" required placeholder="Indique la razón (cruce de horarios, enfermedad, etc.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Pasar datos al modal de rechazo dinámicamente
        $('.btn-rechazar').click(function() {
            var id = $(this).data('id');
            var titulo = $(this).data('titulo');
            $('#rechazo_id').val(id);
            $('#rechazo_titulo').text(titulo);
        });
    });
</script>