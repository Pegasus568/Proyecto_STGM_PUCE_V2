<?php
// index.php - TABLERO PRINCIPAL (DASHBOARD)
// ---------------------------------------------------------

// 1. Configuración de errores (Útil para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Autenticación
require_once 'includes/auth.php';

// --- LÓGICA DE REDIRECCIÓN POR ROL ---
// El Dashboard es solo para Administradores. 
// Docentes y Estudiantes van directo a su agenda.
if (in_array($_SESSION['usuario_rol'], ['DOCENTE', 'ESTUDIANTE'])) {
    header("Location: tutorias.php");
    exit;
}
// -------------------------------------

// 3. Conexión a Base de Datos
require_once 'includes/db.php';

// 4. Inicializar contadores
$totalUsuarios = 0;
$totalTutorias = 0;
$totalReportes = 0;
$errorDb = "";

// 5. Obtener Estadísticas (Solo para Admin)
try {
    // Contar Usuarios Activos (excluyendo eliminados)
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'ACTIVO' AND deleted_at IS NULL");
    $totalUsuarios = $stmt->fetchColumn();

    // Contar Tutorías/Mentorías Programadas (futuras)
    $stmt = $pdo->query("SELECT COUNT(*) FROM tutorias WHERE estado = 'PROGRAMADA' AND deleted_at IS NULL");
    $totalTutorias = $stmt->fetchColumn();

    // Contar Reportes Generados
    $stmt = $pdo->query("SELECT COUNT(*) FROM reportes WHERE deleted_at IS NULL");
    $totalReportes = $stmt->fetchColumn();

} catch (PDOException $e) {
    // Si falla la BD, no rompemos la página, solo mostramos 0 y guardamos el error
    $errorDb = $e->getMessage();
}

// 6. Cargar la Vista (Header)
$tituloPagina = "Tablero Principal";
require_once 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Resumen del Sistema</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <?php if($errorDb): ?>
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-exclamation-triangle"></i> Alerta de Base de Datos</h5>
            No se pudieron cargar las estadísticas: <?php echo $errorDb; ?>
        </div>
        <?php endif; ?>

        <div class="row">
            
            <div class="col-lg-4 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $totalUsuarios; ?></h3>
                        <p>Usuarios Activos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="usuarios.php" class="small-box-footer">Administrar Usuarios <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            
            <div class="col-lg-4 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $totalTutorias; ?></h3>
                        <p>Sesiones Programadas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <a href="tutorias.php" class="small-box-footer">Ver Agenda Global <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $totalReportes; ?></h3>
                        <p>Documentos Generados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <a href="reportes.php" class="small-box-footer">Ver Archivo <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0">
                        <h3 class="card-title text-primary"><i class="fas fa-university mr-1"></i> SGTM - PUCE Ambato</h3>
                    </div>
                    <div class="card-body">
                        <h4>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></h4>
                        <p class="lead">
                            Estás en el panel de administración. Desde aquí puedes tener una vista general del uso de la plataforma.
                            Utiliza el menú lateral para gestionar usuarios, revisar la agenda global de tutorías o consultar el historial de reportes.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div></section>
<?php
// 7. Cargar el pie de página (Scripts y cierre de HTML)
require_once 'includes/footer.php';
?>