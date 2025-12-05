<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$miId = (int)$_SESSION['usuario_id'];
$miRol = $_SESSION['usuario_rol'];

// Mensajes
$mensaje = ""; $error = "";

/* --- Crear reporte (ADMIN o DOCENTE) --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($miRol, ['ADMIN','DOCENTE'])) {
    $tutoria_id = !empty($_POST['tutoria_id']) ? (int)$_POST['tutoria_id'] : null;
    $tipo = $_POST['tipo'] ?? 'OBSERVACION';
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $privado = isset($_POST['privado']) ? 1 : 0;
    $tutor_id = !empty($_POST['tutor_id']) ? (int)$_POST['tutor_id'] : null;
    $estudiante_id = !empty($_POST['estudiante_id']) ? (int)$_POST['estudiante_id'] : null;

    if ($titulo === '' || $contenido === '') {
        $error = "Título y contenido son obligatorios.";
    } else {
        $stmt = $mysqli->prepare("
            INSERT INTO reportes (tutoria_id, tipo, titulo, contenido, creado_por, tutor_id, estudiante_id, privado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssiiii",
            $tutoria_id, $tipo, $titulo, $contenido, $miId, $tutor_id, $estudiante_id, $privado
        );
        if ($stmt->execute()) {
            $mensaje = "Reporte guardado correctamente.";
        } else {
            $error = "Error al guardar: " . $stmt->error;
        }
        $stmt->close();
    }
}

/* --- Obtener lista según rol --- */
if ($miRol === 'ADMIN') {
    $sql = "SELECT r.*, u.nombre AS autor, t.titulo AS tutoria_titulo
            FROM reportes r
            LEFT JOIN usuarios u ON r.creado_por = u.id
            LEFT JOIN tutorias t ON r.tutoria_id = t.id
            ORDER BY r.fecha_creacion DESC";
    $res = $mysqli->query($sql);
} elseif ($miRol === 'DOCENTE') {
    $stmt = $mysqli->prepare("
        SELECT r.*, u.nombre AS autor, t.titulo AS tutoria_titulo
        FROM reportes r
        LEFT JOIN usuarios u ON r.creado_por = u.id
        LEFT JOIN tutorias t ON r.tutoria_id = t.id
        WHERE r.tutor_id = ? OR r.creado_por = ?
        ORDER BY r.fecha_creacion DESC
    ");
    $stmt->bind_param("ii", $miId, $miId);
    $stmt->execute();
    $res = $stmt->get_result();
} else { // ESTUDIANTE
    $stmt = $mysqli->prepare("
        SELECT r.*, u.nombre AS autor, t.titulo AS tutoria_titulo
        FROM reportes r
        LEFT JOIN usuarios u ON r.creado_por = u.id
        LEFT JOIN tutorias t ON r.tutoria_id = t.id
        WHERE r.estudiante_id = ? OR (r.privado = 0 AND r.tutoria_id IN (
            SELECT id FROM tutorias WHERE id_estudiante = ?
        ))
        ORDER BY r.fecha_creacion DESC
    ");
    $stmt->bind_param("ii", $miId, $miId);
    $stmt->execute();
    $res = $stmt->get_result();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reportes - SGTM</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/css/sgtm_puce.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- opcional para gráficas -->
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php include 'sidebar_header.php'; /* si tienes include con menú */ ?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <h1>Reportes</h1>
      <p class="text-muted">Gestión de actas, observaciones y reportes por tutoría</p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <?php if ($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

      <?php if (in_array($miRol, ['ADMIN','DOCENTE'])): ?>
      <!-- Formulario para crear reporte -->
      <div class="card">
        <div class="card-header bg-puce"><h3 class="card-title">Crear Reporte</h3></div>
        <div class="card-body">
          <form method="post">
            <div class="form-row">
              <div class="form-group col-md-4">
                <label>Título *</label>
                <input name="titulo" class="form-control" required>
              </div>
              <div class="form-group col-md-2">
                <label>Tipo</label>
                <select name="tipo" class="form-control">
                  <option value="OBSERVACION">Observación</option>
                  <option value="ACTA">Acta</option>
                  <option value="REPORTE">Reporte</option>
                </select>
              </div>
              <div class="form-group col-md-2">
                <label>Privado</label>
                <select name="privado" class="form-control">
                  <option value="0">No</option>
                  <option value="1">Sí (solo tutor/estudiante)</option>
                </select>
              </div>
              <div class="form-group col-md-4">
                <label>Vincular a tutoría (opcional)</label>
                <input name="tutoria_id" class="form-control" placeholder="ID tutoría">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Tutor (ID)</label>
                <input name="tutor_id" class="form-control" placeholder="ID tutor">
              </div>
              <div class="form-group col-md-6">
                <label>Estudiante (ID)</label>
                <input name="estudiante_id" class="form-control" placeholder="ID estudiante">
              </div>
            </div>

            <div class="form-group">
              <label>Contenido *</label>
              <textarea name="contenido" class="form-control" rows="5" required></textarea>
            </div>

            <button class="btn btn-primary">Guardar reporte</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <!-- Listado -->
      <div class="card">
        <div class="card-header bg-naranja"><h3 class="card-title">Listado de Reportes</h3></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Fecha</th><th>Título</th><th>Tipo</th><th>Tutor</th><th>Estudiante</th><th>Privado</th><th>Autor</th><th>Ver</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $res->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r['fecha_creacion']); ?></td>
                  <td><?php echo htmlspecialchars($r['titulo']); ?></td>
                  <td><?php echo htmlspecialchars($r['tipo']); ?></td>
                  <td><?php echo htmlspecialchars($r['tutor_id']); ?></td>
                  <td><?php echo htmlspecialchars($r['estudiante_id']); ?></td>
                  <td><?php echo $r['privado'] ? 'Sí' : 'No'; ?></td>
                  <td><?php echo htmlspecialchars($r['autor']); ?></td>
                  <td><a href="ver_reporte.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-info">Ver</a></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Gráfica simple (solo admin) -->
      <?php if ($miRol === 'ADMIN'): ?>
      <div class="card">
        <div class="card-header bg-puce"><h3 class="card-title">Estadísticas Rápidas</h3></div>
        <div class="card-body">
          <canvas id="chartReports" height="100"></canvas>
        </div>
      </div>

      <script>
      // Petición AJAX simple para obtener conteo por tipo (puedes crear endpoint o usar PHP inline)
      fetch('reportes_stats.php')
        .then(r => r.json())
        .then(data => {
          const ctx = document.getElementById('chartReports').getContext('2d');
          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: data.labels,
              datasets: [{ label: 'Reportes', data: data.counts }]
            }
          });
        });
      </script>
      <?php endif; ?>

    </div>
  </section>
</div>

</body>
</html>
