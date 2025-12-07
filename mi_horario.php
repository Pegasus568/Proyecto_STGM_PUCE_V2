<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if ($_SESSION['usuario_rol'] !== 'DOCENTE') { header("Location: index.php"); exit; }
$usuarioId = $_SESSION['usuario_id'];
$mensaje = "";

// CREAR HORARIO CON VALIDACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dia = $_POST['dia'];
    $inicio = $_POST['hora_inicio'];
    $fin = $_POST['hora_fin'];

    if ($inicio >= $fin) {
        $mensaje = "<div class='alert alert-danger'>Hora fin debe ser mayor a inicio.</div>";
    } else {
        // VALIDACIÓN DE SOLAPAMIENTO (NUEVO)
        $sqlSolape = "SELECT id FROM horarios_docentes 
                      WHERE docente_id = ? AND dia_semana = ? 
                      AND (? < hora_fin AND ? > hora_inicio)";
        $stmt = $pdo->prepare($sqlSolape);
        $stmt->execute([$usuarioId, $dia, $inicio, $fin]);
        
        if ($stmt->fetch()) {
            $mensaje = "<div class='alert alert-danger'>Error: Este horario se cruza con otro bloque existente.</div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO horarios_docentes (docente_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuarioId, $dia, $inicio, $fin]);
            $mensaje = "<div class='alert alert-success'>Bloque agregado.</div>";
        }
    }
}

// BORRAR CON VALIDACIÓN DE INTEGRIDAD
if (isset($_GET['borrar'])) {
    $idH = $_GET['borrar'];
    
    // Obtener detalles del bloque a borrar
    $bloque = $pdo->query("SELECT * FROM horarios_docentes WHERE id=$idH")->fetch();
    
    // Buscar citas activas en las próximas 24h+ que caigan en ese día/hora
    // Nota: Esta validación es compleja en SQL puro con fechas dinámicas.
    // Simplificación: Buscamos si hay CUALQUIER cita PENDIENTE/CONFIRMADA futura que coincida con este día de la semana.
    
    $sqlCheck = "SELECT count(*) FROM tutorias 
                 WHERE tutor_id = ? 
                 AND estado IN ('PENDIENTE', 'CONFIRMADA')
                 AND fecha >= CURDATE()
                 AND DAYOFWEEK(fecha) - 1 = ? -- MySQL Sunday=1, nuestro Lunes=1. Ajuste necesario según DB
                 AND hora_inicio >= ? AND hora_fin <= ?";
    
    // Ajuste día semana: Si en tu tabla 1=Lunes, en MySQL DAYOFWEEK(Lunes)=2.
    // Entonces: DAYOFWEEK(fecha) = dia_semana + 1.
    $diaMySQL = $bloque['dia_semana'] + 1; 

    $stmtCheck = $pdo->prepare("SELECT id FROM tutorias WHERE tutor_id=? AND estado IN ('PENDIENTE','CONFIRMADA') AND fecha >= CURDATE() AND DAYOFWEEK(fecha)=? AND hora_inicio >= ? AND hora_fin <= ?");
    $stmtCheck->execute([$usuarioId, $diaMySQL, $bloque['hora_inicio'], $bloque['hora_fin']]);

    if ($stmtCheck->fetch()) {
        $mensaje = "<div class='alert alert-warning'>No se puede eliminar: Tienes tutorías agendadas en este horario. Cancélalas primero.</div>";
    } else {
        $pdo->prepare("DELETE FROM horarios_docentes WHERE id=?")->execute([$idH]);
        $mensaje = "<div class='alert alert-info'>Horario eliminado.</div>";
    }
}

// Listar
$dias = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes'];
$lista = $pdo->query("SELECT * FROM horarios_docentes WHERE docente_id=$usuarioId ORDER BY dia_semana, hora_inicio")->fetchAll();

require_once 'includes/header.php';
?>
<div class="content-header"><div class="container-fluid"><h1>Mi Disponibilidad</h1></div></div>
<div class="content">
    <div class="container-fluid">
        <?php echo $mensaje; ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Nuevo Bloque</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group"><label>Día</label><select name="dia" class="form-control"><?php foreach($dias as $k=>$v) echo "<option value='$k'>$v</option>"; ?></select></div>
                            <div class="form-group"><label>Inicio</label><input type="time" name="hora_inicio" class="form-control" required></div>
                            <div class="form-group"><label>Fin</label><input type="time" name="hora_fin" class="form-control" required></div>
                            <button class="btn btn-success btn-block">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card"><div class="card-body p-0"><table class="table"><thead><tr><th>Día</th><th>Hora</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach($lista as $l): ?>
                    <tr>
                        <td><?php echo $dias[$l['dia_semana']]; ?></td>
                        <td><?php echo substr($l['hora_inicio'],0,5).' - '.substr($l['hora_fin'],0,5); ?></td>
                        <td><a href="mi_horario.php?borrar=<?php echo $l['id']; ?>" class="text-danger"><i class="fas fa-trash"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody></table></div></div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>