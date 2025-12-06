<?php
// reportes.php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Seguridad rol
if ($_SESSION['usuario_rol'] === 'ESTUDIANTE') { header("Location: index.php"); exit; }

$tituloPagina = "Gestión de Reportes";
$usuarioId = $_SESSION['usuario_id'];

// Mensajes Flash
$mensaje = $_SESSION['flash_mensaje'] ?? "";
$tipoMsg = $_SESSION['flash_tipo'] ?? "info";
unset($_SESSION['flash_mensaje'], $_SESSION['flash_tipo']);

// Consultas (Solo lectura)
$tutoriasDisponibles = $pdo->query("SELECT id, titulo, fecha FROM tutorias WHERE estado='REALIZADA' AND tutor_id=$usuarioId")->fetchAll();
$estudiantes = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol='ESTUDIANTE' ORDER BY nombre")->fetchAll();
$sqlR = "SELECT r.*, u.nombre as autor FROM reportes r JOIN usuarios u ON r.creado_por = u.id WHERE r.deleted_at IS NULL";
if ($_SESSION['usuario_rol'] === 'DOCENTE') $sqlR .= " AND r.creado_por = $usuarioId";
$sqlR .= " ORDER BY r.created_at DESC";
$reportes = $pdo->query($sqlR)->fetchAll();

require_once 'includes/header.php'; 
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-6"><h1>Reportes</h1></div>
            <div class="col-6 text-right">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalReporte">Nuevo Reporte</button>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <?php if($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMsg; ?> alert-dismissible fade show">
                <?php echo $mensaje; ?><button class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="card card-warning card-outline">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Título</th><th>Autor</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($reportes as $r): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>
                            <td><?php echo $r['tipo']; ?></td>
                            <td><?php echo htmlspecialchars($r['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($r['autor']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info btn-leer" 
                                    data-titulo="<?php echo htmlspecialchars($r['titulo']); ?>" 
                                    data-contenido="<?php echo htmlspecialchars($r['contenido']); ?>"
                                    data-toggle="modal" data-target="#modalLeer"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReporte">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="controllers/reportes_controller.php" method="POST">
                <div class="modal-header bg-primary"><h5 class="modal-title">Nuevo Reporte</h5></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-8"><label>Título</label><input type="text" name="titulo" class="form-control" required></div>
                        <div class="col-4"><label>Tipo</label><select name="tipo" class="form-control"><option>OBSERVACION</option><option>ACTA</option><option>REPORTE_GENERAL</option></select></div>
                    </div>
                    <div class="form-group mt-2">
                        <label>Vincular a Tutoría</label>
                        <select name="tutoria_id" class="form-control">
                            <option value="">-- No vincular --</option>
                            <?php foreach($tutoriasDisponibles as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>O Estudiante Manual</label>
                        <select name="estudiante_manual_id" class="form-control">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach($estudiantes as $e): ?>
                                <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Contenido</label><textarea name="contenido" class="form-control" rows="5" required></textarea></div>
                    <div class="form-check"><input type="checkbox" name="privado" class="form-check-input"><label>Privado</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLeer">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info"><h5 class="modal-title" id="leerTitulo"></h5></div>
            <div class="modal-body" id="leerContenido" style="white-space: pre-wrap;"></div>
            <div class="modal-footer"><button class="btn btn-default" onclick="window.print()">Imprimir</button></div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script>
$('.btn-leer').click(function(){
    $('#leerTitulo').text($(this).data('titulo'));
    $('#leerContenido').text($(this).data('contenido'));
});
</script>