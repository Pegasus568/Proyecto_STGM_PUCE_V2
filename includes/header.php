<?php
if (!isset($tituloPagina)) { $tituloPagina = "SGTM - PUCE"; }
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
    <link rel="stylesheet" href="assets/css/sgtm_pucce.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 elevation-1">
        <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li></ul>
        <ul class="navbar-nav ml-auto"><li class="nav-item"><span class="nav-link"><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></span></li></ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="#" class="brand-link"><span class="brand-text font-weight-light pl-3">PUCE | SGTM</span></a>
        <div class="sidebar">
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    
                    <?php if ($_SESSION['usuario_rol'] === 'ADMIN'): ?>
                        <li class="nav-item"><a href="index.php" class="nav-link <?php echo ($paginaActual=='index.php')?'active':''; ?>"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                        <li class="nav-header">ADMINISTRACIÓN</li>
                        <li class="nav-item"><a href="usuarios.php" class="nav-link <?php echo ($paginaActual=='usuarios.php')?'active':''; ?>"><i class="nav-icon fas fa-users"></i><p>Usuarios</p></a></li>
                        <li class="nav-item"><a href="carreras.php" class="nav-link <?php echo ($paginaActual=='carreras.php')?'active':''; ?>"><i class="nav-icon fas fa-university"></i><p>Carreras</p></a></li>
                    <?php endif; ?>

                    <?php if ($_SESSION['usuario_rol'] !== 'ADMIN'): ?>
                        <li class="nav-header">MI GESTIÓN</li>
                        <?php if ($_SESSION['usuario_rol'] === 'DOCENTE'): ?>
                            <li class="nav-item"><a href="mi_horario.php" class="nav-link <?php echo ($paginaActual=='mi_horario.php')?'active':''; ?>"><i class="nav-icon fas fa-clock"></i><p>Mi Disponibilidad</p></a></li>
                        <?php endif; ?>
                        
                        <li class="nav-item"><a href="tutorias.php" class="nav-link <?php echo ($paginaActual=='tutorias.php')?'active':''; ?>"><i class="nav-icon fas fa-chalkboard-teacher"></i><p>Agenda</p></a></li>

                        <?php if ($_SESSION['usuario_rol'] === 'DOCENTE'): ?>
                            <li class="nav-item"><a href="reportes.php" class="nav-link <?php echo ($paginaActual=='reportes.php')?'active':''; ?>"><i class="nav-icon fas fa-file-contract"></i><p>Reportes</p></a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <li class="nav-header">SISTEMA</li>
                    <li class="nav-item"><a href="logout.php" class="nav-link text-danger"><i class="nav-icon fas fa-sign-out-alt"></i><p>Cerrar Sesión</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid"></div>
        </div>
        <section class="content"><div class="container-fluid"></div>