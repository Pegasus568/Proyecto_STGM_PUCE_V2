<?php
// 1. Incluir autenticación (ya maneja la sesión)
require_once 'includes/auth.php';

// 2. Incluir conexión a BD
require_once 'includes/db.php';

// 3. Lógica específica de esta página (Dashboard)
try {
    // Contar usuarios activos
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE deleted_at IS NULL AND estado = 'ACTIVO'");
    $totalUsuarios = $stmt->fetchColumn();

    // Contar tutorías programadas
    $stmt = $pdo->query("SELECT COUNT(*) FROM tutorias WHERE deleted_at IS NULL AND estado = 'PROGRAMADA'");
    $totalTutorias = $stmt->fetchColumn();
    
    // Contar reportes
    $stmt = $pdo->query("SELECT COUNT(*) FROM reportes WHERE deleted_at IS NULL");
    $totalReportes = $stmt->fetchColumn();

} catch (PDOException $e) {
    $totalUsuarios = $totalTutorias = $totalReportes = 0;
    // Aquí podrías guardar $e->getMessage() en un log
}

// 4. Configurar título y cargar cabecera
$tituloPagina = "Tablero Principal";
require_once 'includes/header.php';
?>

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
                <p>Tutorías Programadas</p>
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
                <p>Documentos Generados</p>
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
                <p>Hola <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>,</p>
                <p>Desde este panel podrás gestionar el seguimiento académico y las tutorías de la carrera. Usa el menú lateral para navegar.</p>
            </div>
        </div>
    </div>
</div>

<?php
// 5. Cargar pie de página
require_once 'includes/footer.php';
?>