<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
if ($_SESSION['usuario_rol'] !== 'ADMIN') { header("Location: index.php"); exit; }

$tituloPagina = "Gestión de Usuarios";
$mensaje = $_SESSION['flash_mensaje'] ?? "";
$tipoMsg = $_SESSION['flash_tipo'] ?? "info";
unset($_SESSION['flash_mensaje'], $_SESSION['flash_tipo']);

$carreras = $pdo->query("SELECT id, nombre FROM carreras WHERE estado = 1 ORDER BY nombre")->fetchAll();
$sql = "SELECT u.*, c.nombre as nombre_carrera FROM usuarios u LEFT JOIN carreras c ON u.carrera_id = c.id WHERE u.deleted_at IS NULL ORDER BY u.id DESC";
$usuarios = $pdo->query($sql)->fetchAll();

require_once 'includes/header.php'; 
?>

<div class="content-header"><div class="container-fluid"><h1>Usuarios</h1></div></div>

<div class="content">
    <div class="container-fluid">
        <?php if($mensaje): ?><div class="alert alert-<?php echo $tipoMsg; ?>"><?php echo $mensaje; ?></div><?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Nuevo Usuario</h3></div>
                    <form action="controllers/usuarios_controller.php" method="POST">
                        <div class="card-body">
                            <input type="hidden" name="accion" value="crear">
                            <div class="form-group"><label>Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                            <div class="form-group"><label>Correo</label><input type="email" name="correo" class="form-control" required></div>
                            <div class="form-group"><label>Cédula</label><input type="text" name="cedula" class="form-control"></div>
                            
                            <div class="form-group">
                                <label>Rol</label>
                                <select name="rol" id="create_rol" class="form-control" onchange="toggleFields('create')">
                                    <option value="ESTUDIANTE">Estudiante</option>
                                    <option value="DOCENTE">Docente</option>
                                    <option value="ADMIN">Administrador</option>
                                </select>
                            </div>

                            <div id="create_academic_fields">
                                <div class="form-group"><label>Carrera</label>
                                    <select name="carrera_id" class="form-control">
                                        <option value="">-- Seleccione --</option>
                                        <?php foreach($carreras as $c) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; ?>
                                    </select>
                                </div>
                                <div class="form-group" id="create_sem_group"><label>Semestre</label>
                                    <select name="semestre" class="form-control">
                                        <option value="">-- N/A --</option>
                                        <?php for($i=1;$i<=10;$i++) echo "<option value='{$i}ro'>{$i}ro Semestre</option>"; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group"><label>Contraseña</label><input type="password" name="password" class="form-control" required></div>
                        </div>
                        <div class="card-footer"><button class="btn btn-primary btn-block">Crear</button></div>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <thead><tr><th>Nombre</th><th>Rol</th><th>Académico</th><th>Acción</th></tr></thead>
                            <tbody>
                                <?php foreach($usuarios as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['nombre']); ?><br><small><?php echo $u['correo']; ?></small></td>
                                    <td><?php echo $u['rol']; ?></td>
                                    <td><?php echo $u['nombre_carrera']; ?> <?php if($u['semestre']) echo "({$u['semestre']})"; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning btn-edit" data-id="<?php echo $u['id']; ?>"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning"><h5 class="modal-title">Editar Usuario</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <form action="controllers/usuarios_controller.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="usuario_id" id="edit_id">
                    
                    <div class="form-group"><label>Nombre</label><input type="text" name="nombre" id="edit_nombre" class="form-control" required></div>
                    <div class="form-group"><label>Correo</label><input type="email" name="correo" id="edit_correo" class="form-control" required></div>
                    <div class="form-group"><label>Cédula</label><input type="text" name="cedula" id="edit_cedula" class="form-control"></div>
                    
                    <div class="form-group"><label>Rol</label>
                        <select name="rol" id="edit_rol" class="form-control" onchange="toggleFields('edit')">
                            <option value="ESTUDIANTE">Estudiante</option>
                            <option value="DOCENTE">Docente</option>
                            <option value="ADMIN">Administrador</option>
                        </select>
                    </div>

                    <div id="edit_academic_fields">
                        <div class="form-group"><label>Carrera</label>
                            <select name="carrera_id" id="edit_carrera" class="form-control">
                                <option value="">-- Seleccione --</option>
                                <?php foreach($carreras as $c) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; ?>
                            </select>
                        </div>
                        <div class="form-group" id="edit_sem_group"><label>Semestre</label>
                            <select name="semestre" id="edit_semestre" class="form-control">
                                <option value="">-- N/A --</option>
                                <?php for($i=1;$i<=10;$i++) echo "<option value='{$i}ro'>{$i}ro Semestre</option>"; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group"><label>Nueva Clave (Opcional)</label><input type="password" name="password" class="form-control"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-warning">Guardar Cambios</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
function toggleFields(prefix) {
    const rol = $(`#${prefix}_rol`).val();
    const container = $(`#${prefix}_academic_fields`);
    const semGroup = $(`#${prefix}_sem_group`);

    if (rol === 'ADMIN') {
        container.hide();
    } else if (rol === 'DOCENTE') {
        container.show();
        semGroup.hide();
    } else {
        container.show();
        semGroup.show();
    }
}

$('.btn-edit').click(function() {
    const id = $(this).data('id');
    fetch(`controllers/api_get_entity.php?entity=usuario&id=${id}`)
        .then(res => res.json())
        .then(data => {
            $('#edit_id').val(data.id);
            $('#edit_nombre').val(data.nombre);
            $('#edit_correo').val(data.correo);
            $('#edit_cedula').val(data.cedula);
            $('#edit_rol').val(data.rol);
            $('#edit_carrera').val(data.carrera_id);
            $('#edit_semestre').val(data.semestre);
            toggleFields('edit');
            $('#modalEditar').modal('show');
        });
});

$(document).ready(() => toggleFields('create'));
</script>