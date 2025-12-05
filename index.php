<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}


// Tomamos los datos del usuario logueado
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$rolUsuario    = $_SESSION['usuario_rol'] ?? '';

// Consultas para dashboard
$resultUsuarios  = $mysqli->query("SELECT COUNT(*) AS total FROM usuarios");
$rowUsuarios     = $resultUsuarios->fetch_assoc();
$totalUsuarios   = $rowUsuarios['total'];

$resultTutorias  = $mysqli->query("SELECT COUNT(*) AS total FROM tutorias");
$rowTutorias     = $resultTutorias->fetch_assoc();
$totalTutorias   = $rowTutorias['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGTM - PUCE Ambato</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/sgtm_puce.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark">
        <!-- botón menú -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
    <span class="nav-link font-weight-bold">
        SGTM - PUCE Ambato
    </span>
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
        <!-- Logo -->
        <a href="index.php" class="brand-link">
            <!-- si tienes el logo, ponlo en assets/img/logo_puce.png -->
            <!-- <img src="assets/img/logo_puce.png" class="brand-image img-circle elevation-3" alt="PUCE"> -->
            <span class="brand-text font-weight-light">PUCE | AMBATO</span>
        </a>

        <!-- Menú lateral -->
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">
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
                        <a href="tutorias.php" class="nav-link">
                            <i class="nav-icon fas fa-user-graduate"></i>
                            <p>Tutorías</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="reportes.php" class="nav-link">
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

    <!-- Contenido principal -->
    <div class="content-wrapper">
        <!-- Encabezado -->
        <section class="content-header">
            <div class="container-fluid">
                <h1 class="text-white">Sistema de Gestión de Tutorías o Mentorías</h1>
            </div>
        </section>

        <!-- Contenido -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">

                    <!-- Tarjeta Usuarios -->
                    <div class="col-md-6 col-12">
                        <div class="card">
                            <div class="card-header bg-puce">
                                <h3 class="card-title">Resumen de Usuarios</h3>
                            </div>
                            <div class="card-body">
                                <p><strong>Total de usuarios registrados:</strong> <?php echo $totalUsuarios; ?></p>
                                <p class="text-muted">
                                    Incluye administradores, tutores y estudiantes.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta Tutorías -->
                    <div class="col-md-6 col-12">
                        <div class="card">
                            <div class="card-header bg-naranja">
                                <h3 class="card-title">Resumen de Tutorías</h3>
                            </div>
                            <div class="card-body">
                                <p><strong>Total de tutorías registradas:</strong> <?php echo $totalTutorias; ?></p>
                                <p class="text-muted">
                                    Próximamente aquí se mostrarán estadísticas de sesiones,
                                    asistencia y resultados académicos.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Aquí luego podemos poner tablas, gráficos, etc. -->
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer text-center">
        <strong>PUCE Ambato</strong> · SGTM · <?php echo date('Y'); ?>
    </footer>
</div>

<!-- Scripts AdminLTE -->
<script src="adminlte/plugins/jquery/jquery.min.js"></script>
<script src="adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="adminlte/dist/js/adminlte.min.js"></script>

</body>
</html>
