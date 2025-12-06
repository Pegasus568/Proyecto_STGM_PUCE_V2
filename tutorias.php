<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$tituloPagina = "Agenda Académica";
$usuarioId = $_SESSION['usuario_id'];
$rolUsuario = $_SESSION['usuario_rol'];

$mensaje = $_SESSION['flash_mensaje'] ?? "";
$tipoMsg = $_SESSION['flash_tipo'] ?? "info";
unset($_SESSION['flash_mensaje'], $_SESSION['flash_tipo']);

if ($rolUsuario === 'ADMIN') { header("Location: index.php"); exit; }

$carreras = $pdo->query("SELECT id, nombre FROM carreras WHERE estado = 1 ORDER BY nombre")->fetchAll();

$sqlList = "SELECT t.*, doc.nombre as nombre_tutor, est.nombre as nombre_estudiante 
            FROM tutorias t
            JOIN usuarios doc ON t.tutor_id = doc.id
            JOIN usuarios est ON t.estudiante_id = est.id
            WHERE t.deleted_at IS NULL AND (t.tutor_id = ? OR t.estudiante_id = ?)
            ORDER BY t.fecha DESC, t.hora_inicio ASC";
$stmt = $pdo->prepare($sqlList);
$stmt->execute([$usuarioId, $usuarioId]);
$tutorias = $stmt->fetchAll();

echo '<link rel="stylesheet" href="adminlte/plugins/select2/css/select2.min.css">';
echo '<link rel="stylesheet" href="adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">';
require_once 'includes/header.php'; 
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-6"><h1>Agenda</h1></div>
            <div class="col-6 text-right"><button class="btn btn-primary" data-toggle="modal" data-target="#modalSolicitar">Agendar</button></div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <?php if($mensaje): ?><div class="alert alert-<?php echo $tipoMsg; ?>"><?php echo $mensaje; ?></div><?php endif; ?>

        <div class="card card-primary card-outline">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Estado</th><th>Tipo</th><th>Fecha</th><th>Contraparte</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($tutorias as $t): ?>
                        <tr>
                            <td><span class="badge badge-<?php echo ($t['estado']=='PENDIENTE')?'warning':(($t['estado']=='CONFIRMADA')?'success':'danger'); ?>"><?php echo $t['estado']; ?></span></td>
                            <td><?php echo $t['tipo']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($t['fecha'])) . " " . substr($t['hora_inicio'],0,5); ?></td>
                            <td><?php echo ($rolUsuario==='DOCENTE')?$t['nombre_estudiante']:$t['nombre_tutor']; ?></td>
                            <td>
                                <?php if($t['estado'] === 'PENDIENTE' && $t['solicitado_por'] != $usuarioId): ?>
                                    <form action="controllers/tutorias_controller.php" method="POST" class="d-inline">
                                        <input type="hidden" name="accion" value="confirmar">
                                        <input type="hidden" name="tutoria_id" value="<?php echo $t['id']; ?>">
                                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                    </form>
                                    <button class="btn btn-sm btn-danger btn-reject" data-id="<?php echo $t['id']; ?>"><i class="fas fa-times"></i></button>
                                <?php elseif(!in_array($t['estado'], ['REALIZADA', 'CANCELADA', 'RECHAZADA'])): ?>
                                    <button class="btn btn-sm btn-outline-primary btn-edit" data-id="<?php echo $t['id']; ?>"><i class="fas fa-edit"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSolicitar">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary"><h5 class="modal-title">Nueva Solicitud</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <form action="controllers/tutorias_controller.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="solicitar">
                    <div class="row">
                        <div class="col-8"><label>Título</label><input type="text" name="titulo" class="form-control" required></div>
                        <div class="col-4"><label>Tipo</label><select name="tipo" id="tipoS" class="form-control"><option>TUTORIA</option><option>MENTORIA</option></select></div>
                    </div>
                    
                    <div class="bg-light p-2 my-2 border rounded">
                        <div class="row">
                            <div class="col-6"><label>Carrera</label>
                                <select id="filterCarrera" class="form-control"><option value="">-- Todas --</option>
                                <?php foreach($carreras as $c) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; ?>
                                </select>
                            </div>
                            <div class="col-6"><label>Semestre</label>
                                <select id="filterSemestre" class="form-control"><option value="">-- Todos --</option>
                                <?php for($i=1;$i<=10;$i++) echo "<option value='{$i}ro'>{$i}ro</option>"; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Seleccionar Personas</label>
                        <select name="id_contraparte[]" id="selectContraparte" class="form-control select2" multiple required></select>
                    </div>

                    <div class="row">
                        <div class="col-4"><label>Fecha</label><input type="date" name="fecha" id="inFecha" class="form-control" required></div>
                        <div class="col-4"><label>Inicio</label><select name="hora_inicio" id="inInicio" class="form-control" disabled></select></div>
                        <div class="col-4"><label>Fin</label><select name="hora_fin" id="inFin" class="form-control" readonly></select></div>
                    </div>
                    <div class="form-group mt-2"><label>Lugar</label><input type="text" name="lugar" class="form-control"></div>
                    <input type="hidden" name="modalidad" value="PRESENCIAL">
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Enviar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning"><h5 class="modal-title">Reprogramar</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <form action="controllers/tutorias_controller.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="tutoria_id" id="ed_id">
                    <div class="form-group"><label>Título</label><input type="text" name="titulo" id="ed_titulo" class="form-control" required></div>
                    <div class="row">
                        <div class="col-4"><label>Fecha</label><input type="date" name="fecha" id="ed_fecha" class="form-control" required></div>
                        <div class="col-4"><label>Inicio</label><input type="time" name="hora_inicio" id="ed_inicio" class="form-control" required></div>
                        <div class="col-4"><label>Fin</label><input type="time" name="hora_fin" id="ed_fin" class="form-control" required></div>
                    </div>
                    <div class="form-group mt-2"><label>Lugar</label><input type="text" name="lugar" id="ed_lugar" class="form-control"></div>
                    <input type="hidden" name="modalidad" id="ed_mod" value="PRESENCIAL">
                </div>
                <div class="modal-footer"><button class="btn btn-warning">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRechazar">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="controllers/tutorias_controller.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="rechazar">
                    <input type="hidden" name="tutoria_id" id="rej_id">
                    <label>Motivo</label><textarea name="motivo_rechazo" class="form-control" required></textarea>
                </div>
                <div class="modal-footer"><button class="btn btn-danger">Confirmar</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="adminlte/plugins/select2/js/select2.full.min.js"></script>
<script>
const rol = "<?php echo $rolUsuario; ?>";
const miId = "<?php echo $usuarioId; ?>";
$('.select2').select2({ theme: 'bootstrap4' });

// 1. FILTROS USUARIOS
function cargarUsuarios() {
    const c = $('#filterCarrera').val();
    const s = $('#filterSemestre').val();
    const rolB = (rol==='DOCENTE')?'ESTUDIANTE':'DOCENTE';
    
    let url = `controllers/get_usuarios_carrera.php?rol=${rolB}&carrera_id=${c}&semestre=${s}`;
    
    fetch(url).then(r=>r.json()).then(d=>{
        $('#selectContraparte').empty();
        d.forEach(u => {
            let txt = u.nombre + (u.semestre ? ` (${u.semestre})` : '');
            $('#selectContraparte').append(new Option(txt, u.id));
        });
    });
}
$('#filterCarrera, #filterSemestre').change(cargarUsuarios);
if(rol==='ESTUDIANTE') cargarUsuarios(); // Cargar docentes al inicio

// 2. DISPONIBILIDAD HORARIA
$('#inFecha').change(function() {
    const f = this.value;
    const sel = $('#selectContraparte').val();
    // Si es grupo, usa horario del docente (yo). Si es estudiante, usa el del profe seleccionado.
    const idRev = (rol==='DOCENTE') ? miId : (sel ? sel[0] : null);
    
    $('#inInicio').empty().prop('disabled', true);
    if(f && idRev) {
        fetch(`controllers/get_horarios.php?docente_id=${idRev}&fecha=${f}`)
        .then(r=>r.json()).then(d=>{
            if(d.slots) {
                $('#inInicio').prop('disabled', false);
                d.slots.forEach(h => $('#inInicio').append(new Option(h, h)));
            } else {
                $('#inInicio').append(new Option("No disponible", ""));
            }
        });
    }
});

// 3. HORA FIN AUTO
$('#inInicio').change(function() {
    const h = this.value;
    const t = $('#tipoS').val();
    $('#inFin').empty();
    if(!h) return;
    
    const base = new Date(`2000-01-01T${h}:00`);
    const mins = (t==='TUTORIA') ? [15,30,45,60] : [30,45,60];
    mins.forEach(m => {
        let d = new Date(base.getTime() + m*60000);
        let s = d.toTimeString().substring(0,5);
        $('#inFin').append(new Option(`${s} (${m}m)`, s));
    });
});

// 4. EDITAR Y RECHAZAR
$('.btn-edit').click(function() {
    const id = $(this).data('id');
    fetch(`controllers/api_get_entity.php?entity=tutoria&id=${id}`).then(r=>r.json()).then(d=>{
        $('#ed_id').val(d.id);
        $('#ed_titulo').val(d.titulo);
        $('#ed_fecha').val(d.fecha);
        $('#ed_inicio').val(d.hora_inicio.substring(0,5));
        $('#ed_fin').val(d.hora_fin.substring(0,5));
        $('#ed_lugar').val(d.lugar);
        $('#modalEditar').modal('show');
    });
});
$('.btn-reject').click(function(){ $('#rej_id').val($(this).data('id')); $('#modalRechazar').modal('show'); });
</script>