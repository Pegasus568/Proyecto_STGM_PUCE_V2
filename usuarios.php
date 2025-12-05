<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Solo ADMIN puede entrar aquí
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'ADMIN') {
    header("Location: index.php");
    exit;
}

$nombreUsuario = $_SESSION['usuario_nombre'];
$rolUsuario    = $_SESSION['usuario_rol'];

$mensaje = "";
$mensajeError = "";
    
// Crear nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $rol      = $_POST['rol'] ?? 'ESTUDIANTE';
    $estado   = $_POST['estado'] ?? 'ACTIVO';
    $password = $_POST['password'] ?? '';

    $cedula   = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $carrera  = trim($_POST['carrera'] ?? '');
    $ciclo    = trim($_POST['ciclo'] ?? '');

    if ($nombre === '' || $correo === '' || $password === '') {
        $mensajeError = "Nombre, correo y contraseña son obligatorios.";
    } else {
        // Verificar correo único
        $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $mensajeError = "Ya existe un usuario con ese correo.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $mysqli->prepare("
                INSERT INTO usuarios
                    (nombre, correo, rol, estado, password_hash,
                     cedula, telefono, carrera, ciclo, creado_en)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtInsert->bind_param(
                "sssssssss",
                $nombre, $correo, $rol, $estado, $hash,
                $cedula, $telefono, $carrera, $ciclo
            );
            if ($stmtInsert->execute()) {
                $mensaje = "Usuario creado correctamente.";
            } else {
                $mensajeError = "Error al guardar: " . $stmtInsert->error;
            }
            $stmtInsert->close();
        }
        $stmt->close();
    }
}

// Listado de usuarios
$resUsuarios = $mysqli->query("
    SELECT id, nombre, correo, rol, estado, carrera, ciclo, ultimo_login
    FROM usuarios
    ORDER BY rol, nombre
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - SGTM PUCE Ambato</title>
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
                        <a href="usuarios.php" class="nav-link active">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="tutorias.php" class="nav-link">
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
                <h1>Usuarios</h1>
                <p class="text-muted">
                    Administración de cuentas de administradores, docentes y estudiantes.
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

                <!-- Formulario nuevo usuario -->
                <div class="card">
                    <div class="card-header bg-puce">
                        <h3 class="card-title">Nuevo Usuario</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Nombre completo *</label>
                                    <input type="text" name="nombre" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Correo institucional *</label>
                                    <input type="email" name="correo" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Contraseña inicial *</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Rol</label>
                                    <select name="rol" class="form-control">
                                        <option value="ADMIN">Administrador</option>
                                        <option value="DOCENTE">Docente</option>
                                        <option value="ESTUDIANTE" selected>Estudiante</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control">
                                        <option value="ACTIVO">Activo</option>
                                        <option value="INACTIVO">Inactivo</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Cédula</label>
                                    <input type="text" name="cedula" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono" class="form-control">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Carrera (si es estudiante)</label>
                                    <input type="text" name="carrera" class="form-control">
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Ciclo</label>
                                    <input type="text" name="ciclo" class="form-control" placeholder="5to, 6to...">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Guardar usuario</button>
                        </form>
                    </div>
                </div>

                <!-- Tabla de usuarios -->
                <div class="card">
                    <div class="card-header bg-naranja">
                        <h3 class="card-title">Listado de Usuarios</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Carrera / Ciclo</th>
                                <th>Estado</th>
                                <th>Último acceso</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php while ($u = $resUsuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                    <td><?php echo htmlspecialchars($u['rol']); ?></td>
                                    <td><?php echo htmlspecialchars(($u['carrera'] ?? '') . ' ' . ($u['ciclo'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($u['estado']); ?></td>
                                    <td><?php echo htmlspecialchars($u['ultimo_login']); ?></td>
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
