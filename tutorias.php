<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Proteger acceso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId   = $_SESSION['usuario_id'];
$rolUsuario  = $_SESSION['usuario_rol'];
$nombreUsuario = $_SESSION['usuario_nombre'];

$mensaje = "";
$mensajeError = "";

// Si el ADMIN crea una nueva tutoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rolUsuario === 'ADMIN') {
    $titulo       = trim($_POST['titulo'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $fecha        = $_POST['fecha'] ?? null;
    $hora_inicio  = $_POST['hora_inicio'] ?? null;
    $hora_fin     = $_POST['hora_fin'] ?? null;
    $modalidad    = $_POST['modalidad'] ?? 'PRESENCIAL';
    $lugar        = trim($_POST['lugar'] ?? '');
    $id_tutor     = (int)($_POST['id_tutor'] ?? 0);
    $id_estudiante= (int)($_POST['id_estudiante'] ?? 0);

    if ($titulo === '' || !$fecha || !$id_tutor || !$id_estudiante) {
        $mensajeError = "Título, fecha, tutor y estudiante son obligatorios.";
    } else {
        $stmt = $mysqli->prepare("
            INSERT INTO tutorias (titulo, descripcion, fecha, hora_inicio, hora_fin,
                                  modalidad, lugar, estado, id_tutor, id_estudiante)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'PROGRAMADA', ?, ?)
        ");
        $stmt->bind_param(
            "sssssssii",
            $titulo, $descripcion, $fecha, $hora_inicio, $hora_fin,
            $modalidad, $lugar, $id_tutor, $id_estudiante
        );
        if ($stmt->execute()) {
            $mensaje = "Tutoría registrada correctamente.";
        } else {
            $mensajeError = "Error al guardar: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Listas de usuarios para combos (solo ADMIN necesita esto)
$docentes = [];
$estudiantes = [];
if ($rolUsuario === 'ADMIN') {
    $resDoc = $mysqli->query("SELECT id, nombre FROM usuarios WHERE rol='DOCENTE' AND estado='ACTIVO' ORDER BY nombre");
    while ($row = $resDoc->fetch_assoc()) {
        $docentes[] = $row;
    }
    $resEst = $mysqli->query("SELECT id, nombre FROM usuarios WHERE rol='ESTUDIANTE' AND estado='ACTIVO' ORDER BY nombre");
    while ($row = $resEst->fetch_assoc()) {
        $estudiantes[] = $row;
    }
}

// Tutorías a mostrar según rol
if ($rolUsuario === 'ADMIN') {
    $sqlTutorias = "
        SELECT t.*, 
               u1.nombre AS nombre_tutor,
               u2.nombre AS nombre_estudiante
        FROM tutorias t
        LEFT JOIN usuarios u1 ON t.id_tutor = u1.id
        LEFT JOIN usuarios u2 ON t.id_estudiante = u2.id
        ORDER BY t.fecha DESC, t.hora_inicio DESC
    ";
} elseif ($rolUsuario === 'DOCENTE') {
    $sqlTutorias = "
        SELECT t.*, 
               u1.nombre AS nombre_tutor,
               u2.nombre AS nombre_estudiante
        FROM tutorias t
        LEFT JOIN usuarios u1 ON t.id_tutor = u1.id
        LEFT JOIN usuarios u2 ON t.id_estudiante = u2.id
        WHERE t.id_tutor = {$usuarioId}
        ORDER BY t.fecha DESC, t.hora_inicio DESC
    ";
} else { // ESTUDIANTE
    $sqlTutorias = "
        SELECT t.*, 
               u1.nombre AS nombre_tutor,
               u2.nombre AS nombre_estudiante
        FROM tutorias t
        LEFT JOIN usuarios u1 ON t.id_tutor = u1.id
        LEFT JOIN usuarios u2 ON t.id_estudiante = u2.id
        WHERE t.id_estudiante = {$usuarioId}
        ORDER BY t.fecha DESC, t.hora_inicio DESC
    ";
}

$resTutorias = $mysqli->query($sqlTutorias);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tutorías - SGTM PUCE Ambato</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/css/sgtm_puce.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link font-weight-bold">SGTM - PUCE Ambato</span>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link">
                    <?php echo htmlspecialchars($nombreUsuario . " (" . $rolUsuario . ")"); ?>
                </span>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="index.php" class="brand-link">
            <span class="brand-text font-weight-light">PUCE | AMBATO</span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">MÓDULOS SGTM</li>

                    <li class="nav-item">
                        <a href="usuarios.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="tutorias.php" class="nav-link active">
                            <i class="nav-icon fas fa-user-graduate"></i>
                            <p>Tutorías</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Reportes</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Cerrar sesión</p>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Contenido -->
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1>Tutorías</h1>
                <p class="text-muted">
                    Gestión y seguimiento de sesiones de tutoría académica.
                </p>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">

                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>

                <?php if ($mensajeError): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError); ?></div>
                <?php endif; ?>

                <!-- SOLO ADMIN puede crear nuevas tutorías -->
                <?php if ($rolUsuario === 'ADMIN'): ?>
                <div class="card">
                    <div class="card-header bg-puce">
                        <h3 class="card-title">Nueva Tutoría</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Título *</label>
                                    <input type="text" name="titulo" class="form-control" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Fecha *</label>
                                    <input type="date" name="fecha" class="form-control" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Modalidad</label>
                                    <select name="modalidad" class="form-control">
                                        <option value="PRESENCIAL">Presencial</option>
                                        <option value="VIRTUAL">Virtual</option>
                                        <option value="HIBRIDA">Híbrida</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Hora inicio</label>
                                    <input type="time" name="hora_inicio" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Hora fin</label>
                                    <input type="time" name="hora_fin" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Lugar / enlace</label>
                                    <input type="text" name="lugar" class="form-control" placeholder="Aula 201, Zoom, Teams...">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Tutor *</label>
                                    <select name="id_tutor" class="form-control" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($docentes as $d): ?>
                                            <option value="<?php echo $d['id']; ?>">
                                                <?php echo htmlspecialchars($d['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Estudiante *</label>
                                    <select name="id_estudiante" class="form-control" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($estudiantes as $e): ?>
                                            <option value="<?php echo $e['id']; ?>">
                                                <?php echo htmlspecialchars($e['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Descripción / objetivos de la tutoría</label>
                                <textarea name="descripcion" class="form-control" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Guardar tutoría</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Listado de tutorías -->
                <div class="card">
                    <div class="card-header bg-naranja">
                        <h3 class="card-title">Listado de Tutorías</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Título</th>
                                <th>Tutor</th>
                                <th>Estudiante</th>
                                <th>Modalidad</th>
                                <th>Lugar</th>
                                <th>Estado</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php while ($t = $resTutorias->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['fecha']); ?></td>
                                    <td><?php echo htmlspecialchars(($t['hora_inicio'] ?? '') . ' - ' . ($t['hora_fin'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($t['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre_tutor']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre_estudiante']); ?></td>
                                    <td><?php echo htmlspecialchars($t['modalidad']); ?></td>
                                    <td><?php echo htmlspecialchars($t['lugar']); ?></td>
                                    <td><?php echo htmlspecialchars($t['estado']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <footer class="main-footer text-center">
        <strong>PUCE Ambato</strong> · SGTM · <?php echo date('Y'); ?>
    </footer>
</div>

<script src="adminlte/plugins/jquery/jquery.min.js"></script>
<script src="adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
