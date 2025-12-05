<?php
if (!isset($tituloPagina)) {
    $tituloPagina = "SGTM - PUCE";
}
// Lógica simple para marcar menú activo
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $tituloPagina; ?> | SGTM</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/css/sgtm_puce.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 elevation-1">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link font-weight-bold text-dark">Sistema de Gestión de Tutorías</span>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="nav-link">
                    <i class="fas fa-user-circle mr-1"></i>
                    <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>
                    <small class="badge badge-info ml-1"><?php echo htmlspecialchars($_SESSION['usuario_rol'] ?? ''); ?></small>
                </span>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="index.php" class="brand-link">
            <span class="brand-image img-circle elevation-3 bg-white d-flex justify-content-center align-items-center" style="width: 33px; height: 33px; font-weight:bold; color:#00296b;">P</span>
            <span class="brand-text font-weight-light pl-2">PUCE | SGTM</span>
        </a>

        <div class="sidebar">
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo ($paginaActual == 'index.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">GESTIÓN</li>

                    <li class="nav-item">
                        <a href="usuarios.php" class="nav-link <?php echo ($paginaActual == 'usuarios.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="tutorias.php" class="nav-link <?php echo ($paginaActual == 'tutorias.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chalkboard-teacher"></i>
                            <p>Tutorías</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="reportes.php" class="nav-link <?php echo ($paginaActual == 'reportes.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>Reportes</p>
                        </a>
                    </li>

                    <li class="nav-header">SISTEMA</li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link text-danger">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Cerrar Sesión</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?php echo $tituloPagina; ?></h1>
                    </div>
                </div>
            </div>
        </div>
        
        <section class="content">
            <div class="container-fluid"></div>