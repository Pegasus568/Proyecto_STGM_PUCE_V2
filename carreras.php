<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Solo ADMIN
if ($_SESSION['usuario_rol'] !== 'ADMIN') {
    header("Location: index.php");
    exit;
}

$tituloPagina = "Gestión de Carreras";

// Mensajes Flash
$mensaje = $_SESSION['flash_mensaje'] ?? "";
$tipoMsg = $_SESSION['flash_tipo'] ?? "info";
unset($_SESSION['flash_mensaje'], $_SESSION['flash_tipo']);

// Listar Carreras Activas
$sql = "SELECT * FROM carreras WHERE estado = 1 ORDER BY nombre ASC";
$carreras = $pdo->query($sql)->fetchAll();

require_once 'includes/header.php'; 
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Carreras Universitarias</h1></div>
            <div class="col-sm-6 text-right">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalCrear">
                    <i class="fas fa-plus mr-2"></i> Nueva Carrera
                </button>
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

        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre de la Carrera</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($carreras as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($c['codigo']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong></td>
                            <td><span class="badge badge-success">Activa</span></td>
                            <td>
                                <form action="controllers/carreras_controller.php" method="POST" onsubmit="return confirm('¿Desactivar esta carrera?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="carrera_id" value="<?php echo $c['id']; ?>">
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($carreras)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No hay carreras registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrear">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary"><h5 class="modal-title">Registrar Carrera</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <form action="controllers/carreras_controller.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <div class="form-group">
                        <label>Nombre de la Carrera *</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Ingeniería de Software">
                    </div>
                    <div class="form-group">
                        <label>Código / Abreviatura</label>
                        <input type="text" name="codigo" class="form-control" placeholder="Ej: SW-01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>