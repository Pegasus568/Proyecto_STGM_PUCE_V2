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
            <div class="col-6"><h1>Agenda Académica</h1></div>
            <div class="col-6 text-right">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalSolicitar">
                    <i class="fas fa-plus-circle mr-2"></i> Agendar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <?php if($mensaje): ?><div class="alert alert-<?php echo $tipoMsg; ?>"><?php echo $mensaje; ?></div><?php endif; ?>

        <div class="card card-primary card-outline">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover">
                    <thead class="bg-light"><tr><th>Estado</th><th>Tipo</th><th>Fecha</th><th>Contraparte</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($tutorias as $t): ?>
                            <?php
                            $fechaCita = strtotime($t['fecha'] . ' ' . $t['hora_inicio']);
                            $ahora = time();
                            $esPasada = $ahora > $fechaCita;
                            // REGLA 48 HORAS: Disponible para Docente Y Estudiante
                            $esEditable = (($fechaCita - $ahora) / 3600 >= 48) && in_array($t['estado'], ['PENDIENTE','PROGRAMADA','CONFIRMADA']);
                            ?>
                        <tr>
                            <td>
                                <?php 
                                    $b = 'secondary';
                                    if($t['estado']=='PENDIENTE') $b='warning';
                                    elseif($t['estado']=='CONFIRMADA') $b='primary';
                                    elseif($t['estado']=='REALIZADA') $b='success';
                                    elseif($t['estado']=='NO_ASISTIO' || $t['estado']=='RECHAZADA') $b='danger';
                                    echo "<span class='badge badge-$b'>{$t['estado']}</span>";
                                ?>
                            </td>
                            <td><?php echo $t['tipo']; ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($t['fecha'])); ?> <br>
                                <small class="text-muted"><?php echo substr($t['hora_inicio'],0,5) . ' - ' . substr($t['hora_fin'],0,5); ?></small>
                            </td>
                            <td>
                                <i class="fas fa-user-circle text-muted"></i> 
                                <?php echo ($rolUsuario==='DOCENTE') ? $t['nombre_estudiante'] : $t['nombre_tutor']; ?>
                            </td>
                            <td>
                                <?php if($t['estado'] === 'PENDIENTE' && $t['solicitado_por'] != $usuarioId): ?>
                                    <form action="controllers/tutorias_controller.php" method="POST" class="d-inline">
                                        <input type="hidden" name="accion" value="confirmar">
                                        <input type="hidden" name="tutoria_id" value="<?php echo $t['id']; ?>">
                                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                    </form>
                                    <button class="btn btn-sm btn-danger btn-reject" data-id="<?php echo $t['id']; ?>"><i class="fas fa-times"></i></button>
                                
                                <?php elseif($rolUsuario === 'DOCENTE' && $esPasada && $t['estado'] === 'CONFIRMADA'): ?>
                                    <button class="btn btn-primary btn-sm btn-asistencia" data-id="<?php echo $t['id']; ?>" data-estudiante="<?php echo $t['nombre_estudiante']; ?>">
                                        <i class="fas fa-clipboard-check"></i>
                                    </button>

                                <?php elseif($esEditable): ?>
                                    <button class="btn btn-sm btn-outline-primary btn-edit" 
                                            data-id="<?php echo $t['id']; ?>" 
                                            data-tutor-id="<?php echo $t['tutor_id']; ?>"
                                            title="Reprogramar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-cancel" data-id="<?php echo $t['id']; ?>" title="Cancelar">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                
                                <?php else: ?>
                                    <small class="text-muted">-</small>
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
            <div class="modal-header bg-primary"><h5 class="modal-title">Agendar</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <form action="controllers/tutorias_controller.php" method="POST" id="formCrear">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="solicitar">
                    <div class="row">
                        <div class="col-6"><label>Título *</label><input type="text" name="titulo" class="form-control" required></div>
                        <div class="col-6"><label>Tipo *</label>
                            <select name="tipo" id="tipoSession" class="form-control">
                                <option value="TUTORIA">Tutoría Individual</option>
                                <option value="MENTORIA">Mentoría Individual</option>
                                <?php if ($rolUsuario === 'DOCENTE'): ?>
                                    <option value="TUTORIA_GRUPAL" class="text-primary font-weight-bold">👥 Tutoría Grupal</option>
                                    <option value="MENTORIA_GRUPAL" class="text-primary font-weight-bold">👥 Mentoría Grupal</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="bg-light p-2 my-2 border rounded">
                        <div class="row">
                            <div class="col-6"><label><small>Carrera</small></label>
                                <select id="filterCarrera" class="form-control"><option value="">-- Todas --</option>
                                <?php foreach($carreras as $c) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; ?>
                                </select>
                            </div>
                            <div class="col-6" id="colFilterSemestre" style="<?php echo ($rolUsuario === 'ESTUDIANTE')?'display:none':''; ?>">
                                <label><small>Semestre</small></label>
                                <select id="filterSemestre" class="form-control" <?php echo ($rolUsuario === 'ESTUDIANTE')?'disabled':''; ?>>
                                    <option value="">-- Todos --</option>
                                    <?php for($i=1;$i<=10;$i++) echo "<option value='{$i}ro'>{$i}ro</option>"; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Seleccionar <span id="lblContraparte"><?php echo ($rolUsuario==='DOCENTE')?'Estudiante':'Docente'; ?></span> *</label>
                        <select name="id_contraparte[]" id="selectContraparte" class="form-control select2" multiple required style="width: 100%;"></select>
                    </div>

                    <div class="row">
                        <div class="col-4"><label>Fecha *</label><input type="date" name="fecha" id="inFecha" class="form-control" required></div>
                        <div class="col-4"><label>Inicio *</label><select name="hora_inicio" id="inInicio" class="form-control" disabled><option value="">-</option></select></div>
                        <div class="col-4"><label>Fin *</label><select name="hora_fin" id="inFin" class="form-control" readonly><option value="">-</option></select></div>
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
                    
                    <input type="hidden" id="ed_tutor_id">

                    <div class="form-group"><label>Título</label><input type="text" name="titulo" id="ed_titulo" class="form-control" required></div>
                    
                    <div class="row">
                        <div class="col-4">
                            <label>Fecha</label>
                            <input type="date" name="fecha" id="ed_fecha" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label>Inicio</label>
                            <select name="hora_inicio" id="ed_inicio" class="form-control" required>
                                <option value="">Cargando...</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label>Fin</label>
                            <select name="hora_fin" id="ed_fin" class="form-control" readonly></select>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-6"><label>Modalidad</label><select name="modalidad" id="ed_mod" class="form-control"><option>PRESENCIAL</option><option>VIRTUAL</option></select></div>
                        <div class="col-6"><label>Lugar</label><input type="text" name="lugar" id="ed_lugar" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-warning">Guardar Cambios</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancelar">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="controllers/tutorias_controller.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="cancelar">
                    <input type="hidden" name="tutoria_id" id="cancel_id">
                    <p class="text-danger">Esta acción es irreversible.</p>
                    <label>Motivo:</label><textarea name="motivo_cancelacion" class="form-control" required></textarea>
                </div>
                <div class="modal-footer"><button class="btn btn-danger">Confirmar</button></div>
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
                    <label>Motivo:</label><textarea name="motivo_rechazo" class="form-control" required></textarea>
                </div>
                <div class="modal-footer"><button class="btn btn-danger">Rechazar</button></div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalAsistencia">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info"><h5 class="modal-title">Registro</h5></div>
            <form action="controllers/tutorias_controller.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="asistencia">
                    <input type="hidden" name="tutoria_id" id="asis_id">
                    <p>Estudiante: <strong id="asis_nombre"></strong></p>
                    <div class="form-group">
                        <label>Asistencia:</label><br>
                        <input type="radio" name="asistio" value="1" checked> SÍ 
                        <input type="radio" name="asistio" value="0" class="ml-3"> NO
                    </div>
                    <div class="form-group"><label>Observaciones</label><textarea name="observaciones" class="form-control" required></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="adminlte/plugins/select2/js/select2.full.min.js"></script>

<script>
$(document).ready(function() {
    const rol = "<?php echo $rolUsuario; ?>";
    const miId = "<?php echo $usuarioId; ?>";
    const today = new Date().toISOString().split('T')[0];
    
    $('#inFecha, #ed_fecha').attr('min', today);

    // --- 1. SELECT2 LÓGICA ---
    function initSelect2(limit) {
        if ($('#selectContraparte').hasClass("select2-hidden-accessible")) $('#selectContraparte').select2('destroy');
        $('#selectContraparte').select2({ theme: 'bootstrap4', maximumSelectionLength: limit });
    }
    initSelect2(1);

    $('#tipoSession').change(function() {
        const tipo = $(this).val();
        $('#selectContraparte').val(null).trigger('change');
        if (tipo.includes('GRUPAL')) {
            initSelect2(20);
            $('#lblContraparte').text('Estudiantes (Grupo)');
        } else {
            initSelect2(1);
            $('#lblContraparte').text(rol === 'DOCENTE' ? 'Estudiante' : 'Docente');
        }
        $('#inInicio').trigger('change');
    });

    // --- 2. FILTROS (CARRERA/SEMESTRE) ---
    function cargarUsuarios() {
        const c = $('#filterCarrera').val();
        // Si es estudiante, ignora semestre
        const s = (rol === 'DOCENTE') ? $('#filterSemestre').val() : '';
        const rolB = (rol==='DOCENTE')?'ESTUDIANTE':'DOCENTE';
        
        if (rol === 'DOCENTE') $('#filterSemestre').prop('disabled', !c);

        fetch(`controllers/get_usuarios_carrera.php?rol=${rolB}&carrera_id=${c}&semestre=${s}`)
            .then(r=>r.json()).then(d=>{
                $('#selectContraparte').empty();
                d.forEach(u => {
                    let txt = u.nombre + (u.semestre ? ` (${u.semestre})` : '');
                    $('#selectContraparte').append(new Option(txt, u.id));
                });
            });
    }
    $('#filterCarrera, #filterSemestre').change(cargarUsuarios);
    if(rol==='ESTUDIANTE') cargarUsuarios();

    // --- 3. HELPER: CARGAR HORAS EN UN SELECT ---
    // targetSelect: ID del select a llenar (#inInicio o #ed_inicio)
    // fecha: YYYY-MM-DD
    // idDocente: ID del profesor a consultar
    // preSelected: (Opcional) Hora que ya tiene la cita para pre-seleccionar
    function fetchAndFillHours(targetSelect, fecha, idDocente, preSelected = null) {
        const sel = $(targetSelect);
        sel.empty().prop('disabled', true);
        
        if(fecha && idDocente) {
            fetch(`controllers/get_horarios.php?docente_id=${idDocente}&fecha=${fecha}`)
            .then(r=>r.json()).then(d=>{
                if(d.slots && d.slots.length > 0) {
                    sel.prop('disabled', false).append('<option value="">--</option>');
                    d.slots.forEach(h => {
                        sel.append(new Option(h, h));
                    });
                    
                    // Caso especial Editar: Si la hora actual no está en los slots (porque ya está ocupada por mí mismo),
                    // la agregamos visualmente para no perderla.
                    if (preSelected && !d.slots.includes(preSelected)) {
                        sel.append(new Option(preSelected + " (Actual)", preSelected, true, true));
                    } else if (preSelected) {
                        sel.val(preSelected);
                    }

                } else {
                    sel.append('<option>No disponible</option>');
                    // Si no hay slots pero estoy editando, mostrar la actual
                    if (preSelected) sel.append(new Option(preSelected + " (Actual)", preSelected, true, true));
                }
                sel.trigger('change'); // Recalcular fin
            });
        }
    }

    // --- 4. LOGICA MODAL CREAR ---
    $('#inFecha').change(function() {
        const f = this.value;
        const sel = $('#selectContraparte').val();
        const idRev = (rol==='DOCENTE') ? miId : (sel && sel.length > 0 ? sel[0] : null);
        fetchAndFillHours('#inInicio', f, idRev);
    });

    $('#selectContraparte').on('change', function() {
        if(rol === 'ESTUDIANTE') $('#inFecha').trigger('change');
    });

    $('#inInicio').change(function() {
        const h = this.value;
        const rawTipo = $('#tipoSession').val();
        calcFin('#inFin', h, rawTipo);
    });

    // Helper Calcular Fin
    function calcFin(target, h, rawTipo) {
        $(target).empty();
        if(!h || h.includes('Cargando')) return;
        const base = new Date(`2000-01-01T${h.substring(0,5)}:00`);
        const isMentoria = rawTipo && rawTipo.indexOf('MENTORIA') !== -1;
        const mins = isMentoria ? [30,45,60] : [15,30,45,60];
        mins.forEach(m => {
            let d = new Date(base.getTime() + m*60000);
            let s = d.toTimeString().substring(0,5);
            $(target).append(new Option(`${s} (${m}m)`, s));
        });
    }

    // --- 5. LOGICA MODAL EDITAR (¡Ahora inteligente!) ---
    $('.btn-edit').click(function() {
        const id = $(this).data('id');
        const tutorId = $(this).data('tutor-id'); // ID del profe de esa cita
        
        fetch(`controllers/api_get_entity.php?entity=tutoria&id=${id}`).then(r=>r.json()).then(d=>{
            $('#ed_id').val(d.id);
            $('#ed_titulo').val(d.titulo);
            $('#ed_fecha').val(d.fecha);
            $('#ed_lugar').val(d.lugar);
            $('#ed_mod').val(d.modalidad);
            $('#ed_tutor_id').val(tutorId); // Guardar ID profe

            // Cargar horas para la fecha actual de la cita, pre-seleccionando la hora guardada
            // Usamos 'TUTORIA' genérico para calcular fin, o detectamos según título si guardaras tipo
            fetchAndFillHours('#ed_inicio', d.fecha, tutorId, d.hora_inicio.substring(0,5));
            
            // Forzar llenado de fin (timeout pequeño para esperar que se llene inicio)
            setTimeout(() => {
                calcFin('#ed_fin', d.hora_inicio.substring(0,5), 'TUTORIA'); // Asumimos default, si quieres estricto guarda tipo en data
                $('#ed_fin').val(d.hora_fin.substring(0,5));
            }, 500);

            $('#modalEditar').modal('show');
        });
    });

    // Si cambia fecha en editar, recargar horas del profe
    $('#ed_fecha').change(function() {
        const f = this.value;
        const tid = $('#ed_tutor_id').val();
        fetchAndFillHours('#ed_inicio', f, tid);
    });

    $('#ed_inicio').change(function() {
        calcFin('#ed_fin', this.value, 'TUTORIA');
    });

    // Otros modales
    $('.btn-cancel').click(function(){ $('#cancel_id').val($(this).data('id')); $('#modalCancelar').modal('show'); });
    $('.btn-reject').click(function(){ $('#rej_id').val($(this).data('id')); $('#modalRechazar').modal('show'); });
    $('.btn-asistencia').click(function(){ $('#asis_id').val($(this).data('id')); $('#asis_nombre').text($(this).data('estudiante')); $('#modalAsistencia').modal('show'); });
});
</script>