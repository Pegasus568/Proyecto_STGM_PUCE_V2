<?php
// index.php
// 1. Activar reporte de errores para depuración inmediata
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Incluir archivos esenciales
require_once 'includes/auth.php';
require_once 'includes/db.php'; // Asegúrate de que este archivo tenga la conexión $pdo correcta

// Inicializar contadores en 0 por seguridad
$totalUsuarios = 0;
$totalTutorias = 0;
$totalReportes = 0;
$errorBD = "";

try {
    // 3. Consultas seguras (Verificamos si existen datos)
    
    // Usuarios
    $sqlUser = "SELECT COUNT(*) FROM usuarios WHERE estado = 'ACTIVO'";
    // Truco: Si la columna deleted_at existe, la usamos. Si no, ignoramos para que no de error 500.
    $checkCol = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'deleted_at'");
    if($checkCol->fetch()) {
        $sqlUser .= " AND deleted_at IS NULL";
    }
    $totalUsuarios = $pdo->query($sqlUser)->fetchColumn();

    // Tutorías
    $sqlTut = "SELECT COUNT(*) FROM tutorias WHERE 1=1"; // 1=1 permite concatenar ANDs fácil
    $checkColT = $pdo->query("SHOW COLUMNS FROM tutorias LIKE 'deleted_at'");
    if($checkColT->fetch()) {
        $sqlTut .= " AND deleted_at IS NULL";
    }
    $totalTutorias = $pdo->query($sqlTut)->fetchColumn();
    
    // Reportes
    $sqlRep = "SELECT COUNT(*) FROM reportes";
    $totalReportes = $pdo->query($sqlRep)->fetchColumn();

} catch (PDOException $e) {
    // Si falla algo, capturamos el error en esta variable en lugar de romper la página
    $errorBD = "Error de conexión o consulta: " . $e->getMessage();
}

$tituloPagina = "Tablero Principal";
require_once 'includes/header.php';
?>

<?php if($errorBD): ?>
<div class="alert alert-danger">
    <h5><i class="icon fas fa-ban"></i> Error Crítico</h5>
    <?php echo $errorBD; ?>
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
            <a href="usuarios.php" class="small-box-footer">Ver detalle <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo $totalTutorias; ?></h3>
                <p>Sesiones Totales</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <a href="tutorias.php" class="small-box-footer">Ir a agenda <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo $totalReportes; ?></h3>
                <p>Documentos</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-contract"></i>
            </div>
            <a href="reportes.php" class="small-box-footer">Ver historial <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Bienvenido al Sistema SGTM</h3>
            </div>
            <div class="card-body">
                <p>Hola <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></strong>,</p>
                <p>Bienvenido al Sistema de Gestión de Tutorías y Mentorías de la PUCE Ambato.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>