<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Solo Docentes
if ($_SESSION['usuario_rol'] !== 'DOCENTE') {
    header("Location: index.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$mensaje = "";

// --- GUARDAR HORARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dia = $_POST['dia'];
    $inicio = $_POST['hora_inicio'];
    $fin = $_POST['hora_fin'];

    if ($inicio >= $fin) {
        $mensaje = "<div class='alert alert-danger'>Error: La hora de fin debe ser mayor a la de inicio.</div>";
    } else {
        // Evitar duplicados simples (opcional: validación más compleja de solapamiento)
        $stmt = $pdo->prepare("INSERT INTO horarios_docentes (docente_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)");
        if($stmt->execute([$usuarioId, $dia, $inicio, $fin])) {
            $mensaje = "<div class='alert alert-success'>Horario agregado correctamente.</div>";
        }
    }
}

// --- BORRAR HORARIO ---
if (isset($_GET['borrar'])) {
    $idBorrar = $_GET['borrar'];
    $stmt = $pdo->prepare("DELETE FROM horarios_docentes WHERE id = ? AND docente_id = ?");
    $stmt->execute([$idBorrar, $usuarioId]);
    header("Location: mi_horario.php");
    exit;
}

// Listar horarios actuales
$dias = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes'];
$sql = "SELECT * FROM horarios_docentes WHERE docente_id = ? ORDER BY dia_semana, hora_inicio";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId]);
$misHorarios = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Mi Disponibilidad Semanal</h1></div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <?php echo $mensaje; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Nuevo Bloque de Atención</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Día de la Semana</label>
                                <select name="dia" class="form-control">
                                    <?php foreach($dias as $num => $nombre): ?>
                                        <option value="<?php echo $num; ?>"><?php echo $nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Desde</label>
                                <input type="time" name="hora_inicio" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Hasta</label>
                                <input type="time" name="hora_fin" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-save mr-2"></i> Guardar Horario
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="callout callout-info">
                    <h5><i class="fas fa-info"></i> Nota:</h5>
                    <p>Estos son los rangos generales en los que aceptas tutorías. Los estudiantes solo podrán agendar citas dentro de estos bloques.</p>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-outline card-warning">
                    <div class="card-header"><h3 class="card-title">Mis Horarios Registrados</h3></div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Día</th>
                                    <th>Rango Horario</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($misHorarios as $h): ?>
                                <tr>
                                    <td><span class="badge badge-info"><?php echo $dias[$h['dia_semana']]; ?></span></td>
                                    <td class="font-weight-bold">
                                        <?php echo substr($h['hora_inicio'], 0, 5); ?> - <?php echo substr($h['hora_fin'], 0, 5); ?>
                                    </td>
                                    <td>
                                        <a href="mi_horario.php?borrar=<?php echo $h['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este horario?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($misHorarios)): ?>
                                    <tr><td colspan="3" class="text-muted text-center">No has configurado horarios. Los estudiantes no podrán agendarte citas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>